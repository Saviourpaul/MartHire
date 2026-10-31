<?php

namespace App\Policies;

use App\Models\ApplicationForm;
use App\Models\User;
use App\Services\CandidateSelectionService;
use App\Support\OrganizationPermissions;

class ApplicationFormPolicy
{
    public function __construct(private OrganizationPermissions $permissions) {}

    public function view(User $user, ApplicationForm $application): bool
    {
        return $user->isApplicant() && $application->user_id === $user->id;
    }

    public function managePipeline(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'move_candidates', application: $application);
    }

    public function select(User $user, ApplicationForm $application): bool
    {
        return $this->selectCandidates($user, $application)
            && app(CandidateSelectionService::class)->eligible($application);
    }

    /** Permission check kept separate from the interview prerequisite. */
    public function selectCandidates(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'select_candidates', application: $application);
    }

    public function viewForEmployer(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'view_candidates', application: $application);
    }

    public function sendCandidateEmail(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'send_candidate_email', application: $application);
    }

    public function viewDocuments(User $user, ApplicationForm $application): bool
    {
        return $user->isAdmin() || ($user->isApplicant() && $application->user_id === $user->id)
            || $this->permissions->can($user, 'view_documents', application: $application);
    }

    public function assign(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'assign_candidates', application: $application);
    }

    public function addNote(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'add_notes', application: $application);
    }

    public function submitEvaluation(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'submit_evaluations', application: $application);
    }

    public function viewEvaluations(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'view_all_evaluations', application: $application);
    }

    public function createTask(User $user, ApplicationForm $application): bool
    {
        return $this->permissions->can($user, 'create_tasks', application: $application);
    }
}
