<?php

namespace App\Support\RateLimiting;

use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cache store decorator for the rate limiter.
 *
 * When the underlying store (Redis) is unreachable it degrades gracefully:
 * reads behave as cache misses and writes are treated as no-ops, so the rate
 * limiter "fails open" (allows the request) instead of taking the whole flow
 * down with it. Every outage is logged once per process.
 *
 * Set config('rate_limiting.fail_open') to false to re-throw instead (fail
 * closed), which blocks rate-limited requests while the store is down.
 */
class FailOpenStore implements Store
{
    private static bool $outageLogged = false;

    public function __construct(
        private readonly Store $inner,
        private readonly bool $failOpen = true,
    ) {}

    public function get($key): mixed
    {
        return $this->guard(fn () => $this->inner->get($key), null);
    }

    /**
     * @param  array<int, string>  $keys
     * @return array<string, mixed>
     */
    public function many(array $keys): array
    {
        return $this->guard(fn () => $this->inner->many($keys), array_fill_keys($keys, null));
    }

    public function put($key, $value, $seconds): bool
    {
        return $this->guard(fn () => $this->inner->put($key, $value, $seconds), true);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function putMany(array $values, $seconds): bool
    {
        return $this->guard(fn () => $this->inner->putMany($values, $seconds), true);
    }

    public function increment($key, $value = 1): int|bool
    {
        return $this->guard(fn () => $this->inner->increment($key, $value), $value);
    }

    public function decrement($key, $value = 1): int|bool
    {
        return $this->guard(fn () => $this->inner->decrement($key, $value), $value);
    }

    public function forever($key, $value): bool
    {
        return $this->guard(fn () => $this->inner->forever($key, $value), true);
    }

    public function forget($key): bool
    {
        return $this->guard(fn () => $this->inner->forget($key), true);
    }

    public function flush(): bool
    {
        return $this->guard(fn () => $this->inner->flush(), true);
    }

    public function getPrefix(): string
    {
        return $this->inner->getPrefix();
    }

    /**
     * Proxy additional store methods (add, lock, connection, …) with the same
     * fail-open guard so callers such as Repository::add() keep working.
     *
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $method, array $arguments): mixed
    {
        return $this->guard(fn () => $this->inner->{$method}(...$arguments), true);
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @param  T  $fallback
     * @return T
     */
    private function guard(callable $callback, mixed $fallback): mixed
    {
        try {
            return $callback();
        } catch (Throwable $exception) {
            if (! $this->failOpen) {
                throw $exception;
            }

            if (! self::$outageLogged) {
                self::$outageLogged = true;
                Log::warning('Rate limiter store unavailable; failing open.', [
                    'exception' => $exception->getMessage(),
                ]);
            }

            return $fallback;
        }
    }
}
