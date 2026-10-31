# Candidate review workspace

## Audit of the existing implementation

The employer review page is served by `OrganizationApplicationController::show`
and the `organisations.candidates.show` route. Application access is scoped by
organization membership, assigned job, and `ApplicationFormPolicy`. Interviewers
can see candidates who reached Interview but cannot view their private documents
or send candidate email. These distinctions remain in force in the workspace.

`ApplicationForm` already snapshots the applicant's names, contact details,
demographics, address, and profile image when the application is submitted. The
old review view primarily displayed the applicant's current account details, so
later profile edits could change what the employer saw. The workspace presents
the submission snapshot, using account values only for empty legacy fields.
Avatar URLs are generated through the configured public storage disk; supplied
URLs, absolute paths, traversal segments, and control characters are rejected.

`CandidatePipelineStage` and `PipelineTransitionService` already enforce the
sequence Submitted → Shortlisted → Interview → Selected. Rejection is available
from each nonterminal stage. Selected and Rejected are terminal. Moves run in a
database transaction with row locks, authorization, status history, audit events,
and existing workflow notifications. Selection has a separate permission. The
redesign reuses these transitions rather than adding another progression system.

Selection now requires a completed interview for the same application, an end
time in the past, a non-declined response, the existing selection permission,
and explicit `selection_confirmed` consent. Reaching Interview or scheduling
an appointment does not satisfy this prerequisite. The policy hides unavailable
selection actions; the locked transition service rechecks the rule independently
of the UI and request validator.

The explicit interview completion action requires its current version and
`completion_confirmed` consent. It permits only an ended Scheduled/Rescheduled
interview while the application is in Interview, excludes cancelled/declined
appointments, increments the version and records `interview_completed` activity.
It sends no invitation and does not change the candidate's pipeline stage.

Transition remarks were already supported by the request and stored both in the
individual history row and as the latest application remarks. The previous page
offered no remarks input and did not display historical remarks. Those stored
remarks are useful review context and are retained.

`ApplicationStatusHistory` records pipeline transitions and the original
submission. Immutable `ActivityLog` rows record pipeline, interview, email, and
document access actions, but the old detail page loaded only stage histories.
The workspace combines stage history with known interview, email, and document
events. Duplicate pipeline audit rows are omitted. Event presentation uses
explicit labels and selected fields, never a dump of audit payloads, email bodies,
tokens, or private storage paths. Email/document entries respect the associated
permissions. The latest 50 entries are ordered by timestamp and stable ID; a
bounded-history notice is available when older entries exist.

Private document preview and download controllers already authorize each request,
resolve the configured private disk, verify file presence and MIME type, emit
private/no-store headers, and log access. PDF and supported image previews can
reuse those endpoints inside the employer page. Office formats retain the
authorized download fallback; the implementation does not upload candidate files
to a third-party viewer. Legacy per-document approval/rejection columns remain
read-only historical data following the pipeline migration. They are not a
second approval workflow.

The audit found that the preview/download route names existed only in model
helpers, with no registered routes. The workspace restores authenticated routes
and performs authorization after organization context initialization. Stored-byte
signatures determine supported inline formats; filename extensions and stale
database MIME metadata cannot enable an unsafe preview.

Interview scheduling is already implemented through `OrganizationInterviewController`
and `InterviewService`: online/onsite details, date and time validation, explicit
timezones with UTC persistence, schedule/reschedule/cancel actions, optimistic
versions, email templates, queued candidate messages, delivery/failure history,
and candidate confirm/decline links. These workflows and authorization are reused.
Email delivery depends on the application's configured mail transport and queue
worker. Scheduling an interview and moving a candidate into the Interview stage
remain distinct existing actions.

Interview services were present but their routes, organization forms, invitation
email view and signed response views were missing. The integration restores those
connections, corrects the preview route authorization check, and retains meeting
logistics in generic reschedule templates. It also restores the `UserPreference`
model already referenced by the stored timezone preference service.

Standalone notes, evaluations/ratings, candidate assignments, and tasks have some
permission scaffolding but no complete candidate review workflow. The submitted
application also has no structured employment timeline, education timeline,
LinkedIn profile, match percentage, rating, or tags. The redesign does not
fabricate those reference-image details or add inactive controls.

## Presentation boundaries

`CandidateReviewService::build(ApplicationForm $application, User $viewer)`
returns the snapshot-preferred profile, pipeline progress, and readable authorized
activity. It performs its own employer-view authorization before retrieving data.
The Blade workspace and reusable partials render those values alongside existing
forms and private document URLs. Interactive behavior belongs in a separate
JavaScript module; responsive styling uses the existing Marthire design tokens.

There are no new database tables, pipeline stages, or data migrations required
for the workspace redesign. Existing application, interview, message, document,
and history records remain authoritative.

## Local PDF rendering and deployment

`candidate-review.js` owns tabs, document selection, private authenticated fetches,
error feedback, PDF page navigation and zoom. PDF.js is imported only for a PDF.
The renderer uses a local version-matched worker and supporting font, CMap, WASM
and ICC assets copied from the locked npm dependency by Vite. It renders one page
at a time, limits canvas memory, cancels obsolete work and releases private blobs
and document sessions. Accessible page text is available when the PDF contains
text. Images preview directly; unsupported, damaged or password-protected files
retain an authorized download fallback.

The browser check found the native PDF iframe blank in the in-app browser;
canvas rendering removes that plugin dependency. The implementation follows the
[official PDF.js examples](https://mozilla.github.io/pdf.js/examples/) and ships
the package license beside its supporting assets.

Use Node 22.13 or newer (Node 24 is used locally), `npm ci` and `npm run build`.
Deploy the complete `public/asset/marthire/build` directory, including its
versioned `pdfjs` directory and worker asset. No migration is required for this
workspace. Interview delivery continues to use the existing configured queue
worker and mail transport; the interface distinguishes queued from sent/failed.

## Verification

Focused regressions in `CandidateReviewWorkspaceTest` cover submission snapshot
consistency, legacy fallback, unsafe avatar keys, transition remarks, readable
activity without raw audit leakage, interviewer visibility, and bounded stable
activity ordering. Existing candidate management tests continue to cover illegal
stage skips, organization isolation, and candidate pipeline access.

Document tests additionally verify stream authorization, tenant isolation,
supported file signatures, private headers, access logging and missing files.
Interview tests cover schedule/reschedule/cancel, UTC storage, signed responses,
validation preservation and escaped email rendering. JavaScript tests cover
document races, disposal, tab focus, terminal-stage confirmation, page rendering,
PDF memory limits and failure feedback. Browser checks used synthetic data for
desktop/mobile layout, PDF rendering/zoom, inline scheduling, mode switching and
activity display. No real interview invitations were sent during verification.
