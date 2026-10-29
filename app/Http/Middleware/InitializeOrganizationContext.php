<?php

namespace App\Http\Middleware;

use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Services\OrganizationProvisioner;
use App\Support\OrganizationContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InitializeOrganizationContext
{
    public function __construct(private OrganizationContext $context, private OrganizationProvisioner $provisioner) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $this->context->clear();
        $user = $request->user();

        if (! $user || ! $user->isOrganization()) {
            $this->context->set(null, true);

            return $next($request);
        }

        $memberships = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->with('organization')->where('user_id', $user->id)->where('status', 'active')->orderBy('id')->get();

        if (! $request->routeIs('team-invitations.*') && $memberships->isEmpty() && ! OrganizationMember::withoutGlobalScope(OrganizationScope::class)
            ->withTrashed()->where('user_id', $user->id)->exists()) {
            $this->provisioner->ensureForEmployer($user);
            $memberships = OrganizationMember::withoutGlobalScope(OrganizationScope::class)
                ->with('organization')->where('user_id', $user->id)->where('status', 'active')->get();
        }

        $membership = $memberships->firstWhere('organization_id', (int) $request->session()->get('active_organization_id'))
            ?? $memberships->first();
        $organization = $membership?->organization;
        $this->context->set($organization, $request->routeIs('home', 'Browse-jobs', 'client.jobs-listings', 'job-details', 'applications.create'));
        $request->attributes->set('organization', $organization);
        $request->attributes->set('organizationMemberships', $memberships);

        if ($organization) {
            $request->session()->put('active_organization_id', $organization->id);
        } else {
            $request->session()->forget('active_organization_id');
        }

        return $next($request);
    }
}
