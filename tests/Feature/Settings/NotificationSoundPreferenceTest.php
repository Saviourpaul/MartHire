<?php

use App\Models\SettingsAuditEvent;
use App\Models\User;

test('settings render the account-backed notification sound switch', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('In-app notification sound')
        ->assertSee('Play a sound for new notifications')
        ->assertSee('role="switch"', false)
        ->assertSee('aria-checked="false"', false)
        ->assertSee('data-notification-sound-update-url', false);

    expect($user->fresh()->userPreference)->toBeNull();
});

test('a user can save their own notification sound preference and an audit record is written', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patchJson(route('settings.notifications.sound.update'), ['enabled' => true])
        ->assertOk()
        ->assertJsonPath('enabled', true);

    expect($user->fresh()->userPreference->notification_sound_enabled)->toBeTrue();
    expect(SettingsAuditEvent::query()->where('user_id', $user->id)
        ->where('action', 'notification_sound_updated')->sole()->changes)
        ->toBe(['before' => null, 'after' => true]);

    $this->get(route('settings.edit'))
        ->assertOk()
        ->assertSee('aria-checked="true"', false)
        ->assertSee('data-notification-sound-enabled="true"', false);

    $this->patchJson(route('settings.notifications.sound.update'), ['enabled' => false])
        ->assertOk()
        ->assertJsonPath('enabled', false);

    expect($user->fresh()->userPreference->notification_sound_enabled)->toBeFalse();
    expect(SettingsAuditEvent::query()->where('user_id', $user->id)
        ->where('action', 'notification_sound_updated')->count())->toBe(2);
});

test('notification sound preference updates reject invalid values and user targeting fields', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $this->actingAs($user)->patchJson(route('settings.notifications.sound.update'), ['enabled' => 'not-a-boolean'])
        ->assertUnprocessable();
    $this->patchJson(route('settings.notifications.sound.update'), ['enabled' => true, 'user_id' => $otherUser->id])
        ->assertUnprocessable();

    expect($user->fresh()->userPreference)->toBeNull();
    expect($otherUser->fresh()->userPreference)->toBeNull();
});
