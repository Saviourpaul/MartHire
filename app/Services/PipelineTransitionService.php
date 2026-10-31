<?php

namespace App\Services;

use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PipelineTransitionService
{
    public function __construct(private ActivityLogger $activityLogger, private WorkflowNotificationService $notifications) {}

    public function moveCandidate(ApplicationForm $application, User $employer, CandidatePipelineStage $stage, ?string $remarks = null, bool $selectionConfirmed = false): ApplicationForm
    {
        return DB::transaction(function () use ($application, $employer, $stage, $remarks, $selectionConfirmed): ApplicationForm {
            Organization::whereKey($application->organization_id)->lockForUpdate()->firstOrFail();
            $application = ApplicationForm::query()->lockForUpdate()->findOrFail($application->id);
            Gate::forUser($employer)->authorize('managePipeline', $application);

            if ($stage === CandidatePipelineStage::Selected) {
                Gate::forUser($employer)->authorize('selectCandidates', $application);
            }

            $currentStage = $application->status;

            if (! $currentStage->canTransitionTo($stage)) {
                throw ValidationException::withMessages([
                    'stage' => "A candidate cannot move from {$currentStage->label()} to {$stage->label()}.",
                ]);
            }

            if ($stage === CandidatePipelineStage::Selected) {
                // Interview mutation and pipeline movement share organization /
                // application locks, so this check cannot race completion/cancel.
                app(CandidateSelectionService::class)->ensureEligible($application);
                if (! $selectionConfirmed) {
                    throw ValidationException::withMessages([
                        'selection_confirmed' => 'Explicitly confirm this candidate as Selected to continue.',
                    ]);
                }
            }

            $movedAt = now();
            $application->update(['status' => $stage, 'reviewed_by' => $employer->id, 'reviewed_at' => $movedAt, 'employer_remarks' => $remarks]);
            $history = $application->statusHistories()->create([
                'from_status' => $currentStage, 'to_status' => $stage, 'changed_by' => $employer->id,
                'remarks' => $remarks, 'created_at' => $movedAt,
            ]);
            $this->activityLogger->record($application->organization_id, $application, $employer, 'pipeline', [
                'from_stage' => $currentStage->value, 'to_stage' => $stage->value,
            ]);
            $this->notifications->applicationStatusChanged($application, $history->id);

            return $application->fresh(['job', 'applicant', 'documents', 'statusHistories.changedBy']);
        });
    }
}
