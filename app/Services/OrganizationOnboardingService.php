<?php

namespace App\Services;

use App\Models\OrganizationMember;
use App\Models\OrganizationOnboardingProgress;
use App\Support\OrganizationPermissions;
use Illuminate\Support\Facades\DB;

class OrganizationOnboardingService
{
    /** @return list<string> */
    public function stepsFor(OrganizationMember $membership): array
    {
        $steps = ['welcome'];
        $permissions = OrganizationPermissions::ROLE_PERMISSIONS[$membership->role->value] ?? [];

        if (in_array('hiring_settings', $permissions, true)) {
            $steps[] = 'profile';
        }

        if (in_array('manage_team', $permissions, true)) {
            $steps[] = 'team';
        }

        $steps[] = 'finish';

        return $steps;
    }

    public function initialize(OrganizationMember $membership): OrganizationOnboardingProgress
    {
        return OrganizationOnboardingProgress::query()->firstOrCreate(
            ['organization_member_id' => $membership->getKey()],
            ['current_step' => 'welcome', 'completed_steps' => [], 'skipped_steps' => []],
        );
    }

    /** @param array<string, string> $options */
    public function updateFocus(OrganizationOnboardingProgress $progress, string $focus, array $options): void
    {
        abort_unless(array_key_exists($focus, $options), 422);
        $progress->forceFill(['recruitment_focus' => $focus])->save();
    }

    public function reconcile(OrganizationMember $membership, OrganizationOnboardingProgress $progress): OrganizationOnboardingProgress
    {
        if ($progress->isComplete()) {
            return $progress;
        }

        $steps = $this->stepsFor($membership);
        if (in_array($progress->current_step, $steps, true)) {
            return $progress;
        }

        $done = array_unique([...($progress->completed_steps ?? []), ...($progress->skipped_steps ?? [])]);
        $next = collect($steps)->first(fn (string $step): bool => ! in_array($step, $done, true)) ?? 'finish';
        $progress->forceFill(['current_step' => $next])->save();

        return $progress->refresh();
    }

    /**
     * Persist a step transition under a row lock so duplicate form submissions
     * cannot skip steps or overwrite a concurrent device's progress.
     */
    public function advance(OrganizationMember $membership, string $step, bool $skipped = false): OrganizationOnboardingProgress
    {
        return DB::transaction(function () use ($membership, $step, $skipped): OrganizationOnboardingProgress {
            $progress = OrganizationOnboardingProgress::query()
                ->where('organization_member_id', $membership->getKey())->lockForUpdate()->firstOrFail();
            $steps = $this->stepsFor($membership);
            abort_unless($step !== 'finish', 409, 'Use the onboarding completion action to finish setup.');
            abort_unless(in_array($step, $steps, true), 403);
            abort_unless($progress->current_step === $step, 409, 'This onboarding step has already changed. Reload to continue.');

            $completed = $progress->completed_steps ?? [];
            $skippedSteps = $progress->skipped_steps ?? [];
            if ($skipped) {
                $skippedSteps = array_values(array_unique([...$skippedSteps, $step]));
            } else {
                $completed = array_values(array_unique([...$completed, $step]));
            }

            $nextIndex = array_search($step, $steps, true) + 1;
            $next = $steps[$nextIndex] ?? 'complete';
            $progress->forceFill([
                'completed_steps' => $completed,
                'skipped_steps' => $skippedSteps,
                'current_step' => $next,
                'completed_at' => $next === 'complete' ? now() : null,
                'dismissed_at' => null,
            ])->save();

            return $progress->refresh();
        });
    }

    /** Complete onboarding only after every role-specific step is handled. */
    public function complete(OrganizationMember $membership): OrganizationOnboardingProgress
    {
        return DB::transaction(function () use ($membership): OrganizationOnboardingProgress {
            $progress = OrganizationOnboardingProgress::query()
                ->where('organization_member_id', $membership->getKey())->lockForUpdate()->firstOrFail();
            $steps = $this->stepsFor($membership);
            $requiredSteps = array_slice($steps, 0, -1);
            $handledSteps = array_unique([
                ...($progress->completed_steps ?? []),
                ...($progress->skipped_steps ?? []),
            ]);

            abort_unless($progress->current_step === 'finish', 409, 'Complete the current onboarding step before finishing.');
            abort_unless(collect($requiredSteps)->every(fn (string $step): bool => in_array($step, $handledSteps, true)), 409, 'Complete or skip each setup step before finishing.');
            abort_unless(in_array('welcome', $progress->completed_steps ?? [], true), 409, 'Save your workspace preferences before finishing.');

            $progress->forceFill([
                'completed_steps' => array_values(array_unique([...($progress->completed_steps ?? []), 'finish'])),
                'current_step' => 'complete',
                'completed_at' => now(),
                'dismissed_at' => null,
            ])->save();

            return $progress->refresh();
        });
    }

    public function goBack(OrganizationMember $membership, string $step): OrganizationOnboardingProgress
    {
        return DB::transaction(function () use ($membership, $step): OrganizationOnboardingProgress {
            $progress = OrganizationOnboardingProgress::query()
                ->where('organization_member_id', $membership->getKey())->lockForUpdate()->firstOrFail();
            $steps = $this->stepsFor($membership);
            $currentIndex = array_search($progress->current_step, $steps, true);
            $requestedIndex = array_search($step, $steps, true);
            abort_unless($currentIndex !== false && $requestedIndex !== false && $requestedIndex === $currentIndex - 1, 409);
            $progress->forceFill(['current_step' => $step, 'dismissed_at' => null, 'completed_at' => null])->save();

            return $progress->refresh();
        });
    }

    public function requiresOnboarding(OrganizationOnboardingProgress $progress): bool
    {
        return ! $progress->isComplete();
    }

    /** @return array<string, string> */
    public function focusOptions(): array
    {
        return [
            'recruitment_lead' => 'Recruitment lead',
            'hr_people' => 'HR / People team',
            'recruiter' => 'Recruiter',
            'hiring_manager' => 'Hiring manager',
            'other' => 'Other',
        ];
    }
}
