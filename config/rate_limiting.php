<?php

/*
|--------------------------------------------------------------------------
| Rate limiting & abuse prevention
|--------------------------------------------------------------------------
| Single source of truth for every throttled workflow. Tune these against
| real traffic. Counters are stored in Redis (never the main database) so
| the application cache and the limiter stay independent.
*/

return [

    /*
    | Cache store used for rate-limit counters. Keep this on Redis even when
    | the application cache (CACHE_STORE) uses another driver.
    */
    'store' => env('RATE_LIMIT_STORE', 'redis'),

    /*
    | Behaviour when the limiter store (Redis) is unreachable.
    | true  = fail open: allow the request through and log the outage.
    | false = fail closed: block rate-limited requests until the store recovers.
    */
    'fail_open' => (bool) env('RATE_LIMIT_FAIL_OPEN', true),

    /*
    | Per-flow thresholds. Each limit is [max attempts, decay in minutes].
    | Keys are kept explicit so the intent (per email, per IP, per token, per
    | user) is obvious and easy to tune.
    */
    'limits' => [

        'login' => [
            'email_ip' => ['max' => 5, 'decay' => 1],   // 5 / minute per email+IP
            'ip' => ['max' => 20, 'decay' => 60],        // 20 / hour per IP
        ],

        'registration' => [
            'ip' => ['max' => 5, 'decay' => 60],         // 5 / hour per IP
        ],

        'password_reset' => [
            'email' => ['max' => 3, 'decay' => 60],      // 3 / hour per email
            'ip' => ['max' => 10, 'decay' => 60],        // 10 / hour per IP
            'cooldown_seconds' => 60,                    // min gap between requests per email
        ],

        'password_update' => [
            'ip' => ['max' => 10, 'decay' => 60],        // 10 / hour per IP (token reset submits)
        ],

        // Email verification limits are configured under config/auth.php
        // (auth.verification.*) and enforced alongside a database-backed resend
        // cooldown in the verification flow.

        'invitation' => [
            'ip' => ['max' => 10, 'decay' => 60],        // 10 / hour per IP
            'token_failures' => 5,                       // failed attempts per token before lockout
        ],

        'confirmation_link' => [
            'ip' => ['max' => 20, 'decay' => 1],         // 20 / minute per IP
        ],

        'application' => [
            'burst' => ['max' => 3, 'decay' => 1],       // 3 / minute per user
            'hourly' => ['max' => 10, 'decay' => 60],    // 10 / hour per user
        ],
    ],

    /*
    | CAPTCHA escalation. The failure counters and "requires CAPTCHA" state are
    | always tracked; the external provider stays disabled until keys are set.
    */
    'captcha' => [
        'enabled' => (bool) env('RATE_LIMIT_CAPTCHA_ENABLED', false),
        'provider' => env('RATE_LIMIT_CAPTCHA_PROVIDER', 'recaptcha'),
        'thresholds' => [
            'login' => 3,          // CAPTCHA after 3 failed logins
            'registration' => 2,   // CAPTCHA after 2 rapid registrations
            'password_reset' => 3, // CAPTCHA on repeat from same IP
        ],
        'decay' => 60, // minutes the failure count is remembered
    ],
];
