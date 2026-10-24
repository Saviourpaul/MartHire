<?php

use App\Integrations\Workable\Exceptions\CredentialsRejected;
use App\Integrations\Workable\Exceptions\PayloadRejected;
use App\Integrations\Workable\Exceptions\RateLimited;
use App\Integrations\Workable\Exceptions\WorkableException;

test('rate_limited_carries_retry_after', function () {
    expect((new RateLimited(retryAfter: 30))->retryAfter())->toBe(30);
});

test('payload_rejected_carries_validation_errors', function () {
    expect((new PayloadRejected(['email' => ['has already been taken']]))->validationErrors())
        ->toBe(['email' => ['has already been taken']]);
});

test('credentials_rejected_is_a_workable_exception', function () {
    expect(new CredentialsRejected())->toBeInstanceOf(WorkableException::class);
});
