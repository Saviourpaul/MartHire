<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;
use App\Support\OrganizationPermissions;

class JobPolicy
{
    public function __construct(private OrganizationPermissions $permissions) {}

    public function view(User $user, Job $job): bool
    {
        return $this->permissions->can($user, 'view_jobs', $job);
    }

    public function create(User $user): bool
    {
        return $this->permissions->can($user, 'create_jobs');
    }

    public function update(User $user, Job $job): bool
    {
        return $this->permissions->can($user, 'edit_jobs', $job);
    }

    public function delete(User $user, Job $job): bool
    {
        return $this->permissions->can($user, 'delete_jobs', $job);
    }

    public function manageTeam(User $user, Job $job): bool
    {
        return $this->permissions->can($user, 'assign_job_members', $job);
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
