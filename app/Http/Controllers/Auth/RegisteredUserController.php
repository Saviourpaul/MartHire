<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PlatformSettingsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(PlatformSettingsService $settings): View
    {
        return view('auth.register', ['registrationEnabled' => $settings->current()->public_registration_enabled]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request, PlatformSettingsService $settings): RedirectResponse
    {
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->letters()->numbers()->symbols()->uncompromised()],
        ]);

        $user = DB::transaction(function () use ($request, $settings): User {
            $settings->assertPublicRegistrationEnabled();

            return User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'name' => trim($request->first_name.' '.$request->last_name),
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => UserRole::Applicant,
                'status' => UserStatus::Pending,
            ]);
        });

        // Pending users have a limited session for the verification and resend
        // screens only. Product routes remain behind the verified middleware.
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('auth.version', (int) $user->authentication_version);
        event(new Registered($user));

        return redirect()->route('verification.notice');
    }
}
