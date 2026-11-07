<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user instanceof User && $user->isActive() && $user->hasVerifiedEmail() && ! $user->trashed()
        && (int) $user->id === (int) $id;
});

Broadcast::channel('admins', function (User $user): bool {
    return $user->isAdmin() && $user->isActive() && $user->hasVerifiedEmail() && ! $user->trashed();
});

Broadcast::channel('organizations.{organization}', function (User $user, int $organization): bool {
    if (! $user->isOrganization() || ! $user->isActive() || ! $user->hasVerifiedEmail() || $user->trashed()) {
        return false;
    }

    return OrganizationMember::withoutGlobalScope(OrganizationScope::class)
        ->where('organization_id', $organization)
        ->where('user_id', $user->id)
        ->where('status', 'active')
        ->whereNull('deleted_at')
        ->exists();
});
