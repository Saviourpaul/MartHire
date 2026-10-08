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

    $approvedJob = Job::factory()->approved()->create([
        'employer_id' => $employer->id,
        'title' => 'Platform Analyst',
        'company' => 'Nexa Works',
        'category' => 'Technology',
        'due_date' => now()->addMonth(),
        'created_at' => now()->startOfMonth()->addDays(3),
    ]);

    Job::factory()->create([
        'employer_id' => $employer->id,
        'status' => JobStatus::Pending,
        'category' => 'Healthcare',
        'created_at' => now()->startOfMonth()->addDays(4),
    ]);

    ApplicationForm::factory()->approved($employer)->create([
        'job_id' => $approvedJob->id,
        'user_id' => $applicant->id,
        'submitted_at' => now()->startOfMonth()->addDays(5),
        'reviewed_at' => now()->startOfMonth()->addDays(7),
    ]);

    $this->actingAs($admin)
        ->get(route('Reports', ['period' => 'this_month']))
        ->assertOk()
        ->assertSee('Administrative Reports')
        ->assertSee('Platform Statistics')
        ->assertSee('Recruitment Analytics')
        ->assertSee('Job Moderation')
        ->assertSee('Security And Rate Limits')
        ->assertSee('Platform Configuration')
        ->assertSee('Export CSV')
        ->assertSee('Platform Analyst')
        ->assertSee('Technology')
        ->assertSee('Nexa Works')
        ->assertSee('2.0 days');
});

it('exports administrative reports as csv for admins', function () {
    $admin = User::factory()->admin()->create();
    $employer = User::factory()->employer()->create([
        'first_name' => 'Export',
        'last_name' => 'Employer',
    ]);
    $job = Job::factory()->approved()->create([
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

it('reports canonical states by country while retaining historical geography', function () {
    User::factory()->applicant()->create(['country_code' => 'IN', 'country' => 'India', 'state' => 'Punjab', 'city' => 'Amritsar']);
    User::factory()->applicant()->create(['country_code' => 'PK', 'country' => 'Pakistan', 'state' => 'Punjab', 'city' => 'Lahore']);
    User::factory()->applicant()->create(['nationality' => 'Nigerian', 'state_of_origin' => 'Bayelsa']);

    $report = app(AdminReportService::class)->getReport(Request::create('/Reports'));
    $geography = collect($report['geography']['applicantsByState'])->pluck('total', 'state')->all();

    expect($geography)->toMatchArray(['Punjab, India' => 1, 'Punjab, Pakistan' => 1, 'Bayelsa, Nigerian' => 1]);
});
