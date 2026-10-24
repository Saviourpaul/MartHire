<?php

use App\Integrations\Greenhouse\Exceptions\CredentialsRejected;
use App\Integrations\Greenhouse\Exceptions\GreenhouseException;
use App\Integrations\Greenhouse\Exceptions\RateLimited;

test('rate_limited_carries_retry_after', function () {
    expect((new RateLimited(retryAfter: 30))->retryAfter())->toBe(30);
});

test('credentials_rejected_is_a_greenhouse_exception', function () {
    expect(new CredentialsRejected())->toBeInstanceOf(GreenhouseException::class);
});

test('greenhouse_exception_is_a_runtime_exception', function () {
    expect(new GreenhouseException())->toBeInstanceOf(\RuntimeException::class);
});
