<?php

use App\Enums\OrganizationMemberStatus;
use App\Enums\OrganizationRole;
use App\Enums\TeamInvitationStatus;
use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\JobPostMember;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use App\Services\OrganizationTeamService;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @return array{Organization, User, OrganizationMember}
 */
function organizationTeamFixture(OrganizationRole $role = OrganizationRole::Owner): array
{
    $organization = Organization::factory()->create();
    $user = User::factory()->employer()->create();
    app(OrganizationContext::class)->set($organization);
    $member = OrganizationMember::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => OrganizationMemberStatus::Active,
    ]);
    app(OrganizationContext::class)->set($organization);

    return [$organization, $user, $member];
}

/**
 * @return array{TeamInvitation, string}
 */
function organizationTeamInvitation(Organization $organization, string $email, OrganizationRole $role = OrganizationRole::Recruiter): array
{
    $token = Str::random(64);
    $invitation = TeamInvitation::factory()->create([
        'organization_id' => $organization->id,
        'email' => $email,
        'role' => $role,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now()->addDays(7),
        'status' => TeamInvitationStatus::Pending,
    ]);

    return [$invitation, $token];
}

it('queues hashed seven day employer invitations without storing the bearer token in notifications or activity', function () {
    [$organization, $owner] = organizationTeamFixture();
    $recipient = User::factory()->employer()->create();
    Notification::fake();
    $this->freezeTime();

    $invitation = app(OrganizationTeamService::class)->invite($organization, $owner, Str::upper($recipient->email), OrganizationRole::Recruiter);

    expect($invitation->email)->toBe($recipient->email)
        ->and($invitation->expires_at->toDateTimeString())->toBe(now()->addDays(7)->toDateTimeString())
        ->and($invitation->token_hash)->toHaveLength(64)
        ->and($invitation->status)->toBe(TeamInvitationStatus::Pending);

    Notification::assertSentTo($recipient, TeamInvitationNotification::class, function (TeamInvitationNotification $notification, array $channels) use ($recipient, $invitation): bool {
        $token = basename((string) parse_url($notification->toMail($recipient)->actionUrl, PHP_URL_PATH));

        expect($notification)->toBeInstanceOf(ShouldQueue::class)->toBeInstanceOf(ShouldBeEncrypted::class)
            ->and($notification->afterCommit)->toBeTrue()
            ->and($channels)->toBe(['mail', 'database'])
            ->and(hash('sha256', $token))->toBe($invitation->token_hash)
            ->and(json_encode($notification->toArray($recipient)))->not->toContain($token)
            ->and(ActivityLog::query()->first()->payload['action'])->toBe('invitation_sent');

        return true;
    });
});

it('uses on demand mail for new invitees and rejects applicant and suspended accounts', function () {
    [$organization, $owner] = organizationTeamFixture();
    $applicant = User::factory()->applicant()->create();
    $suspended = User::factory()->employer()->suspended()->create();
    Notification::fake();
    $service = app(OrganizationTeamService::class);

    $service->invite($organization, $owner, 'new-team@example.test', OrganizationRole::HiringManager);
    Notification::assertSentOnDemand(TeamInvitationNotification::class);

    foreach ([$applicant, $suspended] as $recipient) {
        expect(fn () => $service->invite($organization, $owner, $recipient->email, OrganizationRole::Recruiter))
            ->toThrow(ValidationException::class);
    }

    expect(TeamInvitation::query()->count())->toBe(1);
});

it('revokes previous pending tokens when a replacement invitation is sent', function () {
    [$organization, $owner] = organizationTeamFixture();
    Notification::fake();
    $service = app(OrganizationTeamService::class);

    $first = $service->invite($organization, $owner, 'new-team@example.test', OrganizationRole::Recruiter);
    $second = $service->invite($organization, $owner, 'new-team@example.test', OrganizationRole::HiringManager);

    expect($first->fresh()->status)->toBe(TeamInvitationStatus::Revoked)
        ->and($second->status)->toBe(TeamInvitationStatus::Pending)
        ->and($second->token_hash)->not->toBe($first->token_hash);
});

it('creates and verifies an employer only through a valid invitation and logs in the new member', function () {
    [$organization] = organizationTeamFixture();
    [$invitation, $token] = organizationTeamInvitation($organization, 'new-employer@example.test', OrganizationRole::Interviewer);
    Queue::fake();
    Notification::fake();

    $this->post(route('team-invitations.accept', $token), [
        'first_name' => 'New',
        'last_name' => 'Interviewer',
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ])->assertRedirect(route('dashboard'))
        ->assertSessionHas('active_organization_id', $organization->id);

    $user = User::query()->where('email', $invitation->email)->firstOrFail();
    $this->assertAuthenticatedAs($user);
    expect($user->role)->toBe(UserRole::Employer)
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->isActive())->toBeTrue()
        ->and(Hash::check('SecurePassword123!', $user->password))->toBeTrue()
        ->and($invitation->fresh()->status)->toBe(TeamInvitationStatus::Accepted)
        ->and(OrganizationMember::withoutGlobalScope(OrganizationScope::class)->where('user_id', $user->id)->first()->role)->toBe(OrganizationRole::Interviewer);
});

it('binds acceptance to the invited email and requires existing employers to sign in', function () {
    [$organization] = organizationTeamFixture();
    $recipient = User::factory()->employer()->create();
    $wrongUser = User::factory()->employer()->create();
    [$invitation, $token] = organizationTeamInvitation($organization, $recipient->email);
    $service = app(OrganizationTeamService::class);

    expect(fn () => $service->accept($token, null))->toThrow(ValidationException::class)
        ->and(fn () => $service->accept($token, $wrongUser))->toThrow(HttpException::class)
        ->and(fn () => $service->accept($token, $recipient, ['email' => $wrongUser->email]))->toThrow(ValidationException::class);

    expect($invitation->fresh()->status)->toBe(TeamInvitationStatus::Pending);
    $member = $service->accept($token, $recipient);
    expect($member->user_id)->toBe($recipient->id)
        ->and(fn () => $service->accept($token, $recipient))->toThrow(HttpException::class);
});

it('rejects expired invitations and applicants without changing their platform role', function () {
    [$organization] = organizationTeamFixture();
    $applicant = User::factory()->applicant()->create();
    [$invitation, $token] = organizationTeamInvitation($organization, $applicant->email);
    $service = app(OrganizationTeamService::class);

    expect(fn () => $service->accept($token, $applicant))->toThrow(ValidationException::class);
    $invitation->update(['expires_at' => now()->subSecond()]);

    $this->get(route('team-invitations.show', $token))->assertGone();
    expect($applicant->fresh()->role)->toBe(UserRole::Applicant)
        ->and($invitation->fresh()->status)->toBe(TeamInvitationStatus::Pending);
});

it('declines without creating an account and consumes the token exactly once', function () {
    [$organization] = organizationTeamFixture();
    [$invitation, $token] = organizationTeamInvitation($organization, 'decline@example.test');
    $userCount = User::query()->count();

    $this->post(route('team-invitations.decline', $token))->assertRedirect(route('login'));
    $this->post(route('team-invitations.decline', $token))->assertGone();
    expect($invitation->fresh()->status)->toBe(TeamInvitationStatus::Declined)
        ->and(User::query()->count())->toBe($userCount);
});

it('restricts team management and protects owners from HR managers', function () {
    [$organization, $owner, $ownerMember] = organizationTeamFixture();
    $hr = User::factory()->employer()->create();
    $hrMember = OrganizationMember::factory()->create(['organization_id' => $organization->id, 'user_id' => $hr->id, 'role' => OrganizationRole::HrManager]);
    $recruiter = User::factory()->employer()->create();
    OrganizationMember::factory()->create(['organization_id' => $organization->id, 'user_id' => $recruiter->id, 'role' => OrganizationRole::Recruiter]);
    Notification::fake();
    $service = app(OrganizationTeamService::class);

    expect(fn () => $service->invite($organization, $recruiter, 'blocked@example.test', OrganizationRole::Interviewer))->toThrow(HttpException::class)
        ->and(fn () => $service->invite($organization, $hr, 'owner@example.test', OrganizationRole::Owner))->toThrow(HttpException::class)
        ->and(fn () => $service->updateMember($ownerMember, $hr, OrganizationRole::Recruiter, OrganizationMemberStatus::Active))->toThrow(HttpException::class)
        ->and(fn () => $service->removeMember($ownerMember, $hr))->toThrow(HttpException::class)
        ->and(fn () => $service->updateMember($hrMember, $hr, OrganizationRole::Owner, OrganizationMemberStatus::Active))->toThrow(HttpException::class);

    $service->invite($organization, $hr, 'allowed@example.test', OrganizationRole::HiringManager);
    expect(TeamInvitation::query()->count())->toBe(1);
});

it('protects the last active owner from removal suspension and demotion', function () {
    [$organization, $owner, $member] = organizationTeamFixture();
    $service = app(OrganizationTeamService::class);

    expect(fn () => $service->removeMember($member, $owner))->toThrow(ValidationException::class)
        ->and(fn () => $service->updateMember($member, $owner, OrganizationRole::Recruiter, OrganizationMemberStatus::Active))->toThrow(ValidationException::class)
        ->and(fn () => $service->updateMember($member, $owner, OrganizationRole::Owner, OrganizationMemberStatus::Suspended))->toThrow(ValidationException::class);

    expect($member->fresh()->role)->toBe(OrganizationRole::Owner)
        ->and($member->fresh()->status)->toBe(OrganizationMemberStatus::Active);
});

it('removes memberships and assignments immediately while preserving user and activity authors', function () {
    [$organization, $owner] = organizationTeamFixture();
    $recruiter = User::factory()->employer()->create();
    $member = OrganizationMember::factory()->create(['organization_id' => $organization->id, 'user_id' => $recruiter->id, 'role' => OrganizationRole::Recruiter]);
    $job = Job::factory()->create(['organization_id' => $organization->id, 'employer_id' => $owner->id]);
    JobPostMember::factory()->create(['organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $recruiter->id]);
    $activity = ActivityLog::factory()->create(['organization_id' => $organization->id, 'actor_id' => $recruiter->id]);

    app(OrganizationTeamService::class)->removeMember($member, $owner);

    expect(OrganizationMember::withoutGlobalScope(OrganizationScope::class)->find($member->id))->toBeNull()
        ->and($member->fresh()->trashed())->toBeTrue()
        ->and(JobPostMember::withoutGlobalScope(OrganizationScope::class)->where('user_id', $recruiter->id)->count())->toBe(0)
        ->and(User::query()->find($recruiter->id))->not->toBeNull()
        ->and($activity->fresh()->actor_id)->toBe($recruiter->id);

    $this->actingAs($recruiter)->withSession(['active_organization_id' => $organization->id])
        ->get(route('employer.applications.show', ApplicationForm::factory()->create(['job_id' => $job->id, 'organization_id' => $organization->id])))
        ->assertNotFound();
});

it('does not allow managers to operate on another organizations team', function () {
    [$firstOrganization, $firstOwner] = organizationTeamFixture();
    [$secondOrganization, $secondOwner, $secondMember] = organizationTeamFixture();
    $service = app(OrganizationTeamService::class);

    expect(fn () => $service->updateMember($secondMember, $firstOwner, OrganizationRole::Recruiter, OrganizationMemberStatus::Active))->toThrow(HttpException::class)
        ->and(fn () => $service->invite($firstOrganization, $secondOwner, 'blocked@example.test', OrganizationRole::Recruiter))->toThrow(HttpException::class);
});

it('blocks forged HR owner changes and permits ordinary team role updates through the routes', function () {
    [$organization, $owner, $ownerMember] = organizationTeamFixture();
    $hr = User::factory()->employer()->create();
    $hrMember = OrganizationMember::factory()->create(['organization_id' => $organization->id, 'user_id' => $hr->id, 'role' => OrganizationRole::HrManager]);
    Notification::fake();

    $this->actingAs($hr)->withSession(['active_organization_id' => $organization->id]);
    $this->post(route('employer.team.invitations.store'), ['email' => 'forged@example.test', 'role' => 'owner'])->assertForbidden();
    $this->patch(route('employer.team.members.update', $ownerMember), ['role' => 'recruiter'])->assertForbidden();
    $this->delete(route('employer.team.members.destroy', $ownerMember))->assertForbidden();

    $this->actingAs($owner)->withSession(['active_organization_id' => $organization->id])
        ->patch(route('employer.team.members.update', $hrMember), ['role' => 'recruiter'])
        ->assertRedirect(route('employer.team.index'));

    expect($hrMember->fresh()->role)->toBe(OrganizationRole::Recruiter);
    Notification::assertNothingSent();
});

it('rate limits invitation creation and token redemption', function () {
    [$organization, $owner] = organizationTeamFixture();
    Notification::fake();
    $this->actingAs($owner)->withSession(['active_organization_id' => $organization->id]);

    foreach (range(1, 5) as $number) {
        $this->post(route('employer.team.invitations.store'), ['email' => 'invite'.$number.'@example.test', 'role' => 'recruiter'])
            ->assertRedirect(route('employer.team.index'));
    }

    $this->post(route('employer.team.invitations.store'), ['email' => 'blocked@example.test', 'role' => 'recruiter'])->assertTooManyRequests();
    expect(TeamInvitation::query()->count())->toBe(5);

    $this->post(route('logout'));
    $body = ['first_name' => 'New', 'last_name' => 'User', 'password' => 'SecurePassword123!', 'password_confirmation' => 'SecurePassword123!'];

    foreach (range(1, 6) as $number) {
        $this->post(route('team-invitations.accept', Str::repeat('x', 64)), $body)->assertNotFound();
    }

    $this->post(route('team-invitations.accept', Str::repeat('x', 64)), $body)->assertTooManyRequests();
});

it('refuses revoked tokens suspended employers and wrong email declines without consuming pending invitations', function () {
    [$organization] = organizationTeamFixture();
    $recipient = User::factory()->employer()->create();
    $wrongUser = User::factory()->employer()->create();
    [$invitation, $token] = organizationTeamInvitation($organization, $recipient->email);
    $service = app(OrganizationTeamService::class);

    expect(fn () => $service->decline($token, $wrongUser))->toThrow(HttpException::class);
    $recipient->suspend();
    expect(fn () => $service->accept($token, $recipient))->toThrow(ValidationException::class)
        ->and($invitation->fresh()->status)->toBe(TeamInvitationStatus::Pending);

    $invitation->update(['status' => TeamInvitationStatus::Revoked]);
    $this->get(route('team-invitations.show', $token))->assertGone();
    expect(fn () => $service->accept($token, null))->toThrow(HttpException::class);
});

it('accepts an existing employer into a second organization and restores context after redemption', function () {
    [$organization] = organizationTeamFixture();
    [$otherOrganization, $recipient] = organizationTeamFixture();
    app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $recipient, &$invitation, &$token): void {
        [$invitation, $token] = organizationTeamInvitation($organization, $recipient->email);
    });

    $member = app(OrganizationTeamService::class)->accept($token, $recipient);

    expect($member->organization_id)->toBe($organization->id)
        ->and($invitation->fresh()->status)->toBe(TeamInvitationStatus::Accepted)
        ->and(app(OrganizationContext::class)->organizationId())->toBe($otherOrganization->id);
});

it('lets an authenticated employer decline another organizations invitation without changing their current organization', function () {
    [$organization] = organizationTeamFixture();
    [$otherOrganization, $recipient] = organizationTeamFixture();
    app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $recipient, &$invitation, &$token): void {
        [$invitation, $token] = organizationTeamInvitation($organization, $recipient->email);
    });

    $this->actingAs($recipient)->withSession(['active_organization_id' => $otherOrganization->id])
        ->post(route('team-invitations.decline', $token))->assertRedirect(route('dashboard'))
        ->assertSessionHas('active_organization_id', $otherOrganization->id);

    expect($invitation->fresh()->status)->toBe(TeamInvitationStatus::Declined)
        ->and(app(OrganizationContext::class)->organizationId())->toBe($otherOrganization->id);
});
