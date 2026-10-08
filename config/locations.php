<?php

return [
    'base_url' => env('WORLD_LOCATION_API_URL', 'https://world.bmbc.cloud/api'),
    'fresh_seconds' => 86400,
    'stale_seconds' => 7776000,
    'cooldown_seconds' => 60,
    'requests_per_minute' => 60,
    'snapshot_path' => resource_path('locations'),
    // Independent of CACHE_STORE, SESSION_DRIVER, and the application's database.
    'cache' => [
        'driver' => 'file',
        'path' => storage_path('framework/cache/locations'),
        'lock_path' => storage_path('framework/cache/locations'),
    ],
];
