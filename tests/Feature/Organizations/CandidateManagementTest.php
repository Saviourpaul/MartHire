<?php

use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use App\Support\OrganizationContext;

function createApplicationForOrganization(User $organizationUser, User $applicant): ApplicationForm
{
    $organization = app(OrganizationProvisioner::class)->ensureForEmployer($organizationUser);
    $membership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
        ->where('user_id', $organizationUser->id)->firstOrFail();
    $membership->onboardingProgress()->firstOrFail()->forceFill([
        'current_step' => 'complete',
        'completed_steps' => ['welcome', 'profile', 'team', 'finish'],
        'completed_at' => now(),
    ])->save();

    return app(OrganizationContext::class)->runInOrganization($organization, function () use ($organizationUser, $applicant, $organization) {
        $job = $organizationUser->jobs()->create([
            'organization_id' => $organization->id,
            'title' => 'Backend Engineer',
            'description' => 'A full description of the backend engineer role with enough detail.',
            'company' => 'Acme Co',
            'location' => 'Remote',
            'employment_type' => 'full_time',
            'start_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        return ApplicationForm::create([
            'job_id' => $job->id,
            'organization_id' => $organization->id,
            'user_id' => $applicant->id,
            'reference' => 'APP-'.$applicant->id.'-'.$job->id,
            'status' => CandidatePipelineStage::Submitted,
            'submitted_at' => now(),
            'first_name' => $applicant->first_name,
            'last_name' => $applicant->last_name,
            'email' => $applicant->email,
            'phone' => '08012345678',
        ]);
    });
}

test('only organization members can view the candidate pipeline', function () {
    $this->actingAs(User::factory()->organization()->create())
        ->get(route('organisations.candidates.index'))
        ->assertOk();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('organisations.candidates.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('organisations.candidates.index'))
        ->assertForbidden();
});

test('the candidate pipeline shows an empty state with no candidates', function () {
    $this->actingAs(User::factory()->organization()->create())
        ->get(route('organisations.candidates.index'))
        ->assertOk()
        ->assertSee('No candidates here yet.');
});

test('the pipeline can be filtered by stage', function () {
    $this->actingAs(User::factory()->organization()->create())
        ->get(route('organisations.candidates.index', ['stage' => 'shortlisted']))
        ->assertOk();

    $this->actingAs(User::factory()->organization()->create())
        ->get(route('organisations.candidates.index', ['stage' => 'not-a-real-stage']))
        ->assertNotFound();
});

test('candidate and applicant application URLs use the application reference and keep authorized ID links working', function () {
    $organizationUser = User::factory()->organization()->create();
    $applicant = User::factory()->create();
    $application = createApplicationForOrganization($organizationUser, $applicant);
    $organizationUrl = route('organisations.candidates.show', $application);
    $applicantUrl = route('client.applications.show', $application);

    expect(str_ends_with($organizationUrl, '/'.$application->reference))->toBeTrue()
        ->and(str_ends_with($applicantUrl, '/'.$application->reference))->toBeTrue();

    $this->actingAs($organizationUser)
        ->get(route('organisations.candidates.show', ['applicationForm' => $application->id]))
        ->assertStatus(301)
        ->assertRedirect($organizationUrl);

    [, $otherOrganizationUser] = organizationWithOwner();
    $this->actingAs($otherOrganizationUser)
        ->get(route('organisations.candidates.show', ['applicationForm' => $application->id]))
        ->assertForbidden();

    $this->actingAs($organizationUser)
        ->patch(route('organisations.candidates.move', ['applicationForm' => $application->id]), ['stage' => 'shortlisted'])
        ->assertRedirect();

    $this->actingAs($applicant)
        ->get(route('client.applications.show', ['applicationForm' => $application->id]))
        ->assertStatus(301)
        ->assertRedirect($applicantUrl);

    $this->actingAs(User::factory()->create())
        ->get(route('client.applications.show', ['applicationForm' => $application->id]))
        ->assertForbidden();
});

test('candidate job filters use slugs and legacy numeric filters redirect to the slug query', function () {
    $organizationUser = User::factory()->organization()->create();
    $application = createApplicationForOrganization($organizationUser, User::factory()->create());
    $job = Job::withoutGlobalScope(OrganizationScope::class)->findOrFail($application->job_id);
    $canonical = route('organisations.candidates.index', ['job' => $job->slug]);

    $this->actingAs($organizationUser)
        ->get(route('organisations.candidates.index', ['job' => $job->slug]))
        ->assertOk();

    $this->actingAs($organizationUser)
        ->get(route('organisations.candidates.index', ['job_id' => $job->id]))
        ->assertStatus(301)
        ->assertRedirect($canonical);

    $this->actingAs($organizationUser)
        ->get(route('organisations.candidates.index', ['job' => 'not-a-real-job']))
        ->assertNotFound();
});

test('an organization member can see and move a real candidate through the pipeline', function () {
    $organizationUser = User::factory()->organization()->create();
    $applicant = User::factory()->create();
    $application = createApplicationForOrganization($organizationUser, $applicant);

    expect($application->status)->toBe(CandidatePipelineStage::Submitted);

    $this->actingAs($organizationUser)
        ->get(route('organisations.candidates.index'))
        ->assertOk()
        ->assertSee($applicant->email);

    $this->actingAs($organizationUser)
        ->get(route('organisations.candidates.show', $application))
        ->assertOk()
        ->assertSee('Move to Shortlisted')
        ->assertSee('Move to Rejected');

    $this->actingAs($organizationUser)
        ->patch(route('organisations.candidates.move', $application), ['stage' => 'shortlisted'])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect($application->fresh()->status)->toBe(CandidatePipelineStage::Shortlisted);
    expect($application->statusHistories()->count())->toBe(1);
    $this->actingAs($applicant)->get(route('notifications.index'))
        ->assertOk()->assertSee('Application status updated')->assertSee('Shortlisted');
    $this->get(route('client.applications.show', $application))->assertOk()->assertSee('Shortlisted');
    $applicantFragment = $this->withHeader('X-Live-Tables', 'client-application')
        ->getJson(route('client.applications.show', $application))->assertOk();
    expect($applicantFragment->json('regions.client-application'))->toContain('Shortlisted');

    $organizationFragment = $this->actingAs($organizationUser)->withHeader('X-Live-Tables', 'candidate-review')
        ->getJson(route('organisations.candidates.show', $application))->assertOk();
    expect($organizationFragment->json('regions.candidate-review'))->toContain('Shortlisted');
});

test('a candidate cannot skip pipeline stages', function () {
    $organizationUser = User::factory()->organization()->create();
    $application = createApplicationForOrganization($organizationUser, User::factory()->create());

    $this->actingAs($organizationUser)
        ->patch(route('organisations.candidates.move', $application), ['stage' => 'selected', 'selection_confirmed' => 1])
        ->assertSessionHasErrors('stage');

    expect($application->fresh()->status)->toBe(CandidatePipelineStage::Submitted);
});

test('an organization cannot move another organization\'s candidate', function () {
    $owningOrganization = User::factory()->organization()->create();
    $otherOrganization = User::factory()->organization()->create();
    $application = createApplicationForOrganization($owningOrganization, User::factory()->create());

    $this->actingAs($otherOrganization)
        ->get(route('organisations.candidates.show', $application))
        ->assertForbidden();

    $this->actingAs($otherOrganization)
        ->patch(route('organisations.candidates.move', $application), ['stage' => 'shortlisted'])
        ->assertForbidden();
});

test('an organization can export its own candidate list as csv', function () {
    $organizationUser = User::factory()->organization()->create();
    $applicant = User::factory()->create(['first_name' => 'Exported', 'last_name' => 'Candidate']);
    createApplicationForOrganization($organizationUser, $applicant);

    $response = $this->actingAs($organizationUser)->get(route('organisations.candidates.export'));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();
    $this->assertStringContainsString('Candidate,Email,Job,Stage,Applied', $csv);
    $this->assertStringContainsString('Exported Candidate', $csv);
    $this->assertStringContainsString($applicant->email, $csv);
});

test('admin and applicant users cannot export the candidate list', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('organisations.candidates.export'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('organisations.candidates.export'))
        ->assertForbidden();
});
