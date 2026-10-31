# Production Redis Connectivity Runbook

## Purpose

Use this runbook when production logs contain messages such as:

```text
Rate limiter store unavailable; failing open.
Connection refused [...redis.io:PORT]
```

The current application uses Redis for rate limiting. A Redis outage can reduce abuse protection and, in a few code paths, still cause an application error.

## What the error means

`Connection refused` means the production server reached the target network address but the Redis TCP connection was rejected. It is usually not a Laravel validation or browser issue, and it is not normally a wrong-password error.

## First checks

1. In Redis Cloud, confirm the database is active and its endpoint and port match production configuration.
2. Check Redis Cloud database status, maintenance notices, memory usage, and connection count/limit.
3. Confirm the production server's public/NAT IP is permitted by any Redis Cloud source-IP allow-list.
4. Verify the provider's required TLS mode. Use its TLS connection URL/scheme when TLS is required.
5. From the production host, test the Redis connection with the exact endpoint, port, TLS setting, username, and password. Do not put credentials in logs, tickets, or shell history.
6. Compare the running production configuration—not only the `.env` file—with local values for `RATE_LIMIT_STORE`, `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT`, and `REDIS_URL`.

## After correcting configuration or networking

On production, rebuild Laravel configuration and restart long-running processes so they do not retain stale Redis settings:

```bash
php artisan config:clear
php artisan config:cache
# Restart PHP-FPM and queue workers using the hosting provider's approved process.
```

Then verify that a normal login, password-reset request, and invitation flow complete without Redis exceptions.

## Deferred application hardening

The rate limiter's fail-open wrapper protects Laravel's main `RateLimiter`, but `App\Support\RateLimiting\AbuseGuard` currently reads the Redis cache store directly. During an outage, those direct reads/writes can still throw an exception.

Future implementation:

1. Introduce one shared, guarded rate-limit-store service/repository.
2. Make both Laravel `RateLimiter` and `AbuseGuard` use it.
3. Preserve an explicit policy decision:
   - **Fail open:** keep login/register/reset available, but temporarily skip cooldown/CAPTCHA/token-lock enforcement.
   - **Fail closed:** retain abuse protection, but block affected flows while Redis is unavailable.
4. Add monitoring for Redis connection failures, connection-limit saturation, and fail-open activations.
5. Add tests covering a refused Redis connection for login, password reset, invitation, CAPTCHA, and token-lock flows.

## Evidence to retain for an incident

- Timestamp and request route/request ID.
- Full exception stack trace (redacted of secrets).
- Redis Cloud database status and connection metrics.
- The production server's outbound IP and TLS test result.
- Effective Laravel configuration values with passwords and complete URLs redacted.

## Relevant application files

- `app/Support/RateLimiting/FailOpenStore.php`
- `app/Providers/RateLimitServiceProvider.php`
- `app/Support/RateLimiting/AbuseGuard.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `config/rate_limiting.php`
- `config/database.php`
