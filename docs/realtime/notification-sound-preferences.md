# Notification sound preference implementation

## User behavior

- The notification dropdown contains notifications only; sound controls live in the Settings page under **In-app notification sound**.
- The setting uses an accessible button switch with `role="switch"` and `aria-checked`, matching the supplied reference. Native button behavior supports keyboard activation, the setting remains labeled, and reduced-motion preferences disable the switch animation.
- Changing the switch saves immediately. Enabling sound starts a short preview synchronously from the user's click, then confirms the saved preference through the existing shared toast system.
- If the save fails, the switch returns to its previous state and the shared error toast explains that the preference was not saved.
- The saved account preference applies on all authenticated pages for admin, organization, and applicant users. Reverb unread-count behavior and authorization are unchanged.

## Data and authorization flow

1. `user_preferences.notification_sound_enabled` stores the nullable boolean. `null` means the user has not explicitly selected a value; the effective default is off.
2. The `layouts.app` view composer loads that preference and renders its effective state onto the page body for the global sound handler.
3. The Settings switch sends a same-origin `PATCH` request with the CSRF token to `settings.notifications.sound.update`.
4. `UpdateNotificationSoundPreferenceRequest` requires an authenticated, active, verified user and validates the boolean. The controller updates only the current authenticated user's preference through `UserPreferencesService`.
5. The service serializes the update with the user's row lock and writes an immutable personal settings audit event when the value changes.

The switch is not embedded in the notification dropdown fragment, and its state does not depend on a full-page save of email-notification preferences.

## Existing browser preference migration

The previous version stored `marthire.notification-sound.enabled` in local storage. On a page load where the database preference is still `null`, the JavaScript migrates that existing value to the authenticated account preference without playing a sound. After a server preference is saved, the same local-storage key is kept as a non-authoritative mirror for same-browser tab synchronization. The server value is authoritative on subsequent page loads.

If browser storage is unavailable, the account preference and switch still work; cross-tab synchronization is optional. If the preference request fails, playback errors, or the audio asset is unavailable, notification handling continues normally.

## Files

- `database/migrations/2026_11_07_120000_add_notification_sound_preference_to_user_preferences.php`
- `app/Models/UserPreference.php`
- `app/Services/UserPreferencesService.php`
- `app/Http/Requests/UpdateNotificationSoundPreferenceRequest.php`
- `app/Http/Controllers/SettingsController.php`
- `routes/web.php`
- `app/Providers/AppServiceProvider.php`
- `resources/views/settings/settings.blade.php`
- `resources/views/layouts/app.blade.php`
- `resources/js/notification-sound.js`
- `resources/css/app.css`
- `tests/Feature/Settings/NotificationSoundPreferenceTest.php`
- `tests/js/notification-sound.test.mjs`

## Deployment and verification

Apply the included database migration before deploying the updated application code, and rebuild/deploy the Vite assets and public notification MP3 together. Do not release the code before the new column exists in production. If the host has no shell access, use its supported release or database-administration process to apply the migration; do not run schema changes from a page request.

Focused checks:

```sh
node --test tests/js/notification-sound.test.mjs tests/js/realtime-workspaces.test.mjs
npm run build
```

For the Windows test shell, run the focused Laravel tests against an isolated in-memory database:

```powershell
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = ':memory:'
$env:DB_URL = ''
$env:WORLD_DB_CONNECTION = 'sqlite'
$env:APP_ENV = 'testing'
vendor\bin\pest.bat --no-configuration tests\Feature\Settings\NotificationSoundPreferenceTest.php tests\Feature\LiveDashboardTablesTest.php
```

Manual checks should confirm the switch saves and restores for each role, legacy local-storage state migrates once, a new unread notification plays only when enabled, disabling stops sound, same-browser tabs synchronize, and failed requests leave the previous setting in place.

Implementation verification completed on 2026-11-07:

- JavaScript suite: 103 tests passed.
- Focused Laravel settings and dashboard tests: 8 tests and 65 assertions passed using the isolated in-memory SQLite test database.
- `npm run build`: completed successfully. Vite kept the existing unresolved `/asset/fonts/fa-brands-400.woff2` reference for runtime resolution; it did not block the build.
