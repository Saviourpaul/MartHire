<?php

return [
    'setup_expire_days' => max(1, (int) env('ORGANIZATION_DEMO_SETUP_EXPIRE_DAYS', 7)),
    'requester_update_expire_days' => max(1, (int) env('ORGANIZATION_DEMO_UPDATE_EXPIRE_DAYS', 7)),
    'booking_url' => env('ORGANIZATION_DEMO_BOOKING_URL'),

    'captcha' => [
        'enabled' => (bool) env('ORGANIZATION_DEMO_CAPTCHA_ENABLED', false),
        'site_key' => env('RECAPTCHA_SITE_KEY'),
        'secret_key' => env('RECAPTCHA_SECRET_KEY'),
        'verify_url' => 'https://www.google.com/recaptcha/api/siteverify',
        'timeout_seconds' => 5,
    ],

    'public_email_domains' => [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.uk', 'outlook.com',
        'hotmail.com', 'live.com', 'icloud.com', 'aol.com', 'proton.me', 'protonmail.com',
    ],
];
