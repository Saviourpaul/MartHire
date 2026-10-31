<?php

namespace App\Services;

use App\Enums\CandidatePipelineStage;
use App\Enums\InterviewCandidateResponse;
use App\Enums\InterviewMode;
use App\Enums\InterviewTemplateType;
use App\Models\ActivityLog;
use App\Models\ApplicationForm;
use App\Models\ApplicationStatusHistory;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Presentation data for an authorized employer; never exposes raw audit payloads. */
class CandidateReviewService
{
    private const ACTIVITY_LIMIT = 50;

    public function build(ApplicationForm $application, User $viewer): array
    {
        Gate::forUser($viewer)->authorize('viewForEmployer', $application);
        $application->loadMissing('applicant');
        $histories = $application->statusHistories()->with('changedBy')->latest('created_at')->latest('id')->limit(self::ACTIVITY_LIMIT + 1)->get();

        return [
            'profile' => $this->profile($application),
            'pipeline' => $this->pipeline($application, $histories),
            'activity' => $this->activity($application, $viewer, $histories),
        ];
    }

    private function profile(ApplicationForm $application): array
    {
        $values = [];
        foreach (['first_name', 'middle_name', 'last_name', 'email', 'phone', 'gender', 'marital_status',
            'address', 'country_code', 'country', 'state', 'city', 'local_government_area', 'profile_image_path'] as $field) {
            $snapshot = $application->getAttribute($field);
            $fallback = $application->applicant?->getAttribute($field);
            $values[$field] = is_string($snapshot) && trim($snapshot) !== '' ? trim($snapshot)
                : (is_string($fallback) && trim($fallback) !== '' ? trim($fallback) : null);
        }
        $name = trim(implode(' ', array_filter([$values['first_name'], $values['middle_name'], $values['last_name']])));
        $birthDate = $application->date_of_birth ?? $application->applicant?->date_of_birth;
        // Resolve legacy snapshots as a pair, rather than combining a submitted
        // country name with a country code from a later account edit.
        $snapshotCode = trim((string) $application->country_code);
        $snapshotName = trim((string) $application->country);
        $country = app(CountryDisplayService::class)->present(
            $snapshotCode !== '' || $snapshotName !== '' ? $snapshotCode : $values['country_code'],
            $snapshotCode !== '' || $snapshotName !== '' ? $snapshotName : $values['country'],
        );
        $values['country_code'] = $country['code'];
        $values['country'] = $country['code'] || $snapshotName !== '' || $values['country'] ? $country['name'] : null;
        $locality = array_unique(array_filter([$values['city'] ?: $values['local_government_area'], $values['state']]));
        $location = array_unique(array_filter([
            $values['city'] ?: $values['local_government_area'], $values['state'], $values['country'],
        ]));
        $avatarUrl = $this->avatarUrl($values['profile_image_path']);
        unset($values['profile_image_path']);

        return [
            ...$values,
            'name' => $name ?: 'Candidate',
            'initials' => mb_strtoupper(mb_substr($values['first_name'] ?? 'C', 0, 1).mb_substr($values['last_name'] ?? '', 0, 1)),
            'avatar_url' => $avatarUrl,
            'date_of_birth' => $birthDate?->format('j M Y'),
            'gender' => $values['gender'] ? ucfirst($values['gender']) : null,
            'marital_status' => $values['marital_status'] ? ucfirst($values['marital_status']) : null,
            'location' => implode(', ', $location),
            'location_without_country' => implode(', ', $locality),
            'country_data' => $country,
        ];
    }

    private function avatarUrl(?string $path): ?string
    {
        // Only a relative storage key may reach Storage::url(), never a supplied URL.
        if ($path === null || preg_match('/[\x00-\x1f\x7f:%\\\\?#]/', $path) || str_starts_with($path, '/')
            || in_array('..', explode('/', $path), true) || in_array('.', explode('/', $path), true)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function pipeline(ApplicationForm $application, Collection $histories): array
    {
        $stages = [CandidatePipelineStage::Submitted, CandidatePipelineStage::Shortlisted, CandidatePipelineStage::Interview, CandidatePipelineStage::Selected];
        $currentIndex = array_search($application->status, $stages, true);
        $rejected = $application->status === CandidatePipelineStage::Rejected;
        $reached = $histories->flatMap(fn (ApplicationStatusHistory $history): array => [$history->from_status, $history->to_status]);

        return [
            'stage' => $application->status->value,
            'label' => $application->status->label(),
            'is_terminal' => $application->status->isTerminal(),
            'steps' => array_map(function (CandidatePipelineStage $stage, int $index) use ($application, $currentIndex, $rejected, $reached): array {
                $finalRejected = $rejected && $stage === CandidatePipelineStage::Selected;
                $current = $application->status === $stage || $finalRejected;
                $complete = $rejected ? $reached->contains($stage) : $index < $currentIndex;

                return [
                    'stage' => $finalRejected ? CandidatePipelineStage::Rejected->value : $stage->value,
                    'label' => $finalRejected ? CandidatePipelineStage::Rejected->label() : $stage->label(),
                    'state' => $finalRejected ? 'rejected' : ($current ? 'current' : ($complete ? 'complete' : 'upcoming')),
                    'current' => $current,
                    'complete' => $complete,
                ];
            }, $stages, array_keys($stages)),
        ];
    }

    private function activity(ApplicationForm $application, User $viewer, Collection $histories): array
    {
        $canViewDocuments = Gate::forUser($viewer)->allows('viewDocuments', $application);
        $types = ['interview'];
        if ($canViewDocuments) {
            $types[] = 'document_view';
        }
        if (Gate::forUser($viewer)->allows('send_candidate_email', $application)) {
            $types[] = 'email';
        }
        $logs = $application->activityLogs()->whereIn('type', $types)->with('actor')
            ->latest('created_at')->latest('id')->limit(self::ACTIVITY_LIMIT + 1)->get();
        $documents = $canViewDocuments
            ? ($application->relationLoaded('documents') ? $application->documents : $application->documents()->get())->keyBy('id')
            : collect();
        $entries = $histories->map(fn (ApplicationStatusHistory $history): array => [
            'id' => 'history-'.$history->id,
            'type' => 'pipeline',
            'title' => $history->from_status === null && $history->to_status === CandidatePipelineStage::Submitted
                ? 'Application submitted' : 'Moved to '.$history->to_status->label(),
            'detail' => $history->from_status ? $history->from_status->label().' → '.$history->to_status->label() : null,
            'remarks' => $history->remarks,
            'actor' => $history->changedBy ? trim($history->changedBy->first_name.' '.$history->changedBy->last_name) : 'System',
            'at' => $history->created_at,
        ])->concat($logs->map(function (ActivityLog $log) use ($documents): array {
            $payload = $log->payload;
            $title = 'Interview updated';
            $detail = null;
            if ($log->type === 'interview') {
                $title = match ($payload['action'] ?? null) {
                    'interview_scheduled' => 'Interview scheduled',
                    'interview_rescheduled' => 'Interview rescheduled',
                    'interview_cancelled' => 'Interview cancelled',
                    'interview_completed' => 'Interview completed',
                    'interview_response' => 'Interview response received',
                    default => 'Interview updated',
                };
                $detail = $this->interviewDetail($payload);
            } elseif ($log->type === 'document_view') {
                $title = ($payload['action'] ?? null) === 'download' ? 'Document downloaded' : 'Document previewed';
                $document = $documents->get($payload['document_id'] ?? null);
                $detail = $document?->displayLabel();
            } elseif ($log->type === 'email') {
                $title = match ($payload['action'] ?? null) {
                    'candidate_email_queued' => 'Candidate email queued',
                    'candidate_email_retried' => 'Candidate email retried',
                    'candidate_email_sent' => 'Candidate email sent',
                    'candidate_email_failed' => 'Candidate email failed',
                    default => 'Candidate email updated',
                };
                $template = $payload['template_type'] ?? null;
                $detail = is_string($template) ? InterviewTemplateType::tryFrom($template)?->label() : null;
            }

            return [
                'id' => 'log-'.$log->id, 'type' => $log->type, 'title' => $title, 'detail' => $detail, 'remarks' => null,
                'actor' => is_string($payload['actor_name'] ?? null) ? $payload['actor_name']
                    : ($log->actor ? trim($log->actor->first_name.' '.$log->actor->last_name) : 'System'),
                'at' => $log->created_at,
            ];
        }))->sort(function (array $first, array $second): int {
            return ($second['at']?->getTimestamp() ?? 0) <=> ($first['at']?->getTimestamp() ?? 0)
                ?: strnatcmp($second['id'], $first['id']);
        });

        return ['entries' => $entries->take(self::ACTIVITY_LIMIT)->values()->all(), 'has_more' => $entries->count() > self::ACTIVITY_LIMIT, 'limit' => self::ACTIVITY_LIMIT];
    }

    private function interviewDetail(array $payload): ?string
    {
        $snapshot = is_array($payload['interview'] ?? null) ? $payload['interview'] : [];
        if (($payload['action'] ?? null) === 'interview_response') {
            $response = $snapshot['candidate_response'] ?? null;

            return is_string($response) ? InterviewCandidateResponse::tryFrom($response)?->label() : null;
        }
        $mode = $snapshot['mode'] ?? null;
        $parts = [is_string($mode) ? InterviewMode::tryFrom($mode)?->label() : null];
        try {
            if (is_string($snapshot['start_at'] ?? null) && is_string($snapshot['timezone'] ?? null)) {
                $parts[] = CarbonImmutable::parse($snapshot['start_at'])->timezone($snapshot['timezone'])->format('j M Y, g:ia').' ('.$snapshot['timezone'].')';
            }
        } catch (Throwable) {
            // Old or malformed audit metadata must not make a candidate unreadable.
        }

        return implode(' · ', array_filter($parts)) ?: null;
    }
}
