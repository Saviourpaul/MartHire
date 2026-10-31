<?php

use App\Enums\CandidatePipelineStage;
use App\Enums\InterviewCandidateResponse;
use App\Enums\InterviewStatus;
use App\Enums\OrganizationRole;
use App\Models\ActivityLog;
use App\Models\ApplicationForm;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Models\JobPostMember;
use App\Models\User;
use App\Services\CandidateSelectionService;
use App\Services\InterviewService;
use App\Services\PipelineTransitionService;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

function selectionWorkflowApplication(CandidatePipelineStage $stage = CandidatePipelineStage::Interview): array
{
    [$organization, $owner] = organizationWithOwner();
    $application = app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $owner, $stage): ApplicationForm {
        $applicant = User::factory()->create();
        $job = $owner->jobs()->create([
            'organization_id' => $organization->id, 'title' => 'Selection Workflow Engineer',
            'description' => 'A complete description for the interview and selection workflow.',
            'company' => 'Workflow Co', 'location' => 'Nigeria', 'employment_type' => 'full_time',
            'start_date' => now(), 'due_date' => now()->addMonth(),
        ]);

        return ApplicationForm::create([
            'organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $applicant->id,
            'reference' => 'SELECT-'.$job->id, 'status' => $stage, 'submitted_at' => now(),
            'first_name' => $applicant->first_name, 'last_name' => $applicant->last_name,
            'email' => $applicant->email, 'phone' => '+2348031234567',
        ]);
    });

    return [$organization, $owner, $application];
}

function selectionWorkflowInterview(ApplicationForm $application, array $attributes = []): Interview
{
    return Interview::withoutGlobalScopes()->create([
        'organization_id' => $application->organization_id, 'application_form_id' => $application->id,
        'mode' => 'online', 'platform' => 'google_meet', 'meeting_link' => 'https://meet.example.test/interview',
        'contact_name' => 'Hiring Team', 'start_at' => now()->subHours(2), 'end_at' => now()->subHour(),
        'timezone' => 'UTC', 'status' => InterviewStatus::Scheduled, 'candidate_response' => InterviewCandidateResponse::Confirmed,
        'version' => 1, ...$attributes,
    ]);
}

beforeEach(function () {
    Mail::fake();
    Queue::fake();
    Notification::fake();
});

test('reaching Interview alone neither offers nor permits selection', function () {
    [, $owner, $application] = selectionWorkflowApplication();
    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))->assertOk()->assertDontSee('Move to Selected');
    $this->actingAs($owner)->patch(route('organisations.candidates.move', $application), [
        'stage' => 'selected', 'selection_confirmed' => 1,
    ])->assertSessionHasErrors('stage');
    expect($application->fresh()->status)->toBe(CandidatePipelineStage::Interview)
        ->and($application->statusHistories()->count())->toBe(0);
});

test('scheduled rescheduled cancelled declined or future interviews cannot qualify a candidate for selection', function (array $attributes) {
    [$organization, $owner, $application] = selectionWorkflowApplication();
    selectionWorkflowInterview($application, $attributes);
    $eligible = app(OrganizationContext::class)->runInOrganization($organization,
        fn () => app(CandidateSelectionService::class)->eligible($application));
    expect($eligible)->toBeFalse();
    $this->actingAs($owner)->patch(route('organisations.candidates.move', $application), [
        'stage' => 'selected', 'selection_confirmed' => 1,
    ])->assertSessionHasErrors('stage');
})->with([
    'scheduled' => [[]],
    'rescheduled' => [['status' => InterviewStatus::Rescheduled]],
    'cancelled' => [['status' => InterviewStatus::Cancelled]],
    'completed but declined' => [['status' => InterviewStatus::Completed, 'candidate_response' => InterviewCandidateResponse::Declined]],
    'completed but future' => [['status' => InterviewStatus::Completed, 'start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHour()]],
]);

test('completion records one reviewed interview without changing the pipeline or queuing email', function () {
    [, $owner, $application] = selectionWorkflowApplication();
    $interview = selectionWorkflowInterview($application);
    $this->actingAs($owner)->patch(route('organisations.interviews.complete', $interview), [
        'version' => 1, 'completion_confirmed' => 1,
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($interview->fresh()->status)->toBe(InterviewStatus::Completed)
        ->and($interview->fresh()->version)->toBe(2)
        ->and($application->fresh()->status)->toBe(CandidatePipelineStage::Interview)
        ->and(CandidateMessage::withoutGlobalScopes()->count())->toBe(0)
        ->and(ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->where('type', 'interview')->count())->toBe(1);
    $activity = ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->sole();
    expect($activity->payload['action'])->toBe('interview_completed');
    Mail::assertNothingSent();
    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))->assertOk()->assertSee('Move to Selected');
});

test('selection requires a completed interview and an explicit confirmation', function () {
    [, $owner, $application] = selectionWorkflowApplication();
    selectionWorkflowInterview($application, ['status' => InterviewStatus::Completed]);
    foreach ([[], ['selection_confirmed' => 0]] as $confirmation) {
        $this->actingAs($owner)->patch(route('organisations.candidates.move', $application), [
            'stage' => 'selected', ...$confirmation,
        ])->assertSessionHasErrors('selection_confirmed');
    }
    $this->actingAs($owner)->patch(route('organisations.candidates.move', $application), [
        'stage' => 'selected', 'selection_confirmed' => 1, 'remarks' => 'Interview completed and hiring decision approved.',
    ])->assertSessionHasNoErrors()->assertRedirect();
    expect($application->fresh()->status)->toBe(CandidatePipelineStage::Selected)
        ->and($application->statusHistories()->count())->toBe(1);
});

test('future cancelled declined completed or non-Interview appointments cannot be completed', function (array $attributes, CandidatePipelineStage $stage) {
    [, $owner, $application] = selectionWorkflowApplication($stage);
    $interview = selectionWorkflowInterview($application, $attributes);
    $this->actingAs($owner)->patch(route('organisations.interviews.complete', $interview), [
        'version' => 1, 'completion_confirmed' => 1,
    ])->assertSessionHasErrors('interview');
    expect($interview->fresh()->status)->toBe($interview->status)
        ->and($interview->fresh()->version)->toBe(1)
        ->and(ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->count())->toBe(0);
})->with([
    'future' => [['start_at' => now()->addDay(), 'end_at' => now()->addDay()->addHour()], CandidatePipelineStage::Interview],
    'cancelled' => [['status' => InterviewStatus::Cancelled], CandidatePipelineStage::Interview],
    'declined' => [['candidate_response' => InterviewCandidateResponse::Declined], CandidatePipelineStage::Interview],
    'already completed' => [['status' => InterviewStatus::Completed], CandidatePipelineStage::Interview],
    'rejected pipeline' => [[], CandidatePipelineStage::Rejected],
]);

test('stale versions missing confirmation and duplicate completion cannot create activity', function () {
    [, $owner, $application] = selectionWorkflowApplication();
    $interview = selectionWorkflowInterview($application);
    $this->actingAs($owner)->patch(route('organisations.interviews.complete', $interview), ['version' => 1])
        ->assertSessionHasErrors('completion_confirmed');
    $this->actingAs($owner)->patch(route('organisations.interviews.complete', $interview), ['version' => 2, 'completion_confirmed' => 1])
        ->assertSessionHasErrors('version');
    $this->actingAs($owner)->patch(route('organisations.interviews.complete', $interview), ['version' => 1, 'completion_confirmed' => 1])
        ->assertSessionHasNoErrors();
    $this->actingAs($owner)->patch(route('organisations.interviews.complete', $interview), ['version' => 1, 'completion_confirmed' => 1])
        ->assertSessionHasErrors('version');
    expect(ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->count())->toBe(1);
});

test('completed interviews belonging to another application do not unlock selection', function () {
    [, $owner, $application] = selectionWorkflowApplication();
    [, , $otherApplication] = selectionWorkflowApplication();
    selectionWorkflowInterview($otherApplication, ['status' => InterviewStatus::Completed]);
    $this->actingAs($owner)->patch(route('organisations.candidates.move', $application), [
        'stage' => 'selected', 'selection_confirmed' => 1,
    ])->assertSessionHasErrors('stage');
});

test('selection and completion preserve team and tenant authorization', function () {
    [$organization, $owner, $application] = selectionWorkflowApplication();
    $interview = selectionWorkflowInterview($application, ['status' => InterviewStatus::Completed]);
    $recruiter = addOrganizationMember($organization, OrganizationRole::Recruiter);
    $interviewer = addOrganizationMember($organization, OrganizationRole::Interviewer);
    app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $application, $recruiter, $interviewer): void {
        foreach ([$recruiter, $interviewer] as $member) {
            JobPostMember::create(['organization_id' => $organization->id, 'job_id' => $application->job_id, 'user_id' => $member->user_id]);
        }
    });
    $this->actingAs($recruiter->user)->patch(route('organisations.candidates.move', $application), [
        'stage' => 'selected', 'selection_confirmed' => 1,
    ])->assertForbidden();
    $interview->update(['status' => InterviewStatus::Scheduled]);
    $this->actingAs($interviewer->user)->patch(route('organisations.interviews.complete', $interview), [
        'version' => 1, 'completion_confirmed' => 1,
    ])->assertForbidden();
    [, $otherOwner] = organizationWithOwner();
    $this->actingAs($otherOwner)->patch(route('organisations.interviews.complete', $interview), [
        'version' => 1, 'completion_confirmed' => 1,
    ])->assertForbidden();
});

test('locked services recheck completion and confirmation even when called without HTTP validation', function () {
    [$organization, $owner, $application] = selectionWorkflowApplication();
    $interview = selectionWorkflowInterview($application);
    app(OrganizationContext::class)->runInOrganization($organization, function () use ($application, $owner, $interview): void {
        $service = app(PipelineTransitionService::class);
        expect(fn () => $service->moveCandidate($application, $owner, CandidatePipelineStage::Selected, selectionConfirmed: true))
            ->toThrow(ValidationException::class);
        app(InterviewService::class)->complete($interview, $owner, ['version' => 1, 'completion_confirmed' => 1]);
        expect(Gate::forUser($owner)->allows('select', $application))->toBeTrue();
        expect(fn () => $service->moveCandidate($application, $owner, CandidatePipelineStage::Selected))->toThrow(ValidationException::class);
    });
    expect($application->fresh()->status)->toBe(CandidatePipelineStage::Interview);
});
