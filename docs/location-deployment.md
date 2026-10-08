# Location integration and deployment

The primary source is `https://world.bmbc.cloud/api`, without authentication. English requests use verified HTTPS, no redirects, a 2-second connection timeout and a 5-second total timeout. The upstream returns `success`, `message`, and `data`. ISO2 codes and parent-scoped state/city names are application identities; live API IDs remain internal and must never be replaced by bundled IDs.

Public stateless endpoints return `data: [{value,label}]` and `meta: {available,source,empty,fetched_at}`:

- `/api/locations/countries`
- `/api/locations/states?country=NG`
- `/api/locations/cities?country=NG&state=Lagos`

Sources: `api`, `cache` (24 hours), `stale` (last-known-good, up to 90 days), then `snapshot`. Unknown selections return 422, unavailable catalogs return friendly 503, and the independent per-IP 60/minute limiter returns 429 with Retry-After. Per-catalog locks, a 60/minute upstream budget and 60-second failure cooldown prevent request storms. Unexpected empty lists fall back to known data; genuinely empty child tiers are optional. Unavailable data never bypasses membership validation.

The dedicated file store in `config/locations.php` does not use CACHE_STORE or SESSION_DRIVER. Deploy all of `resources/locations`: 250 countries, 5,011 states, 150,719 city records deduplicated by scoped name. The pinned source is nnjeim/world 1.1.39, commit `1a419baf4e9dc6e83bdb392c8c8ebf328135856a`. The bundle includes MIT attribution and SHA256 input/partition checksums. Runtime reads only the country index and selected state's city partition, never the original 53 MB city JSON. The generator under `scripts/` is build-time only; after package removal supply the pinned repository datasets as its argument. Dataset upgrades require explicit version, checksum, and data review.

## Deploy and verify before cleanup

1. Back up the database using your host's backup/export facility. Preserve user/application records and all eight reference tables listed below. Record backup location, timestamp, checksums and row counts, and verify restore into a separate database. Never put backups in `public/`.
2. Deploy Composer lockfile dependencies (`composer install --no-dev --optimize-autoloader`) and frontend assets (`npm ci && npm run build`) in a coordinated maintenance window. Stop old form submissions racing the schema transition. The World package must no longer be registered/discovered.
3. Run `php artisan migrate --force`. The guarded canonical migration adds nullable selection fields without foreign keys and backfills only unambiguous country/state matches. Existing canonical values and historical nationality/LGA values are preserved. LGAs never become cities and backfill does not confirm a location. Normal migrations do not drop lookup tables.
4. Ensure PHP can write `storage/framework/cache/locations`, including lock files. Keep it outside the web root and persistent across releases. With multiple instances, share this independent file store on a persistent, lock-capable filesystem for shared cooldown/upstream budgets. Otherwise each node has its own budget and upstream 429s still use fallback. Do not use the main database for location cache/rate limiting. Configure trusted proxies safely for real client IPs.
5. Rebuild config/route caches (`php artisan config:cache`, `php artisan route:cache`). Verify only the three new location endpoints remain, not package DB-backed routes or the old LGA route. Allow outbound HTTPS to the configured host; never disable TLS checks.
6. Verify profile/application prefill, cancellation, error restoration, rapid selection changes, dependent lists, summaries, empty tiers, and retry feedback. Legacy completed profiles remain complete until the next successful edit/application confirms a canonical selection.
7. In staging, disconnect the main database with CACHE_STORE=database and SESSION_DRIVER=database: all three location endpoints must work without session cookies. Repeat with a cold cache and the upstream unavailable. Private authenticated page rendering and record saving still need the application database.

Only after the backup and deployment checks pass, run the **separate opt-in** migration:

```sh
LOCATION_REFERENCE_BACKUP_CONFIRMED=1 LOCATION_DEPLOYMENT_VERIFIED=1 php artisan migrate --path=database/location-cleanup --force
```

PowerShell:

```powershell
$env:LOCATION_REFERENCE_BACKUP_CONFIRMED = '1'
$env:LOCATION_DEPLOYMENT_VERIFIED = '1'
php artisan migrate --path=database/location-cleanup --force
Remove-Item Env:LOCATION_REFERENCE_BACKUP_CONFIRMED
Remove-Item Env:LOCATION_DEPLOYMENT_VERIFIED
```

Cleanup removes only `nigeria_local_government_areas`, `nigeria_states`, `cities`, `states`, `countries`, `timezones`, `currencies`, and `languages`, in dependency order. It checks the canonical schema and never disables foreign keys. An unexpected dependency must be investigated, not forcibly bypassed. Do not invoke cleanup through HTTP or a scheduler. Implementation tests do not run cleanup against the application's database.

Rollback retains user/application canonical values. Removed reference records require restoration from the verified backup, not a fresh API seed. Preserve the prior code/package release for rollback. Do not reverse historical migrations that drop nationality/LGA columns.

## Regression checks

```sh
php artisan test tests/Feature/WorldLocationServiceTest.php tests/Feature/LocationMigrationTest.php tests/Feature/ProfileTest.php tests/Feature/ApplicationFormWorkflowTest.php
node --test tests/js/location-selector.test.mjs
npm run build
```

The affected location/profile/application/authentication checks pass, as do the selector race/retry tests and frontend build. Canonical-location administrative search and country-scoped geographic reporting also have focused passing tests. Broader existing regression failures remain outside this change: dashboard route/view expectations, registration/password-reset/password-confirmation expectations, and an administrative report fixture calling the removed `approved()` factory method. Resolve these before declaring the entire platform regression-clean.

The PHP adapter's real upstream request timed out in this development environment and correctly used the bundled fallback. Confirm live outbound HTTPS in production with TLS verification still enabled. A connected browser was unavailable during implementation; complete the manual UI checks above before setting `LOCATION_DEPLOYMENT_VERIFIED`. Composer reported advisories in remaining dependencies; perform a fresh `composer audit` and review upgrades separately before release.

Monitor location cache permission warnings, fallback warnings, and 503/429 rates. The authentication, verification-mail, welcome-mail and password-reset flows are unchanged.

For manual local browser verification without accessing the main database, set `APP_ENV=testing` in a temporary shell and run `php -S 127.0.0.1:8127 -t public tests/browser/location-preview.php`. Visit `/profile` and `/jobs/1/apply` on that loopback host. The guarded test router uses only an in-memory SQLite database and bundled locations; it is not an application route and must never be used as a production server. Stop it after checking the forms. Automated tests cover provider failures and out-of-order responses; a connected browser is still needed for visual checks.
