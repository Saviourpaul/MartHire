<?php

namespace App\Services;

use App\Enums\SecurityActionPurpose;
use App\Jobs\SendSecurityChallengeEmail;
use App\Models\SecurityChallenge;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use SensitiveParameter;
use Throwable;

class SecurityChallengeService
{
    public function issue(Request $request, SecurityActionPurpose $purpose): SecurityChallenge
    {
        $code = (string) random_int(100000, 999999);
        $challenge = DB::transaction(function () use ($request, $purpose, $code): SecurityChallenge {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $this->assertEligible($user, $purpose);
            abort_unless(filled($user->google_id), 403);
            $sessionHash = $this->fingerprint($request->session()->getId());
            $latest = SecurityChallenge::query()->where('user_id', $user->id)
                ->where('purpose', $purpose->value)->where('session_hash', $sessionHash)->latest()->first();

            if ($latest && $latest->created_at->gt(now()->subSeconds(60))) {
                throw ValidationException::withMessages(['code' => 'Wait 60 seconds before requesting another code.']);
            }

            if (SecurityChallenge::query()->where('user_id', $user->id)->where('created_at', '>', now()->subHour())->count() >= 6) {
                throw ValidationException::withMessages(['code' => 'Too many code requests. Try again in an hour.']);
            }

            $ipHash = $this->fingerprint((string) $request->ip());
            $ipKey = 'settings-security-send:'.$ipHash;
            if (RateLimiter::increment($ipKey, 3600) > 20) {
                throw ValidationException::withMessages(['code' => 'Too many code requests. Try again later.']);
            }

            SecurityChallenge::query()->where('user_id', $user->id)->where('purpose', $purpose->value)
                ->where('session_hash', $sessionHash)->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'code_hash' => null]);

            $id = (string) Str::uuid();

            return SecurityChallenge::query()->create([
                'id' => $id,
                'user_id' => $user->id,
                'purpose' => $purpose,
                'session_hash' => $sessionHash,
                'email_hash' => $this->fingerprint($user->email),
                'authentication_version' => (int) $user->authentication_version,
                'code_hash' => $this->codeHash($id, $code),
                'ip_hash' => $ipHash,
                'expires_at' => now()->addMinutes(15),
            ]);
        }, 3);

        DB::afterCommit(function () use ($challenge, $code): void {
            try {
                SendSecurityChallengeEmail::dispatch($challenge->id, $code);
            } catch (Throwable) {
                $challenge->update(['failed_at' => now()]);
            }
        });

        return $challenge;
    }

    /** @param Closure(User): mixed $operation */
    public function confirm(Request $request, SecurityActionPurpose $purpose, Closure $operation): mixed
    {
        $error = null;
        $result = DB::transaction(function () use ($request, $purpose, $operation, &$error): mixed {
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->getKey());
            $this->assertEligible($user, $purpose);
            if ((int) $request->session()->get('auth.version', -1) !== (int) $user->authentication_version) {
                abort(409, 'Your session changed. Sign in again before continuing.');
            }

            if (! filled($user->google_id)) {
                $field = $purpose === SecurityActionPurpose::AccountClose ? 'password' : 'current_password';
                if (! is_string($request->input($field)) || ! Hash::check($request->input($field), $user->password)) {
                    $error = [$field => __('auth.password')];

                    return null;
                }

                return $operation($user);
            }

            $challenge = SecurityChallenge::query()->whereKey((string) $request->input('challenge_id'))
                ->where('user_id', $user->id)->lockForUpdate()->first();

            if (! $challenge || $challenge->purpose !== $purpose || $challenge->consumed_at !== null
                || $challenge->expires_at->isPast() || $challenge->attempts >= 5 || $challenge->sent_at === null
                || ! hash_equals($challenge->session_hash, $this->fingerprint($request->session()->getId()))
                || ! hash_equals($challenge->email_hash, $this->fingerprint($user->email))
                || $challenge->authentication_version !== (int) $user->authentication_version) {
                $error = ['code' => 'Request a new code for this action and try again.'];

                return null;
            }

            $code = $request->input('code');
            if (! is_string($code) || ! preg_match('/\A[0-9]{6}\z/', $code)
                || ! hash_equals((string) $challenge->code_hash, $this->codeHash($challenge->id, $code))) {
                $challenge->increment('attempts');
                $error = ['code' => 'The code is incorrect.'];

                return null;
            }

            $result = $operation($user);
            SecurityChallenge::query()->where('user_id', $user->id)->whereNull('consumed_at')
                ->update(['consumed_at' => now(), 'code_hash' => null]);

            return $result;
        }, 3);

        if ($error !== null) {
            $bag = match ($purpose) {
                SecurityActionPurpose::PasswordChange => 'updatePassword',
                SecurityActionPurpose::AccountClose => 'userDeletion',
                default => 'default',
            };
            throw ValidationException::withMessages($error)->errorBag($bag);
        }

        return $result;
    }

    public function fingerprint(#[SensitiveParameter] string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }

    public function codeHash(string $id, #[SensitiveParameter] string $code): string
    {
        return $this->fingerprint($id.':'.$code);
    }

    private function assertEligible(User $user, SecurityActionPurpose $purpose): void
    {
        abort_unless($user->isActive() && $user->hasVerifiedEmail() && ! $user->trashed(), 403);
    }
}
