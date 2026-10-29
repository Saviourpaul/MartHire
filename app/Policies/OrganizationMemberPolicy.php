<?php

namespace App\Policies;

use App\Enums\OrganizationRole;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Support\OrganizationPermissions;

class OrganizationMemberPolicy
{
    public function __construct(private OrganizationPermissions $permissions) {}

    public function update(User $user, OrganizationMember $member): bool
    {
        $actor = $this->permissions->membership($user);

        return $actor && $actor->organization_id === $member->organization_id
            && $this->permissions->can($user, 'manage_team')
            && ($actor->role === OrganizationRole::Owner || $member->role !== OrganizationRole::Owner);
    }

    public function delete(User $user, OrganizationMember $member): bool
    {
        return $this->update($user, $member);
    }
}
