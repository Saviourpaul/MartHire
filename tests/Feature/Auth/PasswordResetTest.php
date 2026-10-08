<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\PasswordResetLinkSent;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Transport\TransportInterface;

function issuedResetToken(User $user): string
{
    $token = null;

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token): bool {
        $token = $notification->token;

        return true;
    });

    return $token;
}

test('reset password link screen can be rendered', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Forgot Your Password?');
});

test('requesting a reset stores only a hashed token and sends the plain token in the reset URL', function () {
    Notification::fake();
    $user = User::factory()->create();

    $response = $this->from(route('password.request'))->post(route('password.email'), ['email' => $user->email]);

    $response
        ->assertRedirect(route('password.request', absolute: false))
        ->assertSessionHas('status');

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee(__('passwords.sent'));

    $token = issuedResetToken($user);
    $record = DB::table('password_reset_tokens')->where('email', $user->email)->first();

    expect($record)->not->toBeNull()
        ->and($record->token)->not->toBe($token)
        ->and(Hash::check($token, $record->token))->toBeTrue();

    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user, $token): bool {
        $message = $notification->toMail($user);
        $expectedUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        expect($message->markdown)->toBe('emails.auth.reset-password');
        expect($message->viewData['url'])->toBe($expectedUrl);

        return true;
    });
});

test('mail transport failures are reported and return a friendly error on the request form', function () {
    Exceptions::fake();
    Event::fake([PasswordResetLinkSent::class]);
    config(['app.debug' => true]);
    $user = User::factory()->create();
    $technicalError = 'Connection could not be established with host "smtp.private-example.test:587": Connection refused';
    $exception = new TransportException($technicalError);
    $transport = Mockery::mock(TransportInterface::class);
    $transport->shouldReceive('send')->once()->andThrow($exception);
    Mail::mailer()->setSymfonyTransport($transport);
    $friendlyError = 'Unable to connect at the moment. An error occurred while processing your request. Please try again later.';

    $this->post(route('password.email'), [
        'email' => $user->email,
        'unexpected_field' => 'do-not-flash',
    ])
        ->assertRedirect(route('password.request'))
        ->assertSessionHasErrors(['mail' => $friendlyError])
        ->assertSessionMissing('status')
        ->assertSessionHasInput('email', $user->email);

    expect(session()->getOldInput())->toBe(['email' => $user->email]);

    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee($friendlyError)
        ->assertSee('role="alert"', false)
        ->assertSee($user->email)
        ->assertDontSee('Connection could not be established with host')
        ->assertDontSee('smtp.private-example.test');

    Exceptions::assertReported(fn (TransportException $reported): bool => $reported === $exception);
    Event::assertNotDispatched(PasswordResetLinkSent::class);
    $this->assertGuest();
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
});

test('the reset form receives the token and email from the reset URL', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);
    $token = issuedResetToken($user);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('name="token"', false)
        ->assertSee('value="'.$token.'"', false)
        ->assertSee('type="hidden" name="email" value="'.$user->email.'"', false);
});

test('a valid reset completes once and shows confirmation before redirecting to login', function () {
    Event::fake([PasswordReset::class]);
    Notification::fake();
    $user = User::factory()->create();
    $oldRememberToken = $user->remember_token;

    $this->post(route('password.email'), ['email' => $user->email]);
    $token = issuedResetToken($user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('password.reset.success'))
        ->assertSessionHas('password_reset_success', true);

    $user->refresh();

    expect(Hash::check('NewPassword1!', $user->password))->toBeTrue()
        ->and($user->remember_token)->not->toBe($oldRememberToken);
    $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    Event::assertDispatched(PasswordReset::class, fn (PasswordReset $event): bool => $event->user->is($user));

    $this->get(route('password.reset.success'))
        ->assertOk()
        ->assertSee('Your password has been reset successfully. Redirecting you to login…')
        ->assertSee('role="status"', false)
        ->assertSee('<meta http-equiv="refresh" content="3;url='.route('login').'">', false)
        ->assertSee('href="'.route('login').'"', false)
        ->assertSee('Sign in now')
        ->assertDontSee('<form', false)
        ->assertDontSee('name="password"', false)
        ->assertDontSee($token)
        ->assertSessionMissing('password_reset_success');

    $this->get(route('password.reset.success'))->assertRedirect(route('login'));
    Event::assertDispatchedTimes(PasswordReset::class, 1);
    $this->assertGuest();
});

test('the completion page cannot show success without a completed reset', function () {
    $this->get(route('password.reset.success'))
        ->assertRedirect(route('login'))
        ->assertSessionMissing('password_reset_success');
});

test('reset validation failures display clear form errors without retaining passwords', function (array $overrides, string $field, string $message) {
    Notification::fake();
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create();
    $this->post(route('password.email'), ['email' => $user->email]);
    $token = issuedResetToken($user);
    $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
    $payload = array_replace([
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ], $overrides);

    $response = $this->from($resetUrl)->post(route('password.store'), $payload);
    $response->assertRedirect()
        ->assertSessionHasErrors([$field => $message])
        ->assertSessionMissing('password_reset_success')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation')
        ->assertSessionMissing('_old_input.token');

    $this->get($response->headers->get('Location'))
        ->assertOk()
        ->assertSee('role="alert"', false)
        ->assertSee($message)
        ->assertSee('href="'.route('password.request').'"', false)
        ->assertSee('name="password"', false)
        ->assertDontSee('value="NewPassword1!"', false)
        ->assertDontSee('http-equiv="refresh"', false);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    Event::assertNotDispatched(PasswordReset::class);
})->with([
    'missing token' => [['token' => null], 'token', 'This password reset link is invalid or has expired. Please request a new link.'],
    'missing email' => [['email' => null], 'email', 'This password reset link is invalid or has expired. Please request a new link.'],
    'invalid email' => [['email' => 'invalid-address'], 'email', 'This password reset link is invalid or has expired. Please request a new link.'],
    'malformed token' => [['token' => ['unexpected']], 'token', 'This password reset link is invalid or has expired. Please request a new link.'],
    'malformed email' => [['email' => ['unexpected']], 'email', 'This password reset link is invalid or has expired. Please request a new link.'],
    'missing password' => [['password' => ''], 'password', 'Enter your new password.'],
    'missing confirmation' => [['password_confirmation' => ''], 'password_confirmation', 'Confirm your new password.'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password', 'Use a password with at least 8 characters.'],
    'mismatched confirmation' => [['password_confirmation' => 'DifferentPassword1!'], 'password', 'The passwords do not match.'],
]);

test('an unexpected database failure returns a safe form error and rolls back the reset', function () {
    Exceptions::fake();
    Notification::fake();
    Event::fake([PasswordReset::class]);
    config(['app.debug' => true]);
    $user = User::factory()->create();
    $oldRememberToken = $user->remember_token;
    $this->post(route('password.email'), ['email' => $user->email]);
    $token = issuedResetToken($user);
    $exception = new RuntimeException('Token deletion failed on db.private-example.test');

    // Fail after the password update and token deletion have actually executed.
    DB::listen(function (QueryExecuted $query) use ($exception): void {
        if (str_contains(strtolower($query->sql), 'delete from')
            && str_contains($query->sql, 'password_reset_tokens')) {
            throw $exception;
        }
    });

    $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);
    $message = 'Unable to reset your password at the moment. Please try again later.';
    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertRedirect($resetUrl)
        ->assertSessionHasErrors(['reset' => $message])
        ->assertSessionMissing('password_reset_success')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    $this->get($resetUrl)
        ->assertOk()
        ->assertSee($message)
        ->assertSee('role="alert"', false)
        ->assertDontSee($exception->getMessage())
        ->assertDontSee('db.private-example.test');

    $user->refresh();
    expect(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->remember_token)->toBe($oldRememberToken);
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    Exceptions::assertReported(fn (RuntimeException $reported): bool => $reported === $exception);
});

test('an invalid reset token cannot change the password', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);
    issuedResetToken($user);

    $this->from(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
        ->post(route('password.store'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertSessionHasErrors(['reset' => 'This password reset link is invalid or has expired. Please request a new link.']);

    $this->get(route('password.reset', ['token' => 'invalid-token', 'email' => $user->email]))
        ->assertOk()
        ->assertSee('role="alert"', false)
        ->assertSee('This password reset link is invalid or has expired. Please request a new link.');

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
});

test('an expired reset token cannot change the password or fire the reset event', function () {
    Event::fake([PasswordReset::class]);
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email]);
    $token = issuedResetToken($user);

    DB::table('password_reset_tokens')
        ->where('email', $user->email)
        ->update(['created_at' => now()->subMinutes(config('auth.passwords.users.expire') + 1)]);

    $this->from(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->post(route('password.store'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'NewPassword1!',
            'password_confirmation' => 'NewPassword1!',
        ])
        ->assertSessionHasErrors(['reset' => 'This password reset link is invalid or has expired. Please request a new link.']);

    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertSee('This password reset link is invalid or has expired. Please request a new link.')
        ->assertSee('href="'.route('password.request').'"', false);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    Event::assertNotDispatched(PasswordReset::class);
});

test('a reset token cannot reset a different or unknown account', function (bool $existingAccount) {
    Notification::fake();
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create();
    $otherUser = $existingAccount ? User::factory()->create() : null;
    $email = $otherUser?->email ?? 'unknown@example.test';
    $this->post(route('password.email'), ['email' => $user->email]);
    $token = issuedResetToken($user);
    $resetUrl = route('password.reset', ['token' => $token, 'email' => $email]);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])
        ->assertRedirect($resetUrl)
        ->assertSessionHasErrors(['reset' => 'This password reset link is invalid or has expired. Please request a new link.'])
        ->assertSessionMissing('password_reset_success');

    $this->get($resetUrl)
        ->assertOk()
        ->assertSee('This password reset link is invalid or has expired. Please request a new link.')
        ->assertSee('role="alert"', false);

    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    if ($otherUser) {
        expect(Hash::check('password', $otherUser->fresh()->password))->toBeTrue();
    }
    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
    Event::assertNotDispatched(PasswordReset::class);
})->with(['different existing account' => true, 'unknown account' => false]);

test('a used reset token cannot reset the password again', function () {
    Notification::fake();
    Event::fake([PasswordReset::class]);
    $user = User::factory()->create();
    $this->post(route('password.email'), ['email' => $user->email]);
    $token = issuedResetToken($user);
    $resetUrl = route('password.reset', ['token' => $token, 'email' => $user->email]);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertRedirect(route('password.reset.success'));
    $this->get(route('password.reset.success'))->assertOk();

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'AnotherPassword1!',
        'password_confirmation' => 'AnotherPassword1!',
    ])
        ->assertRedirect($resetUrl)
        ->assertSessionHasErrors(['reset' => 'This password reset link is invalid or has expired. Please request a new link.'])
        ->assertSessionMissing('password_reset_success');

    $this->get($resetUrl)
        ->assertOk()
        ->assertSee('This password reset link is invalid or has expired. Please request a new link.')
        ->assertDontSee('Your password has been reset successfully.');

    expect(Hash::check('NewPassword1!', $user->fresh()->password))->toBeTrue();
    Event::assertDispatchedTimes(PasswordReset::class, 1);
});

test('resetting a password does not bypass email verification', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->post(route('password.email'), ['email' => $user->email]);
    $token = issuedResetToken($user);

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPassword1!',
        'password_confirmation' => 'NewPassword1!',
    ])->assertRedirect(route('password.reset.success'));

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'NewPassword1!',
    ])->assertRedirect(route('verification.notice', absolute: false));
});
