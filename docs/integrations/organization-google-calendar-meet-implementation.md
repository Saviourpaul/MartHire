# Organization Google Calendar and Meet Integration

## Purpose

This document defines a safe, incremental implementation for letting an organization connect Google Calendar and use it when coordinating interviews. The first provider is Google Calendar, with Google Meet link generation for online interviews. The provider boundary should allow future communication integrations (for example, organization-specific Slack notifications) without coupling their credentials or delivery behavior to interview logic.

This is an implementation plan. The application code has not been changed as part of preparing it.

## Existing implementation

- Google sign-in is already wired through Laravel Socialite in `app/Http/Controllers/GoogleAuthController.php`. It authenticates a user; it does not authorize access to an organization's Calendar and must remain a separate flow.
- Google OAuth client settings are already present in `config/services.php` (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_REDIRECT_URI`). 
Calendar authorization needs its own callback URI and must request additional Calendar scopes only when an organization owner connects the integration.
- The interview workflow already creates, reschedules, cancels, and completes interviews through `OrganizationInterviewController` and `InterviewService`. Scheduling persists the interview transactionally and queues the candidate email through `CandidateMessageService` after commit.
- Online interviews support Google Meet, Zoom, Microsoft Teams, or another provider. At present, the organization supplies a meeting URL manually; the form and request require `meeting_link` for every online interview.
- Organization settings are limited to users authorized by the organization policy. The existing policy grants hiring settings to the Owner role. This is the appropriate permission boundary for connecting and disconnecting an organization's Google account.
- Workflow notifications, candidate email delivery status, activity logging, and live workspace refreshes already have shared services. Integration feedback should reuse those mechanisms.
- `config/services.php` contains a Slack bot token, but it is a platform-level setting rather than an organization connection. It should not be used to send one organization's recruitment data to a globally configured Slack destination.

## Scope and user experience

### Initial release

1. Add an **Integrations** section to organization settings.
2. Let an organization Owner connect or disconnect one Google Calendar account for the active organization. Show only the provider and connected account email; never expose tokens.
3. On interview scheduling, offer an opt-in to add the interview to the connected Google Calendar. For an online Google Meet interview, allow Marthire to request a new Meet conference instead of requiring the organization to paste a URL.
4. After Calendar confirms the event and, when requested, returns the Meet URL, use that URL in the existing invitation-email rendering and delivery workflow.
5. Synchronize event changes after interview rescheduling and remove or cancel the event after interview cancellation. Continue using the existing candidate email flow so the candidate does not receive duplicate calendar and email invitations by default.
6. Show Calendar synchronization state and a retry action to authorized organization users. A Calendar failure must not roll back an already scheduled interview or silently report that the event was synchronized.

The integration is optional. Organizations without a connected account retain the current manual meeting-link flow and existing email behavior.

### Later providers

Use an organization/provider connection model and provider adapter so Slack or other services can be added later. Do not include Slack delivery in the first release without deciding its organization-specific OAuth/install flow, channel selection, notification categories, consent, and retry policy.

## Proposed architecture

### Connection and authorization

- Add authenticated organization routes for `connect`, OAuth `callback`, and `disconnect`. Keep the callback separate from the Google sign-in callback.
- Restrict connection management to an active, verified Owner in the active organization. Recheck that membership in the callback because the Owner's access may have changed while they were at Google.
- Use a one-time, cryptographically random OAuth `state`, bind it in the Laravel session to the user and organization, compare it on callback, then consume it. Reject missing, expired, replayed, or mismatched state.
- Request offline access so background jobs can refresh access tokens. Request only the scopes needed to create and update Calendar events and request the connected account identity for display. Do not request Gmail, Drive, or unrelated user-data scopes.
- Keep Google sign-in unchanged. Use a dedicated configured callback URL such as `GOOGLE_CALENDAR_REDIRECT_URI`; the URI must be registered in the Google Cloud OAuth client.
- Handle reauthorization, denied consent, revoked refresh tokens, reconnect, and disconnect as explicit states. A revoked token marks the connection as requiring reauthorization and informs the organization through existing feedback mechanisms.

Google recommends validating OAuth `state`, using offline access for background API access, refreshing access tokens, and handling refresh-token invalidation. The Calendar API requires `conferenceDataVersion=1` to create Meet conference data; conference generation may be asynchronous, so the application must wait for the Meet entry point before sending an invitation that depends on it.

### Storage and secret handling

- Add an organization integration connection table keyed by organization and provider. Store provider account identity, granted scopes, status, connection timestamps, and a refresh token encrypted using Laravel's encrypted cast.
- Do not persist short-lived access tokens unless an operational need is demonstrated; obtain/refresh them for API calls and keep them short-lived.
- Do not include OAuth tokens in views, JSON, queue arguments, exception messages, logs, activity payloads, or audit snapshots. Use a hidden model attribute and redact provider responses.
- Add interview Calendar mapping/status fields or a small synchronization table containing the connection, external event ID, state, last successful sync, and a safe error code. Preserve interview records and external IDs on disconnect so the user can understand which events were previously linked.
- Do not store a Meet conference URL in a generic audit payload. The existing interview's meeting link is the appropriate restricted location.

### Provider and application services

Keep provider API details outside controllers and the interview domain service:

- `OrganizationIntegration` model/repository: organization/provider connection state.
- `GoogleOAuthService`: authorization URL, callback token exchange, account identity, refresh, revocation.
- `CalendarProvider` contract: create, update, and cancel an event; return event ID and optional Meet URL.
- `GoogleCalendarProvider`: Google Calendar REST API adapter with bounded timeouts, retry classification, idempotent event IDs, and response validation.
- `InterviewCalendarSyncService`: loads the current interview and connection, applies the active organization context, synchronizes the current interview version, persists status, and triggers existing workflow feedback.
- Queued jobs perform remote Calendar calls after the interview database transaction commits. Jobs should contain identifiers and minimal encrypted message data, not OAuth tokens. Use uniqueness/overlap protection and retry only transient errors.

Do not call Google from inside `InterviewService`'s database transaction. Preserve the existing transaction boundary and candidate-message outbox.

### Interview lifecycle

1. Validate the interview form as it does today. If the organization requests Calendar/Meet, confirm the current organization has a usable connection server-side; never trust a client-supplied integration ID.
2. Persist the interview and an idempotent sync intent in the same database transaction. Dispatch the sync job only after commit.
3. For an auto-generated Meet link, create or retrieve the Calendar event using a stable event identifier. If Google reports conference creation pending, retry/poll with bounded backoff. Once the Meet URL is present, update the interview only if its version is still current, then queue the existing candidate invitation using the current email template renderer.
4. For an event that uses a manually supplied meeting URL, queue the current candidate email without waiting for Calendar, while syncing the event independently.
5. On reschedule, update the mapped event using the latest persisted interview version. On cancellation, cancel/delete the mapped event with Calendar notifications disabled by default, then retain the existing candidate cancellation email.
6. If a stale job runs after another edit, it must not overwrite the latest interview or generate a second event. If the integration is disconnected or its refresh token is revoked, record a retryable or reauthorization-required state and preserve the interview.

Calendar event invitations should not be sent automatically in the initial release because Marthire already sends candidate interview invitations. This prevents duplicate candidate email. The connected calendar receives the event; the existing Marthire email contains the Meet link and response links.

### Recruitment notifications and team collaboration

- Continue using `WorkflowNotificationService`, `WorkspaceUpdateBroadcaster`, and existing candidate-message delivery state; do not introduce another toast/notification implementation.
- Send an in-app organization notification when a Calendar sync requires action, such as a permanent API error or reauthorization. Reuse current role-based recipients and delivery preferences.
- Keep provider API failures distinct from candidate email failures. The workspace should show Calendar sync status separately from invitation email status.
- In the first release, the event is created on the connected account's primary calendar. The organization should connect an account/calendar that its hiring team can access through Google Calendar sharing. A selectable shared-calendar feature can be added after confirming the required list/read scopes and UX.

## Security and reliability requirements

- Enforce `auth`, `verified`, `active.account`, `role:organization`, active organization context, and Owner authorization on connection actions.
- Verify the OAuth callback state and re-authorize the current user against the same organization before saving tokens.
- Enforce organization scoping in every connection and interview query. Reject cross-organization event IDs and never accept a provider connection ID from form input as authority.
- Encrypt refresh tokens at rest; ensure production `APP_KEY` is stable and protected. Redact all OAuth and Calendar response secrets from logs.
- Use HTTPS-only OAuth redirect URLs in production and register exact callback URLs in the Google Cloud Console.
- Use bounded HTTP connect/response timeouts, provider rate-limit handling, exponential backoff for transient failures, and no retry for permanent permission/validation errors.
- Make event creation idempotent and updates version-aware. Expose retry and reconnect states without disclosing raw Google API errors to candidates or unauthorized team members.
- Use the existing queue infrastructure for API calls. Production must have its configured queue worker running; do not make the web request wait on Calendar or fall back to inline API calls.
- Provide safe behavior when queue or Google is unavailable: the interview remains saved, sync state remains visible, and the candidate email behavior matches whether an auto-generated Meet URL was required.

## Files expected to be added or modified

### Add

- `app/Enums/OrganizationIntegrationProvider.php`
- `app/Enums/IntegrationConnectionStatus.php`
- `app/Enums/InterviewCalendarSyncStatus.php`
- `app/Models/OrganizationIntegration.php`
- `app/Integrations/Calendar/CalendarProvider.php`
- `app/Integrations/Google/GoogleOAuthService.php`
- `app/Integrations/Google/GoogleCalendarProvider.php`
- `app/Services/InterviewCalendarSyncService.php`
- `app/Jobs/SyncInterviewCalendar.php`
- `app/Http/Controllers/OrganizationIntegrationController.php`
- `app/Http/Requests/ConnectGoogleCalendarRequest.php` (if callback inputs require validation beyond OAuth response validation)
- Migrations for organization connections and interview event-sync state.
- `tests/Feature/Organizations/GoogleCalendarIntegrationTest.php`
- `tests/Unit/Integrations/GoogleCalendarProviderTest.php`
- `docs/integrations/organization-google-calendar-meet-implementation.md` (this plan)

### Modify

- `config/services.php` — add a dedicated Calendar callback setting while reusing the registered Google OAuth client credentials.
- `routes/web.php` — add protected organization integration and OAuth callback routes.
- `app/Models/Interview.php` and `app/Services/InterviewService.php` — persist sync intent and dispatch after commit while preserving current manual-link path.
- `app/Services/InterviewWorkspaceData.php` and `app/Http/Controllers/OrganizationInterviewController.php` — expose connection/sync state to authorized form and review views.
- `app/Http/Requests/SaveInterviewRequest.php` and `resources/views/organisations/interviews/_form.blade.php` — validate and display optional Calendar/Meet actions.
- `app/Services/CandidateMessageService.php` — queue the candidate invitation after an auto-generated Meet link becomes available, reusing existing email snapshots and delivery statuses.
- `app/Services/WorkflowNotificationService.php` or a focused integration notification service — notify organization members through existing notification/broadcast mechanisms.
- `app/Http/Controllers/SettingsController.php`, `resources/views/settings/settings.blade.php`, and `resources/css/app.css` — render the Owner-only integration card and connection state.
- `app/Models/Organization.php` — add a relationship for organization integrations.

No changes are planned to Google sign-in behavior, applicant document delivery, public job pages, or the existing notification sound/toast implementation.

## Verification plan

### Feature tests

- Owner can start OAuth and complete a callback with valid state; token is encrypted, account identity is saved, and no token is returned in the response.
- Recruiters, applicants, admins outside the intended organization route, suspended users, non-owner members, and cross-organization callbacks cannot connect, disconnect, or use another organization's integration.
- Missing, mismatched, expired, and replayed OAuth state is rejected without saving credentials.
- A disconnected organization retains the current manual meeting URL workflow unchanged.
- Calendar event creation adds a Meet link only when requested, waits for conference readiness, and queues the existing candidate email with that link.
- Reschedule updates the existing event; cancellation affects only its mapped event; repeated jobs do not duplicate Calendar events.
- A stale sync job does not overwrite a newer interview version.
- Transient API failures retry; revoked tokens set a reauthorization state; permanent errors remain visible to the organization and do not break interview review or candidate email status.
- Existing scheduling, rescheduling, cancellation, candidate response, pipeline, and invitation email tests continue to pass.

### Operational checks

- Configure `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, and `GOOGLE_CALENDAR_REDIRECT_URI` in each environment; register the exact redirect URI and enable Google Calendar API in the Google Cloud project.
- Confirm the Google OAuth consent screen and requested scopes are approved for the intended user population.
- Confirm the production queue worker, retries, failed-job alerting, and encrypted application key are configured before enabling Calendar sync.
- Exercise connect, create, reschedule, cancel, disconnect, revoked-token reconnect, and retry flows in a non-production Google Workspace account.

## External API references

- [Google OAuth 2.0 for web server applications](https://developers.google.com/identity/protocols/oauth2/web-server)
- [Google OAuth 2.0 best practices](https://developers.google.com/identity/protocols/oauth2/resources/best-practices)
- [Google Calendar event creation and Meet conference data](https://developers.google.com/workspace/calendar/api/guides/create-events)
- [Google Calendar Events: insert](https://developers.google.com/workspace/calendar/api/v3/reference/events/insert)
- [Google Calendar Events: update](https://developers.google.com/workspace/calendar/api/v3/reference/events/update)
