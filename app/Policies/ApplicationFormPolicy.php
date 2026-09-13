<?php

namespace App\Policies;

use App\Models\ApplicationForm;
use App\Models\User;

class ApplicationFormPolicy
{
    public function view(User $user, ApplicationForm $application): bool
    {
        return $user->isApplicant() && $application->user_id === $user->id;
    }

    public function managePipeline(User $user, ApplicationForm $application): bool
    {
        $application->loadMissing('job');

        return $user->isEmployer() && $application->job->employer_id === $user->id;
    }
}
