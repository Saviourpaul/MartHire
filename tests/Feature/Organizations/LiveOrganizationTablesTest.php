<?php

use App\Enums\CandidatePipelineStage;
use App\Enums\InterviewStatus;
use App\Enums\JobStatus;
use App\Enums\OrganizationRole;
use App\Models\ApplicationForm;
use App\Models\Interview;
use App\Models\Job;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

function liveTableJob($organization, User $owner, array $attributes = []): Job
{
    return app(OrganizationContext::class)->runInOrganization($organization, fn () => $owner->jobs()->create([
        'organization_id' => $organization->id, 'title' => 'Live Table Engineer',
        'description' => 'A complete engineering job description for live table regressions.',
        'company' => 'Live Table Co', 'location' => 'Nigeria', 'employment_type' => 'full_time',
        'start_date' => now(), 'due_date' => now()->addMonth(), 'status' => JobStatus::Open, ...$attributes,
    ]));
}

function liveTableApplication($organization, User $owner, CandidatePipelineStage $stage = CandidatePipelineStage::Submitted): ApplicationForm
{
    $job = liveTableJob($organization, $owner);
    $applicant = User::factory()->create(['first_name' => 'Live', 'last_name' => 'Candidate']);

    return app(OrganizationContext::class)->runInOrganization($organization, fn () => ApplicationForm::create([
        'organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $applicant->id,
        'reference' => 'LIVE-'.$job->id, 'status' => $stage, 'submitted_at' => now(),
        'first_name' => $applicant->first_name, 'last_name' => $applicant->last_name,
        'email' => $applicant->email, 'phone' => '+2348031234567',
    ]));
}

beforeEach(function () {
    Notification::fake();
    Queue::fake();
    $this->withoutMiddleware(ThrottleRequests::class);
});

test('organization jobs return an authorized filtered fragment without the page shell', function () {
    [$organization, $owner] = organizationWithOwner();
    liveTableJob($organization, $owner, ['title' => 'Matching Engineer']);
    liveTableJob($organization, $owner, ['title' => 'Different Role']);
    [$other, $otherOwner] = organizationWithOwner();
    liveTableJob($other, $otherOwner, ['title' => 'Matching Secret']);

    $response = $this->actingAs($owner)->withHeader('X-Live-Tables', 'organization-jobs')
        ->getJson(route('organisations.jobs.index', ['search' => 'Matching']))->assertOk()
        ->assertJsonStructure(['regions' => ['organization-jobs'], 'url', 'title']);
    $html = $response->json('regions.organization-jobs');
    expect($html)->toContain('Matching Engineer', 'data-live-filter')->not->toContain('Different Role', 'Matching Secret', '<!DOCTYPE', 'app-sidebar');
    expect($response->headers->get('Cache-Control'))->toContain('private', 'no-store');
});

test('deleting the last job on a later page refreshes the remaining valid page', function () {
    [$organization, $owner] = organizationWithOwner();
    $jobs = collect();
    for ($index = 0; $index < 11; $index++) {
        $jobs->push(liveTableJob($organization, $owner, ['title' => sprintf('Role %02d', $index)]));
    }
    $this->actingAs($owner)->withHeader('X-Live-Action', '1')->deleteJson(route('organisations.jobs.destroy', $jobs->last()))
        ->assertOk()->assertJsonPath('refresh', true)->assertJsonPath('topics', ['jobs', 'candidates', 'applications'])
        ->assertSessionMissing('success');
    $response = $this->withHeader('X-Live-Tables', 'organization-jobs')->getJson(route('organisations.jobs.index', ['page' => 2]))->assertOk();
    expect($response->json('url'))->toContain('page=1');
    expect($response->json('regions.organization-jobs'))->toContain('Role 00')->not->toContain('Role 10');
});

test('candidate filters and a JSON stage action stay synchronized with the same backend query', function () {
    [$organization, $owner] = organizationWithOwner();
    $application = liveTableApplication($organization, $owner);
    $this->actingAs($owner)->withHeader('X-Live-Action', '1')->patchJson(route('organisations.candidates.move', $application), ['stage' => 'shortlisted'])
        ->assertOk()->assertJsonPath('message', 'Candidate moved to Shortlisted.')->assertSessionMissing('success');
    $response = $this->withHeader('X-Live-Tables', 'organization-candidates')->getJson(route('organisations.candidates.index', [
        'stage' => 'shortlisted', 'search' => 'Live', 'job_id' => $application->job_id,
    ]))->assertOk();
    expect($response->json('regions.organization-candidates'))->toContain('Live Candidate', 'Shortlisted', 'data-live-link');
    $this->withHeader('X-Live-Tables', 'organization-candidates')->getJson(route('organisations.candidates.index', ['stage' => 'submitted']))
        ->assertOk()->assertJson(fn ($json) => $json->where('regions.organization-candidates', fn ($html) => ! str_contains($html, 'Live Candidate'))->etc());
    expect($application->fresh()->status)->toBe(CandidatePipelineStage::Shortlisted);
});

test('team fragments retain both independent page parameters and their row modals', function () {
    [$organization, $owner] = organizationWithOwner();
    for ($index = 0; $index < 16; $index++) {
        addOrganizationMember($organization, OrganizationRole::Recruiter);
    }
    app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $owner): void {
        for ($index = 0; $index < 11; $index++) {
            TeamInvitation::create([
                'organization_id' => $organization->id, 'email' => 'invite'.$index.'@example.test', 'role' => OrganizationRole::Recruiter,
                'token_hash' => hash('sha256', 'live-token-'.$index), 'expires_at' => now()->addWeek(), 'invited_by' => $owner->id,
            ]);
        }
    });
    $response = $this->actingAs($owner)->withHeader('X-Live-Tables', 'organization-team')->getJson(route('organisations.team.index', [
        'members_page' => 2, 'invitations_page' => 2,
    ]))->assertOk();
    $html = $response->json('regions.organization-team');
    expect($html)->toContain('members_page=2', 'invitations_page=2', 'manage-member-', 'invite-email');
    preg_match_all('/href="([^"]+)"/', $html, $matches);
    $pagerUrls = array_filter($matches[1], fn ($url) => str_contains($url, 'members_page=') || str_contains($url, 'invitations_page='));
    expect($pagerUrls)->not->toBeEmpty();
    foreach ($pagerUrls as $url) {
        parse_str(parse_url(html_entity_decode($url), PHP_URL_QUERY) ?? '', $query);
        expect($query)->toHaveKeys(['members_page', 'invitations_page']);
    }
});

test('team live mutations return synchronized data while preserving validation and ownership', function () {
    [$organization, $owner] = organizationWithOwner();
    $member = addOrganizationMember($organization, OrganizationRole::Recruiter);
    $this->actingAs($owner)->withHeader('X-Live-Action', '1')->postJson(route('organisations.team.invitations.store'), [
        'email' => 'new-live@example.test', 'role' => 'recruiter',
    ])->assertOk()->assertJsonPath('topics', ['team'])->assertSessionMissing('success');
    $this->patchJson(route('organisations.team.members.update', $member), ['role' => 'hiring_manager', 'status' => 'suspended'])
        ->assertOk()->assertJsonPath('refresh', true);
    expect($member->fresh()->role)->toBe(OrganizationRole::HiringManager);
    $this->postJson(route('organisations.team.invitations.store'), ['email' => 'invalid', 'role' => 'recruiter'])
        ->assertUnprocessable()->assertJsonValidationErrors('email');
    [, $otherOwner] = organizationWithOwner();
    $this->actingAs($otherOwner)->patchJson(route('organisations.team.members.update', $member), ['role' => 'recruiter'])->assertForbidden();
});

test('candidate review live actions retain completion and explicit selection requirements', function () {
    [$organization, $owner] = organizationWithOwner();
    $application = liveTableApplication($organization, $owner, CandidatePipelineStage::Interview);
    $interview = app(OrganizationContext::class)->runInOrganization($organization, fn () => Interview::create([
        'organization_id' => $organization->id, 'application_form_id' => $application->id,
        'mode' => 'online', 'platform' => 'google_meet', 'meeting_link' => 'https://meet.example.test/live',
        'contact_name' => 'Hiring Team', 'start_at' => now()->subHours(2), 'end_at' => now()->subHour(), 'timezone' => 'UTC',
    ]));
    $this->actingAs($owner)->withHeader('X-Live-Action', '1')->patchJson(route('organisations.candidates.move', $application), ['stage' => 'selected', 'selection_confirmed' => 1])
        ->assertUnprocessable()->assertJsonValidationErrors('stage');
    $this->patchJson(route('organisations.interviews.complete', $interview), ['version' => 1, 'completion_confirmed' => 1])
        ->assertOk()->assertJsonPath('topics', ['interviews', 'candidates']);
    expect($interview->fresh()->status)->toBe(InterviewStatus::Completed)->and($application->fresh()->status)->toBe(CandidatePipelineStage::Interview);
    $response = $this->withHeader('X-Live-Tables', 'candidate-review')->getJson(route('organisations.candidates.show', $application))->assertOk();
    expect($response->json('regions.candidate-review'))->toContain('Move to Selected', 'Completed')->not->toContain('<!DOCTYPE');
    $this->patchJson(route('organisations.candidates.move', $application), ['stage' => 'selected'])->assertUnprocessable()->assertJsonValidationErrors('selection_confirmed');
    $this->patchJson(route('organisations.candidates.move', $application), ['stage' => 'selected', 'selection_confirmed' => 1])->assertOk();
    expect($application->fresh()->status)->toBe(CandidatePipelineStage::Selected);
});
