<?php

namespace App\Http\Middleware;

use App\Services\OrganizationOnboardingService;
use App\Support\OrganizationPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationOnboardingIsComplete
{
    public function __construct(
        private readonly OrganizationPermissions $permissions,
        private readonly OrganizationOnboardingService $onboarding,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->isOrganization() || ! $user->hasVerifiedEmail() || ! $user->isActive()
            || $request->routeIs('organisations.onboarding.*')) {
            return $next($request);
        }

        $membership = $this->permissions->membership($user);
        if (! $membership) {
            return $next($request);
        }

        $progress = $this->onboarding->initialize($membership);
        if ($this->onboarding->requiresOnboarding($progress)) {
            return to_route('organisations.onboarding.show');
        }

        return $next($request);
    }
}
