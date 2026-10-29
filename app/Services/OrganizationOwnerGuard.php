<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Closure;
use Illuminate\Database\Eloquent\Collection;
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
                $hasOtherOwner = $this->eligibleOwners((int) $organizationId, $user->id)->isNotEmpty();

                if (! $hasOtherOwner) {
                    throw ValidationException::withMessages(['user' => 'Assign another active Owner before removing or deactivating this account.']);
                }
            }

            return $operation();
        });
    }

    /** @return Collection<int, OrganizationMember> */
    public function eligibleOwners(int $organizationId, ?int $exceptUserId = null): Collection
    {
        return OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->join('users as owner_accounts', 'owner_accounts.id', '=', 'organization_members.user_id')
            ->where('organization_members.organization_id', $organizationId)
            ->where('organization_members.role', 'owner')->where('organization_members.status', 'active')
            ->when($exceptUserId !== null, fn ($query) => $query->where('organization_members.user_id', '<>', $exceptUserId))
            ->where('owner_accounts.status', 'active')->where('owner_accounts.role', 'organization')
            ->whereNull('owner_accounts.deleted_at')->whereNotNull('owner_accounts.email_verified_at')
            ->orderBy('owner_accounts.id')->select('organization_members.*')->lockForUpdate()->get();
    }
}
