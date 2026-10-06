<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Mail\WelcomeEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VerifyEmailController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Already verified: do nothing, so clicking the link twice is harmless
        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        // Verify and activate together, or neither
        DB::transaction(function () use ($user) {
            $user->markEmailAsVerified();

            $user->forceFill([
                'status'      => UserStatus::Active,
                'approved_at' => now(),
            ])->save();
        });

        event(new Verified($user));

        // The account is already activated. A mail failure must not break that.
        try {
            Mail::to($user->email)->send(new WelcomeEmail($user));
        } catch (\Throwable $e) {
            Log::error('Welcome email failed', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        return redirect()
            ->intended(route('dashboard', absolute: false))
            ->with('success', 'Your email has been verified. Welcome to Marthire.');
    }
}