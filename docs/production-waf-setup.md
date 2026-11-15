# Production WAF and Edge Protection Setup

## Status

This is a provider-neutral implementation guide. No CDN/WAF rules are configured by this repository, and following this guide requires access to the production edge and hosting provider. Select the provider and confirm the production origin before creating rules.

## Purpose

The application keeps authentication and other rate-limited flows available when Redis is unavailable by failing open. During that interval, Redis-backed counters cannot reliably stop repeated requests. Edge rules provide an independent IP- and traffic-based layer in front of Laravel. They complement the application's identity-aware controls; they do not replace them.

## Protected requests

Start with HTTP `POST` requests to sensitive public flows. Confirm the final paths against the deployed route list before publishing rules.

| Route | Edge protection goal |
| --- | --- |
| `/login` | Limit credential-guessing bursts and sustained attempts from one source. |
| `/register` | Reduce automated account creation and verification-mail abuse. |
| `/forgot-password` | Limit reset-mail requests, including requests using many different email addresses. |
| `/reset-password` | Limit repeated token/password submissions. |
| `/confirm-password`, `/password` | Limit repeated password-confirmation and authenticated password-change attempts. |
| `/email/verification-notification`, `/verify-email` | Reduce resend and code-guessing bursts. |
| `/request-demo` | Reduce automated demo requests. |
| `/organization-setup/*` | Limit repeated invitation setup attempts. |
| `/team-invitations/*/accept`, `/team-invitations/*/decline` | Limit token guessing and repeated actions. |
| `/jobs/*/apply`, `/workable-jobs/*/apply` | Reduce application spam while preserving normal candidate traffic. |

Apply rules by method and path. Do not rate-limit all `GET` traffic or static assets as part of this policy. Keep `/up` available to the host's health monitor and independent of Redis status.

## Rule design

1. Review normal peak traffic, shared-office and mobile-carrier NAT patterns, and existing application limits before choosing numeric thresholds. Do not copy a per-account limit directly to an IP-only edge rule.
2. Start new rules in count/log mode. Observe legitimate peak traffic and false positives before enabling enforcement.
3. Set separate burst and sustained limits for each route group. Use the provider's rate-limit counters and managed bot signals when available. Prefer a temporary rate-limit response for a clear excess; keep unrelated routes and users unaffected.
4. Avoid logging request bodies, passwords, reset tokens, verification codes, or full query strings. Retain rule ID, path, method, timestamp, source address, action, and request ID for incident review.
5. Set a clear ownership path for changing or disabling rules during an incident. Record the active thresholds and rollback steps in the provider console or infrastructure repository.

## Client IP and origin protection

- Ensure the application sees the actual client address from the selected edge provider. Configure Laravel's trusted-proxy settings for only the provider and hosting proxy ranges used by production.
- Do not trust arbitrary `X-Forwarded-For`, `Forwarded`, or vendor-specific client-IP headers from direct internet requests.
- Where the hosting platform supports it, restrict the origin so public requests must pass through the WAF/CDN. Preserve required provider health checks and deployment access.
- Verify TLS from the client to the edge and from the edge to the origin. Use the canonical production host for signed application links.

## Redis-outage response

1. Alert on Redis provider health and the application warning `Rate limiter store unavailable; failing open.`
2. Confirm the edge rules are active and that requests cannot bypass the edge through the origin address.
3. If traffic is abusive, temporarily tighten the relevant route rules in small increments and watch legitimate login, registration, and reset traffic for false positives.
4. Restore Redis connectivity and verify provider health. Exercise representative authentication and submission flows, then return any temporary edge rules to their recorded baseline.

The application warning is logged once per PHP process, so use Redis provider metrics for continuing-failure and recovery alerts. Edge rule activation and threshold changes are production operations; a Laravel code deploy alone does not apply them.

## Staging validation and rollout

- Validate the rule expression against the exact production host, route paths, and HTTP methods in staging or provider preview mode.
- Exercise normal login, registration, password reset, verification, demo request, invitation, and application submission from more than one network. Include a shared-NAT scenario.
- Confirm over-limit requests are stopped at the edge, legitimate requests are unaffected, and logs do not contain secrets or request bodies.
- Roll out in count mode first, review provider metrics, then enable enforcement for one route group at a time.
- Keep the previous rule values and a one-step rollback procedure available to the on-call operator.

## Inputs required before production configuration

- Selected CDN/WAF provider and production zone/account.
- Canonical hostnames and origin address(es).
- Provider proxy address ranges and the Laravel trusted-proxy configuration owner.
- Normal peak request rates for each protected path and the shared-NAT profile of the user base.
- Alert destination, on-call owner, and approved rollback procedure.
