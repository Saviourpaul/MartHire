<?php

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

it('rejects expired reset links as soon as they are opened and at submission', function (bool $debug) {
    config(['app.debug' => $debug]);
    $user = User::factory()->create();
    $token = Password::createToken($user);
    DB::table('password_reset_tokens')->where('email', $user->email)
        ->update(['created_at' => now()->subMinutes(config('auth.passwords.users.expire') + 1)]);
    Event::fake([PasswordReset::class]);
    $url = route('password.reset', ['token' => $token, 'email' => $user->email]);
    $this->get($url)->assertGone()->assertSee('Password reset link unavailable')
        ->assertSee('Password reset links are valid for 60 minutes')
        ->assertSee('href="'.route('password.request').'"', false)
        ->assertDontSee('name="password"', false)->assertDontSee($token)->assertDontSee($user->email);
    $this->postJson(route('password.store'), [
        'token' => $token, 'email' => $user->email, 'password' => 'NewPassword123!', 'password_confirmation' => 'NewPassword123!',
    ])->assertGone()->assertJsonPath('code', 'password_reset')->assertJsonMissingPath('trace');
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
    Event::assertNotDispatched(PasswordReset::class);
})->with([true, false]);

it('treats replaced used and unknown reset tokens consistently without exposing accounts', function (string $state) {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $email = $user->email;

    if ($state === 'replaced') {
        Password::createToken($user);
    } elseif ($state === 'used') {
        Password::deleteToken($user);
    } else {
        $email = 'unknown@example.test';
    }

    $this->getJson(route('password.reset', ['token' => $token, 'email' => $email]))->assertGone()
        ->assertExactJson([
            'message' => __('expiration.password_reset_message'),
            'code' => 'password_reset',
            'recovery_url' => route('password.request'),
            'recovery_label' => __('expiration.reset_action'),
        ]);
    expect(Hash::check('password', $user->fresh()->password))->toBeTrue();
})->with(['replaced', 'used', 'unknown']);

it('keeps live reset links and ordinary password validation working', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);
    $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))->assertOk()
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Cache-Control', 'no-store, private')
        ->assertSee('name="password"', false);
    $this->postJson(route('password.store'), [
        'token' => $token, 'email' => $user->email, 'password' => 'short', 'password_confirmation' => 'short',
    ])->assertUnprocessable()->assertJsonValidationErrors('password');
    expect(Password::tokenExists($user, $token))->toBeTrue();
});
