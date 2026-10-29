<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAuthenticationVersion
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $request->hasSession()) {
            return $next($request);
        }

        $version = (int) $user->authentication_version;
        $stamp = $request->session()->get('auth.version');

        if ($stamp === null && ($version === 0 || Auth::guard('web')->viaRemember())) {
            $request->session()->put('auth.version', $version);
        } elseif (! is_int($stamp) || $stamp !== $version) {
            Auth::guard('web')->logoutCurrentDevice();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Your session has ended. Please sign in again.'], 401);
            }

            return redirect()->route('login')->with('status', 'Your session has ended. Please sign in again.');
        }

        return $next($request);
    }
}
