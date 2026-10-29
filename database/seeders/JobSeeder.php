<?php

namespace Database\Seeders;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class JobSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the test user
        $testUser = User::where('email', 'test@example.com')->first();

        if ($testUser) {
            // Create 8 open jobs for the test user
            Job::factory(8)
                ->for($testUser, 'employer')
                ->open()
                ->create();

            // Include closed and suspended examples
            Job::factory(2)
                ->for($testUser, 'employer')
                ->sequence(
                    ['status' => JobStatus::Closed],
                    ['status' => JobStatus::Suspended, 'suspended_at' => now(), 'suspension_reason' => 'Correct the job content before reopening.'],
                )
                ->create([
                    'category' => 'General Recruitment',
                ]);
        }
    }
}
