<?php

namespace App\Providers;

use App\Support\RateLimiting\AbuseGuard;
use App\Support\RateLimiting\FailOpenStore;
use App\Support\RateLimiting\RateLimitCacheRepository;
use Illuminate\Cache\RateLimiter;
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

        // Share one decorated repository between Laravel's RateLimiter and
        // AbuseGuard's cooldown and token-failure counters.
        $this->app->singleton(RateLimitCacheRepository::class, function ($app): RateLimitCacheRepository {
            $base = $app['cache']->store(config('rate_limiting.store'));
            $store = new FailOpenStore($base->getStore(), (bool) config('rate_limiting.fail_open'));

            return new RateLimitCacheRepository($store);
        });

        // Point the rate limiter at the configured store (Redis), independent of
        // the application cache, and degrade gracefully when it is unavailable.
        $this->app->extend(RateLimiter::class, function (RateLimiter $limiter, $app): RateLimiter {
            return new RateLimiter($app->make(RateLimitCacheRepository::class));
        });
    }

    public function boot(): void
    {
        $this->app->make(AbuseGuard::class)->registerLimiters();
    }
}
