<?php

namespace App\Services;

use App\Enums\CandidatePipelineStage;
use App\Enums\InterviewCandidateResponse;
use App\Enums\InterviewStatus;
use App\Models\ApplicationForm;
use Illuminate\Validation\ValidationException;

/** Shared selection prerequisite for presentation, policies and locked transitions. */
class CandidateSelectionService
{
    public function eligible(ApplicationForm $application): bool
    {
        return $application->status === CandidatePipelineStage::Interview
            && $application->interviews()
                ->where('organization_id', $application->organization_id)
                ->where('status', InterviewStatus::Completed->value)
                ->where('candidate_response', '!=', InterviewCandidateResponse::Declined->value)
                ->where('end_at', '<=', now())
                ->whereColumn('end_at', '>', 'start_at')
                ->exists();
    }

    public function ensureEligible(ApplicationForm $application): void
    {
        if (! $this->eligible($application)) {
            throw ValidationException::withMessages([
                'stage' => 'Complete an interview for this candidate before confirming them as Selected.',
            ]);
        }
    }
}
