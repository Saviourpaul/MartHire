<?php

namespace Database\Factories;

use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ApplicationForm>
 */
class ApplicationFormFactory extends Factory
{
    protected $model = ApplicationForm::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'user_id' => User::factory()->applicant(),
            'reference' => 'APP-'.now()->format('Ymd').'-'.Str::upper(fake()->unique()->bothify('??####')),
            'status' => CandidatePipelineStage::Submitted,
            'submitted_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'nationality' => 'Nigeria',
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-18 years')->format('Y-m-d'),
            'gender' => fake()->randomElement(['male', 'female', 'other']),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'state_of_origin' => 'Lagos',
            'local_government_area' => 'Ikeja',
            'address' => fake()->streetAddress(),
            'zipcode' => fake()->postcode(),
            'profile_image_path' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
            'employer_remarks' => null,
        ];
    }

    public function selected(?User $reviewer = null): static
    {
        return $this->moved(CandidatePipelineStage::Selected, $reviewer, 'Candidate selected.');
    }

    public function rejected(?User $reviewer = null): static
    {
        return $this->moved(CandidatePipelineStage::Rejected, $reviewer, 'Candidate rejected.');
    }

    public function moved(CandidatePipelineStage $status, ?User $reviewer = null, ?string $remarks = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
            'reviewed_by' => $reviewer?->id,
            'reviewed_at' => now(),
            'employer_remarks' => $remarks,
        ]);
    }
}
