<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('lets admins create, update, activate, suspend, and soft delete users', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'first_name' => 'Acme',
            'last_name' => 'Hiring',
            'username' => 'acme-hiring',
            'email' => 'acme@example.com',
            'password' => 'Password1!',
            'password_confirmation' => 'Password1!',
            'role' => UserRole::Employer->value,
            'status' => UserStatus::Active->value,
        ])
        ->assertRedirect();

    $user = User::where('email', 'acme@example.com')->firstOrFail();

    expect($user->role)->toBe(UserRole::Employer)
        ->and($user->status)->toBe(UserStatus::Active);

    $this->actingAs($admin)
        ->get(route('Employers'))
        ->assertOk()
        ->assertSee('acme@example.com');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $user), [
            'first_name' => 'Jane',
            'last_name' => 'Applicant',
            'username' => 'jane-applicant',
            'email' => 'jane@example.com',
            'role' => UserRole::Applicant->value,
            'status' => UserStatus::Suspended->value,
        ])
        ->assertRedirect();

    expect($user->fresh())
        ->role->toBe(UserRole::Applicant)
        ->status->toBe(UserStatus::Suspended)
        ->suspended_at->not->toBeNull();

    $this->actingAs($admin)
        ->patch(route('admin.users.activate', $user))
        ->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::Active);

    $this->actingAs($admin)
        ->patch(route('admin.users.suspend', $user))
        ->assertRedirect();

    expect($user->fresh()->status)->toBe(UserStatus::Suspended);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $user))
        ->assertRedirect();

    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);
});

it('sends password reset links from admin user management', function () {
    Notification::fake();

    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.users.password-reset', $user))
        ->assertRedirect()
        ->assertSessionHas('success');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('protects admins from modifying or deleting themselves', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.suspend', $admin))
        ->assertSessionHasErrors('user');

    expect($admin->fresh()->status)->toBe(UserStatus::Active);

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors('user');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'deleted_at' => null,
    ]);
});

it('searches canonical locations and preserves historical location search', function () {
    $admin = User::factory()->admin()->create();
    $canonical = User::factory()->applicant()->create(['country_code' => 'CA', 'country' => 'Canada', 'state' => 'Ontario', 'city' => 'Toronto']);
    $legacy = User::factory()->applicant()->create(['nationality' => 'Nigerian', 'state_of_origin' => 'Lagos', 'local_government_area' => 'Historical LGA']);

    $this->actingAs($admin)->get(route('applicants', ['search' => 'Canada Ontario Toronto']))
        ->assertOk()->assertViewHas('users', fn ($users): bool => $users->count() === 1 && $users->first()->id === $canonical->id
            && $users->first()->city === 'Toronto');
    $this->actingAs($admin)->get(route('applicants', ['search' => 'Historical LGA']))
        ->assertOk()->assertViewHas('users', fn ($users): bool => $users->count() === 1 && $users->first()->id === $legacy->id
            && $users->first()->local_government_area === 'Historical LGA');
});
