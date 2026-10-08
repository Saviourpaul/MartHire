<?php

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Database\Seeders\NigeriaLocationSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(NigeriaLocationSeeder::class);
});

function fakeProfileImage(): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'avatar.png',
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=')
    );
}

function validApplicantProfilePayload(array $overrides = []): array
{
    return array_merge([
        'first_name' => 'Test',
        'last_name' => 'User',
        'username' => 'test-user',
        'email' => 'test@example.com',
        'date_of_birth' => '1995-05-15',
        'profile_image' => fakeProfileImage(),
        'phone' => '+2348012345678',
        'address' => '12 Market Road',
        'nationality' => 'Nigeria',
        'state_of_origin' => 'Lagos',
        'local_government_area' => 'Ikeja',
        'zipcode' => '100001',
    ], $overrides);
}

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile edit page starts in read only mode with edit controls', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Update Profile')
        ->assertSee('Save Changes')
        ->assertSee('Cancel')
        ->assertSee('id="profile-actions" class="d-none mt-3"', false);
});

test('applicant profile information can be updated with required setup fields', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $email = $user->email;
    $verifiedAt = $user->email_verified_at;

    $response = $this
        ->actingAs($user)
        ->patch('/profile', validApplicantProfilePayload([
            'email' => $email,
        ]));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test', $user->first_name);
    $this->assertSame('User', $user->last_name);
    $this->assertSame('test-user', $user->username);
    $this->assertSame($email, $user->email);
    $this->assertSame('1995-05-15', $user->date_of_birth->format('Y-m-d'));
    $this->assertSame('+2348012345678', $user->phone);
    $this->assertSame('Nigeria', $user->nationality);
    $this->assertSame('Lagos', $user->state_of_origin);
    $this->assertSame('Ikeja', $user->local_government_area);
    $this->assertEquals($verifiedAt, $user->email_verified_at);
    Storage::disk('public')->assertExists($user->profile_image_path);
    $this->assertStringContainsString('storage/'.$user->profile_image_path, $user->profileImageUrl());
});

test('uploaded profile image displays on the profile edit page', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/profile', validApplicantProfilePayload([
            'email' => $user->email,
        ]))
        ->assertRedirect('/profile');

    $user->refresh();

    $this
        ->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('storage/'.$user->profile_image_path, false);
});

test('profile image updates replace the previous stored image', function () {
    Storage::fake('public');

    $user = User::factory()->completeApplicantProfile()->create([
        'profile_image_path' => 'profile-images/old.png',
    ]);
    Storage::disk('public')->put('profile-images/old.png', 'old-image');

    $this
        ->actingAs($user)
        ->patch('/profile', validApplicantProfilePayload([
            'email' => $user->email,
        ]))
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertNotSame('profile-images/old.png', $user->profile_image_path);
    Storage::disk('public')->assertMissing('profile-images/old.png');
    Storage::disk('public')->assertExists($user->profile_image_path);
    $this->assertStringContainsString('storage/'.$user->profile_image_path, $user->profileImageUrl());
});

test('applicants must provide setup fields before saving profile changes', function () {
    $user = User::factory()->create();

    $this
        ->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'username' => 'test-user',
            'email' => $user->email,
        ])
        ->assertSessionHasErrors([
            'profile_image',
            'date_of_birth',
            'phone',
            'address',
            'nationality',
            'state_of_origin',
            'local_government_area',
            'zipcode',
        ]);
});

test('employers and admins can update profile without applicant setup fields', function () {
    foreach ([User::factory()->employer()->create(), User::factory()->admin()->create()] as $user) {
        $this
            ->actingAs($user)
            ->patch('/profile', [
                'first_name' => 'Test',
                'last_name' => 'User',
                'username' => 'test-user-'.$user->id,
                'email' => $user->email,
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');
    }
});

test('profile email address cannot be changed', function () {
    $user = User::factory()->completeApplicantProfile()->create([
        'email' => 'original@example.com',
    ]);

    $this
        ->actingAs($user)
        ->patch('/profile', validApplicantProfilePayload([
            'email' => 'changed@example.com',
            'profile_image' => null,
        ]))
        ->assertSessionHasErrors('email');

    $this->assertSame('original@example.com', $user->fresh()->email);
});

test('email timestamp is unchanged when the email address is unchanged', function () {
    $user = User::factory()->completeApplicantProfile()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', validApplicantProfilePayload([
            'email' => $user->email,
            'profile_image' => null,
        ]));

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
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
    $this->assertSoftDeleted('users', [
        'id' => $user->id,
    ]);
});

test('correct password must be provided to delete account', function () {
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

    $this->assertNotNull($user->fresh());
});

test('admin user management does not edit applicant profile setup fields', function () {
    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->completeApplicantProfile()->create([
        'phone' => '+2348000000000',
    ]);

    $this
        ->actingAs($admin)
        ->put(route('admin.users.update', $applicant), [
            'first_name' => 'Managed',
            'last_name' => 'Applicant',
            'username' => 'managed-applicant',
            'email' => 'managed@example.com',
            'role' => UserRole::Applicant->value,
            'status' => UserStatus::Active->value,
            'phone' => '+2348999999999',
        ])
        ->assertRedirect();

    expect($applicant->fresh())
        ->first_name->toBe('Managed')
        ->phone->toBe('+2348000000000');
});
