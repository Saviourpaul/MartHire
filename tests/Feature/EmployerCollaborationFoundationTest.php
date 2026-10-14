<?php

use App\Enums\CandidatePipelineStage;
use App\Enums\OrganizationRole;
use App\Models\ActivityLog;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\JobPostMember;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\OrganizationTeamService;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** @return array<string, mixed> */
function foundationFixture(OrganizationRole $role = OrganizationRole::Recruiter, bool $assigned = true, string $stage = 'interview'): array
{
    app(OrganizationContext::class)->set(null, true);
    $owner = User::factory()->employer()->create();
    $organization = $owner->organizationMemberships()->firstOrFail()->organization;
    $member = User::factory()->employer()->create();
    $membership = OrganizationMember::factory()->create([
        'organization_id' => $organization->id, 'user_id' => $member->id, 'role' => $role,
    ]);
    $job = Job::factory()->create(['employer_id' => $owner->id, 'title' => 'Foundation visible vacancy']);
    $application = ApplicationForm::factory()->create(['job_id' => $job->id, 'status' => $stage]);
    $document = ApplicationDocument::factory()->create(['application_form_id' => $application->id, 'file_path' => 'foundation/test.pdf', 'mime_type' => 'application/pdf']);

    if ($assigned) {
        JobPostMember::create(['organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $member->id]);
    }

    return compact('owner', 'organization', 'member', 'membership', 'job', 'application', 'document');
}

it('blocks every employer resource in another organization by direct URL', function () {
    $data = foundationFixture();
    $outsider = User::factory()->employer()->create();
    $this->actingAs($outsider);
    $this->get(route('jobs.show', $data['job']))->assertNotFound();
    $this->put(route('jobs.update', $data['job']), [])->assertNotFound();
    $this->delete(route('jobs.destroy', $data['job']))->assertNotFound();
    $this->get(route('employer.applications.show', $data['application']))->assertNotFound();
    $this->patch(route('employer.applications.pipeline.move', $data['application']), ['stage' => 'selected'])->assertNotFound();
    $this->get(route('application-documents.preview', $data['document']))->assertNotFound();
    $this->get(route('application-documents.download', $data['document']))->assertNotFound();
    $this->get('/employer/applications/'.$data['application']->id.'/notes')->assertNotFound();
    $this->get(route('applicants.profile.show', $data['application']->applicant))->assertForbidden();
});

it('requires job assignment for restricted roles even within one organization', function (OrganizationRole $role) {
    $data = foundationFixture($role, false);
    $this->actingAs($data['member'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->get(route('jobs.show', $data['job']))->assertForbidden();
    $this->get(route('employer.applications.show', $data['application']))->assertForbidden();
    $this->get(route('application-documents.preview', $data['document']))->assertForbidden();
})->with([OrganizationRole::Recruiter, OrganizationRole::HiringManager, OrganizationRole::Interviewer]);

it('gives HR managers organization visibility without a job assignment', function () {
    $data = foundationFixture(OrganizationRole::HrManager, false);
    $this->actingAs($data['member'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->get(route('jobs.show', $data['job']))->assertOk();
    $this->get(route('employer.applications.show', $data['application']))->assertOk();
    $this->get(route('employer.team.index'))->assertOk();
});

it('limits interviewers to candidates who have reached interview and blocks their documents', function () {
    $data = foundationFixture(OrganizationRole::Interviewer);
    $early = ApplicationForm::factory()->create(['job_id' => $data['job']->id, 'status' => 'submitted']);
    $rejectedEarly = ApplicationForm::factory()->create(['job_id' => $data['job']->id, 'status' => 'rejected']);
    $rejectedAfterInterview = ApplicationForm::factory()->create(['job_id' => $data['job']->id, 'status' => 'rejected']);
    $rejectedAfterInterview->statusHistories()->create(['from_status' => 'shortlisted', 'to_status' => 'interview', 'changed_by' => $data['owner']->id, 'created_at' => now()]);
    $this->actingAs($data['member'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->get(route('employer.applications.show', $early))->assertForbidden();
    $this->get(route('employer.applications.show', $rejectedEarly))->assertForbidden();
    $this->get(route('employer.applications.show', $rejectedAfterInterview))->assertOk();
    $this->get(route('employer.applications.show', $data['application']))->assertOk()
        ->assertDontSee(route('application-documents.preview', $data['document']), false);
    $this->get(route('application-documents.preview', $data['document']))->assertForbidden();
    $this->get(route('application-documents.download', $data['document']))->assertForbidden();
    $this->patch(route('employer.applications.pipeline.move', $data['application']), ['stage' => 'selected'])->assertForbidden();
    $this->get(route('employer.Applied-Candidates'))->assertViewHas('applications', fn ($applications) => $applications->total() === 2);
    $this->get(route('jobs.show', $data['job']))->assertViewHas('job', fn ($job) => $job->applications_count === 2);
    $this->get(route('jobs'))->assertViewHas('jobs', fn ($jobs) => $jobs->first()->applications_count === 2);
});

it('prevents recruiter final selection but allows a hiring manager to select', function () {
    $data = foundationFixture();
    $this->actingAs($data['member'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->patch(route('employer.applications.pipeline.move', $data['application']), ['stage' => 'selected'])->assertForbidden();
    expect($data['application']->fresh()->status)->toBe(CandidatePipelineStage::Interview);
    $data['membership']->update(['role' => OrganizationRole::HiringManager]);
    $this->put(route('jobs.update', $data['job']), [])->assertForbidden();
    $this->patch(route('employer.applications.pipeline.move', $data['application']), ['stage' => 'selected'])->assertRedirect();
    expect($data['application']->fresh()->status)->toBe(CandidatePipelineStage::Selected);
    $this->assertDatabaseHas('application_status_histories', ['application_form_id' => $data['application']->id, 'to_status' => 'selected', 'changed_by' => $data['member']->id]);
    $this->assertDatabaseHas('activity_logs', ['application_form_id' => $data['application']->id, 'type' => 'pipeline']);
});

it('scopes dashboard counts and candidate lists to the assigned jobs', function () {
    $data = foundationFixture();
    $otherJob = Job::factory()->create(['employer_id' => $data['owner']->id, 'title' => 'Hidden internal vacancy']);
    ApplicationForm::factory()->create(['job_id' => $otherJob->id]);
    $this->actingAs($data['member'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->get(route('dashboard'))->assertOk()->assertViewHas('metrics', fn ($metrics) => $metrics['total_jobs'] === 1 && $metrics['total_applications'] === 1);
    $this->get(route('jobs'))->assertOk()->assertSee($data['job']->title)->assertDontSee($otherJob->title);
    $this->get(route('employer.Applied-Candidates'))->assertViewHas('applications', fn ($applications) => $applications->total() === 1);
});

it('validates job assignments against active members and restricts assignment changes', function () {
    $data = foundationFixture();
    $outsider = User::factory()->employer()->create();
    $this->actingAs($data['owner'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->put(route('jobs.team.update', $data['job']), ['user_ids' => [$outsider->id]])->assertSessionHasErrors('user_ids');
    $this->put(route('jobs.team.update', $data['job']), ['user_ids' => [$data['member']->id]])->assertRedirect();
    $this->assertDatabaseHas('job_post_members', ['job_id' => $data['job']->id, 'user_id' => $data['member']->id]);
    $this->actingAs($data['member'])->put(route('jobs.team.update', $data['job']), ['user_ids' => []])->assertForbidden();
});

it('revokes removed membership access on the very next request', function () {
    $data = foundationFixture();
    app(OrganizationContext::class)->set($data['organization']);
    app(OrganizationTeamService::class)->removeMember($data['membership'], $data['owner']);
    $this->actingAs($data['member'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->get(route('jobs.show', $data['job']))->assertNotFound();
    $this->get(route('employer.applications.show', $data['application']))->assertNotFound();
    $this->get(route('application-documents.download', $data['document']))->assertNotFound();
});

it('serves only authorized private documents and records views', function () {
    $data = foundationFixture();
    Storage::fake('local');
    Storage::fake('public');
    Storage::disk('local')->put($data['document']->file_path, '%PDF-1.4 private document');
    $this->actingAs($data['member'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->get(route('application-documents.preview', $data['document']))->assertOk()->assertHeader('Content-Disposition', 'inline; filename='.$data['document']->original_name);
    $this->get(route('application-documents.download', $data['document']))->assertOk();
    expect(ActivityLog::where('type', 'document_view')->count())->toBe(2);
    Storage::disk('local')->delete($data['document']->file_path);
    Storage::disk('public')->put($data['document']->file_path, 'public fallback must not be served');
    $this->get(route('application-documents.download', $data['document']))->assertNotFound();
});

it('preserves public job browsing and applicant-owned records across organizations', function () {
    $data = foundationFixture();
    $outsider = User::factory()->employer()->create();
    $this->actingAs($outsider)->get(route('Browse-jobs'))->assertOk()->assertSee($data['job']->title);
    $this->get(route('job-details', $data['job']))->assertOk();
    $applicant = $data['application']->applicant;
    $this->actingAs($applicant)->get(route('client.applications.show', $data['application']))->assertOk();
    Storage::fake('local');
    Storage::disk('local')->put($data['document']->file_path, '%PDF-1.4');
    $this->get(route('application-documents.download', $data['document']))->assertOk();
});

it('allows only active membership organization switches', function () {
    $data = foundationFixture();
    $outsider = User::factory()->employer()->create();
    $otherOrganization = $outsider->organizationMemberships()->firstOrFail()->organization;
    $this->actingAs($data['member'])->post(route('employer.organizations.switch'), ['organization_id' => $otherOrganization->id])->assertForbidden();
    $this->post(route('employer.organizations.switch'), ['organization_id' => $data['organization']->id])->assertRedirect(route('dashboard'))->assertSessionHas('active_organization_id', $data['organization']->id);
});

it('protects the last owner through account deletion and administrator actions', function () {
    $data = foundationFixture();
    $this->actingAs($data['owner'])->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('user');
    expect($data['owner']->fresh()->trashed())->toBeFalse();
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->patch(route('admin.users.suspend', $data['owner']))->assertSessionHasErrors('user');
    $this->put(route('admin.users.update', $data['owner']), ['role' => 'applicant', 'status' => 'active'])->assertSessionHasErrors('user');
    expect($data['owner']->fresh()->isActive())->toBeTrue();
});

it('isolates notification read state to the recipient', function () {
    $data = foundationFixture();
    $notification = $data['owner']->notifications()->create(['id' => (string) Str::uuid(), 'type' => 'test', 'data' => ['title' => 'Private invitation']]);
    $this->actingAs($data['member'])->patch(route('employer.notifications.read', $notification->id))->assertNotFound();
    $this->actingAs($data['owner'])->get(route('employer.notifications'))->assertOk()->assertSee('Private invitation');
    $this->post(route('employer.notifications.read-all'))->assertRedirect();
    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('preserves notification pages for applicant and administrator accounts', function () {
    $this->actingAs(User::factory()->applicant()->create())->get(route('client.notifications'))->assertOk()->assertSee('No notifications yet.');
    $this->actingAs(User::factory()->admin()->create())->get(route('notifications'))->assertOk()->assertSee('Notifications');
});

it('rejects deleted and unverified accounts when assigning job members', function () {
    $data = foundationFixture();
    $data['member']->forceFill(['email_verified_at' => null])->save();
    $this->actingAs($data['owner'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->put(route('jobs.team.update', $data['job']), ['user_ids' => [$data['member']->id]])->assertSessionHasErrors('user_ids');
    $data['member']->forceFill(['email_verified_at' => now()])->save();
    $data['member']->delete();
    $this->put(route('jobs.team.update', $data['job']), ['user_ids' => [$data['member']->id]])->assertSessionHasErrors('user_ids');
});

it('denies unverified employers and inactive admins access to suspended public job details', function () {
    $data = foundationFixture();
    $data['job']->update(['status' => 'suspended']);
    $data['owner']->forceFill(['email_verified_at' => null])->save();
    $this->actingAs($data['owner'])->get(route('job-details', $data['job']))->assertNotFound();
    $admin = User::factory()->admin()->suspended()->create();
    $this->actingAs($admin)->get(route('job-details', $data['job']))->assertNotFound();
});

it('does not count deleted owners when protecting the final active owner', function () {
    $data = foundationFixture(OrganizationRole::Owner);
    $data['member']->delete();
    $this->actingAs($data['owner'])->withSession(['active_organization_id' => $data['organization']->id]);
    $this->delete(route('profile.destroy'), ['password' => 'password'])->assertSessionHasErrors('user');
    expect($data['owner']->fresh()->trashed())->toBeFalse();
});

it('preserves recruitment descendants when running the ownership constraint migration on SQLite', function () {
    $data = foundationFixture();
    $data['application']->statusHistories()->create(['from_status' => 'shortlisted', 'to_status' => 'interview', 'changed_by' => $data['owner']->id, 'created_at' => now()]);
    $migration = require database_path('migrations/2026_10_12_192958_require_organization_ownership_on_recruitment_tables.php');
    $migration->up();
    $migration->down();

    $this->assertDatabaseHas('job_posts', ['id' => $data['job']->id, 'organization_id' => $data['organization']->id]);
    $this->assertDatabaseHas('application_forms', ['id' => $data['application']->id]);
    $this->assertDatabaseHas('application_documents', ['id' => $data['document']->id]);
    $this->assertDatabaseCount('application_status_histories', 1);
});
