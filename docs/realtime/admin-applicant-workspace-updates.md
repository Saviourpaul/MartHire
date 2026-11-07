# Admin and applicant workspace updates

## Scope

This implementation connects applicant account activity to the admin workspace and makes admin account-status decisions visible to applicants. Durable notices use the existing workflow notification inbox and email outbox. Reverb messages are private workspace invalidation signals; the browser refreshes the affected authorized page data through the existing LiveTables flow.

## Behavior

| Applicant or admin action | Admin workspace | Applicant |
| --- | --- | --- |
| Applicant registers | Active, verified admins receive a persisted inbox notice; the user table and notification summary are invalidated | Existing registration and email-verification flow is unchanged |
| Applicant changes profile details, preferences, skills, résumé, employment, or education | A field-name-only audit record and persisted admin inbox notice are created; user and notification views are invalidated | Existing profile save and checklist behavior is retained |
| Applicant submits an unchanged profile section | No profile activity audit or admin notice is created | Existing save response is retained |
| Admin changes an applicant's status | User table and notification summary refresh; other eligible admins receive a persisted moderation notice | A persisted account-status notice and mandatory email are queued for a verified applicant |

Applicant registration and profile update messages contain no profile field values. Realtime events contain only allow-listed topic names and generic messages; the browser obtains current records from authorized Laravel routes.

## Access control and delivery

- Admin notices are addressed only to active, verified administrators and are stored per recipient. The acting administrator is excluded from the moderation notice because the existing moderation action already gives that user feedback.
- Applicant status notices are restricted to the applicant account that was moderated. A verified applicant receives an email even when suspended and unable to access the authenticated inbox. The email exception is limited to the `applicant_account_status` category, the same user's account subject, and a verified applicant; other email eligibility and preference checks still apply.
- Applicant personal profile changes create audit records containing changed field names only. They do not record profile values, addresses, or uploaded file contents.
- No-op saves are checked before updating applicant profile sections, so they do not create duplicate audit entries, notifications, or optional admin emails.
- Admin subscriptions remain protected by the existing active-and-verified admin channel authorization. Workspace update broadcasts carry no applicant record or document data.
- Notification and broadcast failures are logged without rolling back a successful registration or profile save.

## Implementation files

### Added

- `app/Services/ApplicantAccountActivityService.php`
- `tests/Feature/Realtime/AdminApplicantAccountUpdatesTest.php`

### Updated

- Applicant registration and profile activity: `app/Http/Controllers/Auth/RegisteredUserController.php`, `app/Http/Controllers/ProfileController.php`, and `app/Http/Controllers/ApplicantProfileController.php`.
- Notification categories, recipients, persistence, and status email delivery: `app/Enums/NotificationCategory.php`, `app/Services/WorkflowNotificationService.php`, `app/Services/NotificationInboxService.php`, and `app/Services/NotificationEmailService.php`.
- Account moderation and live invalidation: `app/Services/AccountLifecycleService.php` and `resources/views/admin/dashboard.blade.php`.
- Dashboard regression coverage: `tests/Feature/LiveDashboardTablesTest.php`.

The Reverb transport, private workspace channels, shared toast mechanism, notification dropdown, and LiveTables infrastructure are reused from the Admin ↔ Organization workspace work documented in `admin-organization-workspace-updates.md`.

## Verification

The related regression run passed **61 tests and 376 assertions**, covering applicant registration, profile privacy and no-op saves, notification preferences, applicant suspension and activation, account status email delivery, existing organization moderation notices, LiveTables dashboard refresh, admin user table actions, user management, registration, profile updates, and applicant profile sections.

PHP syntax checks passed for the changed service, controller, enum, and feature-test files. `git diff --check` reported no whitespace errors. The test runner in this local sandbox required a temporary bootstrap file under the system temp directory to load the project's Composer autoloader; the temporary file was removed after the test run.
