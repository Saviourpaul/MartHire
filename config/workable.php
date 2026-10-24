<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Workable API
    |--------------------------------------------------------------------------
    |
    | Non-secret settings for the Workable integration. The base URL is built
    | per account from the host template and that account's subdomain, which
    | is stored per company in the database. API tokens are also per company
    | and are intentionally NOT configured here or in .env.
    |
    */

    'api_version' => env('WORKABLE_API_VERSION', 'v3'),

    'host_template' => env('WORKABLE_HOST_TEMPLATE', 'https://%s.workable.com/spi/%s'),

    'user_agent' => env('WORKABLE_USER_AGENT', 'NearbyWorks/1.0 (+ops@nearbyworks.test)'),

    'fake' => filter_var(env('WORKABLE_FAKE', false), FILTER_VALIDATE_BOOLEAN),

    'sync_empty_guard_min_open' => (int) env('WORKABLE_SYNC_EMPTY_GUARD_MIN_OPEN', 5),

    'schema_cache_minutes' => (int) env('WORKABLE_SCHEMA_CACHE_MINUTES', 15),

    'application' => [

        'max_file_mb' => (int) env('APPLICATION_MAX_FILE_MB', 5),

        'payload_retention_days' => (int) env('APPLICATION_PAYLOAD_RETENTION_DAYS', 30),

    ],

];
