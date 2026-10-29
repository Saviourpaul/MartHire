<?php

namespace App\Http\Requests\Auth;

use App\Support\RateLimiting\AbuseGuard;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            $this->hitRateLimiter();
            app(AbuseGuard::class)->recordFailure('login', $this->emailIdentifier());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($this->user()?->isSuspended()) {
            Auth::logout();
            $this->hitRateLimiter();

            throw ValidationException::withMessages([
                'email' => 'Your account has been suspended. Please contact support.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        app(AbuseGuard::class)->clearFailures('login', $this->emailIdentifier());
    }

    /**
     * Ensure the login request is not rate limited, using both a tight
     * per-email+IP limit and a looser per-IP ceiling.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $emailIpMax = (int) config('rate_limiting.limits.login.email_ip.max', 5);
        $ipMax = (int) config('rate_limiting.limits.login.ip.max', 20);

        $blocked = RateLimiter::tooManyAttempts($this->throttleKey(), $emailIpMax)
            || RateLimiter::tooManyAttempts($this->ipThrottleKey(), $ipMax);

        if (! $blocked) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            RateLimiter::availableIn($this->throttleKey()),
            RateLimiter::availableIn($this->ipThrottleKey()),
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Record a failed attempt against both the email+IP and IP-only windows.
     */
    private function hitRateLimiter(): void
    {
        RateLimiter::hit($this->throttleKey(), (int) config('rate_limiting.limits.login.email_ip.decay', 1) * 60);
        RateLimiter::hit($this->ipThrottleKey(), (int) config('rate_limiting.limits.login.ip.decay', 60) * 60);
    }

    /**
     * Whether repeated failures should prompt a CAPTCHA challenge.
     */
    public function requiresCaptcha(): bool
    {
        return app(AbuseGuard::class)->requiresCaptcha('login', $this->emailIdentifier());
    }

    /**
     * Get the rate limiting throttle key (per email + IP) for the request.
     */
    public function throttleKey(): string
    {
        return 'login:'.$this->emailIdentifier().'|'.$this->ip();
    }

    private function ipThrottleKey(): string
    {
        return 'login-ip:'.$this->ip();
    }

    private function emailIdentifier(): string
    {
        return Str::transliterate(Str::lower($this->string('email')));
    }
}
