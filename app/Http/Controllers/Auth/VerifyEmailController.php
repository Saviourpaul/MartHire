<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyEmailCodeRequest;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerifyEmailController extends Controller
{
    public function __invoke(
        VerifyEmailCodeRequest $request,
        EmailVerificationService $emailVerification,
    ): RedirectResponse {
        if (! $emailVerification->verifyCode($request->user(), $request->validated('code'))) {
            return $this->logoutSuspendedUser($request);
        }

        $request->session()->regenerate();

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
