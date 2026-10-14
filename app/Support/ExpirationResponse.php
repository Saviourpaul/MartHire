<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\ValidateSignature;
use Illuminate\Support\Facades\URL;

class ExpirationResponse
{
    public function gone(Request $request, ?string $type = null): Response|JsonResponse
    {
        $type ??= $request->routeIs('team-invitations.*') ? 'invitation' : 'link';

        return $this->render($request, $type, 410);
    }

    public function invalidSignature(Request $request): Response|JsonResponse
    {
        $verification = $request->routeIs('verification.verify');

        if ($this->hasExpiredSignature($request)) {
            return $this->gone($request, $verification ? 'verification_expired' : 'link');
        }

        return $this->render($request, $verification ? 'verification_invalid' : 'signed_invalid', 403);
    }

    public function hasExpiredSignature(Request $request): bool
    {
        $relative = $request->routeIs('storage.*');
        $ignoredParameters = [];

        foreach ($request->route()?->gatherMiddleware() ?? [] as $middleware) {
            if (! is_string($middleware)) {
                continue;
            }

            [$name, $arguments] = array_pad(explode(':', $middleware, 2), 2, '');

            if (! in_array($name, ['signed', ValidateSignature::class], true)) {
                continue;
            }

            $ignoredParameters = $arguments === '' ? [] : explode(',', $arguments);
            $relative = ($ignoredParameters[0] ?? null) === 'relative';

            if ($relative) {
                array_shift($ignoredParameters);
            }

            break;
        }

        return URL::hasCorrectSignature($request, ! $relative, $ignoredParameters)
            && ! URL::signatureHasNotExpired($request);
    }

    public function invalidVerification(Request $request): Response|JsonResponse
    {
        return $this->render($request, 'verification_invalid', 403);
    }

    public function session(Request $request, bool $oauth = false): Response|JsonResponse
    {
        return $this->render($request, $oauth ? 'oauth_session' : 'session', 419);
    }

    public function unauthenticated(Request $request): RedirectResponse|JsonResponse
    {
        $message = __('expiration.sign_in');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message, 'code' => 'authentication_required', 'recovery_url' => route('login'),
                'recovery_label' => __('expiration.sign_in_action'),
            ], 401)->header('Cache-Control', 'no-store');
        }

        return redirect()->guest(route('login'))->with('info', $message);
    }

    private function render(Request $request, string $type, int $status): Response|JsonResponse
    {
        $minutes = $type === 'password_reset'
            ? config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60)
            : config('auth.verification.expire', 60);
        [$actionRoute, $actionLabel] = match ($type) {
            'password_reset' => ['password.request', __('expiration.reset_action')],
            'application_closed' => ['client.jobs-listings', __('expiration.browse_jobs_action')],
            'verification_expired', 'verification_invalid' => $request->user()
                ? ['verification.notice', __('expiration.verification_action')]
                : ['login', __('expiration.verification_sign_in_action')],
            'session' => $request->user()
                ? ['dashboard', __('expiration.dashboard_action')]
                : ['login', __('expiration.sign_in_action')],
            'oauth_session' => ['auth.google', __('expiration.google_action')],
            default => ['home', __('expiration.home_action')],
        };
        $data = [
            'status' => $status,
            'title' => __('expiration.'.$type.'_title'),
            'message' => __('expiration.'.$type.'_message'),
            'help' => __('expiration.'.$type.'_help', ['minutes' => $minutes]),
            'actionUrl' => route($actionRoute),
            'actionLabel' => $actionLabel,
        ];
        $headers = ['Cache-Control' => 'no-store, private', 'Referrer-Policy' => 'no-referrer'];

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $data['message'], 'code' => $type, 'recovery_url' => $data['actionUrl'],
                'recovery_label' => $data['actionLabel'],
            ], $status, $headers);
        }

        return response()->view('errors.recovery', $data, $status, $headers);
    }
}
