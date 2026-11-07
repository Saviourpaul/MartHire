<?php

use App\Enums\CandidatePipelineStage;
use App\Enums\InterviewCandidateResponse;
use App\Enums\InterviewStatus;
use App\Enums\NotificationCategory;
use App\Models\ActivityLog;
use App\Models\ApplicationForm;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Models\User;
use App\Models\WorkflowNotification;
use App\Services\CandidateMessageService;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

function interviewWorkspaceApplication(CandidatePipelineStage $stage = CandidatePipelineStage::Interview): array
{
    [$organization, $owner] = organizationWithOwner();
    $applicant = User::factory()->create();
    $application = app(OrganizationContext::class)->runInOrganization($organization, function () use ($organization, $owner, $applicant, $stage) {
        $job = $owner->jobs()->create([
            'organization_id' => $organization->id, 'title' => 'Candidate Review Engineer',
            'description' => 'An engineering role with a complete job description.', 'company' => $organization->name,
            'location' => 'Nigeria', 'employment_type' => 'full_time', 'start_date' => now(), 'due_date' => now()->addMonth(),
        ]);

        return ApplicationForm::create([
            'organization_id' => $organization->id, 'job_id' => $job->id, 'user_id' => $applicant->id,
            'reference' => 'INTERVIEW-'.$job->id, 'status' => $stage, 'submitted_at' => now(),
            'first_name' => $applicant->first_name, 'last_name' => $applicant->last_name, 'email' => $applicant->email, 'phone' => '+2348031234567',
        ]);
    });

    return [$owner, $applicant, $application];
}

function interviewWorkspacePayload(array $overrides = []): array
{
    return array_replace([
        '_interview_form' => 'schedule', 'mode' => 'online', 'platform' => 'google_meet',
        'meeting_link' => 'https://meet.example.com/interview', 'timezone' => 'Africa/Lagos',
        'interview_date' => now()->addDays(3)->format('Y-m-d'), 'start_time' => '10:00', 'end_time' => '11:00',
        'contact_name' => 'Hiring Team', 'instructions' => 'Please arrive five minutes early.',
    ], $overrides);
}

beforeEach(function () {
    Queue::fake();
    Mail::fake();
});

test('interview scheduling stays in the candidate workspace and queues a complete invitation', function () {
    [$owner, $applicant, $application] = interviewWorkspaceApplication();
    $this->actingAs($owner)->get(route('organisations.candidates.show', $application))
        ->assertOk()->assertSee('Schedule interview')->assertSee('schedule-interview', false);

    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())
        ->assertSessionHasNoErrors()->assertRedirect(route('organisations.candidates.show', ['applicationForm' => $application, 'tab' => 'interviews']).'#candidate-interviews');

    $interview = Interview::withoutGlobalScopes()->where('application_form_id', $application->id)->sole();
    $message = CandidateMessage::withoutGlobalScopes()->where('interview_id', $interview->id)->sole();
    expect($application->fresh()->status)->toBe(CandidatePipelineStage::Interview)
        ->and($interview->start_at->format('H:i'))->toBe('09:00')
        ->and($interview->timezone)->toBe('Africa/Lagos')
        ->and($message->to_email)->toBe($applicant->email)
        ->and($message->status->value)->toBe('queued')
        ->and($message->rendered_html)->toContain('Confirm attendance', 'https://meet.example.com/interview')
        ->and($message->confirm_url)->not->toBeNull();
    expect(ActivityLog::withoutGlobalScopes()->where('application_form_id', $application->id)->where('type', 'interview')->exists())->toBeTrue();
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $owner->id,
        'category' => NotificationCategory::InterviewUpdate->value,
        'subject_type' => 'application',
        'subject_id' => $application->id,
    ]);
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $applicant->id,
        'category' => NotificationCategory::ApplicantInterviewUpdate->value,
        'subject_type' => 'application',
        'subject_id' => $application->id,
    ]);
    $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('Interview scheduled');
    $this->actingAs($applicant)->get(route('notifications.index'))->assertOk()->assertSee('Interview scheduled');
    Mail::assertNothingSent();
});

test('interviews require ownership and the interview pipeline stage', function () {
    [$owner, , $application] = interviewWorkspaceApplication(CandidatePipelineStage::Shortlisted);
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())->assertForbidden();
    [, $otherOwner] = organizationWithOwner();
    $this->actingAs($otherOwner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())->assertForbidden();
    expect(Interview::withoutGlobalScopes()->count())->toBe(0);
});

test('interview form validation retains submitted details without creating or sending an interview', function () {
    [$owner, , $application] = interviewWorkspaceApplication();
    $url = route('organisations.candidates.show', $application);
    $this->actingAs($owner)->from($url)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload([
        'end_time' => '09:00',
    ]))->assertRedirect($url)->assertSessionHasErrors('end_time')->assertSessionHasInput('start_time', '10:00');
    expect(Interview::withoutGlobalScopes()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('default reschedule messages retain meeting destination and invalidate older response links', function () {
    [$owner, $applicant, $application] = interviewWorkspaceApplication();
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())->assertSessionHasNoErrors();
    $interview = Interview::withoutGlobalScopes()->sole();
    $oldLink = CandidateMessage::withoutGlobalScopes()->sole()->confirm_url;
    $this->actingAs($owner)->patch(route('organisations.interviews.update', $interview), interviewWorkspacePayload([
        '_interview_form' => 'edit-'.$interview->id, 'version' => 1, 'start_time' => '12:00', 'end_time' => '13:00',
    ]))->assertSessionHasNoErrors()->assertRedirect(route('organisations.candidates.show', ['applicationForm' => $application, 'tab' => 'interviews']).'#candidate-interviews');

    expect($interview->fresh()->version)->toBe(2)
        ->and($interview->fresh()->status)->toBe(InterviewStatus::Rescheduled)
        ->and(CandidateMessage::withoutGlobalScopes()->latest('id')->first()->rendered_body)->toContain('https://meet.example.com/interview');
    expect(WorkflowNotification::query()->where('notifiable_id', $applicant->id)
        ->where('category', NotificationCategory::ApplicantInterviewUpdate->value)
        ->where('subject_id', $application->id)->count())->toBe(2);
    $this->actingAs($applicant)->get($oldLink)->assertGone();
    $this->actingAs($owner)->patch(route('organisations.interviews.update', $interview), interviewWorkspacePayload(['version' => 1]))
        ->assertSessionHasErrors('version');
});

test('signed interview responses render safely and record confirmation once', function () {
    [$owner, $applicant, $application] = interviewWorkspaceApplication();
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())->assertSessionHasNoErrors();
    $message = CandidateMessage::withoutGlobalScopes()->sole();
    $this->actingAs($applicant)->get($message->confirm_url)->assertOk()->assertSee('Confirm your interview');
    $this->actingAs($applicant)->post($message->confirm_url)->assertOk()->assertSee('Response recorded');
    expect(Interview::withoutGlobalScopes()->sole()->candidate_response)->toBe(InterviewCandidateResponse::Confirmed);
    $this->actingAs($owner)->get(route('notifications.index'))
        ->assertOk()->assertSee('Interview response');
    $this->actingAs($applicant)->get(route('notifications.index'))
        ->assertOk()->assertSee('Interview response recorded');
    $this->actingAs($applicant)->post($message->confirm_url)->assertOk();
    $this->actingAs($applicant)->get(preg_replace('/signature=[^&]+/', 'signature=invalid', $message->confirm_url))->assertForbidden();
});

test('declining an interview notifies the authorized organization team and the applicant inbox', function () {
    [$owner, $applicant, $application] = interviewWorkspaceApplication();
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())
        ->assertSessionHasNoErrors();
    $message = CandidateMessage::withoutGlobalScopes()->sole();

    $this->actingAs($applicant)->get($message->decline_url)->assertOk();
    $this->actingAs($applicant)->post($message->decline_url)->assertOk()->assertSee('Response recorded');

    $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('Interview response');
    $this->actingAs($applicant)->get(route('notifications.index'))->assertOk()->assertSee('Declined response');
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $owner->id,
        'category' => NotificationCategory::InterviewResponse->value,
        'subject_type' => 'application',
        'subject_id' => $application->id,
    ]);
});

test('cancelling an interview queues cancellation and closes its response link', function () {
    [$owner, $applicant, $application] = interviewWorkspaceApplication();
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())->assertSessionHasNoErrors();
    $interview = Interview::withoutGlobalScopes()->sole();
    $link = CandidateMessage::withoutGlobalScopes()->sole()->confirm_url;
    $this->actingAs($owner)->post(route('organisations.interviews.cancel', $interview), ['version' => 1])->assertSessionHasNoErrors();
    expect($interview->fresh()->status)->toBe(InterviewStatus::Cancelled)
        ->and(CandidateMessage::withoutGlobalScopes()->latest('id')->first()->confirm_url)->toBeNull();
    $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('Interview cancelled');
    $this->actingAs($applicant)->get(route('notifications.index'))->assertOk()->assertSee('Interview cancelled');
    $this->actingAs($applicant)->post($link)->assertGone();
});

test('completed interviews notify the applicant and organization workspace', function () {
    [$owner, $applicant, $application] = interviewWorkspaceApplication();
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())
        ->assertSessionHasNoErrors();
    $interview = Interview::withoutGlobalScopes()->sole();
    $interview->forceFill(['start_at' => now()->subHours(2), 'end_at' => now()->subHour()])->save();

    $this->actingAs($owner)->patch(route('organisations.interviews.complete', $interview), [
        'version' => $interview->version,
        'completion_confirmed' => 1,
    ])->assertSessionHasNoErrors();

    expect($interview->fresh()->status)->toBe(InterviewStatus::Completed);
    $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('Interview marked completed');
    $this->actingAs($applicant)->get(route('notifications.index'))->assertOk()->assertSee('Interview marked completed');
});

test('successful interview email delivery creates an authorized organization notice', function () {
    [$owner, , $application] = interviewWorkspaceApplication();
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())
        ->assertSessionHasNoErrors();
    $message = CandidateMessage::withoutGlobalScopes()->sole();

    app(CandidateMessageService::class)->deliver($message->id, $application->organization_id);

    expect($message->fresh()->status->value)->toBe('sent');
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $owner->id,
        'category' => NotificationCategory::CandidateEmailSent->value,
        'subject_type' => 'application',
        'subject_id' => $application->id,
    ]);
    $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('Interview email sent');
});

test('permanent interview email delivery failures keep the durable organization notice', function () {
    [$owner, , $application] = interviewWorkspaceApplication();
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())
        ->assertSessionHasNoErrors();
    $message = CandidateMessage::withoutGlobalScopes()->sole();

    app(CandidateMessageService::class)->markFailed($message->id, $application->organization_id, new RuntimeException('Transport unavailable'));

    expect($message->fresh()->status->value)->toBe('failed');
    $this->assertDatabaseHas('notifications', [
        'notifiable_id' => $owner->id,
        'category' => NotificationCategory::CandidateEmailFailure->value,
        'subject_type' => 'application',
        'subject_id' => $application->id,
    ]);
    $this->actingAs($owner)->get(route('notifications.index'))->assertOk()->assertSee('Interview email delivery failed');
});

test('invitation HTML escapes candidate-controlled content and preview creates no records', function () {
    [$owner, , $application] = interviewWorkspaceApplication();
    $application->update(['first_name' => '<script>alert(1)</script>']);
    $this->actingAs($owner)->postJson(route('organisations.interviews.preview', $application), interviewWorkspacePayload())
        ->assertOk()->assertJsonStructure(['subject', 'body']);
    expect(Interview::withoutGlobalScopes()->count())->toBe(0);
    $this->actingAs($owner)->post(route('organisations.interviews.store', $application), interviewWorkspacePayload())->assertSessionHasNoErrors();
    $html = CandidateMessage::withoutGlobalScopes()->sole()->rendered_html;
    expect($html)->not->toContain('<script>')->and($html)->toContain('&lt;script&gt;');
    Mail::assertNothingSent();
});
