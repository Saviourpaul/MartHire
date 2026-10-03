<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Str;
use Throwable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class GoogleAuthController extends Controller
{
    /**function: googleLogin
     * Direct user to Google for authentication.
     * 
     */
   public function googleLogin()
{
    return Socialite::driver('google')
        ->with(['prompt' => 'select_account'])
        ->redirect();
}
 public function googleAuthentication(Request $request)
{
    try {
        $googleUser = Socialite::driver('google')->user();

        $googleId = (string) $googleUser->getId();
        $email    = strtolower((string) $googleUser->getEmail());
        $verified = filter_var($googleUser->user['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if (! $email || ! $verified) {
            return redirect()->route('login')->withErrors([
                'email' => 'Your Google email is not verified.',
            ]);
        }

        $user = User::where('google_id', $googleId)->first();

        if (! $user) {
            $user = User::where('email', $email)->first();

            if ($user) {
                if ($user->google_id && $user->google_id !== $googleId) {
                    return redirect()->route('login')->withErrors([
                        'email' => 'This email is already linked to another Google account.',
                    ]);
                }

                $update = ['google_id' => $googleId];

                // Local email was never verified: someone may have pre-registered it.
                // Google just proved ownership, so kill any password set before now.
                if (! $user->email_verified_at) {
                    $update['password'] = Hash::make(Str::random(40));
                    $update['email_verified_at'] = now();
                }

                $user->update($update);
            } else {
                $user = User::create([
                    'first_name'        => $googleUser->user['given_name'] ?? $googleUser->getName(),
                    'last_name'         => $googleUser->user['family_name'] ?? '',
                    'email'             => $email,
                    'google_id'         => $googleId,
                    'email_verified_at' => now(),
                    'password'          => Hash::make(Str::random(40)),
                    
                ]);
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('Dashboard', absolute: false));
    } catch (Throwable $e) {
        report($e);

        return redirect()->route('login')->withErrors([
            'email' => 'Google sign-in could not be completed. Please try again.',
        ]);
    }
}
}
