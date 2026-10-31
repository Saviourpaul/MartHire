<?php

namespace App\Policies;

use App\Enums\CandidatePipelineStage;
use App\Models\ApplicationForm;
use App\Models\Interview;
use App\Models\User;
use App\Support\OrganizationPermissions;

class InterviewPolicy
{
    public function __construct(private OrganizationPermissions $permissions) {}

    public function viewAny(User $user): bool
    {
        return $this->permissions->membership($user) !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Interview $interview): bool
    {
        $application = $interview->applicationForm;

        if (! $application) {
            return false;
        }

        return ($user->isApplicant() && $user->isActive() && $user->hasVerifiedEmail()
                && ! $user->trashed() && $application->user_id === $user->id)
            || $this->permissions->can($user, 'view_candidates', application: $application);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, ApplicationForm $application): bool
    {
        return $application->status === CandidatePipelineStage::Interview
            && $this->permissions->can($user, 'send_candidate_email', application: $application);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Interview $interview): bool
    {
        return $this->send($user, $interview);
    }

    public function reschedule(User $user, Interview $interview): bool
    {
        return $this->send($user, $interview);
    }

    public function cancel(User $user, Interview $interview): bool
    {
        return $this->send($user, $interview);
    }

    public function complete(User $user, Interview $interview): bool
    {
        return $this->manageCompletion($user, $interview) && $interview->canBeCompleted();
    }

    public function manageCompletion(User $user, Interview $interview): bool
    {
        return $interview->applicationForm !== null
            && $this->permissions->can($user, 'move_candidates', application: $interview->applicationForm);
    }

    public function send(User $user, Interview $interview): bool
    {
        return $interview->applicationForm !== null
            && $this->permissions->can($user, 'send_candidate_email', application: $interview->applicationForm);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Interview $interview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Interview $interview): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Interview $interview): bool
    {
        return false;
    }
}
