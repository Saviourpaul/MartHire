<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    public function __invoke(
        EmailVerificationRequest $request,
        EmailVerificationService $emailVerification,
    ): RedirectResponse
    {
        if (! $emailVerification->verify($request->user())) {
            return $this->logoutSuspendedUser($request);
        }

        return redirect()
            ->intended(route('dashboard', absolute: false))
            ->with('success', 'Your email has been verified. Welcome to Marthire.');
    }

    private function logoutSuspendedUser(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors(['email' => 'Your account has been suspended. Please contact support.']);
    }
}
