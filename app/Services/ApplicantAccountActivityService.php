<?php

namespace App\Services;

use App\Models\SettingsAuditEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/** Coordinates durable admin notices and safe workspace invalidations for applicant account activity. */
class ApplicantAccountActivityService
{
    public function __construct(
        private WorkflowNotificationService $notifications,
        private WorkspaceUpdateBroadcaster $workspaceUpdates,
    ) {}

    public function registered(User $applicant): void
    {
        if (! $applicant->isApplicant()) {
            return;
        }

        try {
            $this->notifications->applicantRegistered($applicant);
            $this->workspaceUpdates->admins(
                ['users', 'notifications'],
                'A new applicant has registered.',
                'info',
            );
        } catch (Throwable $exception) {
            $this->logFailure($applicant, $exception);
        }
    }

    /** @param list<string> $changedFields Field names only; values are deliberately never audited or broadcast. */
    public function profileUpdated(User $applicant, array $changedFields): void
    {
        $changedFields = array_values(array_unique(array_filter($changedFields, 'is_string')));
        if (! $applicant->isApplicant() || $changedFields === []) {
            return;
        }

        try {
            DB::transaction(function () use ($applicant, $changedFields): void {
                $audit = SettingsAuditEvent::query()->create([
                    'actor_id' => $applicant->id,
                    'user_id' => $applicant->id,
                    'scope' => 'personal',
                    'action' => 'applicant_profile_updated',
                    'changes' => ['fields' => $changedFields],
                ]);
                $this->notifications->applicantProfileUpdated($applicant, (int) $audit->id);
            }, 3);

            $this->workspaceUpdates->admins(
                ['users', 'notifications'],
                'An applicant updated their account details.',
                'info',
            );
        } catch (Throwable $exception) {
            $this->logFailure($applicant, $exception);
        }
    }

    private function logFailure(User $applicant, Throwable $exception): void
    {
        Log::warning('Applicant account activity notification could not be recorded; the account change remains saved.', [
            'user_id' => $applicant->id,
            'exception' => $exception::class,
        ]);
    }
}
