<?php

namespace App\Http\Middleware;

use App\Services\WorldLocationService;
use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class ThrottleLocationRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        $lock = null;
        try {
            $cache = app(WorldLocationService::class)->cache();
            $limiter = new RateLimiter($cache);
            $key = 'location-client:'.hash('sha256', $request->ip() ?? 'unknown');
            $lock = $cache->lock($key.':lock', 5);
            if (! $lock->get()) {
                return new JsonResponse(['message' => 'Too many location requests. Please try again shortly.', 'meta' => ['available' => false]], 429,
                    ['Retry-After' => 1]);
            }
            if ($limiter->tooManyAttempts($key, config('locations.requests_per_minute'))) {
                return new JsonResponse(['message' => 'Too many location requests. Please try again shortly.', 'meta' => ['available' => false]], 429,
                    ['Retry-After' => $limiter->availableIn($key)]);
            }
            $limiter->hit($key, 60);
        } catch (Throwable) {
            // File permissions need monitoring, but the bundled catalog stays usable.
        } finally {
            try {
                $lock?->release();
            } catch (Throwable) {
                // A failed cache release must not prevent snapshot delivery.
            }
        }

        return $next($request);
    }
}
