<?php

namespace App\Support\RateLimiting;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Central definition of every named rate limiter plus the shared helpers for
 * resend cooldowns, per-token failure locks, and CAPTCHA escalation.
 *
 * All thresholds come from config/rate_limiting.php so they can be tuned in one
 * place without touching controllers or routes.
 */
class AbuseGuard
{
    /**
     * Register all named limiters. Called from RateLimitServiceProvider::boot().
     */
    public function registerLimiters(): void
    {
        RateLimiter::for('login', fn (Request $request): array => [
            $this->limit('login.ip', $request)->by('login-ip:'.$request->ip())
                ->response(fn (Request $r, array $h) => $this->response($r, $h, 'Too many login attempts. Please try again later.')),
            $this->limit('login.email_ip', $request)->by('login:'.$this->emailKey($request).'|'.$request->ip())
                ->response(fn (Request $r, array $h) => $this->response($r, $h, 'Too many login attempts. Please try again later.')),
        ]);

        RateLimiter::for('register', fn (Request $request) => $this->limit('registration.ip', $request)
            ->by('register-ip:'.$request->ip())
            ->response(fn (Request $r, array $h) => $this->response($r, $h, 'Too many sign-up attempts from this network. Please try again later.')));

        RateLimiter::for('password-reset', fn (Request $request): array => [
            $this->limit('password_reset.ip', $request)->by('pwd-reset-ip:'.$request->ip())
                ->response(fn (Request $r, array $h) => $this->response($r, $h, 'Too many password reset requests. Please try again later.')),
            $this->limit('password_reset.email', $request)->by('pwd-reset-email:'.$this->emailKey($request))
                ->response(fn (Request $r, array $h) => $this->response($r, $h, 'Too many password reset requests. Please try again later.')),
        ]);

        RateLimiter::for('password-update', fn (Request $request) => $this->limit('password_update.ip', $request)
            ->by('pwd-update-ip:'.$request->ip())
            ->response(fn (Request $r, array $h) => $this->response($r, $h, 'Too many password reset attempts. Please try again later.')));

        RateLimiter::for('invitation', fn (Request $request) => $this->limit('invitation.ip', $request)
            ->by('invitation-ip:'.$request->ip())
            ->response(fn (Request $r, array $h) => $this->response($r, $h, 'Too many invitation attempts. Please try again later.')));

        RateLimiter::for('confirmation-link', fn (Request $request) => $this->limit('confirmation_link.ip', $request)
            ->by('confirmation-ip:'.$request->ip())
            ->response(fn (Request $r, array $h) => $this->response($r, $h, 'Too many requests. Please slow down and try again shortly.')));

        RateLimiter::for('application-submit', fn (Request $request): array => [
            $this->limit('application.burst', $request)->by('application-user:'.($request->user()?->id ?: $request->ip())),
            $this->limit('application.hourly', $request)->by('application-user-hour:'.($request->user()?->id ?: $request->ip())),
        ]);

        // Email verification keeps its established thresholds (tuned via
        // config/auth.php) and a validation-error response, alongside the
        // database-backed resend cooldown enforced in the verification flow.
        RateLimiter::for('verification-send', function (Request $request): array {
            $response = fn (Request $r, array $h): never => throw ValidationException::withMessages([
                'verification' => 'Too many code requests. Please wait before requesting another code.',
            ]);

            return [
                Limit::perHour((int) config('auth.verification.resend_limit', 6))
                    ->by('verification-send-user:'.$request->user()?->id)->response($response),
                Limit::perHour((int) config('auth.verification.resend_ip_limit', 20))
                    ->by('verification-send-ip:'.$request->ip())->response($response),
            ];
        });

        RateLimiter::for('verification-code', function (Request $request): array {
            $response = fn (Request $r, array $h): never => throw ValidationException::withMessages([
                'code' => 'Too many verification attempts. Please wait before trying again.',
            ]);

            return [
                Limit::perMinute((int) config('auth.verification.max_attempts', 5))
                    ->by('verification-user:'.$request->user()?->id)->response($response),
                Limit::perMinute((int) config('auth.verification.attempt_ip_limit', 20))
                    ->by('verification-ip:'.$request->ip())->response($response),
            ];
        });
    }

    /**
     * Build a Limit from a "group.limit" config path, e.g. "login.email_ip".
     */
    public function limit(string $path, ?Request $request = null): Limit
    {
        $config = (array) config('rate_limiting.limits.'.str_replace('.', '.', $path));
        $max = (int) ($config['max'] ?? 60);
        $decay = (int) ($config['decay'] ?? 1);

        return new Limit('', $max, $decay);
    }

    /* ---------------------------------------------------------------------
     | Resend cooldowns
     | ------------------------------------------------------------------- */

    /**
     * True when the given cooldown key was touched within $seconds. Call touch()
     * after a successful send to start the next cooldown window.
     */
    public function onCooldown(string $key, int $seconds): bool
    {
        if ($seconds <= 0) {
            return false;
        }

        return $this->store()->get($this->prefixed('cooldown:'.$key)) !== null;
    }

    public function secondsUntilAvailable(string $key): int
    {
        $expiresAt = $this->store()->get($this->prefixed('cooldown:'.$key.':at'));

        return $expiresAt ? max(0, (int) $expiresAt - time()) : 0;
    }

    public function touchCooldown(string $key, int $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        $this->store()->put($this->prefixed('cooldown:'.$key), 1, $seconds);
        $this->store()->put($this->prefixed('cooldown:'.$key.':at'), time() + $seconds, $seconds);
    }

    /* ---------------------------------------------------------------------
     | Per-token failure locks (invitation / confirmation)
     | ------------------------------------------------------------------- */

    public function recordTokenFailure(string $token, int $decayMinutes = 60): int
    {
        $key = $this->prefixed('token-failures:'.hash('sha256', $token));
        $this->store()->add($key, 0, $decayMinutes * 60);

        return (int) $this->store()->increment($key);
    }

    public function tokenLocked(string $token, int $max): bool
    {
        $key = $this->prefixed('token-failures:'.hash('sha256', $token));

        return (int) ($this->store()->get($key) ?? 0) >= $max;
    }

    public function clearTokenFailures(string $token): void
    {
        $this->store()->forget($this->prefixed('token-failures:'.hash('sha256', $token)));
    }

    /* ---------------------------------------------------------------------
     | CAPTCHA escalation
     | ------------------------------------------------------------------- */

    public function recordFailure(string $flow, string $identifier): void
    {
        $key = $this->failureKey($flow, $identifier);
        $decay = (int) config('rate_limiting.captcha.decay', 60) * 60;
        $this->store()->add($key, 0, $decay);
        $this->store()->increment($key);
    }

    public function clearFailures(string $flow, string $identifier): void
    {
        $this->store()->forget($this->failureKey($flow, $identifier));
    }

    public function requiresCaptcha(string $flow, string $identifier): bool
    {
        $threshold = (int) config('rate_limiting.captcha.thresholds.'.$flow, 0);

        if ($threshold <= 0) {
            return false;
        }

        return (int) ($this->store()->get($this->failureKey($flow, $identifier)) ?? 0) >= $threshold;
    }

    private function failureKey(string $flow, string $identifier): string
    {
        return $this->prefixed('captcha:'.$flow.':'.hash('sha256', $identifier));
    }

    /* ---------------------------------------------------------------------
     | Shared helpers
     | ------------------------------------------------------------------- */

    private function emailKey(Request $request): string
    {
        return Str::transliterate(Str::lower((string) $request->input('email')));
    }

    private function store(): Repository
    {
        return cache()->store(config('rate_limiting.store'));
    }

    private function prefixed(string $key): string
    {
        return 'abuse-guard:'.$key;
    }

    /**
     * Consistent "too many requests" response: a clear message plus Retry-After.
     *
     * @param  array<string, mixed>  $headers
     */
    public function response(Request $request, array $headers, string $message): Response
    {
        $retryAfter = (int) ($headers['Retry-After'] ?? 60);
        $message = $message.' '.$this->retryPhrase($retryAfter);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 429, $headers);
        }

        return back()
            ->withInput($request->except(['password', 'password_confirmation']))
            ->withErrors(['throttle' => $message]);
    }

    private function retryPhrase(int $seconds): string
    {
        if ($seconds >= 60) {
            $minutes = (int) ceil($seconds / 60);

            return 'Please try again in about '.$minutes.' '.Str::plural('minute', $minutes).'.';
        }

        return 'Please try again in '.$seconds.' '.Str::plural('second', max(1, $seconds)).'.';
    }
}
