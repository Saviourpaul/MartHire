<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request, EmailVerificationService $emailVerification): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        if ($request->user()->isSuspended()) {
            return redirect()->route('verification.notice')
                ->withErrors(['verification' => 'Your account has been suspended. Please contact support.']);
        }

        if (! $emailVerification->sendCode($request->user())) {
            return redirect()->route('verification.notice')
                ->withErrors(['verification' => 'We could not send your verification code. Please try Resend Code shortly.']);
        }

        return redirect()->route('verification.notice')->with('status', 'verification-code-sent');
    }
}
