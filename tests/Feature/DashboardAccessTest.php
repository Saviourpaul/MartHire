<?php

use App\Models\User;

test('the generic dashboard route redirects each role to its own dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('admin.dashboard'));

    $this->actingAs(User::factory()->organization()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('organisations.dashboard'));

    $this->actingAs(User::factory()->create())
        ->get(route('dashboard'))
        ->assertRedirect(route('client.dashboard'));
});

test('admin can view the admin dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.dashboard'))
        ->assertOk();
});

test('organization member can view the organisations dashboard', function () {
    $this->actingAs(User::factory()->organization()->create())
        ->get(route('organisations.dashboard'))
        ->assertOk();
});

test('applicant can view the client dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('client.dashboard'))
        ->assertOk();
});

test('organization and applicant users are forbidden from the admin dashboard', function () {
    $this->actingAs(User::factory()->organization()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('admin and applicant users are forbidden from the organisations dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('organisations.dashboard'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('organisations.dashboard'))
        ->assertForbidden();
});

test('admin and organization users are forbidden from the client dashboard', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('client.dashboard'))
        ->assertForbidden();

    $this->actingAs(User::factory()->organization()->create())
        ->get(route('client.dashboard'))
        ->assertForbidden();
});

test('guests are redirected to login from any dashboard', function () {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->get(route('organisations.dashboard'))->assertRedirect(route('login'));
    $this->get(route('client.dashboard'))->assertRedirect(route('login'));
});
