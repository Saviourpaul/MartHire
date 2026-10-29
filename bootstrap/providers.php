<?php

use App\Providers\AppServiceProvider;
use App\Providers\RateLimitServiceProvider;
use App\Providers\WorldServiceProvider;

return [
    AppServiceProvider::class,
    RateLimitServiceProvider::class,
    WorldServiceProvider::class,
];
