<?php

namespace App\Services;

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use App\Support\OrganizationContext;
use Illuminate\Support\Facades\DB;

class OrganizationProvisioner
{
    public function __construct(private OrganizationContext $context) {}

    public function ensureForEmployer(User $employer): Organization
    {
        abort_unless($employer->isOrganization(), 403);

        return DB::transaction(function () use ($employer): Organization {
            User::query()->whereKey($employer->id)->lockForUpdate()->firstOrFail();
            $membership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                ->where('user_id', $employer->id)->where('status', 'active')->first();

            if ($membership) {
                return $membership->organization;
            }

            abort_if(OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                ->withTrashed()->where('user_id', $employer->id)->exists(), 403);

            $organization = Organization::create(['name' => mb_substr(trim($employer->first_name.' '.$employer->last_name).' Organization', 0, 255)]);
            $this->context->runInOrganization($organization, fn () => OrganizationMember::create([
                'organization_id' => $organization->id, 'user_id' => $employer->id,
                'role' => OrganizationRole::Owner, 'status' => 'active',
            ]));

            return $organization;
        });
    }
}
