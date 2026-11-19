<?php

use App\Models\User;
use App\Models\WorkflowNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('profile password panel offers an email reset without manual password fields', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('password reset link')
        ->assertSee(route('password.reset-link.authenticated'), false)
        ->assertDontSee('name="current_password"', false)
        ->assertDontSee('name="password_confirmation"', false);
});

test('authenticated user receives reset link at their registered email only', function () {
    Notification::fake();

    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)
        ->from('/profile')
        ->post(route('password.reset-link.authenticated'), ['email' => $otherUser->email])
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'password-reset-link-sent');

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertNotSentTo($otherUser, ResetPassword::class);
    $this->assertTrue(Hash::check('password', $user->refresh()->password));
});

test('authenticated user can complete the emailed reset link without signing out first', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('password.reset-link.authenticated'))
        ->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user): bool {
        $this->actingAs($user)
            ->get(route('password.reset', [
                'token' => $notification->token,
                'email' => $user->email,
            ]))
            ->assertOk();

        $this->actingAs($user)
            ->post(route('password.store'), [
                'token' => $notification->token,
                'email' => $user->email,
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertOk()
            ->assertSee('Your password has been updated.');

        $notice = WorkflowNotification::query()
            ->where('notifiable_id', $user->id)
            ->where('event_key', 'like', 'password-reset-completed:%')
            ->firstOrFail();

        expect($notice->data)
            ->toMatchArray([
                'title' => 'Password reset completed',
                'message' => 'Your account password was reset successfully.',
            ]);

        return true;
    });

    $this->assertTrue(Hash::check('new-secure-password', $user->refresh()->password));

    $reauthenticatedUser = $user->fresh();
    $this->actingAs($reauthenticatedUser)
        ->withSession(['auth.version' => (int) $reauthenticatedUser->authentication_version])
        ->get(route('client.dashboard'))
        ->assertOk()
        ->assertSee('Password reset completed')
        ->assertSee('Your account password was reset successfully.');
});

test('the former dashboard password update endpoint cannot change a password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/password', [
        'current_password' => 'password',
        'password' => 'new-secure-password',
        'password_confirmation' => 'new-secure-password',
    ])->assertNotFound();

    $this->assertTrue(Hash::check('password', $user->refresh()->password));
});

test('unauthenticated users cannot use the dashboard reset-link action', function () {
    $this->post(route('password.reset-link.authenticated'))
        ->assertRedirect(route('login'));
});
