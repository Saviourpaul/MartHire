<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    public function __invoke(Request $request, EmailVerificationService $emailVerification): RedirectResponse|View
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect()->intended(route('dashboard', absolute: false))
            : view('auth.verify-email', [
                'resendCooldown' => $emailVerification->resendCooldown($request->user()),
                'deliveryFailed' => $request->user()->email_verification_failed_at !== null,
            ]);
    }
}
