# Organization and applicant workspace updates

## Scope

This implementation completes the organization ↔ applicant workflow updates using the existing private Reverb channel, durable notification inbox, shared toast mechanism, and LiveTables fragments. Broadcast events only invalidate authorized views and carry allow-listed topic names plus short, non-sensitive toast text; refreshed records continue to come from Laravel routes that enforce their existing authorization rules.

## Behavior

| Event | Organization workspace | Applicant workspace |
| --- | --- | --- |
| Application submitted | Persisted `NewApplication` notice for active, verified owner/HR members and assigned recruiters/hiring managers; refresh candidate lists, application counts, dashboard metrics, and notification count | Existing submission receipt remains; application detail now refreshes as a live fragment |
| Pipeline stage changed | Refresh candidate list, open candidate review, application counts, and dashboard | Persisted `ApplicationStatus` notice with a link to the applicant-owned application; private user-channel event refreshes the dashboard, open application, and notification count and shows the existing shared toast |
| Interview scheduled, rescheduled, canceled, or completed | Persisted `InterviewUpdate` notice for authorized hiring-team members; refresh candidate/review views and notification count | Persisted `ApplicantInterviewUpdate` notice; applicant dashboard and open application refresh; a shared toast reports the update |
| Candidate confirms or declines | Existing authorized `InterviewResponse` notice is retained; organization lists and open review pages refresh, with a shared toast | Persisted response-receipt notice and applicant workspace invalidation |
| Interview email sent successfully | Persisted `CandidateEmailSent` notice for the authorized email-sending team; refresh review/interview views and show a shared success toast | No separate email-status notice; the applicant's interview lifecycle notice and invitation email remain in place |
| Interview email fails | Existing durable `CandidateEmailFailure` notice, bounded retry/final failure behavior, and notification email preference are retained; review/interview views refresh and a shared error toast appears | No delivery details are exposed to applicants |

Durable notice event keys derive from application history, interview activity, or email activity IDs, so retries do not create duplicate recipient notices. New inbox notices link to the relevant application only after the inbox rechecks the recipient’s ownership or organization/job assignment.

## Authorization and delivery

- Organization notifications reuse the existing active, verified membership and job-assignment recipient queries. Organization inbox reads further apply `OrganizationPermissions::scopeApplications`; interviewers only see applications that have reached the interview stage.
- Applicant notices are addressed to the application owner. Applicant inbox queries constrain stage and interview notices to applications owned by that user.
- Applicant Reverb updates use the existing private `App.Models.User.{id}` channel. The browser only subscribes with the authenticated applicant's own ID; the channel authorization rejects other users, suspended users, and unverified users.
- Organization invalidations use the existing private organization channel. Reverb events contain no application, interview, document, or account records.
- Application, pipeline, and interview events are broadcast after database commit. Email delivery status is saved first; notice failures are logged without undoing a successful email or causing the queue to send the same email again.
- Existing Laravel policies, signed/versioned interview response links, application ownership checks, and organization-context scoping remain authoritative.
- New interview lifecycle and send-success notices are in-app notices. Existing preference-controlled email notices, including final delivery failures, retain their current behavior.

## Files

### Updated

- Notification categories and inbox scope/action links: `app/Enums/NotificationCategory.php` and `app/Services/NotificationInboxService.php`.
- Durable workflow notices and private workspace invalidations: `app/Services/WorkflowNotificationService.php` and `app/Services/WorkspaceUpdateBroadcaster.php`.
- Interview lifecycle and email delivery outcomes: `app/Services/InterviewService.php` and `app/Services/CandidateMessageService.php`.
- Applicant application live fragment: `app/Http/Controllers/JobApplicationController.php` and `resources/views/client/application-show.blade.php`.
- Private applicant channel subscription: `resources/views/layouts/app.blade.php` and `resources/js/realtime-workspaces.js`.
- Live dashboard and candidate review topics: `resources/views/client/dashboard.blade.php`, `resources/views/organisations/dashboard.blade.php`, and `resources/views/organisations/candidates/show.blade.php`.
- Regression tests: `tests/Feature/Jobs/NativeApplicationTest.php`, `tests/Feature/Organizations/CandidateManagementTest.php`, `tests/Feature/Organizations/CandidateInterviewWorkspaceTest.php`, `tests/Feature/Organizations/LiveOrganizationTablesTest.php`, `tests/Feature/LiveDashboardTablesTest.php`, `tests/Feature/Realtime/AdminOrganizationBroadcastAuthorizationTest.php`, and `tests/Unit/WorkspaceUpdateBroadcasterTest.php`.
- Production frontend assets were rebuilt under `public/asset/marthire/build/`.

No database migration was required: notification categories and event keys use the existing notification schema, and the existing private user channel already provides per-user authorization.

## Verification

- Focused feature and unit regression runs passed **69 tests with 516 assertions**. Coverage includes authorized application recipients, applicant-owned status notices and live fragments, interview schedule/reschedule/cancel/confirm/decline/completion, email send success/failure notices, dashboard refresh markup, and private applicant channel access.
- Laravel Pint passed for the changed PHP files. PHP syntax checks and `git diff --check` passed.
- `npm run build` completed successfully. Vite reported that `/asset/fonts/fa-brands-400.woff2` was not resolved at build time and will be resolved at runtime.
