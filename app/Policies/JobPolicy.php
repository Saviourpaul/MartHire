<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    public function update(User $user, Job $job): bool
    {
        return $user->isEmployer() && $job->employer_id === $user->id;
    }

    public function close(User $user, Job $job): bool
    {
        return $this->update($user, $job);
    }

    public function viewModeration(User $user, Job $job): bool
    {
        return $user->isAdmin();
    }

    public function suspend(User $user, Job $job): bool
    {
        return $user->isAdmin();
    }

    public function deleteAsModerator(User $user, Job $job): bool
    {
        return $user->isAdmin();
    }
}
