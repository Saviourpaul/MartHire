<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\SecurityChallenge;
use App\Models\SettingsAuditEvent;
use App\Models\User;
use App\Notifications\AccountSecurityNotice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;
use Throwable;

class AccountLifecycleService
{
    public function __construct(private OrganizationOwnerGuard $ownerGuard) {}

    public function changePassword(User $user, #[SensitiveParameter] string $password, ?Request $request = null, bool $reset = false): void
    {
        DB::transaction(function () use ($user, $password, $request, $reset): void {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            $user->forceFill(['password' => Hash::make($password)])->save();
            $this->revoke($user);
            $this->audit($user, $reset ? 'password_reset' : 'password_changed');
            $this->notifyAfterCommit($user, $reset ? 'Your password was reset.' : 'Your password was changed.');
            if ($request !== null) {
                DB::afterCommit(fn () => $this->retainCurrentSession($request, $user));
            }
        }, 3);
    }

    public function revokeOthers(Request $request, User $user): void
    {
        DB::transaction(function () use ($request, $user): void {
            $this->revoke($user);
            $this->audit($user, 'other_sessions_revoked');
            DB::afterCommit(fn () => $this->retainCurrentSession($request, $user));
            $this->notifyAfterCommit($user, 'Other devices were signed out of your account.');
        }, 3);
    }

    public function revoke(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $locked = User::withTrashed()->lockForUpdate()->findOrFail($user->id);
            $locked->forceFill([
                'authentication_version' => (int) $locked->authentication_version + 1,
                'remember_token' => Str::random(60),
            ])->save();
            $user->forceFill(['authentication_version' => $locked->authentication_version, 'remember_token' => $locked->remember_token]);
            if (config('session.driver') === 'database') {
                DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
                    ->where('user_id', $user->id)->delete();
            }
            DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))->where('email', $user->email)->delete();
            SecurityChallenge::query()->where('user_id', $user->id)->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'code_hash' => null]);
        }, 3);
    }

    public function logout(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $this->revoke($user);
            $this->audit($user, 'all_sessions_revoked');
        }, 3);
    }

    public function close(User $user): void
    {
        $this->protectRemoval($user, UserRole::Applicant, UserStatus::Suspended, function (User $locked): void {
            $this->revoke($locked);
            $this->audit($locked, 'account_closed');
            $locked->delete();
            $this->notifyAfterCommit($locked, 'Your account was closed. Recruitment records may be retained.');
        });
    }

    public function administer(User $actor, User $target, UserRole $role, UserStatus $status, bool $delete = false): void
    {
        abort_unless($actor->isAdmin() && $actor->isActive() && $actor->hasVerifiedEmail(), 403);
        if ($actor->is($target) && ($delete || $role !== $target->role || $status !== $target->status)) {
            throw ValidationException::withMessages(['user' => 'You cannot suspend, demote, or delete your own administrator account.']);
        }

        $this->protectRemoval($target, $delete ? UserRole::Applicant : $role, $delete ? UserStatus::Suspended : $status,
            function (User $locked) use ($actor, $role, $status, $delete): void {
                $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
                abort_unless($actor->isAdmin() && $actor->isActive() && $actor->hasVerifiedEmail(), 403);
                $changed = $delete || $locked->role !== $role || $locked->status !== $status;
                if ($changed) {
                    $this->revoke($locked);
                }
                if ($delete) {
                    $locked->delete();
                } else {
                    $locked->role = $role;
                    $locked->status = $status;
                    if ($status === UserStatus::Active) {
                        $locked->approved_at ??= now();
                        $locked->suspended_at = null;
                    } elseif ($status === UserStatus::Suspended) {
                        $locked->suspended_at ??= now();
                    }
                    $locked->save();
                }
                SettingsAuditEvent::query()->create(['actor_id' => $actor->id, 'user_id' => $locked->id, 'scope' => 'personal', 'action' => $delete ? 'account_removed_by_admin' : 'account_access_updated', 'changes' => ['role' => $role->value, 'status' => $status->value]]);
            });
    }

    /**
     * Create a user on behalf of an administrator. Admin-created accounts are
     * pre-verified (the admin vouches for the address) and their status is
     * applied with the same timestamp semantics as {@see administer()}.
     *
     * @param  array{first_name: string, last_name: string, email: string, password: string, role: UserRole|string, status: UserStatus|string}  $data
     */
    public function createUser(User $actor, array $data): User
    {
        abort_unless($actor->isAdmin() && $actor->isActive() && $actor->hasVerifiedEmail(), 403);

        return DB::transaction(function () use ($actor, $data): User {
            $actor = User::query()->lockForUpdate()->findOrFail($actor->id);
            abort_unless($actor->isAdmin() && $actor->isActive() && $actor->hasVerifiedEmail(), 403);

            $role = $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']);
            $status = $data['status'] instanceof UserStatus ? $data['status'] : UserStatus::from($data['status']);

            $user = new User([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => Str::lower($data['email']),
                'password' => $data['password'],
                'role' => $role,
            ]);
            $user->status = $status;
            if ($status === UserStatus::Active) {
                $user->approved_at = now();
            } elseif ($status === UserStatus::Suspended) {
                $user->suspended_at = now();
            }
            $user->forceFill(['email_verified_at' => now()]);
            $user->save();

            SettingsAuditEvent::query()->create([
                'actor_id' => $actor->id,
                'user_id' => $user->id,
                'scope' => 'personal',
                'action' => 'account_created_by_admin',
                'changes' => ['role' => $role->value, 'status' => $status->value],
            ]);

            return $user;
        }, 3);
    }

    /** @return Collection<int, array{current: bool, ip_address: string, user_agent: string, last_activity: Carbon}> */
    public function sessions(Request $request): Collection
    {
        if (config('session.driver') !== 'database') {
            return collect();
        }

        return DB::connection(config('session.connection'))->table(config('session.table', 'sessions'))
            ->where('user_id', $request->user()->id)->where('last_activity', '>=', now()->subMinutes((int) config('session.lifetime'))->timestamp)
            ->orderByDesc('last_activity')->get(['id', 'ip_address', 'user_agent', 'last_activity'])
            ->map(fn (object $session): array => [
                'current' => hash_equals($request->session()->getId(), $session->id),
                'ip_address' => $this->maskIp((string) $session->ip_address),
                'user_agent' => Str::limit((string) $session->user_agent, 180),
                'last_activity' => Carbon::createFromTimestamp($session->last_activity),
            ]);
    }

    /** @param Closure(User): void $operation */
    private function protectRemoval(User $target, UserRole $role, UserStatus $status, Closure $operation): void
    {
        DB::transaction(function () use ($target, $role, $status, $operation): void {
            $admins = User::query()->role(UserRole::Admin)->active()->whereNotNull('email_verified_at')
                ->orderBy('id')->lockForUpdate()->get(['id']);
            $locked = User::query()->lockForUpdate()->findOrFail($target->id);
            if ($locked->isAdmin() && $locked->isActive() && $locked->hasVerifiedEmail()
                && ($role !== UserRole::Admin || $status !== UserStatus::Active)
                && $admins->where('id', '<>', $locked->id)->isEmpty()) {
                throw ValidationException::withMessages(['user' => 'You cannot remove the last active, verified administrator.']);
            }

            if ($role !== UserRole::Organization || $status !== UserStatus::Active) {
                $this->ownerGuard->protect($locked, fn () => $operation($locked));
            } else {
                $operation($locked);
            }
        }, 3);
    }

    private function retainCurrentSession(Request $request, User $user): void
    {
        Auth::guard('web')->setUser($user);
        $request->setUserResolver(fn () => $user);
        $request->session()->regenerate(true);
        $request->session()->regenerateToken();
        $request->session()->put('auth.version', (int) $user->authentication_version);
    }

    private function audit(User $user, string $action): void
    {
        SettingsAuditEvent::query()->create(['actor_id' => $user->id, 'user_id' => $user->id, 'scope' => 'personal', 'action' => $action, 'changes' => []]);
    }

    private function notifyAfterCommit(User $user, string $message): void
    {
        DB::afterCommit(function () use ($user, $message): void {
            try {
                $user->notify(new AccountSecurityNotice($message));
            } catch (Throwable) {
                Log::warning('An account security notice could not be queued.', ['user_id' => $user->id]);
            }
        });
    }

    private function maskIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.[0-9]+$/', '.*', $ip);
        }

        return $ip === '' ? 'Unknown' : implode(':', array_slice(explode(':', $ip), 0, 3)).':…';
    }
}
