<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\RateLimiting\AbuseGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $guard = app(AbuseGuard::class);
        $cooldownKey = 'password-reset:'.Str::lower((string) $request->string('email'));
        $cooldown = (int) config('rate_limiting.limits.password_reset.cooldown_seconds', 60);

        // Identical response whether or not the account exists, so the flow never
        // reveals which emails are registered (account enumeration).
        $neutral = fn (): RedirectResponse => back()->with('status', __('passwords.sent'));

        // Honour a short per-email cooldown before sending another link.
        if ($guard->onCooldown($cooldownKey, $cooldown)) {
            return $neutral();
        }

        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            return redirect()->route('password.request')
                ->withInput($request->only('email'))
                ->withErrors([
                    'mail' => __('Unable to connect at the moment. An error occurred while processing your request. Please try again later.'),
                ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            $guard->touchCooldown($cooldownKey, $cooldown);
        }

        return $neutral();
    }
}
