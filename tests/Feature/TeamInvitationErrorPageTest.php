<?php

use App\Enums\TeamInvitationStatus;
use App\Models\TeamInvitation;
use App\Support\OrganizationContext;
use Illuminate\Support\Str;

/** @return array{TeamInvitation, string} */
function unavailableTeamInvitation(string $state): array
{
    app(OrganizationContext::class)->set(null, true);
    $token = Str::random(64);
    $invitation = TeamInvitation::factory()->create([
        'status' => $state === 'expired' ? TeamInvitationStatus::Pending : TeamInvitationStatus::from($state),
        'expires_at' => $state === 'expired' ? now()->subMinute() : now()->addDays(7),
        'token_hash' => hash('sha256', $token),
    ]);

    return [$invitation, $token];
}

it('renders a clear unavailable invitation page for every invitation action', function (bool $debug, string $state) {
    config(['app.debug' => $debug]);
    [$invitation, $token] = unavailableTeamInvitation($state);
    $originalStatus = $invitation->status;
    $originalExpiry = $invitation->expires_at->toDateTimeString();
    $message = 'This invitation has expired or has already been used.';

    $responses = [
        $this->get(route('team-invitations.show', $token)),
        $this->post(route('team-invitations.accept', $token), [
            'first_name' => 'Taylor', 'last_name' => 'Tester',
            'password' => 'SecurePassword123!', 'password_confirmation' => 'SecurePassword123!',
        ]),
        $this->post(route('team-invitations.decline', $token)),
    ];

    foreach ($responses as $response) {
        $response->assertGone()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertSee('Invitation unavailable')
            ->assertSee($message)
            ->assertSee('Ask the person who invited you to send a new invitation.')
            ->assertSee('href="'.route('home').'"', false)
            ->assertDontSee('Symfony\\Component\\HttpKernel\\Exception\\HttpException', false)
            ->assertDontSee('Stack trace');
    }

    app(OrganizationContext::class)->set(null, true);
    expect($invitation->fresh()->status)->toBe($originalStatus)
        ->and($invitation->fresh()->expires_at->toDateTimeString())->toBe($originalExpiry);
})->with([true, false])->with(['expired', 'accepted', 'declined', 'revoked']);

it('preserves JSON errors for unavailable invitations', function (bool $debug) {
    config(['app.debug' => $debug]);
    [, $token] = unavailableTeamInvitation('expired');

    $this->getJson(route('team-invitations.show', $token))
        ->assertGone()
        ->assertJsonPath('message', 'This invitation has expired or has already been used.');
})->with([true, false]);

it('keeps unknown invitation tokens as not found', function () {
    $this->get(route('team-invitations.show', Str::random(64)))->assertNotFound();
});
