<?php

use App\Enums\JobStatus;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Services\AdminReportService;
use Illuminate\Http\Request;

it('renders administrative reports with live recruitment and platform metrics', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create([
        'first_name' => 'Nexa',
        'last_name' => 'Works',
        'state_of_origin' => 'Lagos',
        'last_login_at' => now()->startOfMonth()->addDay(),
    ]);
    $applicant = User::factory()->completeApplicantProfile()->create([
        'state_of_origin' => 'Lagos',
        'last_login_at' => now()->startOfMonth()->addDays(2),
    ]);

    $selectedJob = Job::factory()->open()->create([
        'employer_id' => $employer->id,
        'title' => 'Platform Analyst',
        'company' => 'Nexa Works',
        'category' => 'Technology',
        'due_date' => now()->addMonth(),
        'created_at' => now()->startOfMonth()->addDays(3),
    ]);

    Job::factory()->create([
        'employer_id' => $employer->id,
        'status' => JobStatus::Open,
        'category' => 'Healthcare',
        'created_at' => now()->startOfMonth()->addDays(4),
    ]);

    ApplicationForm::factory()->selected($employer)->create([
        'job_id' => $selectedJob->id,
        'user_id' => $applicant->id,
        'submitted_at' => now()->startOfMonth()->addDays(5),
        'reviewed_at' => now()->startOfMonth()->addDays(7),
    ]);

    $this->actingAs($admin)
        ->get(route('Reports', ['period' => 'this_month']))
        ->assertOk()
        ->assertViewHas('report', fn (array $report): bool => $report['metrics']['total_applications'] === 1
            && $report['metrics']['hiring_success_rate'] === 100.0
            && $report['metrics']['average_time_to_hire_days'] === 2.0
            && array_combine($report['charts']['pipeline']['labels'], $report['charts']['pipeline']['series'])['Selected'] === 1)
        ->assertSee('Reports')
        ->assertSee('Registered Users')
        ->assertSee('Hiring Success Rate')
        ->assertSee('Candidate Pipeline')
        ->assertSee('Job Moderation')
        ->assertSee('Growth Trends')
        ->assertSee('Top Employers')
        ->assertSee('Export CSV')
        ->assertSee('Platform Analyst')
        ->assertSee('Technology')
        ->assertSee('Nexa Works')
        ->assertSee('2 days');
});

it('exports administrative reports as csv for admins', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create([
        'first_name' => 'Export',
        'last_name' => 'Employer',
    ]);
    $job = Job::factory()->open()->create([
        'employer_id' => $employer->id,
        'title' => 'Export Ready Role',
        'company' => 'CSV Limited',
        'created_at' => now()->startOfMonth()->addDay(),
    ]);

    ApplicationForm::factory()->create([
        'job_id' => $job->id,
        'submitted_at' => now()->startOfMonth()->addDays(2),
    ]);

    $response = $this->actingAs($admin)
        ->get(route('Reports.export', ['period' => 'this_month']))
        ->assertOk()
        ->assertDownload();

    $csv = $response->streamedContent();

    expect($csv)
        ->toContain('Section,Metric,Value')
        ->toContain('Most applied jobs')
        ->toContain('Export Ready Role - CSV Limited');
});

it('prevents non-admin users from viewing or exporting administrative reports', function () {
    $employer = User::factory()->employer()->create();

    $this->actingAs($employer)
        ->get(route('Reports'))
        ->assertForbidden();

    $this->actingAs($employer)
        ->get(route('Reports.export'))
        ->assertForbidden();
});

it('reports applicant counts by state of origin including missing geography', function () {
    User::factory()->applicant()->create(['nationality' => 'India', 'state_of_origin' => 'Punjab', 'local_government_area' => 'Amritsar']);
    User::factory()->applicant()->create(['nationality' => 'Pakistan', 'state_of_origin' => 'Punjab', 'local_government_area' => 'Lahore']);
    User::factory()->applicant()->create(['nationality' => 'Nigerian', 'state_of_origin' => 'Bayelsa']);
    User::factory()->applicant()->create(['state_of_origin' => null]);
    User::factory()->applicant()->create(['state_of_origin' => '']);
    User::factory()->employer()->create(['state_of_origin' => 'Lagos']);

    $report = app(AdminReportService::class)->getReport(Request::create('/Reports'));
    $geography = collect($report['geography']['applicantsByState'])->pluck('total', 'state')->all();

    expect($geography)->toHaveCount(3)->toMatchArray(['Punjab' => 2, 'Not Provided' => 2, 'Bayelsa' => 1]);
});
