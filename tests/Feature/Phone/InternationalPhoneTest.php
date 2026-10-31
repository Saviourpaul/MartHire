<?php

use App\Models\User;
use App\Services\PhoneNumberService;

function phoneProfilePayload(User $user, array $overrides = []): array
{
    return array_replace([
        'first_name' => 'Phone', 'last_name' => 'Applicant', 'email' => $user->email,
        'date_of_birth' => '1995-05-05', 'address' => '10 Example Street',
        'country_code' => 'NG', 'country' => 'Nigeria', 'state' => 'Lagos', 'city' => 'Ikeja',
        'phone' => '(416) 555-0123', 'phone_country' => 'CA',
    ], $overrides);
}

test('profile phone parts are authoritative and independent from address country', function () {
    $user = User::factory()->create(['profile_image_path' => 'profile-images/existing.jpg']);
    $this->actingAs($user)->patch(route('profile.update'), phoneProfilePayload($user, [
        'phone_dial_code' => '+999', 'phone_national_number' => 'fake',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->phone)->toBe('+14165550123')->and($user->phone_country)->toBe('CA')
        ->and($user->phone_dial_code)->toBe('+1')->and($user->phone_national_number)->toBe('4165550123')
        ->and($user->country_code)->toBe('NG');
});

test('invalid phone numbers are rejected without changing the saved profile', function () {
    $user = User::factory()->create(['profile_image_path' => 'profile-images/existing.jpg', 'phone' => '+442079460018']);
    $this->actingAs($user)->patch(route('profile.update'), phoneProfilePayload($user, ['phone' => '123']))->assertSessionHasErrors('phone');
    expect($user->fresh()->phone)->toBe('+442079460018');
});

test('legacy phone values and metadata are preserved when an optional phone is not submitted', function () {
    $user = User::factory()->organization()->create(['phone' => 'legacy raw phone', 'phone_country' => 'GB', 'phone_dial_code' => '+44', 'phone_national_number' => '2079460018']);
    $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => 'Other', 'last_name' => 'Name', 'email' => $user->email,
        'phone_country' => 'US', 'phone_dial_code' => '+999', 'phone_national_number' => 'spoof',
    ])->assertSessionHasNoErrors();
    expect($user->fresh()->phone)->toBe('legacy raw phone')->and($user->fresh()->phone_country)->toBe('GB');
});

test('every selectable phone region has a local SVG and the profile uses the shared picker', function () {
    $countries = app(PhoneNumberService::class)->countries();
    expect(count($countries))->toBeGreaterThan(240);
    foreach ($countries as $country) {
        expect($country['flag_url'])->not->toBeNull();
        expect(is_file(public_path('asset/marthire/flags/'.strtolower($country['code']).'.svg')))->toBeTrue();
    }
    $this->actingAs(User::factory()->create())->get(route('profile.edit'))->assertOk()
        ->assertSee('internationalPhone', false)->assertSee('Search country or code…')->assertSee('data-phone-input', false);
});
