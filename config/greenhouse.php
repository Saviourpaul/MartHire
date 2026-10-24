<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Greenhouse Job Board API
    |--------------------------------------------------------------------------
    |
    | Non-secret settings for the Greenhouse Job Board integration. The
    | employer API key is intentionally NOT configured here or in .env.
    |
    */

    'base_url' => env('GREENHOUSE_BASE_URL', 'https://boards-api.greenhouse.io/v1'),

    'user_agent' => env('GREENHOUSE_USER_AGENT', 'NearbyWorks/1.0 (+ops@nearbyworks.test)'),

    'fake' => (bool) filter_var(env('GREENHOUSE_FAKE', false), FILTER_VALIDATE_BOOLEAN),

    'sync_empty_guard_min_open' => (int) env('GREENHOUSE_SYNC_EMPTY_GUARD_MIN_OPEN', 5),

    'schema_cache_minutes' => (int) env('GREENHOUSE_SCHEMA_CACHE_MINUTES', 15),

    'application' => [

        'max_file_mb' => (int) env('APPLICATION_MAX_FILE_MB', 5),

        'payload_retention_days' => (int) env('APPLICATION_PAYLOAD_RETENTION_DAYS', 30),

    ],

];
