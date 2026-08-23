<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Services\AdminDashboardService;
use App\Services\EmployerDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AdminDashboardService $adminDashboardService,
        private readonly EmployerDashboardService $employerDashboardService,
    ) {}

    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuspended()) {
            return $this->logoutSuspendedUser($request);
        }

        return match ($user->role) {
            UserRole::Admin => view('admin.Dashboard', $this->adminDashboardService->getOverview($request)),
            UserRole::Employer => view('employer.Dashboard', $this->employerDashboardService->getOverview($request, $user)),
            UserRole::Applicant => view('client.Dashboard'),
        };
    }

    public function adminAnalytics(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $period = $request->validate([
            'period' => ['nullable', 'in:12_months,30_days,7_days'],
        ])['period'] ?? '12_months';

        return response()->json($this->adminDashboardService->analytics($period));
    }

    public function adminActiveUsers(Request $request): JsonResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return response()->json($this->adminDashboardService->activeUsers());
    }

    private function logoutSuspendedUser(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status', 'Your account has been suspended. Please contact support.');
    }
}
