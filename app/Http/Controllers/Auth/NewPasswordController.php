<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Throwable;

class NewPasswordController extends Controller
{
    private const INVALID_LINK_MESSAGE = 'This password reset link is invalid or has expired. Please request a new link.';

    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        $email = $request->old('email', $request->query('email', ''));

        return view('auth.reset-password', [
            'request' => $request,
            'email' => is_string($email) ? $email : '',
            'resetSuccessful' => false,
        ]);
    }

    /**
     * Display the one-time reset confirmation before returning to login.
     */
    public function success(Request $request): View|RedirectResponse
    {
        if ($request->session()->pull('password_reset_success') !== true) {
            return redirect()->route('login');
        }

        return view('auth.reset-password', ['resetSuccessful' => true]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->session()->forget('password_reset_success');

        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', 'min:8', Rules\Password::defaults()],
            'password_confirmation' => ['required', 'string'],
        ], [
            'token.required' => __(self::INVALID_LINK_MESSAGE),
            'token.string' => __(self::INVALID_LINK_MESSAGE),
            'email.required' => __(self::INVALID_LINK_MESSAGE),
            'email.string' => __(self::INVALID_LINK_MESSAGE),
            'email.email' => __(self::INVALID_LINK_MESSAGE),
            'password.required' => __('Enter your new password.'),
            'password.confirmed' => __('The passwords do not match.'),
            'password.min' => __('Use a password with at least 8 characters.'),
            'password_confirmation.required' => __('Confirm your new password.'),
        ]);

        if ($validator->fails()) {
            return $this->resetFailure($request, $validator->errors());
        }

        try {
            $status = DB::transaction(fn () => Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password): void {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            ));
        } catch (Throwable $exception) {
            report($exception);

            return $this->resetFailure($request, [
                'reset' => __('Unable to reset your password at the moment. Please try again later.'),
            ]);
        }

        if ($status !== Password::PASSWORD_RESET) {
            return $this->resetFailure($request, ['reset' => __(self::INVALID_LINK_MESSAGE)]);
        }

        return redirect()->route('password.reset.success')->with('password_reset_success', true);
    }

    private function resetFailure(Request $request, array|MessageBag $errors): RedirectResponse
    {
        $token = $request->input('token');
        $email = $request->input('email');

        // Missing or malformed credentials still return to a renderable reset form.
        if (! is_string($token) || ! preg_match('/\A[A-Za-z0-9_-]{1,128}\z/', $token) || $token === 'success') {
            $token = 'invalid';
        }

        $input = is_string($email) ? ['email' => $email] : [];

        return redirect()->route('password.reset', ['token' => $token, ...$input])
            ->withInput($input)
            ->withErrors($errors);
    }
}
