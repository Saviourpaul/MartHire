<?php

it('defaults production session cookies to secure when no override is set', function () {
    $previousAppEnv = getenv('APP_ENV');
    $previousOverride = getenv('SESSION_SECURE_COOKIE');

    putenv('APP_ENV=production');
    putenv('SESSION_SECURE_COOKIE');

    try {
        $session = require base_path('config/session.php');

        expect($session['secure'])->toBeTrue()
            ->and($session['http_only'])->toBeTrue()
            ->and($session['same_site'])->toBe('lax');
    } finally {
        restoreEnvironmentVariable('APP_ENV', $previousAppEnv);
        restoreEnvironmentVariable('SESSION_SECURE_COOKIE', $previousOverride);
    }
});

it('allows an explicit session cookie secure override', function () {
    $previousAppEnv = getenv('APP_ENV');
    $previousOverride = getenv('SESSION_SECURE_COOKIE');

    putenv('APP_ENV=production');
    putenv('SESSION_SECURE_COOKIE=false');

    try {
        $session = require base_path('config/session.php');

        expect($session['secure'])->toBeFalse();
    } finally {
        restoreEnvironmentVariable('APP_ENV', $previousAppEnv);
        restoreEnvironmentVariable('SESSION_SECURE_COOKIE', $previousOverride);
    }
});

function restoreEnvironmentVariable(string $name, string|false $value): void
{
    if ($value === false) {
        putenv($name);

        return;
    }

    putenv($name.'='.$value);
}
