<?php

use App\Enums\JobStatus;
use App\Services\AdminReportService;
use App\Support\OrganizationContext;
use Illuminate\Http\Request;

function reportFor(array $jobs): array
{
    [$organization, $owner] = organizationWithOwner();

    app(OrganizationContext::class)->runInOrganization($organization, function () use ($owner, $organization, $jobs): void {
        foreach ($jobs as $overrides) {
            $owner->jobs()->create(array_merge([
                'organization_id' => $organization->id,
                'description' => '<p>A role.</p>',
                'company' => 'Acme Co',
                'category' => 'Engineering',
                'location' => 'Remote',
                'employment_type' => 'full_time',
                'start_date' => now(),
                'due_date' => now()->addDays(30),
            ], $overrides));
        }
    });

    // The admin dashboard reads jobs with unscoped access (no single org context).
    app(OrganizationContext::class)->set(null, true);

    return app(AdminReportService::class)->getReport(Request::create('/'));
}

test('a draft job is excluded from admin job analytics', function () {
    $report = reportFor([
        ['title' => 'Open Analytics Role', 'status' => JobStatus::Open],
        ['title' => 'Draft Analytics Role', 'status' => JobStatus::Draft],
    ]);

    // applicationsPerJob must not expose draft job titles (org-private).
    $titles = array_column($report['applicationsPerJob'], 'title');
    expect($titles)->toContain('Open Analytics Role')->not->toContain('Draft Analytics Role');

    // Draft jobs must not be counted among submitted jobs in the period.
    expect($report['metrics']['submitted_jobs_in_period'])->toBe(1);
});
