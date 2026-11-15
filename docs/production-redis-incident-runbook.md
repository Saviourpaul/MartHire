# Production Redis Connectivity Runbook

## Purpose

Use this runbook when production logs contain messages such as:

```text
Rate limiter store unavailable; failing open.
Connection refused [...redis.io:PORT]
```

The application uses Redis as its dedicated rate-limit store. With `RATE_LIMIT_FAIL_OPEN=true`, requests continue during a Redis outage, but counters and cooldowns cannot be reliably enforced until Redis recovers. The rate limiter and `AbuseGuard` share the fail-open repository so a Redis outage does not turn those counter operations into application errors.

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

Then verify that a normal login, password-reset request, and invitation flow complete without Redis exceptions. Confirm Redis recovery from the provider's connection/health metrics; absence of a new application warning alone is not proof of recovery because the warning is logged once per PHP process.

## Alerts and health checks

- Treat the structured `Rate limiter store unavailable; failing open.` application warning as an alert signal for the start of an outage. Configure the production log/monitoring provider to page or notify on this warning and configure Redis provider alerts for connection failures, connection-limit saturation, and elevated latency. The application warning is emitted once per PHP process, so provider health metrics must also detect recovery and continuing failures.
- The Laravel `/up` endpoint is an application liveness check. Keep it independent of Redis: fail-open is intended to keep the application serving requests during a rate-limit-store outage, and taking every web instance out of service because Redis is unavailable would defeat that policy.
- After Redis recovery, verify provider health and test login, password reset, and invitation flows. The application log warning is useful incident evidence, but Redis provider metrics are needed for proactive availability and recovery alerts.
- Follow the provider-neutral [production WAF setup guide](production-waf-setup.md) to prepare edge rate limits and origin protections; this repository does not contain live provider rules.

## Edge protection during a Redis outage

- Configure request-rate rules at the CDN, WAF, or hosting edge for sensitive POST routes such as login, registration, password reset, demo requests, and application submission. Use per-IP burst limits and managed bot rules to rate-limit or block obvious automation during a Redis incident.
- Keep the application's per-account/per-email limits as the primary fine-grained controls when Redis is available; edge rules complement them and cannot necessarily reproduce their identity-aware keys.
- Preserve the real client IP at the edge and configure Laravel trusted proxies to match the actual proxy chain. Do not accept arbitrary client-supplied forwarding headers as the source IP.
- These rules must be configured in the production edge provider. This repository contains no CDN/WAF deployment configuration, so a code deploy alone cannot activate edge protection.

## Current outage policy

- **Fail open** (`RATE_LIMIT_FAIL_OPEN=true`): keep login and other rate-limited flows available. Redis-backed counters, cooldowns, and token-failure tracking may be unavailable until Redis recovers.
- **Fail closed** (`RATE_LIMIT_FAIL_OPEN=false`): preserve the rate-limit policy but block affected flows while Redis is unavailable. This is not the selected production policy because it makes authentication unavailable during a Redis incident.
- Do not switch rate-limit counters to the primary database as an automatic fallback without load testing. The dedicated Redis store is intentionally separated from the main database to avoid adding high-volume counter reads and writes to the authentication/data workload.

## Evidence to retain for an incident

- Timestamp and request route/request ID.
- Full exception stack trace (redacted of secrets).
- Redis Cloud database status and connection metrics.
- The production server's outbound IP and TLS test result.
- Effective Laravel configuration values with passwords and complete URLs redacted.

## Relevant application files

- `app/Support/RateLimiting/FailOpenStore.php`
- `app/Support/RateLimiting/RateLimitCacheRepository.php`
- `app/Providers/RateLimitServiceProvider.php`
- `app/Support/RateLimiting/AbuseGuard.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `config/rate_limiting.php`
- `config/database.php`
