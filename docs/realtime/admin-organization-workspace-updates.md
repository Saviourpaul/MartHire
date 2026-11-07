# Admin and organization workspace updates

## Scope

This implementation adds private Reverb updates between the admin workspace and organization workspaces. It complements the existing LiveTables refresh behavior, so dashboard tables continue to refresh on their existing interval if a WebSocket is unavailable.

The broadcast payload is an invalidation signal only: an allow-listed set of topic names and, for selected moderation actions, a short user-facing message and toast type. It contains no job, application, candidate, account, or document data. The browser asks existing Laravel routes for the refreshed, authorized HTML fragments.

## Behavior

| Change | Admin workspace | Affected organization |
| --- | --- | --- |
| Organization workspace created | Refresh organization and user views | — |
| Organization profile updated | Refresh organization and job views | Refresh job and team views |
| Organization publishes, edits, closes, or deletes a job | Refresh job views | — |
| Admin suspends or deletes an organization job | Refresh admin job views | Refresh job, candidate, application, and notification views; suspension also creates a durable inbox notification |
| Admin creates or changes an account | Refresh user views; organization changes also refresh organization views | When an organization member’s access changes, notify the other organization managers and refresh team-related views |

Admin and organization views subscribe only to their own private workspace channel. Unrelated applicants and organizations do not receive these updates.

## Delivery and authorization

- `WorkspaceUpdateBroadcaster` validates topic names and dispatches after the database transaction commits. `WorkspaceUpdated` uses Laravel's synchronous broadcast contract, so Reverb workspace invalidations do not wait for a queue worker. Broadcast dispatch errors are logged without undoing the completed business action.
- `WorkspaceUpdated` is a private-channel event with a deliberately small payload.
- `routes/channels.php` authorizes administrators by active, verified account status. Organization subscriptions also require an active, verified organization account and an active membership in the requested organization.
- `resources/js/realtime-workspaces.js` converts an accepted event into the existing `nw:data-changed` event. The existing LiveTables code then refreshes only matching regions; the existing Marthire toast helper displays messages.
- The authenticated header bell dropdown shows the five most recent authorized notifications, can mark individual or all items read, and refreshes through LiveTables when opened or when a notification event arrives. The unread badge also refreshes every 60 seconds in a visible tab to recover from a missed or unavailable broadcast. The Notifications destination also remains in the sidebar.
- Organization job suspension, account-access changes, applicant account-status changes, and candidate workflow actions also use the existing workflow-notification and email-outbox system. These notices remain in the database if the recipient was offline. Organization members can control email delivery from Settings.
- Inbox queries constrain the recipient to their own notification record and re-check organization, job, and membership access before displaying moderation details.

## Files

### Added

- `app/Events/WorkspaceUpdated.php`
- `app/Services/WorkspaceUpdateBroadcaster.php`
- `resources/js/realtime-workspaces.js`
- `database/migrations/2026_11_05_120000_add_organization_moderation_email_to_member_preferences.php`
- `resources/views/notifications/index.blade.php`
- `tests/Feature/Realtime/AdminOrganizationBroadcastAuthorizationTest.php`
- `tests/Feature/Realtime/AdminOrganizationModerationNotificationsTest.php`
- `tests/Unit/WorkspaceUpdateBroadcasterTest.php`

### Updated

- Reverb configuration and channel authorization: `config/reverb.php`, `routes/channels.php`, `.env.example`, `resources/js/echo.js`, and `composer.json`.
- Workspace triggers and durable moderation notices: `app/Services/AccountLifecycleService.php`, `app/Services/OrganizationProvisioner.php`, `app/Services/OrganizationSettingsService.php`, `app/Services/WorkflowNotificationService.php`, `app/Services/NotificationInboxService.php`, `app/Http/Controllers/Admin/JobManagementController.php`, and `app/Http/Controllers/JobController.php`.
- Inbox, preferences, and navigation: `app/Http/Controllers/NotificationController.php`, `app/Http/Controllers/SettingsController.php`, `app/Http/Requests/UpdateNotificationPreferencesRequest.php`, `app/Enums/NotificationCategory.php`, `app/Models/OrganizationMemberPreference.php`, `app/Providers/AppServiceProvider.php`, `routes/web.php`, `resources/views/settings/settings.blade.php`, and `resources/views/layouts/navigation.blade.php`.
- Frontend integration and styling: `resources/js/app.js`, `resources/views/layouts/app.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/components/icon.blade.php`, and `resources/css/app.css`.

## Local setup

The `.env.example` provides local Reverb values. Copy the relevant settings into `.env`, and keep `REVERB_APP_ID`, `REVERB_APP_KEY`, and `REVERB_APP_SECRET` consistent between the server and browser build variables. Start the existing Laravel development stack with:

```sh
composer run dev
```

This starts the web server, queue listener, Vite, and Reverb. The queue listener remains useful for email-outbox and other queued jobs; workspace invalidation broadcasts themselves no longer require it. The browser subscribes only when a Reverb key and host are present; without a socket, the unread badge falls back to periodic refresh and LiveTables continues its normal page-specific polling.

## Production deployment

1. Install the locked Composer and npm dependencies and build the frontend assets (`composer install --no-dev --optimize-autoloader`, `npm ci`, `npm run build`).
2. Apply the database migration using the host’s deployment or database administration facility: `php artisan migrate --force`.
3. Set a unique Reverb app ID, key, and secret on the server. Set `BROADCAST_CONNECTION=reverb`, `REVERB_HOST`, `REVERB_PORT`, and `REVERB_SCHEME` for the server endpoint, and matching `VITE_REVERB_APP_KEY`, `VITE_REVERB_HOST`, `VITE_REVERB_PORT`, and `VITE_REVERB_SCHEME` before building the frontend.
4. Set `REVERB_ALLOWED_ORIGINS` to the exact public site hostnames (hostnames only, comma-separated). Do not use `*` in production.
5. Run `php artisan reverb:start` as a supervised, long-running process. Configure the hosting proxy/firewall to forward the Reverb WebSocket endpoint over TLS, and restart Reverb after deployment or configuration changes. A queue worker is not required for `WorkspaceUpdated`; it is still required for queued email-outbox delivery and other queued jobs.

Reverb is required for immediate WebSocket delivery. If the hosting plan cannot keep Reverb running or proxy WebSocket upgrades, run the Reverb process on a supported external host or use a managed broadcast provider. The unread badge and existing LiveTables polling provide a fallback when broadcasts are unavailable.

## Verification

Verification results for the synchronous-broadcast update are recorded in `synchronous-workspace-broadcasts.md`. The frontend production bundle should be rebuilt after changes to the Echo or workspace JavaScript modules.
