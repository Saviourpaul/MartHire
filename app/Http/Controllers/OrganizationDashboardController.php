<?php

namespace App\Http\Controllers;

use App\Services\OrganizationDashboardService;
use App\Support\LiveTables;
use App\Support\OrganizationContext;
use App\Support\OrganizationPermissions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationDashboardController extends Controller
{
    public function __construct(
        private readonly OrganizationDashboardService $dashboardService,
        private readonly OrganizationPermissions $permissions,
        private readonly OrganizationContext $context,
    ) {}

    public function index(Request $request): View|JsonResponse
    {
        $user = $request->user();
        $membership = $this->permissions->membership($user);

        abort_unless($membership, 403, 'You are not an active member of an organization.');

        return LiveTables::view($request, view('organisations.dashboard', [
            ...$this->dashboardService->getOverview($request, $user),
            'organization' => $this->context->organization(),
            'membership' => $membership,
            'permissions' => OrganizationPermissions::ROLE_PERMISSIONS[$membership->role->value] ?? [],
        ]), ['organization-dashboard']);
    }

    public function analytics(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->permissions->membership($user), 403);

        $period = $request->validate([
            'period' => ['nullable', 'in:12_months,30_days,7_days'],
        ])['period'] ?? '12_months';

        return response()->json($this->dashboardService->analytics($user, $period));
    }
}
