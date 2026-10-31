<?php

return [
    'cache' => [
        'driver' => env('LOCATION_CACHE_STORE'),
        'prefix' => 'marthire.locations.v2',
        'ttl' => (int) env('LOCATION_CACHE_TTL', 3600),
    ],
];
