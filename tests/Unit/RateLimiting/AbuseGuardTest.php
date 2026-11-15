<?php

use App\Support\RateLimiting\AbuseGuard;
use Tests\Support\UnavailableCacheStore;

test('AbuseGuard helpers fail open when the shared rate-limit store is unavailable', function () {
    useUnavailableRateLimitStore();

    $guard = app(AbuseGuard::class);
    $cooldownKey = 'test-cooldown';
    $token = 'test-confirmation-token';

    expect($guard->onCooldown($cooldownKey, 60))->toBeFalse()
        ->and($guard->secondsUntilAvailable($cooldownKey))->toBe(0);

    $guard->touchCooldown($cooldownKey, 60);
    $guard->clearTokenFailures($token);
    $guard->recordTokenFailure($token);

    expect($guard->onCooldown($cooldownKey, 60))->toBeFalse()
        ->and($guard->tokenLocked($token, 1))->toBeFalse()
        ->and(UnavailableCacheStore::$attempts)->toBeGreaterThan(0);
});
