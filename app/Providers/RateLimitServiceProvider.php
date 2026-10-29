<?php

namespace App\Providers;

use App\Support\RateLimiting\AbuseGuard;
use App\Support\RateLimiting\FailOpenStore;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\Repository;
use Illuminate\Support\ServiceProvider;

/**
 * Backs the framework rate limiter with a dedicated (Redis) store wrapped for
 * graceful failure, and registers every named limiter from config via
 * {@see AbuseGuard}. Keeping this separate from AppServiceProvider makes the
 * abuse-prevention surface easy to find and maintain.
 */
class RateLimitServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AbuseGuard::class);

        // Point the rate limiter at the configured store (Redis), independent of
        // the application cache, and degrade gracefully when it is unavailable.
        $this->app->extend(RateLimiter::class, function (RateLimiter $limiter, $app): RateLimiter {
            $base = $app['cache']->store(config('rate_limiting.store'));

            $store = new FailOpenStore($base->getStore(), (bool) config('rate_limiting.fail_open'));

            return new RateLimiter(new Repository($store));
        });
    }

    public function boot(): void
    {
        $this->app->make(AbuseGuard::class)->registerLimiters();
    }
}
