<?php

use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use App\Support\OrganizationContext;

function createApplicationForOrganization(User $organizationUser, User $applicant): ApplicationForm
{
    $organization = app(OrganizationProvisioner::class)->ensureForEmployer($organizationUser);

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
});

test('a candidate cannot skip pipeline stages', function () {
    $organizationUser = User::factory()->organization()->create();
    $application = createApplicationForOrganization($organizationUser, User::factory()->create());

    $this->actingAs($organizationUser)
        ->patch(route('organisations.candidates.move', $application), ['stage' => 'selected'])
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
