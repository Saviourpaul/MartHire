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
    public function __construct(
        private OrganizationContext $context,
        private WorkspaceUpdateBroadcaster $workspaceUpdates,
        private OrganizationOnboardingService $onboarding,
    ) {}

    public function ensureForEmployer(User $employer, ?string $organizationName = null): Organization
    {
        abort_unless($employer->isOrganization() && $employer->isActive() && $employer->hasVerifiedEmail() && ! $employer->trashed(), 403);

        $created = false;
        $organization = DB::transaction(function () use ($employer, $organizationName, &$created): Organization {
            User::query()->whereKey($employer->id)->lockForUpdate()->firstOrFail();
            $membership = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                ->where('user_id', $employer->id)->where('status', 'active')->first();

            if ($membership) {
                return $membership->organization;
            }

            abort_if(OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                ->withTrashed()->where('user_id', $employer->id)->exists(), 403);

            $name = trim((string) $organizationName);
            $name = $name !== '' ? $name : trim($employer->first_name.' '.$employer->last_name).' Organization';
            $organization = Organization::create(['name' => mb_substr($name, 0, 255)]);
            $created = true;
            $membership = $this->context->runInOrganization($organization, fn () => OrganizationMember::create([
                'organization_id' => $organization->id, 'user_id' => $employer->id,
                'role' => OrganizationRole::Owner, 'status' => 'active',
            ]));
            $this->onboarding->initialize($membership);

            return $organization;
        });

        if ($created) {
            $this->workspaceUpdates->admins(['organizations', 'users'], 'An organization workspace was created.', 'info');
        }

        return $organization;
    }
}
