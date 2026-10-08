<?php

use App\Enums\UserStatus;
use App\Jobs\SendWelcomeEmail;
use App\Models\User;
use Illuminate\Support\Facades\Queue;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(string $id, string $email, bool $verified = true): SocialiteUser
{
    return SocialiteUser::fake([
        'id' => $id,
        'email' => $email,
        'name' => 'Ada Obi',
        'given_name' => 'Ada',
        'family_name' => 'Obi',
        'email_verified' => $verified,
    ]);
}

function fakeGoogleProvider(SocialiteUser $user): void
{
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->once()->andReturn($user);

    Socialite::shouldReceive('driver')
        ->once()
        ->with('google')
        ->andReturn($provider);
}

test('a verified Google claim activates a new account through the shared verification flow', function () {
    Queue::fake();
    fakeGoogleProvider(fakeGoogleUser('google-new-user', 'ada.google@example.com'));

    $response = $this->get(route('auth.google-callback'));

    $user = User::where('email', 'ada.google@example.com')->firstOrFail();

    $response->assertRedirect(route('dashboard', absolute: false));
    expect($user->google_id)->toBe('google-new-user')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->status)->toBe(UserStatus::Active)
        ->and($user->welcome_email_queued_at)->not->toBeNull();
    Queue::assertPushed(SendWelcomeEmail::class, fn (SendWelcomeEmail $job): bool => $job->userId === $user->id);
});

test('a verified Google claim activates a linked pending account and schedules one welcome email', function () {
    Queue::fake();
    $user = User::factory()->unverified()->create(['email' => 'linked.google@example.com']);
    fakeGoogleProvider(fakeGoogleUser('google-linked-user', $user->email));

    $this->get(route('auth.google-callback'))->assertRedirect(route('dashboard', absolute: false));

    $user->refresh();
    expect($user->google_id)->toBe('google-linked-user')
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and($user->status)->toBe(UserStatus::Active);
    Queue::assertPushed(SendWelcomeEmail::class, 1);
});

test('an already verified Google account does not receive another welcome job', function () {
    Queue::fake();
    $user = User::factory()->create(['google_id' => 'google-existing-user']);
    fakeGoogleProvider(fakeGoogleUser($user->google_id, $user->email));

    $this->get(route('auth.google-callback'))->assertRedirect(route('dashboard', absolute: false));

    Queue::assertNotPushed(SendWelcomeEmail::class);
});

test('an unverified Google claim is rejected before an account is created', function () {
    Queue::fake();
    fakeGoogleProvider(fakeGoogleUser('google-unverified-user', 'unverified.google@example.com', false));

    $this->get(route('auth.google-callback'))
        ->assertRedirect(route('login', absolute: false))
        ->assertSessionHasErrors('email');

    expect(User::where('email', 'unverified.google@example.com')->exists())->toBeFalse();
    Queue::assertNothingPushed();
});
