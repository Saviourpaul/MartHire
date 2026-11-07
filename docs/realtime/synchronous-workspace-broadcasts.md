# Synchronous Reverb workspace broadcasts

## Purpose

Workspace invalidations and notification toasts should arrive over Reverb without waiting for Laravel's queue worker. The durable notification records and email outbox remain the source of truth; WebSocket messages carry only allow-listed topics and a short generic message.

## Runtime flow

1. A workflow commits its database changes and durable notification record.
2. `WorkspaceUpdateBroadcaster` defers dispatch with `DB::afterCommit()` when called inside a transaction.
3. `WorkspaceUpdated` implements `ShouldBroadcastNow`, so Laravel publishes the small invalidation directly to Reverb after commit instead of placing a `BroadcastEvent` on the queue.
4. The authenticated browser receives `.workspace.updated`, updates the shared toast, refreshes the unread count, and asks existing authorized LiveTables endpoints for fresh HTML.
5. If a message is missed or Echo is unavailable, visible authenticated tabs refresh the unread count every 60 seconds. Durable inbox entries remain available when a user returns.

The synchronous broadcast is best suited to this small invalidation event. The broadcaster catches and logs publish failures after the database commit, and Reverb's HTTP client has bounded connection and total timeouts. A Reverb outage therefore does not undo the business update. The fallback refresh may be delayed until the next visible-tab poll.

## Applicant account status updates

`WorkflowNotificationService::applicantAccountStatusChanged()` now publishes the applicant's status notice to the existing private `App.Models.User.{id}` workspace after the notice is stored. The shared broadcaster defers that event until the surrounding account-status transaction commits. The event contains a generic status message and the `notifications` topic only; it does not include account data.

Channel authorization remains private and unchanged: the user ID must match the authenticated account, and the account must be active and verified to authorize a new subscription. A suspended applicant's durable inbox notice and email remain available even when they cannot establish a new authenticated socket.

## Local development

The existing `composer run dev` script starts Laravel, the queue listener, Vite, and Reverb. The queue listener is still used for email-outbox and other queued jobs; it is not part of `WorkspaceUpdated` broadcast delivery. Keep `BROADCAST_CONNECTION=reverb`, the Reverb application credentials, and the `REVERB_*` / `VITE_REVERB_*` values aligned. `VITE_REVERB_*` values are compiled into the browser bundle, so rebuild assets when those values change.

## Production requirements

Reverb must run as a persistent process. Laravel's Reverb documentation recommends a process manager and describes routing a public WebSocket endpoint through a TLS reverse proxy to Reverb's internal listener. The server bind settings (`REVERB_SERVER_HOST` / `REVERB_SERVER_PORT`) are distinct from the host and port the Laravel publisher uses (`REVERB_HOST` / `REVERB_PORT`). The browser-facing `VITE_REVERB_HOST`, port, and scheme must point to a public `wss` endpoint; `localhost` is only suitable for local development. See the official [Laravel 12 Reverb deployment guide](https://laravel.com/docs/12.x/reverb).

Deployment checklist:

- Use unique production `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` values. Keep the secret server-side; only the public app key belongs in the frontend build.
- Set `REVERB_ALLOWED_ORIGINS` to the site's exact hostnames. Do not use `*` in production.
- Route WebSocket upgrade requests on the public TLS endpoint to the persistent Reverb process. Do not expose the internal listener directly unless the network policy explicitly requires it.
- Keep Reverb under Supervisor, systemd, or an equivalent process manager and restart it after deployment/configuration changes.
- Keep a queue worker or scheduled queue runner for email-outbox delivery and any other queued jobs. Workspace WebSocket invalidations do not depend on that worker.
- If the hosting plan cannot keep a daemon alive or proxy WebSocket upgrades, host Reverb on a supported separate service or switch to a managed broadcasting driver. Environment variables alone cannot make a shared-hosting PHP request behave as a persistent WebSocket server.
- Ensure PHP can reach the Reverb publish endpoint. The configured connect and total timeouts bound the impact of an unavailable endpoint.

The queue-independent design does not make Reverb itself queue-independent in the hosting sense: the Reverb WebSocket server still has to stay online for immediate delivery.

## Fallback and observability

- Reverb is the immediate path for toasts, dropdown refreshes, and workspace invalidation.
- The unread-count endpoint is polled every 60 seconds only while the authenticated page is visible and online. Returning to a hidden tab refreshes it immediately.
- Publish failures are logged as `Workspace update could not be broadcast`; they do not roll back committed work.
- Verify the browser's WebSocket reaches the public Reverb endpoint, private-channel authorization succeeds, and the Reverb process receives the publish. If the socket is unavailable, confirm the unread badge converges on its next poll.

## Tests and build

- `tests/Unit/WorkspaceUpdateBroadcasterTest.php` verifies allow-listed topics, private channels, synchronous dispatch after commit, and that broadcast events are not queued.
- `tests/Feature/Realtime/AdminApplicantAccountUpdatesTest.php` verifies applicant notification creation, email behavior, and applicant/private-workspace update requests for suspension and activation.
- `tests/js/realtime-workspaces.test.mjs` verifies event topic filtering, the shared toast, and unread-count fallback when Echo is unavailable.
- Rebuild browser assets with `npm run build` after changing Reverb frontend configuration or JavaScript.

Current implementation verification: focused PHP tests pass (8 tests, 45 assertions), JavaScript tests pass (3 tests), Pint passes for changed PHP files, and the Vite production build succeeds. The full suite reports 388 passing tests and 3 existing failures in `OrganizationPublicVisibilityTest` (missing logo initials), `CandidateReviewWorkspaceTest` (fixture avatar file is absent on the fake public disk), and `PublicMediaTest` (Cache-Control directive order differs from the expected exact string).

On this Windows checkout, `php artisan test` could not open the XML bootstrap path from its test subprocess. The same suite was run with Pest directly using `--no-configuration` and the SQLite/testing environment values from `phpunit.xml`.
