<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Tests\Support\UnavailableCacheStore;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status', __('passwords.sent'));

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): bool {
        $html = $notification->toMail($user)->render();

        expect(str_contains($html, 'Reset your password'))->toBeTrue()
            ->and(str_contains($html, 'Reset password'))->toBeTrue();

        return true;
    });
});

test('password reset requests are limited per email', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.31']);
    $email = 'unknown-reset-limit@example.test';

    // A nonexistent email avoids the controller's successful-send cooldown,
    // so this exercises the route limiter itself.
    foreach (range(1, 3) as $_) {
        $this->postJson('/forgot-password', ['email' => $email])->assertStatus(302);
    }

    $response = $this->postJson('/forgot-password', ['email' => $email]);

    $response->assertStatus(429)->assertJsonStructure(['message']);
    expect($response->json('message'))->toContain('Too many password reset requests.');
});

test('password reset requests are limited per IP across different email addresses', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.32']);

    // Different nonexistent addresses keep the per-email limit below its
    // threshold while exercising the shared IP limit.
    foreach (range(1, 10) as $attempt) {
        $this->postJson('/forgot-password', [
            'email' => "unknown-ip-limit-{$attempt}@example.test",
        ])->assertStatus(302);
    }

    $response = $this->postJson('/forgot-password', [
        'email' => 'unknown-ip-limit-final@example.test',
    ]);

    $response->assertStatus(429)->assertJsonStructure(['message']);
    expect($response->json('message'))->toContain('Too many password reset requests.');
});

test('password reset responses do not reveal whether an email is registered', function () {
    Notification::fake();
    $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.33']);

    $user = User::factory()->create();
    $registered = $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email]);
    $unknown = $this->from('/forgot-password')->post('/forgot-password', ['email' => 'not-registered@example.test']);

    $registered->assertRedirect('/forgot-password')->assertSessionHas('status', __('passwords.sent'));
    $unknown->assertRedirect('/forgot-password')->assertSessionHas('status', __('passwords.sent'));
    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password cooldown remains fail-open when its backing store is unavailable', function () {
    useUnavailableRateLimitStore();
    Notification::fake();

    $user = User::factory()->create();

    $this->from('/forgot-password')->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect('/forgot-password')
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
    expect(UnavailableCacheStore::$attempts)->toBeGreaterThan(0);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->get('/reset-password/'.$notification->token.'?email='.urlencode($user->email));

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('password.reset.success'));

        $this->get(route('password.reset.success'))->assertOk();

        return true;
    });
});

test('password reset links expire after the configured broker lifetime', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): bool {
        $this->travel(config('auth.passwords.users.expire') + 1)->minutes();

        $this->get(route('password.reset', [
            'token' => $notification->token,
            'email' => $user->email,
        ]))->assertStatus(410);

        return true;
    });
});

test('a password reset token cannot be reused after the password is changed', function () {
    Notification::fake();

    $user = User::factory()->create();
    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): bool {
        $credentials = [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'Single-use-reset-Password-123!',
            'password_confirmation' => 'Single-use-reset-Password-123!',
        ];

        $this->post(route('password.store'), $credentials)
            ->assertRedirect(route('password.reset.success'));

        $this->post(route('password.store'), $credentials)
            ->assertStatus(410);

        return true;
    });
});
