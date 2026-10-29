<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Generic `/dashboard` entry point. Every login/registration/verification
 * redirect keeps pointing here via route('dashboard'); this controller's
 * only job is to send the user on to their role's real dashboard route.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->isSuspended()) {
            return $this->logoutSuspendedUser($request);
        }

        return redirect()->route(match ($user->role) {
            UserRole::Admin => 'admin.dashboard',
            UserRole::Organization => 'organisations.dashboard',
            UserRole::Applicant => 'client.dashboard',
        });
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
