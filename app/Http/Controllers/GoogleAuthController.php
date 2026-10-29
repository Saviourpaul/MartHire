<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Models\User;
use App\Services\EmailVerificationService;
use App\Services\PlatformSettingsService;
use App\Support\ExpirationResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Throwable;

class GoogleAuthController extends Controller
{
    /**function: googleLogin
     * Direct user to Google for authentication.
     *
     */
    public function googleLogin(): \Symfony\Component\HttpFoundation\RedirectResponse
    {
        return Socialite::driver('google')
            ->with(['prompt' => 'select_account'])
            ->redirect();
    }

    public function googleAuthentication(Request $request, EmailVerificationService $emailVerification, PlatformSettingsService $settings): RedirectResponse|Response|JsonResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $googleId = (string) $googleUser->getId();
            $email = strtolower((string) $googleUser->getEmail());
            $verified = filter_var($googleUser->user['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

            if (! $email || ! $verified) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Your Google email is not verified.',
                ]);
            }

            $user = User::where('google_id', $googleId)->first();

            if ($user && strcasecmp($user->email, $email) !== 0) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Your Google email does not match this account. Sign in with your registered email address.',
                ]);
            }

            if (! $user) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    if ($user->google_id && $user->google_id !== $googleId) {
                        return redirect()->route('login')->withErrors([
                            'email' => 'This email is already linked to another Google account.',
                        ]);
                    }

                    $update = ['google_id' => $googleId];

                    // Local email was never verified: Google has now proved ownership.
                    // Preserve the existing account-linking policy of rotating a
                    // pre-registration password before activating the account.
                    if (! $user->email_verified_at) {
                        $update['password'] = Hash::make(Str::random(40));
                    }

                    $user->update($update);
                } else {
                    $user = DB::transaction(function () use ($settings, $googleUser, $googleId, $email): User {
                        $settings->lockRegistrationSettings();
                        $existing = User::query()->where('google_id', $googleId)->orWhere('email', $email)->lockForUpdate()->first();
                        if ($existing) {
                            if (strcasecmp($existing->email, $email) !== 0 || ($existing->google_id && $existing->google_id !== $googleId)) {
                                throw ValidationException::withMessages(['email' => 'This email is already linked to another Google account.']);
                            }
                            $update = ['google_id' => $googleId];
                            if (! $existing->email_verified_at) {
                                $update['password'] = Hash::make(Str::random(40));
                            }
                            $existing->update($update);

                            return $existing;
                        }
                        $settings->assertPublicRegistrationEnabled();

                        return User::create([
                            'first_name' => $googleUser->user['given_name'] ?? $googleUser->getName(),
                            'last_name' => $googleUser->user['family_name'] ?? '',
                            'email' => $email,
                            'google_id' => $googleId,
                            'password' => Hash::make(Str::random(40)),
                            'status' => UserStatus::Pending,
                        ]);
                    });
                }
            }

            // Google supplied an email_verified=true claim. Use the same atomic
            // transition as verification by code so activation and the
            // one-time delayed welcome mail remain consistent across providers.
            $user = $emailVerification->verifyTrustedEmail($user, $email);

            if (! $user) {
                return redirect()->route('login')->withErrors([
                    'email' => 'Your account has been suspended. Please contact support.',
                ]);
            }

            Auth::login($user);
            $request->session()->regenerate();
            $request->session()->put('auth.version', (int) $user->authentication_version);

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (ValidationException $exception) {
            return redirect()->route('login')->withErrors($exception->errors());
        } catch (InvalidStateException $exception) {
            return app(ExpirationResponse::class)->session($request, oauth: true);
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('login')->withErrors([
                'email' => 'Google sign-in could not be completed. Please try again.',
            ]);
        }
    }
}
