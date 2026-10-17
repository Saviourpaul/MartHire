<?php

namespace App\Services;

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Enums\TeamInvitationStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\JobPostMember;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use App\Support\OrganizationContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationTeamService
{
    public function __construct(
        private readonly EmailVerificationService $emailVerification,
        private readonly ActivityLogger $activityLogger,
        private readonly OrganizationContext $context,
    ) {}

    public function invite(Organization $organization, User $actor, string $email, OrganizationRole $role): TeamInvitation
    {
        return DB::transaction(function () use ($organization, $actor, $email, $role): TeamInvitation {
            $this->lockOrganization($organization);
            $manager = $this->authorizeManager($organization, $actor);
            $this->authorizeRole($manager, $role);
            $email = Str::lower(trim($email));
            $recipient = User::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($recipient && (! $recipient->isEmployer() || $recipient->trashed() || $recipient->isSuspended())) {
                throw ValidationException::withMessages(['email' => 'This email cannot be invited as an employer team member.']);
            }

            if (
                $recipient && OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                    ->where('organization_id', $organization->id)
                    ->where('user_id', $recipient->id)
                    ->exists()
            ) {
                throw ValidationException::withMessages(['email' => 'This person is already a member of your organization.']);
            }

            TeamInvitation::withoutGlobalScope(OrganizationScope::class)
                ->where('organization_id', $organization->id)
                ->where('email', $email)
                ->where('status', TeamInvitationStatus::Pending->value)
                ->update(['status' => TeamInvitationStatus::Revoked->value]);

            $token = Str::random(64);
            $invitation = TeamInvitation::create([
                'organization_id' => $organization->id,
                'email' => $email,
                'role' => $role,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addMinutes((int) config('auth.invitations.expire', 10080)),
                'status' => TeamInvitationStatus::Pending,
                'invited_by' => $actor->id,
            ]);

            $this->recordActivity($organization, $actor, [
                'action' => 'invitation_sent',
                'invitation_id' => $invitation->id,
                'email' => $email,
                'role' => $role->value,
            ]);

            $notification = new TeamInvitationNotification($invitation->id, $organization->id, $organization->name, $role, $token);

            if ($recipient) {
                $recipient->notify($notification);
            } else {
                Notification::route('mail', $email)->notify($notification);
            }

            return $invitation;
        });
    }

    public function availableInvitation(string $token): TeamInvitation
    {
        $invitation = $this->invitationQuery($token)->firstOrFail();
        $this->ensureAvailable($invitation);

        return $invitation->load(['organization', 'invitedBy']);
    }

    /**
     * @param  array{first_name?: string, last_name?: string, password?: string, email?: string}  $data
     */
    public function accept(string $token, ?User $actor, array $data = []): OrganizationMember
    {
        $invitation = $this->availableInvitation($token);

        return DB::transaction(function () use ($token, $actor, $data, $invitation): OrganizationMember {
            $organization = Organization::query()->lockForUpdate()->findOrFail($invitation->organization_id);
            $invitation = $this->invitationQuery($token)->lockForUpdate()->firstOrFail();
            $this->ensureAvailable($invitation);

            if (isset($data['email']) && ! $this->emailsMatch($data['email'], $invitation->email)) {
                throw ValidationException::withMessages(['email' => 'Use the email address this invitation was sent to.']);
            }

            $recipient = User::withTrashed()->whereRaw('LOWER(email) = ?', [$invitation->email])->lockForUpdate()->first();

            if ($actor) {
                abort_unless($this->emailsMatch($actor->email, $invitation->email) && $recipient?->is($actor), 403);
            } elseif ($recipient) {
                throw ValidationException::withMessages(['invitation' => 'Sign in with the invited email address before accepting.']);
            }

            if ($recipient && (! $recipient->isEmployer() || $recipient->trashed() || $recipient->isSuspended())) {
                throw ValidationException::withMessages(['invitation' => 'This account cannot join an employer organization.']);
            }

            if (! $recipient) {
                $recipient = User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $invitation->email,
                    'password' => $data['password'],
                    'role' => UserRole::Employer,
                    'status' => UserStatus::Pending,
                ]);
            }

            $recipient = $this->emailVerification->verifyTrustedEmail($recipient, $invitation->email);
            abort_unless($recipient, 403);

            return $this->context->runInOrganization($organization, function () use ($organization, $recipient, $invitation): OrganizationMember {
                $member = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                    ->withTrashed()
                    ->where('organization_id', $organization->id)
                    ->where('user_id', $recipient->id)
                    ->first();

                if ($member && ! $member->trashed()) {
                    throw ValidationException::withMessages(['invitation' => 'You are already a member of this organization.']);
                }

                if ($member) {
                    $member->restore();
                    $member->update(['role' => $invitation->role, 'status' => OrganizationMemberStatus::Active]);
                } else {
                    $member = OrganizationMember::create([
                        'organization_id' => $organization->id,
                        'user_id' => $recipient->id,
                        'role' => $invitation->role,
                        'status' => OrganizationMemberStatus::Active,
                    ]);
                }

                $invitation->update(['status' => TeamInvitationStatus::Accepted, 'accepted_at' => now()]);
                $this->recordActivity($organization, $recipient, [
                    'action' => 'invitation_accepted',
                    'invitation_id' => $invitation->id,
                    'member_id' => $member->id,
                    'role' => $member->role->value,
                ]);

                return $member->load(['user', 'organization']);
            });
        });
    }

    public function decline(string $token, ?User $actor): void
    {
        $invitation = $this->availableInvitation($token);

        DB::transaction(function () use ($token, $actor, $invitation): void {
            $organization = Organization::query()->lockForUpdate()->findOrFail($invitation->organization_id);
            $invitation = $this->invitationQuery($token)->lockForUpdate()->firstOrFail();
            $this->ensureAvailable($invitation);
            abort_if($actor && ! $this->emailsMatch($actor->email, $invitation->email), 403);
            $this->context->runInOrganization($organization, function () use ($organization, $actor, $invitation): void {
                $invitation->update(['status' => TeamInvitationStatus::Declined, 'declined_at' => now()]);

                $this->recordActivity($organization, $actor, [
                    'action' => 'invitation_declined',
                    'invitation_id' => $invitation->id,
                ]);
            });
        });
    }

    public function updateMember(OrganizationMember $member, User $actor, OrganizationRole $role, OrganizationMemberStatus $status): OrganizationMember
    {
        return DB::transaction(function () use ($member, $actor, $role, $status): OrganizationMember {
            $organization = Organization::query()->lockForUpdate()->findOrFail($member->organization_id);
            $manager = $this->authorizeManager($organization, $actor);
            $member = OrganizationMember::withoutGlobalScope(OrganizationScope::class)->lockForUpdate()->findOrFail($member->id);
            $this->authorizeRole($manager, $role, $member);
            $this->protectLastOwner($member, $role, $status);
            $previousRole = $member->role;
            $previousStatus = $member->status;
            $member->update(['role' => $role, 'status' => $status]);

            $this->recordActivity($organization, $actor, [
                'action' => 'member_updated',
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'from_role' => $previousRole->value,
                'to_role' => $role->value,
                'from_status' => $previousStatus->value,
                'to_status' => $status->value,
            ]);

            return $member;
        });
    }

    public function removeMember(OrganizationMember $member, User $actor): void
    {
        DB::transaction(function () use ($member, $actor): void {
            $organization = Organization::query()->lockForUpdate()->findOrFail($member->organization_id);
            $manager = $this->authorizeManager($organization, $actor);
            $member = OrganizationMember::withoutGlobalScope(OrganizationScope::class)->lockForUpdate()->findOrFail($member->id);
            $this->authorizeRole($manager, $member->role, $member);
            $this->protectLastOwner($member, $member->role, OrganizationMemberStatus::Suspended);

            JobPostMember::withoutGlobalScope(OrganizationScope::class)
                ->where('organization_id', $organization->id)
                ->where('user_id', $member->user_id)
                ->delete();

            $member->delete();
            $this->recordActivity($organization, $actor, [
                'action' => 'member_removed',
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'role' => $member->role->value,
            ]);
        });
    }

    private function lockOrganization(Organization $organization): void
    {
        Organization::query()->lockForUpdate()->findOrFail($organization->id);
    }

    private function authorizeManager(Organization $organization, User $actor): OrganizationMember
    {
        abort_unless($actor->isEmployer() && $actor->isActive() && $actor->hasVerifiedEmail(), 403);
        $manager = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $organization->id)
            ->where('user_id', $actor->id)
            ->where('status', OrganizationMemberStatus::Active->value)
            ->first();

        abort_unless($manager && in_array($manager->role, [OrganizationRole::Owner, OrganizationRole::HrManager], true), 403);

        return $manager;
    }

    private function authorizeRole(OrganizationMember $manager, OrganizationRole $role, ?OrganizationMember $target = null): void
    {
        abort_if($manager->role === OrganizationRole::HrManager && ($role === OrganizationRole::Owner || $target?->role === OrganizationRole::Owner), 403);
    }

    private function protectLastOwner(OrganizationMember $member, OrganizationRole $role, OrganizationMemberStatus $status): void
    {
        if (
            $member->role !== OrganizationRole::Owner || $member->status !== OrganizationMemberStatus::Active
            || ($role === OrganizationRole::Owner && $status === OrganizationMemberStatus::Active)
        ) {
            return;
        }

        $ownerCount = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->where('organization_id', $member->organization_id)
            ->where('role', OrganizationRole::Owner->value)
            ->where('status', OrganizationMemberStatus::Active->value)
            ->whereHas('user', fn ($query) => $query->active()->role(UserRole::Employer)->whereNotNull('email_verified_at')->whereNull('deleted_at'))
            ->count();

        if ($ownerCount <= 1) {
            throw ValidationException::withMessages(['member' => 'The organization must keep at least one active owner.']);
        }
    }

    /**
     * @return Builder<TeamInvitation>
     */
    private function invitationQuery(string $token): Builder
    {
        abort_unless(strlen($token) === 64 && ctype_alnum($token), 404);

        return TeamInvitation::withoutGlobalScope(OrganizationScope::class)->where('token_hash', hash('sha256', $token));
    }

    private function ensureAvailable(TeamInvitation $invitation): void
    {
        abort_unless($invitation->status === TeamInvitationStatus::Pending && $invitation->expires_at->isFuture(), 410, 'This invitation has expired or has already been used.');
    }

    private function emailsMatch(string $first, string $second): bool
    {
        return hash_equals(Str::lower(trim($first)), Str::lower(trim($second)));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordActivity(Organization $organization, ?User $actor, array $payload): void
    {
        $this->context->runInOrganization($organization, fn () => $this->activityLogger->record($organization, null, $actor, 'system', $payload));
    }
}
