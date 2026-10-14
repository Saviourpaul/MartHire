<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationOwnerGuard
{
    public function protect(User $user, Closure $operation): mixed
    {
        return DB::transaction(function () use ($user, $operation): mixed {
            $organizationIds = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                ->where('user_id', $user->id)->where('role', 'owner')->where('status', 'active')
                ->orderBy('organization_id')->pluck('organization_id');

            foreach ($organizationIds as $organizationId) {
                Organization::whereKey($organizationId)->lockForUpdate()->firstOrFail();
                $hasOtherOwner = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                    ->where('organization_id', $organizationId)->where('user_id', '<>', $user->id)
                    ->where('role', 'owner')->where('status', 'active')
                    ->whereHas('user', fn ($users) => $users->where('status', 'active')->where('role', 'employer')
                        ->whereNull('users.deleted_at')->whereNotNull('email_verified_at'))->exists();

                if (! $hasOtherOwner) {
                    throw ValidationException::withMessages(['user' => 'Assign another active Owner before removing or deactivating this account.']);
                }
            }

            return $operation();
        });
    }
}
