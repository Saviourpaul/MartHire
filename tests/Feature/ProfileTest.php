<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('an applicant can update their profile information', function () {
    $user = User::factory()->create([
        'profile_image_path' => 'profile-images/existing.jpg',
    ]);

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $user->email,
            'phone' => '08012345678',
            'address' => '1 Test Street',
            'date_of_birth' => '1995-05-05',
            'country_code' => 'NG',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Ikeja',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Updated', $user->first_name);
    $this->assertSame('Name', $user->last_name);
    $this->assertSame('+2348012345678', $user->phone);
    $this->assertNotNull($user->location_confirmed_at);
});

test('the email address cannot be changed from the profile form', function () {
    $user = User::factory()->create([
        'profile_image_path' => 'profile-images/existing.jpg',
    ]);

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => 'someone-else@example.com',
            'phone' => $user->phone ?? '08012345678',
            'address' => $user->address ?? '1 Test Street',
            'date_of_birth' => '1995-05-05',
            'country_code' => 'NG',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Ikeja',
        ])
        ->assertSessionHasErrors('email');

    $this->assertSame($user->email, $user->fresh()->email);
});

test('an applicant must provide a profile image, date of birth, phone, address and location', function () {
    $user = User::factory()->create(['profile_image_path' => null]);

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
        ])
        ->assertSessionHasErrors(['profile_image', 'date_of_birth', 'phone', 'address', 'country_code', 'state', 'city']);
});

test('a non-applicant is not required to fill optional profile fields', function () {
    $organizationUser = User::factory()->organization()->create();

    $this->actingAs($organizationUser)
        ->patch('/profile', [
            'first_name' => $organizationUser->first_name,
            'last_name' => $organizationUser->last_name,
            'email' => $organizationUser->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');
});

test('a user can upload a new profile photo', function () {
    Storage::fake('public');
    $user = User::factory()->create(['profile_image_path' => null]);

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => '08012345678',
            'address' => '1 Test Street',
            'date_of_birth' => '1995-05-05',
            'country_code' => 'NG',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Ikeja',
            'profile_image' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $path = $user->fresh()->profile_image_path;
    $this->assertNotNull($path);
    Storage::disk('public')->assertExists($path);
});

test('email verification status is unchanged when profile information is updated', function () {
    $user = User::factory()->create(['profile_image_path' => 'profile-images/existing.jpg']);

    $this->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Changed',
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => '08012345678',
            'address' => '1 Test Street',
            'date_of_birth' => '1995-05-05',
            'country_code' => 'NG',
            'country' => 'Nigeria',
            'state' => 'Lagos',
            'city' => 'Ikeja',
        ])
        ->assertSessionHasNoErrors();

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can close their account with the correct password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertTrue(User::withTrashed()->findOrFail($user->id)->trashed());
});

test('correct password must be provided to close the account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertFalse($user->fresh()->trashed());
});
