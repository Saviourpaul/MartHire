# NearbyWorks Security Audit

**Audit date:** 18 November 2026  
**Scope:** Application source, routes, authentication, organization access controls, file handling, frontend output, runtime configuration, and locked dependencies.  
**Approach:** Read-only component reviews by development agents, local source and route searches, package-manager audits, focused regression tests, and a localhost-only Chrome/Playwright check. No production data or production systems were used.

## Executive summary

The review confirmed four application-level issues and one production hardening gap. The fixes are in the current code: Workable applications now have applicant-only access and rate limits; generated reset links reject untrusted hosts; CSV exports neutralize spreadsheet formulas; session cookies default to `Secure` in production; and global responses receive baseline browser security headers.

The locked Composer dependencies initially had 37 advisories across 11 packages, including a critical Laravel framework advisory. Compatible updates were applied, and the final Composer audit reports no advisories. The full npm audit still reports 13 high-severity affected package entries through the development/build dependency chain for Picomatch. No patched Picomatch release was available during this audit; production npm dependencies report zero vulnerabilities.

## Findings and remediation

| Severity | Finding | Status |
|---|---|---|
| Critical package advisory | Laravel 12.62.0 was in the affected range for TokenGuard authentication bypass (CVE-2026-114509). | Fixed by updating Laravel to 12.73.0. No TokenGuard guard or API route was found in the application, so exploit reachability was not confirmed. |
| High / Medium package advisories | Composer audit found 37 advisories across 11 packages, including Guzzle, CommonMark, Symfony, Flysystem, phpseclib, and Symfony HtmlSanitizer. | Fixed by updating compatible packages; final `composer audit --locked` is clean. |
| Medium | Workable application endpoints required only authentication. Organizations, admins, unverified applicants, and suspended applicants could reach the form or submission handler, and submissions lacked the intended application limiter. | Fixed in `routes/web.php`; regression tests cover role, verification, account status, and rate limiting. |
| Medium | Password-reset URLs were assembled from the current request host. A forged Host header could influence the reset link sent by email. | Fixed by enabling Laravel trusted-host middleware in `bootstrap/app.php`, using the configured application URL and its subdomains as the trusted host pattern. |
| Medium | Applicant names and organization-controlled job text flowed into CSV exports without spreadsheet-formula neutralization. | Fixed with a shared sanitizer used in all three CSV writers. Formula-leading cells are prefixed as literal text, including values preceded by whitespace or control characters. |
| Medium, conditional | `SESSION_SECURE_COOKIE` had no production-safe default. If unset in production, session cookies could lack the Secure attribute. | Fixed by defaulting the cookie to Secure when `APP_ENV=production`; an explicit environment value still overrides the default. |
| Low | Ordinary application responses did not have global anti-framing and content-sniffing protections. | Fixed with a global middleware that adds `X-Frame-Options`, `X-Content-Type-Options`, a frame-ancestors policy, and a referrer policy. More restrictive response-specific headers are preserved. |
| High, development-only | The npm audit reports 13 affected packages in the build tooling chain through Picomatch’s ReDoS advisory (affected through 4.0.7). | Unresolved: no patched Picomatch release was available. See residual risks. |

### 1. Laravel and Composer advisories — fixed

The starting lockfile contained Laravel Framework `12.62.0`, Guzzle `7.12.3`, CommonMark `2.8.2`, Symfony `7.4.13`, Symfony HtmlSanitizer `7.4.20`, Flysystem `3.35.0`, and phpseclib `4.0.2`. The critical Laravel advisory was CVE-2026-114509. Other reported issues included denial of service and sanitizer, URL, header, and path-handling defects in affected dependencies.

The lockfile now resolves Laravel `12.73.0`, Guzzle `7.16.3`, CommonMark `2.10.7`, Symfony `7.4.23`, Symfony HtmlSanitizer `7.4.23`, Flysystem `3.36.0`, and phpseclib `4.0.3`. Composer completed a fresh audit with **“No security vulnerability advisories found.”** The only configured authentication guard is the session-based `web` guard; no TokenGuard or API route registration was found. The framework update was still applied to remove the vulnerable framework version.

### 2. Workable applicant access — fixed

The Workable application GET and POST routes had only `auth`, unlike the native application routes. The handler creates a queued application and stores uploaded documents, so the missing role and account-state checks allowed non-applicants or ineligible accounts to submit candidate data. The POST also did not use the defined `application-submit` limiter.

Both Workable routes now require authenticated, verified, active applicants. The POST uses `throttle:application-submit`. Denied requests are stopped before provider requests, database writes, queue dispatch, or file storage.

### 3. Host header poisoning of reset links — fixed

`AppServiceProvider` builds password-reset URLs using the current request host. Without trusted-host validation, a forged Host header could make a reset email point to an attacker-controlled domain. If the recipient followed that link, the reset token could be exposed.

`bootstrap/app.php` now enables Laravel's `TrustHosts` middleware. It derives the trusted host pattern from `APP_URL`; production deployments must set `APP_URL` to the canonical application domain. The production-mode Laravel HTTP kernel returned **400** for an untrusted host and generated secure session cookies when no `SESSION_SECURE_COOKIE` override was set.

### 4. Spreadsheet formula injection — fixed

`fputcsv` correctly quotes CSV delimiters but does not prevent spreadsheet software from evaluating cells that begin with `=`, `+`, `-`, or `@`. User-controlled names, emails, phone values, job titles, categories, and employer names could reach export cells.

`App\Support\CsvCellSanitizer` now protects rows in:

- `Admin\UserManagementController`
- `Admin\ReportController`
- `OrganizationApplicationController`

The sanitizer preserves ordinary strings and numeric values, and prefixes formula-leading text after Unicode whitespace or control characters. Unit and export tests cover `=`, `+`, `-`, `@`, tabs, line breaks, non-breaking space, and a BOM.

### 5. Browser protections — fixed

The global `SecurityHeaders` middleware adds `nosniff`, same-origin framing restrictions, a `frame-ancestors 'self'` policy, and a cross-origin referrer policy. It does not replace stricter route-level policies such as `no-referrer` or the sandbox policy on public media.

HSTS is not added by application code because TLS termination and trusted-proxy configuration are deployment-specific. Configure HSTS at the HTTPS edge after confirming all supported hosts are HTTPS-only.

## Review coverage and negative findings

- **Authentication and recovery:** Reviewed login and registration throttles, verified and active-account middleware, session-version invalidation, password-reset broker tokens, expiry/throttling, and account-enumeration behavior. No additional reset-token reuse or session-fixation bypass was confirmed.
- **Authorization and organization data:** Reviewed admin and organization route groups, policies, organization-scoped queries, and ownership checks. No additional IDOR or tenant-boundary bypass was confirmed. `routes/api.php` is not registered and no API routes were found.
- **Database access:** Searched raw SQL and query-builder escape hatches. No user-controlled SQL interpolation was confirmed; observed raw expressions are bound or internal constants.
- **XSS and frontend:** Reviewed raw Blade output, HTML sanitization, JSON-LD encoding, and project-authored JavaScript sinks. Employer job HTML is sanitized, JSON-LD encodes HTML-sensitive characters, icon markup comes from a fixed allowlist, and no additional exploitable output path was confirmed.
- **Uploads and private files:** Reviewed document upload, storage, preview, and download paths. Private documents use authorized routes and private storage; admin document paths and preview signatures are constrained. Public media uses path/type allowlists and bounded image validation. No confirmed upload execution, traversal, or private-document access bypass was found.
- **Secrets:** A redacted-pattern scan of tracked source files found no committed credential patterns. `.env` is ignored and was not opened or printed. This was not a historical Git-secret scan.
- **Third-party URLs:** Workable pagination validates the provider hostname. No attacker-controlled path to an unsafe `paging.next` value was confirmed; enforcing HTTPS, the expected port, and the expected API path remains a defense-in-depth recommendation.

## Residual risks and deployment actions

1. **Development dependency advisories:** Full `npm audit` reports 13 high-severity affected package entries in the development/build graph (`anymatch`, `chokidar`, `fast-glob`, `fdir`, `laravel-vite-plugin`, `micromatch`, `picomatch`, `readdirp`, `sucrase`, `tailwindcss`, `tinyglobby`, `vite`, and `vite-plugin-full-reload`). The root cause is the Picomatch ReDoS advisory; the current registry release line ends at vulnerable Picomatch `4.0.7`. `npm audit fix --dry-run` found no non-breaking changes. The forced proposal would downgrade `laravel-vite-plugin` to `0.2.4`, which is a breaking major change and would not safely resolve the wider graph. Do not expose the Vite development server to untrusted networks; update when upstream releases a patched Picomatch version. `npm audit --omit=dev` reports **0 vulnerabilities**.
2. **Redis outage behavior:** `RATE_LIMIT_FAIL_OPEN=true` deliberately allows requests when Redis is unavailable. In that state, application abuse counters are not reliably enforced. Keep Redis health alerts enabled and use edge/WAF rate limits during outages, as required by the existing availability policy.
3. **Production configuration:** Keep `APP_ENV=production`, `APP_DEBUG=false`, a canonical `APP_URL`, and `SESSION_SECURE_COOKIE=true` in production. The code defaults the cookie to Secure for production, while an explicit false value can override that default. Configure trusted proxy addresses narrowly at the hosting edge; do not trust arbitrary forwarded headers.
4. **WAF and infrastructure:** This review did not access production configuration, hosting controls, TLS certificates, DNS, or a live WAF. Confirm edge rate limits, HTTPS enforcement, HSTS, Redis alerts, and secret rotation policies in the production environment.

## Verification results

- **Composer:** Final `composer audit --locked --no-interaction` — no advisories.
- **npm runtime:** `npm audit --omit=dev --audit-level=low` — 0 vulnerabilities. Full audit — 13 high-severity affected package entries in development/build tooling.
- **Focused PHP/Pest checks:** 32 tests passed, 156 assertions. This includes the sanitizer and host/header unit tests, admin and organization CSV export tests, and the Workable apply suite.
- **Chrome / Playwright:** On a local-only server, the Workable apply page redirected unauthenticated users to `/login`; the login page returned 200 with `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, and a frame-ancestors policy.
- **Production-mode HTTP-kernel smoke check:** An untrusted Host header returned 400. With `APP_ENV=production` and `SESSION_SECURE_COOKIE` unset, the session config defaulted to Secure and both session and XSRF cookies had the Secure attribute.
- **Frontend build:** `npm run build` completed. Existing non-security warnings remain for a Font Awesome asset reference that is resolved at runtime and a JavaScript chunk above 500 kB.

The repository's `phpunit.xml` could not be read by PHP's `is_readable()` in this Windows sandbox, so focused tests ran with Pest's `--no-configuration` option and explicit testing environment variables. A broader combined run also encountered five unrelated expectations involving organization approval/redirect behavior; those were not baseline-compared or changed as part of this audit. The focused security regression suites listed above pass.
