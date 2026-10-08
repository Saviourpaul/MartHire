<?php

use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\Job;
use App\Models\User;

function pipelineApplication(User $employer, ?User $applicant = null): ApplicationForm
{
    return ApplicationForm::factory()
        ->for(Job::factory()->for($employer, 'employer'), 'job')
        ->for($applicant ?? User::factory()->applicant(), 'applicant')
        ->create(['status' => CandidatePipelineStage::Submitted]);
}

it('creates applications in the submitted stage', function () {
    $application = ApplicationForm::factory()->create();

    expect($application->status)->toBe(CandidatePipelineStage::Submitted);
});

it('moves a candidate through allowed pipeline stages with an audit trail', function () {
    $employer = User::factory()->employer()->create();
    $application = pipelineApplication($employer);

    foreach ([CandidatePipelineStage::Shortlisted, CandidatePipelineStage::Interview, CandidatePipelineStage::Selected] as $stage) {
        $this->actingAs($employer)
            ->patch(route('employer.applications.pipeline.move', $application), ['stage' => $stage->value, 'remarks' => 'Pipeline update.'])
            ->assertRedirect();

        $application->refresh();
        expect($application->status)->toBe($stage);
    }

    expect($application->statusHistories()->count())->toBe(3);
});

it('blocks invalid, terminal, and unauthorized pipeline changes', function () {
    $owner = User::factory()->employer()->create();
    $otherEmployer = User::factory()->employer()->create();
    $application = pipelineApplication($owner);

    $this->actingAs($otherEmployer)
        ->patch(route('employer.applications.pipeline.move', $application), ['stage' => CandidatePipelineStage::Shortlisted->value])
        ->assertForbidden();

    $this->actingAs($owner)
        ->from(route('employer.applications.show', $application))
        ->patch(route('employer.applications.pipeline.move', $application), ['stage' => CandidatePipelineStage::Selected->value])
        ->assertRedirect(route('employer.applications.show', $application))
        ->assertSessionHasErrors('stage');

    $application->update(['status' => CandidatePipelineStage::Rejected]);

    $this->actingAs($owner)
        ->from(route('employer.applications.show', $application))
        ->patch(route('employer.applications.pipeline.move', $application), ['stage' => CandidatePipelineStage::Shortlisted->value])
        ->assertRedirect(route('employer.applications.show', $application))
        ->assertSessionHasErrors('stage');
});

it('lets applicants view only their own submitted application', function () {
    $employer = User::factory()->employer()->create();
    $applicant = User::factory()->applicant()->create();
    $otherApplicant = User::factory()->applicant()->create();
    $application = pipelineApplication($employer, $applicant);

    $this->actingAs($applicant)
        ->get(route('client.applications.show', $application))
        ->assertOk()
        ->assertSee('Applicant information')
        ->assertSee('Submitted');

    $this->actingAs($otherApplicant)
        ->get(route('client.applications.show', $application))
        ->assertForbidden();
});
