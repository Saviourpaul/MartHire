<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Password;

test('live admin filtering returns only the authorized table workspace with matching modals', function () {
    $admin = User::factory()->admin()->create();
    $match = User::factory()->create(['first_name' => 'Zoranna', 'email' => 'match@example.test']);
    $other = User::factory()->create(['first_name' => 'Other', 'email' => 'other@example.test']);
    $response = $this->actingAs($admin)->withHeader('X-Live-Tables', 'admin-users')
        ->getJson(route('admin.users.applicants', ['search' => 'Zoranna']));

    $response->assertOk()->assertJsonStructure(['regions' => ['admin-users'], 'url', 'title']);
    $html = $response->json('regions.admin-users');
    expect($html)->toContain($match->email, 'manage-user-'.$match->id, 'data-live-filter')
        ->not->toContain($other->email, 'manage-user-'.$other->id, '<!DOCTYPE html>', 'app-sidebar');
    expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
});

test('live pagination recovers a removed last page and sorting is stable', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->count(11)->create(['created_at' => now()->subDay()]);
    $response = $this->actingAs($admin)->withHeader('X-Live-Tables', 'admin-users')
        ->getJson(route('admin.users.applicants', ['page' => 99, 'sort' => 'first_name', 'direction' => 'asc']));

    $response->assertOk();
    expect($response->json('url'))->toContain('page=2', 'sort=first_name', 'direction=asc');
    expect(substr_count($response->json('regions.admin-users'), 'class="nw-table__main"'))->toBe(1);
});

test('admin user actions return synchronized JSON without replaying a flash message', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($admin)->withHeader('X-Live-Action', '1')->postJson(route('admin.users.suspend', $target))
        ->assertOk()->assertJsonPath('message', 'User suspended successfully.')
        ->assertJsonPath('topics.0', 'users')->assertSessionMissing('success');
    expect($target->fresh()->status)->toBe(UserStatus::Suspended);

    $this->withHeader('X-Live-Tables', 'admin-users')->getJson(route('admin.users.suspended'))
        ->assertOk()->assertJsonPath('regions.admin-users', fn (string $html): bool => str_contains($html, $target->email));
});

test('changing a role immediately removes the account from its old filtered category', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($admin)->withHeader('X-Live-Action', '1')->patchJson(route('admin.users.update', $target), [
        'role' => UserRole::Organization->value, 'status' => UserStatus::Active->value,
    ])->assertOk();
    $response = $this->withHeader('X-Live-Tables', 'admin-users')->getJson(route('admin.users.applicants'));
    $response->assertOk();
    expect($response->json('regions.admin-users'))->not->toContain($target->email);
});

test('live invalid edits preserve backend validation and account safeguards', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->withHeader('X-Live-Action', '1')->patchJson(route('admin.users.update', $admin), [
        'role' => 'invalid', 'status' => 'active',
    ])->assertUnprocessable()->assertJsonValidationErrors('role');
    $this->patchJson(route('admin.users.update', $admin), [
        'role' => 'applicant', 'status' => 'suspended',
    ])->assertUnprocessable();
    expect($admin->fresh()->role)->toBe(UserRole::Admin)->and($admin->fresh()->status)->toBe(UserStatus::Active);
});

test('live password-reset failures use validation JSON instead of a successful refresh', function () {
    Password::shouldReceive('sendResetLink')->once()->andReturn(Password::INVALID_USER);
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();
    $this->actingAs($admin)->withHeader('X-Live-Action', '1')->postJson(route('admin.users.password-reset', $target))
        ->assertUnprocessable()->assertJsonValidationErrors('user');
});

test('live read and action headers cannot bypass admin authorization', function () {
    $applicant = User::factory()->create();
    $target = User::factory()->create();
    $this->actingAs($applicant)->withHeader('X-Live-Tables', 'admin-users')
        ->getJson(route('admin.users.applicants'))->assertForbidden();
    $this->withHeader('X-Live-Action', '1')->postJson(route('admin.users.suspend', $target))->assertForbidden();
    expect($target->fresh()->status)->toBe(UserStatus::Active);
});

test('invalid live filter shapes return validation feedback rather than a server error', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->withHeader('X-Live-Tables', 'admin-users')
        ->getJson(route('admin.users.applicants', ['search' => ['unexpected']]))
        ->assertUnprocessable()->assertJsonValidationErrors('search');
});
