<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
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

        return $this->sendResetLink($request, (string) $request->input('email'));
    }

    /**
     * Send a reset link to the signed-in user's registered email address.
     */
    public function storeForAuthenticatedUser(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $this->sendResetLink($request, $user->getEmailForPasswordReset(), authenticated: true);
    }

    private function sendResetLink(Request $request, string $email, bool $authenticated = false): RedirectResponse
    {
        $guard = app(AbuseGuard::class);
        $email = trim($email);
        $cooldownKey = 'password-reset:'.Str::lower($email);
        $cooldown = (int) config('rate_limiting.limits.password_reset.cooldown_seconds', 60);

        // Identical response whether or not the account exists, so the flow never
        // reveals which emails are registered (account enumeration).
        $neutral = fn (): RedirectResponse => back()->with('status', __('passwords.sent'));

        // Honour a short per-email cooldown before sending another link.
        if ($guard->onCooldown($cooldownKey, $cooldown)) {
            return $authenticated
                ? back()->with('status', 'password-reset-link-cooldown')
                : $neutral();
        }

        try {
            $status = Password::sendResetLink(['email' => $email]);
        } catch (TransportExceptionInterface $exception) {
            report($exception);

            if ($authenticated) {
                return back()->withErrors([
                    'password_reset' => __('Unable to connect at the moment. An error occurred while processing your request. Please try again later.'),
                ]);
            }

            return redirect()->route('password.request')
                ->withInput(['email' => $email])
                ->withErrors([
                    'mail' => __('Unable to connect at the moment. An error occurred while processing your request. Please try again later.'),
                ]);
        }

        if ($status === Password::RESET_LINK_SENT) {
            $guard->touchCooldown($cooldownKey, $cooldown);

            return $authenticated
                ? back()->with('status', 'password-reset-link-sent')
                : $neutral();
        }

        if ($authenticated) {
            if ($status === Password::RESET_THROTTLED) {
                return back()->with('status', 'password-reset-link-cooldown');
            }

            return back()->withErrors([
                'password_reset' => __('Unable to send a password reset link right now. Please try again later.'),
            ]);
        }

        return $neutral();
    }
}
