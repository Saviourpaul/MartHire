<?php

use App\Models\User;

test('applicant dashboard shows incomplete profile tracker', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Profile Completion')
        ->assertSee('0%')
        ->assertSee('Date of birth')
        ->assertSee('Phone number');
});

test('applicant dashboard shows complete profile tracker', function () {
    $user = User::factory()->completeApplicantProfile()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Profile Completion')
        ->assertSee('100%')
        ->assertSee('Complete');
});

test('applicant dashboard sidebar includes applicant section links', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('My Profile')
        ->assertSee('Documents')
        ->assertSee('Jobs')
        ->assertSee('Notifications');
});

test('applicant section pages render', function () {
    $user = User::factory()->create();
    $sections = [
        ['client.profile', 'My Profile'],
        ['client.documents', 'Documents'],
        ['client.jobs', 'Jobs'],
        ['client.notifications', 'Notifications'],
    ];

    foreach ($sections as $section) {
        $this->actingAs($user)
            ->get(route($section[0]))
            ->assertOk()
            ->assertSee($section[1]);
    }
});

test('user model calculates applicant profile completion', function () {
    $user = User::factory()->create([
        'phone' => '+2348012345678',
        'address' => '12 Market Road',
    ]);

    expect($user->missingApplicantProfileFields())->toHaveKeys([
        'profile_image_path',
        'date_of_birth',
        'nationality',
        'state_of_origin',
        'local_government_area',
        'zipcode',
    ])
        ->and($user->applicantProfileCompletionPercentage())->toBe(25)
        ->and($user->hasCompletedApplicantProfile())->toBeFalse()
        ->and($user->profileImageUrl())->toContain('Avatar.png');

    $completeUser = User::factory()->completeApplicantProfile()->make();

    expect($completeUser->applicantProfileCompletionPercentage())->toBe(100)
        ->and($completeUser->hasCompletedApplicantProfile())->toBeTrue();
});
