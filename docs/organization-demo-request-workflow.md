# Organization demo request workflow

## Summary

Organization accounts are requested through the public demo form. Administrators review each request and can offer a guided demo, request more information, approve a trial workspace, decline the request, or mark it as a duplicate. Approved contacts create their own password through a short lived, single use invitation. The invitation continues through the existing organization provisioning and onboarding services.

Applicant registration and the shared login flow are unchanged.

## Request lifecycle

| State | Meaning / next action |
| --- | --- |
| `submitted` | Received and waiting for review. |
| `under_review` | Assigned to an administrator; also used after the requester replies. |
| `information_requested` | A signed, one use link was emailed to the requester. A valid reply returns the request to review. |
| `guided_demo_offered` | The external booking link was emailed. No custom scheduler is introduced. |
| `approved` | A trial invitation was queued; the contact must create their account and password. |
| `rejected` | Closed with an internal reason and a generic requester notification. |
| `duplicate` | Closed and linked to a possible existing request when applicable. |

After invitation acceptance, `onboarding_status` moves through the existing organization onboarding flow to `in_progress` and `completed`.

## Submission and duplicate handling

- The public form collects the contact name, work email, organization name, phone, and organization size. Industry remains optional for compatibility with existing records.
- Server validation normalizes the email and phone. The public POST is throttled; a hidden `website` honeypot rejects automated submissions.
- CAPTCHA is optional by configuration. When enabled, the server verifies the token with the configured provider, times out quickly, and fails closed if configuration, verification, or host matching fails.
- The database stores an active email fingerprint to prevent parallel active requests. A repeat active email returns the same generic confirmation and records a duplicate-submission audit event instead of creating another request or notification.
- Email domains are hashed for duplicate matching. Common public providers such as Gmail are retained and flagged for additional verification; they are not rejected.
- The existing notification system creates an idempotent admin notification using a stable event key and queues email through its notification outbox. A requester confirmation is queued through Laravel's notification queue after the request transaction commits.
- If admin notification creation fails after the request is committed, the requester still receives the confirmation; a repeated submission safely retries the admin notification using the same idempotent event key without resending the requester confirmation.

## Administrative review and authorization

Admin review routes require an authenticated, verified, active administrator and the `demo_requests.review` Gate. The Gate adds an explicit active-account and non-deleted-user check. The detail page is private and sent with `Cache-Control: private, no-store`.

Every review action is validated and stored in `organization_demo_request_events`. State changes use a database transaction and row lock. Guided-demo URLs must be HTTPS and are supplied through trusted environment configuration. Decline notifications do not expose the internal reason.

The former review-page refresh error came from `data-live-link` on a link to a full HTML detail page. The live-table script treated the response as a table fragment and displayed its generic session-expired message. The Review link now uses normal browser navigation; list filters and pagination can remain live.

## Trial invitation security

- The application generates a cryptographically secure 64-character token and stores only its SHA-256 hash.
- New setup links are Laravel temporary signed URLs. The persisted expiry defaults to seven days and is checked independently of the signature.
- Missing or expired setup expiry timestamps fail closed and are marked expired; administrators can issue a fresh invitation.
- The setup controller checks the signature for new invitations and retains unsigned-link compatibility only for records created before this change (`setup_signed_at` is null).
- Account creation locks and rechecks the request, token hash, expiry, one-time acceptance state, and existing account email before creating a user.
- The contact sets their own password. The invited email address is treated as verified because opening the signed link requires possession of the link sent to that address. The existing email-verification service activates the account.
- Account and organization provisioning still use `OrganizationProvisioner`, organization context, and the existing onboarding flow. The invitation is invalidated as part of successful acceptance.

## Data changes

`2026_11_10_090000_enhance_organization_demo_requests.php` is additive to the existing demo request table. It makes `industry` nullable and adds hashed email-domain matching, risk flags, possible-duplicate reference, signed-invitation marker, and one-time requester follow-up token/response fields. Existing email domains are backfilled as hashes; public email providers are flagged. Existing requests and account links are preserved.

Do not edit or rerun the original table-creation migration to deploy this update. Deploy the new migration with the application release. A rollback of the enhancement migration removes the new risk and follow-up fields; back up the database before production rollback.

## Configuration and deployment

Set these values in the deployment environment as needed; do not commit secrets:

```dotenv
ORGANIZATION_DEMO_SETUP_EXPIRE_DAYS=7
ORGANIZATION_DEMO_UPDATE_EXPIRE_DAYS=7
ORGANIZATION_DEMO_BOOKING_URL=https://your-approved-booking-provider.example/...
ORGANIZATION_DEMO_CAPTCHA_ENABLED=false
RECAPTCHA_SITE_KEY=
RECAPTCHA_SECRET_KEY=
```

Set `APP_URL` to the canonical HTTPS production origin; Laravel signed invitation and follow-up links must match the host and scheme used by incoming requests. If CAPTCHA is enabled, provide matching provider keys and register that same host with the provider. If no guided-demo booking URL is configured, the admin action is unavailable until one is provided. Apply migrations during deployment and keep the existing Laravel queue worker running for requester/admin email delivery. Review failed jobs and the notification outbox using the platform's existing operations procedures.

## Files changed

- Routes, Gate, status enum, request/model/service/controller code for submission, review, follow-up, and setup.
- Admin list/detail views and dashboard count/notification integration.
- Public demo form and secure follow-up/setup views.
- Configuration, environment example, and the additive schema migration.
- `tests/Feature/OrganizationDemoRequestWorkflowTest.php` for access control, the full-page review route, honeypot and duplicate handling, Gmail risk flags, review decisions, signed one-time follow-up, invitation setup metadata, and CAPTCHA host validation.

## Verification

Run the focused workflow tests and targeted applicant/onboarding regression checks:

```powershell
php artisan test --filter='OrganizationDemoRequestWorkflowTest|registration presents one applicant Google|new owner onboarding saves progress'
```

The workflow tests use the in-memory SQLite test database, Laravel notification fakes, and fake CAPTCHA HTTP responses. No production database or external CAPTCHA service is contacted. On this checkout, the focused command passed 11 tests and 115 assertions.

A separate run of the entire existing `OrganizationOnboardingTest` file exposed two unrelated current-checkout failures: a redirect assertion expects `/register` without setting a Referer header, and the public careers test references the missing `organisations.careers.show` view. Those are outside this demo-request change and should be tracked separately.
