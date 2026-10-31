<?php

use App\Enums\CandidatePipelineStage;
use App\Enums\JobStatus;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Cache;

function dashboardApplications(User $applicant, int $count, string $prefix = 'Dashboard Role'): array
{
    [$organization, $owner] = organizationWithOwner();
    $applications = app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $owner, $applicant, $count, $prefix): array {
        $applications = [];
        for ($index = 1; $index <= $count; $index++) {
            $job = $owner->jobs()->create([
                'organization_id' => $organization->id, 'title' => $prefix.' '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                'description' => 'A full engineering role description for dashboard table verification.',
                'company' => 'Example Dashboard Co', 'location' => 'Nigeria', 'employment_type' => 'full_time',
                'start_date' => now(), 'due_date' => now()->addMonth(),
            ]);
            $applications[] = ApplicationForm::create([
                'organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $applicant->id,
                'reference' => 'DASH-'.$job->id, 'status' => CandidatePipelineStage::Submitted,
                'submitted_at' => now()->subMinutes($count - $index), 'first_name' => $applicant->first_name,
                'last_name' => $applicant->last_name, 'email' => $applicant->email, 'phone' => '+2348031234567',
            ]);
        }

        return $applications;
    });

    return [$owner, $applications];
}

test('admin live dashboard refreshes cached metrics and recent rows together', function () {
    $admin = User::factory()->admin()->create();
    User::factory()->create();
    $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    expect(Cache::get('admin_dashboard.metrics')['total_applicants'])->toBe(1);
    User::factory()->create(['first_name' => 'Fresh', 'last_name' => 'DashboardAccount']);

    $response = $this->getJson(route('admin.dashboard'), ['X-Live-Tables' => 'admin-dashboard'])
        ->assertOk()->assertJsonStructure(['regions' => ['admin-dashboard'], 'url', 'title']);
    $html = $response->json('regions.admin-dashboard');
    expect($html)->toContain('Fresh DashboardAccount')->not->toContain('<html', '<body');
    preg_match('/Applicants<\/span>.*?<span class="nw-stat__value">([^<]*)<\/span>/s', $html, $metric);
    expect($metric[1] ?? null)->toBe('2');
});

test('organization dashboard fragments preserve organization and job visibility', function () {
    [$owner] = dashboardApplications(User::factory()->create(['first_name' => 'Visible', 'last_name' => 'Applicant']), 1, 'Own Dashboard Role');
    dashboardApplications(User::factory()->create(['first_name' => 'Private', 'last_name' => 'OtherTenant']), 1, 'Other Tenant Role');

    $response = $this->actingAs($owner)->getJson(route('organisations.dashboard'), ['X-Live-Tables' => 'organization-dashboard'])->assertOk();
    $html = $response->json('regions.organization-dashboard');
    expect($html)->toContain('Own Dashboard Role 01', 'Visible Applicant')
        ->not->toContain('Other Tenant Role 01', 'Private OtherTenant', '<html');
});

test('organization open-job metrics exclude nonaccepting jobs and synchronize after a live closure', function () {
    [$organization, $owner] = organizationWithOwner();
    $create = function ($organization, User $employer, JobStatus $status, $dueDate, string $title): Job {
        return app(OrganizationContext::class)->runInOrganization($organization, fn () => $employer->jobs()->create([
            'organization_id' => $organization->id, 'title' => $title,
            'description' => 'A complete job description for the live dashboard metric.',
            'company' => 'Dashboard Count Co', 'location' => 'Nigeria', 'employment_type' => 'full_time',
            'start_date' => today(), 'due_date' => $dueDate, 'status' => $status,
        ]));
    };
    $open = $create($organization, $owner, JobStatus::Open, today()->addMonth(), 'Open future role');
    $create($organization, $owner, JobStatus::Open, today(), 'Open until today');
    $create($organization, $owner, JobStatus::Open, today()->subDay(), 'Expired role');
    foreach ([JobStatus::Draft, JobStatus::Closed, JobStatus::Suspended] as $status) {
        $create($organization, $owner, $status, today()->addMonth(), $status->label().' role');
    }
    [$otherOrganization, $otherOwner] = organizationWithOwner();
    $create($otherOrganization, $otherOwner, JobStatus::Open, today()->addMonth(), 'Other organization role');

    $this->actingAs($owner)->get(route('organisations.dashboard'))->assertOk()
        ->assertViewHas('metrics', fn (array $metrics): bool => $metrics['open_jobs'] === 2 && $metrics['total_jobs'] === 6);
    $this->postJson(route('organisations.jobs.close', $open), ['confirm_closure' => 1], ['X-Live-Action' => '1'])
        ->assertOk()->assertJsonPath('refresh', true);
    expect($open->fresh()->status)->toBe(JobStatus::Closed);

    $response = $this->getJson(route('organisations.dashboard'), ['X-Live-Tables' => 'organization-dashboard'])->assertOk();
    preg_match('/Open jobs<\/span>.*?<span class="nw-stat__value">([^<]*)<\/span>/s', $response->json('regions.organization-dashboard'), $metric);
    expect($metric[1] ?? null)->toBe('1');
});

test('applicant dashboard paginates applications without a new route and retains query state', function () {
    $applicant = User::factory()->create();
    dashboardApplications($applicant, 12);
    $first = $this->actingAs($applicant)->get(route('client.dashboard'))
        ->assertOk()->assertSee('data-live-table="client-dashboard"', false)
        ->assertViewHas('applications', fn ($applications): bool => $applications->total() === 12 && $applications->count() === 10);
    expect($first->getContent())->toContain('applications_page=2');

    $response = $this->getJson(route('client.dashboard', ['applications_page' => 2, 'context' => 'applications']), ['X-Live-Tables' => 'client-dashboard'])->assertOk();
    $html = $response->json('regions.client-dashboard');
    expect($html)->toContain('Dashboard Role 01', 'Dashboard Role 02', 'context=applications')
        ->not->toContain('Dashboard Role 12');
});

test('live dashboard access retains authentication role checks and fragment allowlists', function () {
    $this->getJson(route('admin.dashboard'), ['X-Live-Tables' => 'admin-dashboard'])->assertUnauthorized();
    $applicant = User::factory()->create();
    $this->actingAs($applicant)->getJson(route('admin.dashboard'), ['X-Live-Tables' => 'admin-dashboard'])->assertForbidden();
    $this->actingAs($applicant)->getJson(route('organisations.dashboard'), ['X-Live-Tables' => 'organization-dashboard'])->assertForbidden();
    $this->actingAs($applicant)->getJson(route('client.dashboard'), ['X-Live-Tables' => 'admin-dashboard'])->assertStatus(400);
});
