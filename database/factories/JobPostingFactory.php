<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    public function definition(): array
    {
        $title = fake()->jobTitle();

        return [
            'company_id' => Company::factory(),
            'shortcode' => strtoupper(Str::random(8)),
            'external_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'title' => $title,
            'full_title' => $title,
            'state' => 'published',
            'department' => fake()->randomElement(['Engineering', 'Design', 'Marketing', 'Operations']),
            'workplace_type' => fake()->randomElement(['remote', 'hybrid', 'on_site']),
            'url' => 'https://'.fake()->domainWord().'.workable.com/jobs/'.fake()->numberBetween(1000, 9999),
            'application_url' => 'https://apply.workable.com/'.fake()->domainWord().'/j/'.strtoupper(Str::random(8)).'/',
            'shortlink' => 'https://'.fake()->domainWord().'.workable.com/j/'.strtoupper(Str::random(8)),
            'location' => ['location_str' => fake()->city().', '.fake()->country(), 'city' => fake()->city(), 'country' => fake()->country()],
            'locations' => [],
            'salary' => null,
            'keywords' => ['php', 'laravel'],
            'content_html' => '<p>'.fake()->sentence().'</p>',
            'content_hash' => sha1(fake()->sentence()),
            'external_updated_at' => now()->subHours(fake()->numberBetween(1, 240)),
            'status' => 'open',
            'last_seen_at' => now(),
            'closed_at' => null,
            'apply_supported' => null,
        ];
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => 'closed', 'closed_at' => now()]);
    }
}
