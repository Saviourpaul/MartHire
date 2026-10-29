<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;

test('only admins can view the user management pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get(route('admin.users.organizations'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.applicants'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.administrators'))->assertOk();
    $this->actingAs($admin)->get(route('admin.users.suspended'))->assertOk();

    $this->actingAs(User::factory()->organization()->create())
        ->get(route('admin.users.organizations'))->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.organizations'))->assertForbidden();
});

test('each category only lists users of that category', function () {
    $admin = User::factory()->admin()->create();
    $organization = User::factory()->organization()->create();
    $applicant = User::factory()->create();
    $suspended = User::factory()->create(['status' => UserStatus::Suspended]);

    $this->actingAs($admin)->get(route('admin.users.organizations'))
        ->assertSee($organization->email)->assertDontSee($applicant->email);

    $this->actingAs($admin)->get(route('admin.users.applicants'))
        ->assertSee($applicant->email)->assertDontSee($organization->email);

    $this->actingAs($admin)->get(route('admin.users.suspended'))
        ->assertSee($suspended->email)->assertDontSee($applicant->email);
});

test('search filters the user list', function () {
    $admin = User::factory()->admin()->create();
    $match = User::factory()->create(['first_name' => 'Zoranna']);
    $other = User::factory()->create(['first_name' => 'Regular']);

    $this->actingAs($admin)
        ->get(route('admin.users.applicants', ['search' => 'Zoranna']))
        ->assertSee($match->email)
        ->assertDontSee($other->email);
});

test('admin can create a new user', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.users.store'), [
        'first_name' => 'New',
        'last_name' => 'Person',
        'email' => 'new.person@example.com',
        'password' => 'Vx9#Lp2qZmT8oWr$',
        'password_confirmation' => 'Vx9#Lp2qZmT8oWr$',
        'role' => UserRole::Applicant->value,
        'status' => UserStatus::Active->value,
    ])->assertSessionHasNoErrors()->assertRedirect();

    $this->assertDatabaseHas('users', ['email' => 'new.person@example.com', 'role' => 'applicant']);
});

test('admin can change a user\'s role and status', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $target), [
        'role' => UserRole::Organization->value,
        'status' => UserStatus::Active->value,
    ])->assertSessionHasNoErrors()->assertRedirect();

    expect($target->fresh()->role)->toBe(UserRole::Organization);
});

test('admin can suspend and reactivate a user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->post(route('admin.users.suspend', $target))->assertRedirect();
    expect($target->fresh()->status)->toBe(UserStatus::Suspended);

    $this->actingAs($admin)->post(route('admin.users.activate', $target))->assertRedirect();
    expect($target->fresh()->status)->toBe(UserStatus::Active);
});

test('admin can delete a user', function () {
    $admin = User::factory()->admin()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)->delete(route('admin.users.destroy', $target))->assertRedirect();

    expect(User::withTrashed()->find($target->id)?->trashed() ?? true)->toBeTrue();
});

test('the last active admin cannot be demoted or suspended', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->patch(route('admin.users.update', $admin), [
        'role' => UserRole::Applicant->value,
        'status' => UserStatus::Active->value,
    ])->assertSessionHasErrors();

    expect($admin->fresh()->role)->toBe(UserRole::Admin);
});

test('admin can export each user category as csv', function () {
    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->create(['first_name' => 'Exported', 'last_name' => 'Applicant']);

    $response = $this->actingAs($admin)->get(route('admin.users.applicants.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();
    $this->assertStringContainsString('Name,Email,Phone,Role,Status,Joined', $csv);
    $this->assertStringContainsString('Exported Applicant', $csv);
    $this->assertStringContainsString($applicant->email, $csv);
});

test('organization and applicant users cannot export the admin user list', function () {
    $this->actingAs(User::factory()->organization()->create())
        ->get(route('admin.users.applicants.export'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.applicants.export'))
        ->assertForbidden();
});
