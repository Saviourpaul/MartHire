<?php

use App\Enums\CandidatePipelineStage;
use App\Enums\OrganizationRole;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\JobPostMember;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use App\Support\OrganizationContext;
use Illuminate\Support\Str;

function phaseOneUiOrganization(User $owner): Organization
{
    $organization = app(OrganizationProvisioner::class)->ensureForEmployer($owner);
    $organization->update(['name' => 'Acme Hiring']);

    return $organization;
}

function phaseOneUiMember(Organization $organization, OrganizationRole $role): User
{
    $user = User::factory()->employer()->create();
    app(OrganizationContext::class)->runInOrganization($organization, fn () => OrganizationMember::factory()->create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role' => $role,
    ]));

    return $user;
}

it('renders a working team page with role summaries and an accessible invitation dialog', function () {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $recruiter = phaseOneUiMember($organization, OrganizationRole::Recruiter);

    $this->actingAs($owner)->get(route('employer.team.index'))
        ->assertOk()
        ->assertSee('Hiring Team')
        ->assertSee('Acme Hiring')
        ->assertSee($recruiter->email)
        ->assertSee('Role permissions')
        ->assertSee('Final selection requires a hiring manager or HR.')
        ->assertSee('aria-labelledby="invite-heading"', false)
        ->assertSee('action="'.route('employer.team.invitations.store').'"', false)
        ->assertDontSee('Collaboration Tools')
        ->assertDontSee('href="'.route('permission-management').'"', false)
        ->assertDontSee('href="'.route('Reports').'"', false);
});

it('hides owner membership controls from HR managers', function () {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $hrManager = phaseOneUiMember($organization, OrganizationRole::HrManager);
    $ownerMembership = app(OrganizationContext::class)->runInOrganization($organization, fn () => OrganizationMember::where('user_id', $owner->id)->firstOrFail());

    $this->actingAs($hrManager)->withSession(['active_organization_id' => $organization->id])->get(route('employer.team.index'))
        ->assertOk()
        ->assertSee('Owner managed')
        ->assertDontSee('id="edit-member-'.$ownerMembership->id.'"', false)
        ->assertDontSee('id="remove-member-'.$ownerMembership->id.'"', false)
        ->assertDontSee('<option value="owner"', false);
});

it('renders only permitted job controls for assigned hiring roles', function (OrganizationRole $role, bool $canCreate, bool $canEdit) {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $member = phaseOneUiMember($organization, $role);
    $job = app(OrganizationContext::class)->runInOrganization($organization, function () use ($owner, $organization, $member): Job {
        $job = Job::factory()->create(['employer_id' => $owner->id, 'organization_id' => $organization->id]);
        JobPostMember::factory()->create(['organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $member->id]);

        return $job;
    });

    $index = $this->actingAs($member)->withSession(['active_organization_id' => $organization->id])->get(route('jobs'))->assertOk();
    $canCreate ? $index->assertSee('Create Job') : $index->assertDontSee('Create Job');
    $canEdit ? $index->assertSee('aria-label="Edit job"', false) : $index->assertDontSee('aria-label="Edit job"', false);
    $index->assertDontSee('aria-label="Delete job"', false);

    $this->get(route('jobs.show', $job))->assertOk()
        ->assertSee('Hiring team')
        ->assertSee($member->first_name)
        ->assertDontSee('name="user_ids[]"', false)
        ->assertDontSee('name="_method" value="DELETE"', false);
})->with([
    'recruiter' => [OrganizationRole::Recruiter, true, true],
    'hiring manager' => [OrganizationRole::HiringManager, false, false],
    'interviewer' => [OrganizationRole::Interviewer, false, false],
]);

it('shows job assignment controls to organization owners', function () {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $member = phaseOneUiMember($organization, OrganizationRole::HiringManager);
    $job = app(OrganizationContext::class)->runInOrganization($organization, fn () => Job::factory()->create(['employer_id' => $owner->id, 'organization_id' => $organization->id]));

    $this->actingAs($owner)->get(route('jobs.show', $job))->assertOk()
        ->assertSee('Save hiring team')
        ->assertSee('name="user_ids[]"', false)
        ->assertSee('value="'.$member->id.'"', false)
        ->assertSee('action="'.route('jobs.team.update', $job).'"', false);
});

it('keeps interviewers document and pipeline controls hidden', function () {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $interviewer = phaseOneUiMember($organization, OrganizationRole::Interviewer);
    $application = app(OrganizationContext::class)->runInOrganization($organization, function () use ($owner, $organization, $interviewer): ApplicationForm {
        $job = Job::factory()->create(['employer_id' => $owner->id, 'organization_id' => $organization->id]);
        JobPostMember::factory()->create(['organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $interviewer->id]);
        $application = ApplicationForm::factory()->create(['job_id' => $job->id, 'status' => CandidatePipelineStage::Interview]);
        ApplicationDocument::factory()->create(['application_form_id' => $application->id, 'document_name' => 'Sensitive identity document']);

        return $application;
    });

    $this->actingAs($interviewer)->withSession(['active_organization_id' => $organization->id])->get(route('employer.applications.show', $application))->assertOk()
        ->assertSee('Document access is restricted for your role.')
        ->assertSee('You have read-only access')
        ->assertDontSee('Sensitive identity document')
        ->assertDontSee('application-documents/')
        ->assertDontSee('name="stage"', false);
});

it('limits final selection options to roles allowed to make the decision', function (OrganizationRole $role, bool $canSelect) {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $member = phaseOneUiMember($organization, $role);
    $application = app(OrganizationContext::class)->runInOrganization($organization, function () use ($owner, $organization, $member): ApplicationForm {
        $job = Job::factory()->create(['employer_id' => $owner->id, 'organization_id' => $organization->id]);
        JobPostMember::factory()->create(['organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $member->id]);

        return ApplicationForm::factory()->create(['job_id' => $job->id, 'status' => CandidatePipelineStage::Interview]);
    });

    $response = $this->actingAs($member)->withSession(['active_organization_id' => $organization->id])->get(route('employer.applications.show', $application))->assertOk();
    $response->assertSee('visible to candidate');
    $canSelect ? $response->assertSee('<option value="selected">', false) : $response->assertDontSee('<option value="selected">', false);
})->with([
    'recruiter' => [OrganizationRole::Recruiter, false],
    'hiring manager' => [OrganizationRole::HiringManager, true],
]);

it('renders a locked-email new account invitation with no editable role', function () {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $token = Str::random(64);
    $invitation = TeamInvitation::factory()->create(['organization_id' => $organization->id, 'invited_by' => $owner->id, 'email' => 'new-colleague@example.test', 'token_hash' => hash('sha256', $token)]);

    $this->get(route('team-invitations.show', $token))->assertOk()
        ->assertSee('Join Acme Hiring')
        ->assertSee($invitation->email)
        ->assertSee('Create account and join team')
        ->assertSee('name="password_confirmation"', false)
        ->assertDontSee('name="email"', false)
        ->assertDontSee('name="role"', false)
        ->assertDontSee($invitation->token_hash);
});

it('asks existing invited users to sign in without offering duplicate registration', function () {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $existingUser = User::factory()->employer()->create();
    $token = Str::random(64);
    TeamInvitation::factory()->create(['organization_id' => $organization->id, 'invited_by' => $owner->id, 'email' => $existingUser->email, 'token_hash' => hash('sha256', $token)]);

    $this->get(route('team-invitations.show', $token))->assertOk()
        ->assertSee('Sign in to accept')
        ->assertDontSee('name="password_confirmation"', false)
        ->assertSessionHas('url.intended', route('team-invitations.show', $token));
});

it('renders escaped notification content and the correct read actions', function () {
    $owner = User::factory()->employer()->create();
    phaseOneUiOrganization($owner);
    $notificationId = (string) Str::uuid();
    $owner->notifications()->create([
        'id' => $notificationId,
        'type' => 'team-update',
        'data' => ['title' => 'Team role updated', 'message' => '<script>unsafe()</script>', 'action_url' => route('jobs')],
    ]);

    $this->actingAs($owner)->get(route('employer.notifications'))->assertOk()
        ->assertSee('Team role updated')
        ->assertSee('&lt;script&gt;unsafe()&lt;/script&gt;', false)
        ->assertDontSee('<script>unsafe()</script>', false)
        ->assertSee('action="'.route('employer.notifications.read', $notificationId).'"', false)
        ->assertSee('action="'.route('employer.notifications.read-all').'"', false);
});

it('shows only accessible organizations in the organization switcher', function () {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $secondOrganization = Organization::factory()->create(['name' => 'Second Hiring Team']);
    Organization::factory()->create(['name' => 'Unrelated Private Organization']);
    app(OrganizationContext::class)->runInOrganization($secondOrganization, fn () => OrganizationMember::factory()->create([
        'organization_id' => $secondOrganization->id,
        'user_id' => $owner->id,
        'role' => OrganizationRole::Recruiter,
    ]));

    $this->actingAs($owner)->withSession(['active_organization_id' => $organization->id])->get(route('jobs'))->assertOk()
        ->assertSee('Switch organization')
        ->assertSee('name="organization_id"', false)
        ->assertSee('Second Hiring Team')
        ->assertDontSee('Unrelated Private Organization');
});

it('renders administrator applicant profiles as read-only details', function () {
    $admin = User::factory()->admin()->create();
    $applicant = User::factory()->completeApplicantProfile()->create();

    $this->actingAs($admin)->get(route('applicants.profile.show', $applicant))->assertOk()
        ->assertSee('Applicant Profile')
        ->assertSee($applicant->email)
        ->assertSee($applicant->phone)
        ->assertDontSee('name="first_name"', false)
        ->assertDontSee('name="address"', false);
});

it('keeps candidate details and documents in the main column with alerts above the layout', function () {
    $owner = User::factory()->employer()->create();
    $organization = phaseOneUiOrganization($owner);
    $application = app(OrganizationContext::class)->runInOrganization($organization, function () use ($owner, $organization): ApplicationForm {
        $job = Job::factory()->create(['employer_id' => $owner->id, 'organization_id' => $organization->id]);

        return ApplicationForm::factory()->create(['job_id' => $job->id]);
    });

    $response = $this->actingAs($owner)->withSession(['success' => 'Candidate updated.'])
        ->get(route('employer.applications.show', $application))->assertOk();
    $document = new DOMDocument;
    $previousErrorHandling = libxml_use_internal_errors(true);

    try {
        $document->loadHTML($response->getContent());
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);
    }

    $xpath = new DOMXPath($document);

    expect($xpath->query('//*[@data-application-layout]/*')->length)->toBe(2)
        ->and($xpath->query('//*[@data-application-main]//h2[contains(., "Candidate Details")]')->length)->toBe(1)
        ->and($xpath->query('//*[@data-application-main]//h2[contains(., "All Documents")]')->length)->toBe(1)
        ->and($xpath->query('//aside[@data-application-sidebar]//h2[contains(., "Candidate pipeline")]')->length)->toBe(1)
        ->and($xpath->query('//*[@data-application-layout]//*[@role="status" or @role="alert"]')->length)->toBe(0)
        ->and($xpath->query('//*[@data-application-layout]/preceding-sibling::*[@role="status"]')->length)->toBe(1);
});
