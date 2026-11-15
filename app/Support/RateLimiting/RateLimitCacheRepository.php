<?php

namespace App\Support\RateLimiting;

use Illuminate\Cache\Repository;

/**
 * Dedicated repository shared by framework throttles and AbuseGuard helpers.
 * Its Store is decorated with FailOpenStore by RateLimitServiceProvider.
 */
final class RateLimitCacheRepository extends Repository
{
}
