<?php

use App\Support\RateLimiting\FailOpenStore;
use Tests\Support\UnavailableCacheStore;

test('fail-open store returns safe fallbacks when the backing store is unavailable', function () {
    $store = new FailOpenStore(new UnavailableCacheStore, failOpen: true);

    expect($store->get('missing'))->toBeNull()
        ->and($store->many(['first', 'second']))->toBe(['first' => null, 'second' => null])
        ->and($store->put('key', 'value', 60))->toBeTrue()
        ->and($store->putMany(['first' => 1], 60))->toBeTrue()
        ->and($store->increment('counter', 3))->toBe(3)
        ->and($store->decrement('counter', 2))->toBe(2)
        ->and($store->forever('key', 'value'))->toBeTrue()
        ->and($store->forget('key'))->toBeTrue()
        ->and($store->flush())->toBeTrue()
        ->and($store->add('key', 'value', 60))->toBeTrue();
});

test('fail-closed store rethrows backing store errors', function () {
    $store = new FailOpenStore(new UnavailableCacheStore, failOpen: false);

    expect(fn () => $store->get('key'))
        ->toThrow(\RuntimeException::class, 'Redis connection refused.');
});
