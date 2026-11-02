<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AccountGreetingService;
use App\Services\AdminDashboardService;
use App\Support\LiveTables;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function __construct(private readonly AdminDashboardService $dashboardService) {}

    public function index(Request $request, AccountGreetingService $greetings): View|JsonResponse
    {
        $greeting = $greetings->forUser($request->user());

        return LiveTables::view($request, view('admin.dashboard', [
            ...$this->dashboardService->getOverview($request, LiveTables::requested($request)),
            'greeting' => $greeting['message'],
            'greetingEmoji' => $greeting['emoji'],
        ]), ['admin-dashboard']);
    }

    public function analytics(Request $request): JsonResponse
    {
        $period = $request->validate([
            'period' => ['nullable', 'in:12_months,30_days,7_days'],
        ])['period'] ?? '12_months';

        return response()->json($this->dashboardService->analytics($period));
    }

    public function activeUsers(): JsonResponse
    {
        return response()->json($this->dashboardService->activeUsers());
    }
}
