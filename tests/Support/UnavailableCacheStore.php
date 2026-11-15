<?php

namespace Tests\Support;

use Illuminate\Cache\ArrayStore;
use RuntimeException;

class UnavailableCacheStore extends ArrayStore
{
    public static int $attempts = 0;

    public function get($key)
    {
        return $this->unavailable();
    }

    public function many(array $keys)
    {
        return $this->unavailable();
    }

    public function put($key, $value, $seconds)
    {
        return $this->unavailable();
    }

    public function putMany(array $values, $seconds)
    {
        return $this->unavailable();
    }

    public function increment($key, $value = 1)
    {
        return $this->unavailable();
    }

    public function decrement($key, $value = 1)
    {
        return $this->unavailable();
    }

    public function forever($key, $value)
    {
        return $this->unavailable();
    }

    public function forget($key)
    {
        return $this->unavailable();
    }

    public function flush()
    {
        return $this->unavailable();
    }

    public function add($key, $value, $seconds)
    {
        return $this->unavailable();
    }

    public function getPrefix()
    {
        return '';
    }

    private function unavailable(): never
    {
        self::$attempts++;

        throw new RuntimeException('Redis connection refused.');
    }
}
