<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'date_of_birth' => null,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::Applicant,
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'suspended_at' => null,
            'last_login_at' => null,
            'profile_image_path' => null,
            'phone' => null,
            'address' => null,
            'nationality' => null,
            'state_of_origin' => null,
            'local_government_area' => null,
            'remember_token' => Str::random(10),
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Admin,
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'suspended_at' => null,
        ]);
    }

    public function employer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Employer,
            'status' => UserStatus::Active,
            'approved_at' => now(),
            'suspended_at' => null,
        ]);
    }

    public function applicant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::Applicant,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserStatus::Suspended,
            'suspended_at' => now(),
        ]);
    }

    public function completeApplicantProfile(): static
    {
        return $this->applicant()->state(fn (array $attributes) => [
            'profile_image_path' => 'profile-images/avatar.jpg',
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'nationality' => 'Nigeria',
            'state_of_origin' => 'Lagos',
            'local_government_area' => 'Ikeja',
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
