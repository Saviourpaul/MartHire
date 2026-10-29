<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AccountLifecycleService;
use App\Support\ExpirationResponse;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Throwable;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request, ExpirationResponse $expiration): Response|JsonResponse
    {
        $email = $request->query('email');
        $token = $request->route('token');
        $credentials = Validator::make(['email' => $email, 'token' => $token], [
            'email' => ['required', 'string', 'email', 'max:255'],
            'token' => ['required', 'string', 'max:128', 'regex:/\A[A-Za-z0-9_-]+\z/'],
        ]);

        if ($credentials->fails()) {
            return $expiration->gone($request, 'password_reset');
        }

        $user = User::where('email', $email)->first();

        if (! $user || ! Password::tokenExists($user, $token)) {
            return $expiration->gone($request, 'password_reset');
        }

        return response()->view('auth.reset-password', [
            'request' => $request,
            'email' => is_string($email) ? $email : '',
            'resetSuccessful' => false,
        ])->header('Cache-Control', 'no-store, private')->header('Referrer-Policy', 'no-referrer');
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
    public function store(Request $request, ExpirationResponse $expiration): RedirectResponse|Response|JsonResponse
    {
        $request->session()->forget('password_reset_success');

        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'confirmed', 'min:8', Rules\Password::defaults()],
            'password_confirmation' => ['required', 'string'],
        ], [
            'token.required' => __('expiration.password_reset_message'),
            'token.string' => __('expiration.password_reset_message'),
            'email.required' => __('expiration.password_reset_message'),
            'email.string' => __('expiration.password_reset_message'),
            'email.email' => __('expiration.password_reset_message'),
            'password.required' => __('Enter your new password.'),
            'password.confirmed' => __('The passwords do not match.'),
            'password.min' => __('Use a password with at least 8 characters.'),
            'password_confirmation.required' => __('Confirm your new password.'),
        ]);

        if ($validator->fails()) {
            if ($validator->errors()->has('token') || $validator->errors()->has('email')) {
                return $expiration->gone($request, 'password_reset');
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
            }

            return $this->resetFailure($request, $validator->errors());
        }

        try {
            $status = DB::transaction(function () use ($request): string {
                User::query()->where('email', $request->input('email'))->lockForUpdate()->first();
                DB::table(config('auth.passwords.users.table', 'password_reset_tokens'))
                    ->where('email', $request->input('email'))->lockForUpdate()->first();

                return Password::reset(
                    $request->only('email', 'password', 'password_confirmation', 'token'),
                    function (User $user, string $password): void {
                        app(AccountLifecycleService::class)->changePassword($user, $password, reset: true);

                        event(new PasswordReset($user));
                    }
                );
            }, 3);
        } catch (Throwable $exception) {
            report($exception);

            return $this->resetFailure($request, [
                'reset' => __('Unable to reset your password at the moment. Please try again later.'),
            ]);
        }

        if ($status !== Password::PASSWORD_RESET) {
            return $expiration->gone($request, 'password_reset');
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
