<?php

namespace App\Services;

use App\Enums\InterviewCandidateResponse;
use App\Enums\InterviewMode;
use App\Enums\InterviewPlatform;
use App\Enums\InterviewStatus;
use App\Enums\InterviewTemplateType;
use App\Models\ActivityLog;
use App\Models\ApplicationForm;
use App\Models\Interview;
use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Throwable;

class InterviewService
{
    private const DETAIL_FIELDS = [
        'mode', 'platform', 'meeting_link', 'venue_name', 'address', 'what_to_bring',
        'directions', 'instructions', 'contact_name', 'contact_phone', 'start_at', 'end_at', 'timezone',
    ];

    public function __construct(
        private CandidateMessageService $messages,
        private ActivityLogger $activityLogger,
        private OrganizationContext $context,
        private WorkflowNotificationService $notifications,
    ) {}

    /** @param array<string, mixed> $data */
    public function draft(ApplicationForm $application, array $data): Interview
    {
        $timezone = (string) ($data['timezone'] ?? '');

        if (! in_array($timezone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), true)) {
            throw ValidationException::withMessages(['timezone' => 'Choose a valid interview timezone.']);
        }

        $start = $this->parseDateTime($data, 'start_time', $timezone);
        $end = $this->parseDateTime($data, 'end_time', $timezone);

        if (! $start->isAfter(CarbonImmutable::now('UTC'))) {
            throw ValidationException::withMessages(['interview_date' => 'The interview must start in the future.']);
        }

        if (! $end->isAfter($start)) {
            throw ValidationException::withMessages(['end_time' => 'The interview end time must be after the start time.']);
        }

        $modeValue = $data['mode'] ?? null;
        $mode = $modeValue instanceof InterviewMode ? $modeValue
            : (is_string($modeValue) ? InterviewMode::tryFrom($modeValue) : null);

        if ($mode === null) {
            throw ValidationException::withMessages(['mode' => 'Choose online or onsite for the interview.']);
        }

        $platform = null;

        if ($mode === InterviewMode::Online) {
            $platformValue = $data['platform'] ?? null;
            $platform = $platformValue instanceof InterviewPlatform ? $platformValue
                : (is_string($platformValue) ? InterviewPlatform::tryFrom($platformValue) : null);

            if ($platform === null) {
                throw ValidationException::withMessages(['platform' => 'Choose a valid online interview platform.']);
            }
        }

        return new Interview([
            ...Arr::only($data, ['instructions', 'contact_name', 'contact_phone']),
            'organization_id' => $application->organization_id,
            'application_form_id' => $application->id,
            'mode' => $mode,
            'platform' => $platform,
            'meeting_link' => $mode === InterviewMode::Online ? ($data['meeting_link'] ?? null) : null,
            'venue_name' => $mode === InterviewMode::Onsite ? ($data['venue_name'] ?? null) : null,
            'address' => $mode === InterviewMode::Onsite ? ($data['address'] ?? null) : null,
            'what_to_bring' => $mode === InterviewMode::Onsite ? ($data['what_to_bring'] ?? null) : null,
            'directions' => $mode === InterviewMode::Onsite ? ($data['directions'] ?? null) : null,
            'start_at' => $start,
            'end_at' => $end,
            'timezone' => $timezone,
        ]);
    }

    /** @param array<string, mixed> $data */
    public function schedule(ApplicationForm $application, User $actor, array $data): Interview
    {
        return DB::transaction(function () use ($application, $actor, $data): Interview {
            $application = $this->lockApplication($application);
            Gate::forUser($actor)->authorize('create', [Interview::class, $application]);
            $interview = $this->draft($application, $data);
            $interview->save();
            $this->recordActivity($interview, $actor, 'interview_scheduled');
            $this->messages->queue(
                $interview, $actor,
                $interview->mode === InterviewMode::Online ? InterviewTemplateType::InterviewOnline : InterviewTemplateType::InterviewOnsite,
                $data['subject'] ?? null, $data['body'] ?? null,
            );

            return $interview->load('applicationForm.job');
        });
    }

    /** @param array<string, mixed> $data */
    public function reschedule(Interview $interview, User $actor, array $data): Interview
    {
        return DB::transaction(function () use ($interview, $actor, $data): Interview {
            $interview = $this->lockInterview($interview);
            Gate::forUser($actor)->authorize('update', $interview);
            $this->assertEditable($interview, $data);
            $previous = $this->snapshot($interview);
            $draft = $this->draft($interview->applicationForm, $data);
            $interview->fill(Arr::only($draft->getAttributes(), self::DETAIL_FIELDS));
            $interview->forceFill([
                'status' => InterviewStatus::Rescheduled,
                'candidate_response' => InterviewCandidateResponse::Pending,
                'responded_at' => null,
                'version' => $interview->version + 1,
            ])->save();
            $this->recordActivity($interview, $actor, 'interview_rescheduled', ['previous' => $previous]);
            $this->messages->queue($interview, $actor, InterviewTemplateType::InterviewReschedule, $data['subject'] ?? null, $data['body'] ?? null);

            return $interview->load('applicationForm.job');
        });
    }

    /** @param array<string, mixed> $data */
    public function cancel(Interview $interview, User $actor, array $data = []): Interview
    {
        return DB::transaction(function () use ($interview, $actor, $data): Interview {
            $interview = $this->lockInterview($interview);
            Gate::forUser($actor)->authorize('cancel', $interview);
            $this->assertEditable($interview, $data);
            $previous = $this->snapshot($interview);
            $interview->forceFill([
                'status' => InterviewStatus::Cancelled,
                'candidate_response' => InterviewCandidateResponse::Pending,
                'responded_at' => null,
                'version' => $interview->version + 1,
            ])->save();
            $this->recordActivity($interview, $actor, 'interview_cancelled', ['previous' => $previous]);
            $this->messages->queue($interview, $actor, InterviewTemplateType::InterviewCancel, $data['subject'] ?? null, $data['body'] ?? null);

            return $interview->load('applicationForm.job');
        });
    }

    public function respond(Interview $interview, InterviewCandidateResponse $response, int $version): Interview
    {
        if ($response === InterviewCandidateResponse::Pending) {
            throw ValidationException::withMessages(['response' => 'Choose confirm or decline.']);
        }

        return $this->context->runInOrganization($interview->organization, function () use ($interview, $response, $version): Interview {
            return DB::transaction(function () use ($interview, $response, $version): Interview {
                $interview = $this->lockInterview($interview);
                abort_unless($interview->version === $version && $interview->status->isActive()
                    && $interview->end_at->isFuture(), 410, 'This interview response link is no longer available.');

                if ($interview->candidate_response === $response && $interview->responded_at !== null) {
                    return $interview;
                }

                $previousResponse = $interview->candidate_response;
                $interview->update(['candidate_response' => $response, 'responded_at' => now()]);
                $activity = $this->recordActivity($interview, null, 'interview_response', ['previous_response' => $previousResponse->value]);
                $this->notifications->interviewResponded($interview, $activity->id);

                return $interview;
            });
        });
    }

    /** @param array<string, mixed> $data */
    public function complete(Interview $interview, User $actor, array $data): Interview
    {
        return DB::transaction(function () use ($interview, $actor, $data): Interview {
            $interview = $this->lockInterview($interview);
            Gate::forUser($actor)->authorize('manageCompletion', $interview);
            if (! isset($data['version']) || (int) $data['version'] !== $interview->version) {
                throw ValidationException::withMessages(['version' => 'This interview was changed by another team member. Reload before completing it.']);
            }
            if (! in_array($data['completion_confirmed'] ?? null, [true, 1, '1', 'yes', 'on', 'true'], true)) {
                throw ValidationException::withMessages(['completion_confirmed' => 'Confirm that this interview took place before marking it completed.']);
            }
            if (! $interview->canBeCompleted()) {
                throw ValidationException::withMessages(['interview' => 'Only an ended, scheduled interview that was not declined or cancelled can be marked completed while the candidate is in Interview.']);
            }

            $previous = $this->snapshot($interview);
            $interview->forceFill(['status' => InterviewStatus::Completed, 'version' => $interview->version + 1])->save();
            $this->recordActivity($interview, $actor, 'interview_completed', ['previous' => $previous]);

            // Completion records the employer's review action; selection and
            // candidate communication remain separate explicit actions.
            return $interview->load('applicationForm.job');
        });
    }

    /** @param array<string, mixed> $data */
    private function parseDateTime(array $data, string $field, string $timezone): CarbonImmutable
    {
        if (empty($data['interview_date']) || empty($data[$field])) {
            throw ValidationException::withMessages([$field => 'Provide the interview date and start and end times.']);
        }

        $localTime = $data['interview_date'].' '.$data[$field];

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d H:i', $localTime, $timezone);
        } catch (Throwable) {
            throw ValidationException::withMessages([$field => 'Enter a valid interview date and time.']);
        }

        if (! $date || $date->format('Y-m-d H:i') !== $localTime) {
            throw ValidationException::withMessages([$field => 'This local interview time is not valid in the selected timezone.']);
        }

        return $date->utc();
    }

    private function lockApplication(ApplicationForm $application): ApplicationForm
    {
        Organization::whereKey($application->organization_id)->lockForUpdate()->firstOrFail();

        return ApplicationForm::query()->where('organization_id', $application->organization_id)
            ->lockForUpdate()->findOrFail($application->id);
    }

    private function lockInterview(Interview $interview): Interview
    {
        Organization::whereKey($interview->organization_id)->lockForUpdate()->firstOrFail();
        $application = ApplicationForm::query()->where('organization_id', $interview->organization_id)
            ->lockForUpdate()->findOrFail($interview->application_form_id);

        return Interview::query()->where('application_form_id', $application->id)
            ->lockForUpdate()->findOrFail($interview->id);
    }

    /** @param array<string, mixed> $data */
    private function assertEditable(Interview $interview, array $data): void
    {
        if (isset($data['version']) && (int) $data['version'] !== $interview->version) {
            throw ValidationException::withMessages(['version' => 'This interview was changed by another team member. Reload before editing.']);
        }

        if (! $interview->status->isActive()) {
            throw ValidationException::withMessages(['interview' => 'A cancelled or completed interview cannot be changed.']);
        }
    }

    /** @return array<string, mixed> */
    private function snapshot(Interview $interview): array
    {
        return [
            'mode' => $interview->mode->value,
            'start_at' => $interview->start_at->toIso8601String(),
            'end_at' => $interview->end_at->toIso8601String(),
            'timezone' => $interview->timezone,
            'status' => $interview->status->value,
            'candidate_response' => $interview->candidate_response->value,
            'version' => $interview->version,
        ];
    }

    /** @param array<string, mixed> $payload */
    private function recordActivity(Interview $interview, ?User $actor, string $action, array $payload = []): ActivityLog
    {
        return $this->activityLogger->record($interview->organization_id, $interview->applicationForm, $actor, 'interview', [
            ...$payload, 'action' => $action, 'interview_id' => $interview->id, 'interview' => $this->snapshot($interview),
        ]);
    }
}
