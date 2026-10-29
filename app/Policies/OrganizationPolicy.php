<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Support\OrganizationContext;
use App\Support\OrganizationPermissions;

class OrganizationPolicy
{
    public function __construct(private OrganizationContext $context, private OrganizationPermissions $permissions) {}

    public function manageTeam(User $user, Organization $organization): bool
    {
        return $organization->id === $this->context->organizationId() && $this->permissions->can($user, 'manage_team');
    }

    public function viewTeam(User $user, Organization $organization): bool
    {
        return $this->manageTeam($user, $organization);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $organization->id === $this->context->organizationId() && $this->permissions->can($user, 'hiring_settings');
    }
}
