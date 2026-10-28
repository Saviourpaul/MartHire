<?php

use App\Enums\OrganizationRole;
use App\Enums\TeamInvitationStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\OrganizationTeamService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Str;

// The team-invitations throttle (5/min by IP) uses a cache-backed counter that
// RefreshDatabase does not reset, so running several invitation requests from the
// test IP within a minute would otherwise flake with 429. These tests exercise the
// invitation flow, not the rate limiter, so the throttle is excluded here.
beforeEach(fn () => $this->withoutMiddleware(ThrottleRequests::class));

/**
 * OrganizationTeamService::invite() hashes the token before persisting and never
 * returns the plaintext, so tests pin Str::random() for the duration of the call
 * to recover it, rather than reaching into the service's internals.
 *
 * @return array{token: string, model: TeamInvitation}
 */
function captureInvitationToken(OrganizationTeamService $service, Organization $organization, User $actor, string $email, OrganizationRole $role): array
{
    $plain = null;
    Str::createRandomStringsUsing(function (int $length) use (&$plain): string {
        $string = '';
        while (($len = strlen($string)) < $length) {
            $bytes = random_bytes((int) ceil(($length - $len) / 3) * 3);
            $string .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $length - $len);
        }

        return $plain = $string;
    });
    $model = $service->invite($organization, $actor, $email, $role);
    Str::createRandomStringsNormally();

    return ['token' => $plain, 'model' => $model];
}

test('an invited new person can view and accept the invitation', function () {
    [$organization, $owner] = organizationWithOwner();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, 'brandnew@example.com', OrganizationRole::Recruiter);

    $this->get(route('team-invitations.show', $invitation['token']))->assertOk()->assertSee('brandnew@example.com');

    $this->post(route('team-invitations.accept', $invitation['token']), [
        'first_name' => 'Brandnew', 'last_name' => 'Person',
        'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(User::where('email', 'brandnew@example.com')->exists())->toBeTrue();
});

test('an invited existing organization member can accept and is logged in', function () {
    [$organization, $owner] = organizationWithOwner();
    $invitee = User::factory()->organization()->create();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, $invitee->email, OrganizationRole::Recruiter);

    $this->get(route('team-invitations.show', $invitation['token']))->assertOk()->assertSee($organization->name);

    $this->actingAs($invitee)
        ->post(route('team-invitations.accept', $invitation['token']))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($invitee);
    expect(OrganizationMember::withoutGlobalScope(OrganizationScope::class)
        ->where('user_id', $invitee->id)->where('organization_id', $organization->id)->exists())->toBeTrue();
});

test('declining an invitation marks it declined', function () {
    [$organization, $owner] = organizationWithOwner();
    $invitee = User::factory()->organization()->create();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, $invitee->email, OrganizationRole::Recruiter);

    $this->actingAs($invitee)->post(route('team-invitations.decline', $invitation['token']))->assertRedirect(route('dashboard'));

    expect(TeamInvitation::withoutGlobalScope(OrganizationScope::class)->find($invitation['model']->id)->status)->toBe(TeamInvitationStatus::Declined);
});

test('a wrong-email accept attempt is rejected', function () {
    [$organization, $owner] = organizationWithOwner();
    $otherUser = User::factory()->organization()->create();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, 'target@example.com', OrganizationRole::Recruiter);

    $this->actingAs($otherUser)->post(route('team-invitations.accept', $invitation['token']))->assertForbidden();
});

test('an expired invitation cannot be accepted', function () {
    [$organization, $owner] = organizationWithOwner();
    $invitee = User::factory()->organization()->create();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, $invitee->email, OrganizationRole::Recruiter);
    $invitation['model']->update(['expires_at' => now()->subDay()]);

    $this->actingAs($invitee)->post(route('team-invitations.accept', $invitation['token']))->assertStatus(410);
});
