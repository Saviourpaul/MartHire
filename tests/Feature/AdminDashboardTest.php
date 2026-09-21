<?php

use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;

it('renders admin dashboard with live recruitment metrics', function () {
    $admin = User::factory()->admin()->create();

    $employer = User::factory()->employer()->create([
        'created_at' => now()->startOfMonth()->addDay(),
    ]);

    $applicant = User::factory()->applicant()->create([
        'created_at' => now()->startOfMonth()->addDays(2),
    ]);

    $job = Job::factory()->create([
        'employer_id' => $employer->id,
        'created_at' => now()->startOfMonth()->addDays(3),
    ]);

    ApplicationForm::factory()->create([
        'job_id' => $job->id,
        'user_id' => $applicant->id,
        'status' => CandidatePipelineStage::Submitted,
        'submitted_at' => now()->startOfMonth()->addDays(4),
    ]);

    ApplicationForm::factory()->selected($employer)->create([
        'job_id' => $job->id,
        'user_id' => User::factory()->applicant()->create()->id,
        'submitted_at' => now()->startOfMonth()->addDays(5),
    ]);

    ApplicationForm::factory()->rejected($employer)->create([
        'job_id' => $job->id,
        'user_id' => User::factory()->applicant()->create()->id,
        'submitted_at' => now()->startOfMonth()->addDays(6),
    ]);

    $this->actingAs($admin)
        ->get(route('dashboard', ['period' => 'this_month']))
        ->assertOk()
        ->assertSee('Total Applicants')
        ->assertSee('Total Employers')
        ->assertSee('Total Jobs Posted')
        ->assertSee('Total Applications')
        ->assertSee('Selected Candidates')
        ->assertSee('Rejected Candidates')
        ->assertSee('Analytics')
        ->assertSee('Application Status')
        ->assertSee('Recent Registrations')
        ->assertSee('Recently Posted Jobs')
        ->assertSee('Latest Applications')
        ->assertSee($job->title)
        ->assertSee($applicant->email);
});

it('filters admin dashboard metrics by custom date range', function () {
    $admin = User::factory()->admin()->create();

    User::factory()->employer()->create([
        'created_at' => now()->subMonths(2),
    ]);

    User::factory()->employer()->create([
        'created_at' => now()->subDays(2),
    ]);

    $from = now()->subWeek()->toDateString();
    $to = now()->toDateString();

    $this->actingAs($admin)
        ->get(route('dashboard', [
            'period' => 'custom',
            'date_from' => $from,
            'date_to' => $to,
        ]))
        ->assertOk()
        ->assertSee('Analytics');
});

it('does not expose admin dashboard metrics to non-admin users', function () {
    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Recent Registrations')
        ->assertDontSee('Latest Applications');
});

it('auto-submits dashboard filter when a preset period is selected', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-analytics-period="12_months"', false)
        ->assertSee('data-analytics-period="30_days"', false)
        ->assertSee('data-analytics-period="7_days"', false);
});
