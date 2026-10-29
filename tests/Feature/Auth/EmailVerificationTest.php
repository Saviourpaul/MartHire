<?php

use App\Models\User;
use App\Notifications\VerifyEmailCode;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

test('email verification screen can be rendered', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertStatus(200);
});

test('email can be verified with the code from the notification', function () {
    Notification::fake();
    Event::fake([Verified::class]);

    $user = User::factory()->unverified()->create();

    app(App\Services\EmailVerificationService::class)->sendCode($user);

    Notification::assertSentTo($user, VerifyEmailCode::class, function (VerifyEmailCode $notification) use ($user) {
        $response = $this->actingAs($user)->post('/verify-email', [
            'code' => $notification->code,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        return true;
    });

    Event::assertDispatched(Verified::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('email is not verified with an incorrect code', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    app(App\Services\EmailVerificationService::class)->sendCode($user);

    $this->actingAs($user)->post('/verify-email', ['code' => '000000'])
        ->assertSessionHasErrors('code');

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
