# Marthire Complete Application Build Plan

Prepared: 9 October 2026  
Status: implementation roadmap for a pre-launch application; no application features implemented by this document.

## 1. Purpose, authority, and how to execute this plan

This plan turns the product direction in [What Is an Applicant Tracking System? The MartHire Approach](<What Is an Applicant Tracking System.md>) into a sequential development roadmap. That document remains the authority for Marthire's purpose and recruitment lifecycle. This plan supplies implementation decisions, delivery boundaries, dependencies, and acceptance gates; proposed details below are not claims that the source document already specifies them.

Marthire is an ATS for organizations, employers, recruiters and agencies, candidates, and platform administrators. Its organizing workflow is:

**Organization → Recruitment Campaign → Job Vacancy → Applications → Screening → Shortlisting → Interview → Evaluation → Selection.**

Public vacancy discovery supports this workflow. Product decisions should prioritize managing recruitment from beginning to end, traceable decisions, secure candidate information, configurable organizational processes, and a mobile-friendly candidate experience.

### Execution rules for developers and AI coding assistants

1. Complete Stage 00 before application implementation. Re-read the relevant source, inspect sibling files, and verify the current runtime and schema rather than assuming this dated inventory is still current.
2. Work through Stages 01–18 in order. A stage may contain independent tasks, but dependent behavior must remain unavailable until its prerequisites pass. Stage 19 contains separately gated later releases.
3. Implement a complete vertical slice: migration and factory, authorization, validation, service, controller and routes, Blade/JavaScript UI, notifications where relevant, and tests. Do not mark a stage complete because a template renders.
4. For every stage, demonstrate both the successful workflow and denied/invalid cases. Record migration evidence, tests run, accessibility checks, and acceptance results in the delivery/PR record. Do not generate extra documentation files unless requested.
5. Follow `AGENTS.md`: use Boost `search-docs` before code changes; inspect schema with Boost before schema/model work; create Laravel files using Artisan with `--no-interaction`; use explicit PHP types and TitleCase enum cases; use factories and Pest feature tests; run the affected tests with `php artisan test --compact`; run `vendor/bin/pint --dirty --format agent` after PHP edits.
6. Preserve existing directory conventions and reuse components/services. New dependencies and new base folders need approval. Named classes, routes, migrations, and tests in this plan are proposed contracts to reconcile with the code, not instructions to generate duplicates.
7. Never run destructive migration commands against shared or production data. Prove fresh installation, upgrade, backfill, and recovery on disposable test databases and staging copies.

### Release boundaries

| Release | Included | Gate |
| --- | --- | --- |
| Baseline stabilization | Resolve existing conflicts and version ambiguity; establish reliable tests and migration baseline | Stage 00 |
| Internal alpha | Secure organization/campaign/vacancy management and candidate applications | Stages 01–07 |
| Recruitment beta | Pipeline, shortlisting, manual interviews, evaluations, selection and basic hiring records | Stages 08–12 |
| Launch candidate | Durable communications, operational reports, administration, privacy/security/performance readiness | Stages 13–17 |
| Production launch | Approved deployment, smoke tests, monitoring, backups and support ownership | Stage 18 |
| Later releases | External calendars, assessment/verification providers, SMS/WhatsApp, APIs/webhooks, advanced analytics and AI | Stage 19 |

Email delivery and in-app transactional notifications are core operational infrastructure. Provider-specific calendar coordination, APIs, multi-channel messaging, and AI remain later work, as the source document intends. Launch interviews use manual scheduling and calendar-file downloads. Basic launch reports support day-to-day recruitment; advanced cross-campaign analytics follow later.

## 2. Evidence-based current baseline

The following inventory comes from the working tree, dependency manifests, route definitions, models/services, existing test files, and a read-only Boost schema inspection. **Implemented** means implementation evidence exists; it does not mean the current conflicted tree has passed tests or is production-ready. **Improve** means useful behavior exists but needs extension or hardening. **New** means no corresponding complete implementation was found in the inspected paths.

### Architecture and environment reconciliation

| Area | Observed baseline | Roadmap consequence |
| --- | --- | --- |
| Backend | Laravel monolith; `app/Http/Controllers`, `app/Http/Requests`, `app/Models`, `app/Services`, `app/Policies`, `app/Enums` | Extend these layers; use cohesive domain services instead of introducing microservices |
| Installed versions | `composer.json` requires PHP `^8.2`, Laravel `^12.0`, Pest `^3.8`; Boost reports PHP 8.2, Laravel 12.61.0, Pest 3.8.6, PHPUnit 11.5.50 | `AGENTS.md` lists PHP 8.5/Laravel 13/Pest 5/PHPUnit 13. Stage 00 must resolve the discrepancy. An upgrade is a separately approved dependency change, not an assumed prerequisite |
| Frontend | Blade, Alpine.js, Vite; Tailwind directives in `resources/css/app.css`; public/admin assets and existing layout components | Reuse the working design system after asset audit. README's Bootstrap description is not sufficient evidence of the actual active stack; do not add a second framework or rewrite to an SPA |
| Database | Boost reports a MySQL connection; `phpunit.xml` uses in-memory SQLite | Keep relational/Eloquent design; test production-engine-specific constraints, backfills and concurrency on an isolated database matching production |
| Authentication | Breeze-style session auth, Google Socialite, email verification, password reset, account status middleware | Preserve working flows; extend permissions for organizations without trusting client-provided roles |
| Files and background work | Laravel filesystem services, local private application files, public images/logos; queue tables and verification/welcome jobs | Retain access-controlled downloads; establish durable queue operation early and expand observability later |
| Quality tools | Existing Pest suite and Pint; Larastan/Rector appear in project instructions but are not listed in the inspected Composer manifest | Use installed tooling; approve additions explicitly; do not claim absent tools already run in CI |

The requested `laravel-best-practices` skill was not found in the available project/user skill locations during this review. Restore or locate it before Laravel implementation/review stages that require it. This documentation task uses the supplied project guidelines and version-specific Boost documentation; no Laravel implementation is being attempted.

### Existing capability inventory

| Capability | State | Evidence and required treatment |
| --- | --- | --- |
| Registration, login/logout, email verification, password resets, Google sign-in | Implemented / Improve | `routes/auth.php`, `GoogleAuthController`, auth controllers, `EmailVerificationService`, `SendWelcomeEmail`, `SendVerificationEmail`, `tests/Feature/Auth/*`; reverify linking, suspension, queue and session behavior |
| Global roles and account status | Implemented / Improve | `UserRole`, `UserStatus`, `EnsureUserHasRole`, `EnsureAccountIsActive`, `RbacAccessTest`; roles are `admin`, `employer`, `applicant`, not organization membership roles |
| Employer vacancy CRUD and admin moderation | Implemented / Improve | `JobController`, `Admin/JobManagementController`, `JobStatus`, `JobManagementTest`, `AdminJobManagementTest`; ownership is currently a single `employer_id` |
| Public vacancy browsing/filtering and detail pages | Implemented / Improve | `JobController`, `public-jobs-browser.js`, `PublicJobListingsTest`; approved/deadline filtering exists; add organization/campaign context and explicit publication state |
| Candidate profile and application wizard | Implemented / Improve | `ProfileController`, `JobApplicationController`, `StoreApplicationFormRequest`, `ApplicationFormService`, `application-wizard.js`; verify after conflict resolution and extend professional profile/CV support |
| Candidate application snapshot and duplicate prevention | Implemented / Improve | `ApplicationForm` stores submitted personal details; `application_forms` has unique `(job_id,user_id)` and unique reference; submission service writes history transactionally |
| Identity and education documents | Implemented / Improve | `ApplicationDocument`, `UserIdentificationDocument`, document type enum, private file service and download/preview controllers; CV/cover-letter and versioned reusable professional documents need development |
| Document access control/private migration tooling | Implemented / Improve | `ApplicationDocumentFileService`, `MoveApplicationDocumentsPrivate`, `ApplicationDocumentPreviewTest`, `MoveApplicationDocumentsPrivateCommandTest`; test actual legacy-file cleanup and shared-file retention |
| Pipeline movement and history | Implemented / Improve | `CandidatePipelineStage`, `ApplicationFormService::moveCandidate`, `EmployerApplicationController`, `ApplicationFormPolicy`, `CandidatePipelineTest`; fixed forward transitions and row locking exist |
| Candidate withdrawal | New | Source document promises Withdrawn; current enum has only Submitted, Shortlisted, Interview, Selected, Rejected |
| Candidate/employer/admin dashboards | Implemented / Improve | `DashboardController`, `EmployerDashboardService`, `AdminDashboardService`, dashboard feature tests; add campaign context and actionable queues |
| Basic admin reporting and export | Implemented / Improve | `Admin/ReportController`, `AdminReportService`, `AdminReportTest`; extend tenant-scoped organization reports and historical measures |
| User administration | Implemented / Improve | `Admin/UserManagementController`, `AdminUserManagementTest`; extend organization moderation, privileged-action audit and retention-aware deletion |
| Notifications and settings | Partial / Improve | Notifications table and candidate-facing listing exist; several employer/admin notifications and settings routes return views directly. A page does not prove durable preferences, delivery or settings persistence |
| Template and permission screens | Partial / New backend | `assessment-templates`, `interview-templates`, `email-templates`, `permission-management`, general/email settings are view routes; connect validated, authorized persistence and audit |
| Organizations, team memberships and recruitment campaigns | New | No corresponding models/tables in inspected application inventory and live schema |
| Structured skills, experience and education; CV library | New | Basic user/application fields and education attachments exist; no complete normalized professional profile workflow found |
| Interview records, interviewer assignment and evaluations | New | No complete models, tables or workflow controllers found; an Interview application status is not a scheduled interview |
| Hiring decisions, selection approval, offers and confirmed hire records | New | Selected status exists, but no complete decision/offer/hire domain was found |
| Configurable workflows, recruiter notes/tasks, consent-based talent pools | New | Planned extension of the fixed pipeline; talent pools are later scope |
| Public API, webhooks, external calendars, assessment providers, SMS/WhatsApp, AI | New / Later | Long-term direction in the source document; do not imply providers are integrated because marketing pages mention them |

### Baseline blockers and documentation gaps

At review time, Git reported unresolved entries in `resources/views/client/Application.blade.php`, `resources/views/profile/edit.blade.php`, `tests/Feature/ApplicationFormWorkflowTest.php`, and `tests/Feature/ProfileTest.php`. Literal conflict markers were present in the two test files. Existing unrelated changes also included `resources/js/application-wizard.js` and deleted documentation/location files. This plan does not resolve or overwrite those changes.

`README.md` references `docs/TECHNICAL_DOCUMENTATION.md`, which is deleted in the current working tree. It also lists aspirational features such as saved jobs without equivalent verified backend evidence. No previous stage-by-stage guide was readable in the final documentation inventory. Treat these as documentation gaps, not as a reason to invent an architecture or silently restore deleted files. Stage 00 reconciles them with the repository owner; this roadmap builds on the ATS document actually read.

Repeated `/jobs`, `/profile`, and `/notifications` URI registrations in `routes/web.php` need an effective route-list audit. Middleware alone does not make duplicate method/URI registrations role-aware. Preserve existing named-link compatibility while separating role/workspace URLs.

## 3. Reference-platform findings and Marthire adaptations

Reviewed public pages and official help/developer documentation on 9 October 2026. This is a public product/workflow review, not an authenticated inspection of proprietary dashboards or internal databases. Public capabilities below are evidence; Marthire layouts and schema choices are independent recommendations. Do not reproduce branding, proprietary content, or pixel-identical screens.

| Reference | Observed product/workflow pattern | Adaptation and boundary |
| --- | --- | --- |
| Workforce Africa | Its public site presents recruitment within broader African workforce services, with consultation, onboarding and ongoing support. Its resourcing page covers graduate/executive/experienced hiring and structured assessments. | Support SMEs, NGOs, institutions and agencies with organization-led onboarding, reusable campaign templates and consistent evaluation criteria. African country/timezone/location support should be explicit. Full payroll, employer-of-record and workforce management remain outside the ATS launch. [Homepage](https://workforceafrica.com/), [Workforce Resourcing](https://workforceafrica.com/staff-resourcing-africa/) |
| Lever | Public product material emphasizes automation and connected hiring. Official documentation separates candidate identity from vacancy-specific opportunities and associates stages/history with those opportunities. | Keep a reusable candidate profile and independent per-vacancy `ApplicationForm`; preserve submission snapshots and scoped recruiter access. Show next actions alongside each application. Broad CRM and AI are later additions. [Lever](https://www.lever.co/), [Hiring-manager guide](https://help.lever.co/s/article/Getting-started-with-Lever-as-a-Hiring-Manager), [Developer documentation](https://hire.lever.co/developer/documentation) |
| Workable pipeline and candidate workspace | Official help describes concise pipelines, reporting categories, rejection at the stage reached, and a workspace combining stage context, a candidate list, profile details, actions and activity. | Use campaign/vacancy filters, stage counts, a list/detail workspace and a timeline. Preserve last reached stage for funnel reporting. Keep operational tasks separate from recruitment stages. [Pipeline best practices](https://help.workable.com/hc/en-us/articles/4413312707991-Recruiting-pipeline-best-practices), [Candidate pipeline view](https://help.workable.com/hc/en-us/articles/115012857047-Candidate-profile-in-pipeline-view-overview) |
| Workable evaluations and scheduling | Official help documents structured interview kits/scorecards and independent feedback, plus calendar-backed self-scheduling with configurable availability. | Launch with versioned rubrics, assigned interviewers, manual timezone-aware scheduling and reminders. Add calendar OAuth and self-scheduling after reliable manual scheduling. [Interview kits](https://help.workable.com/hc/en-us/articles/115012304987-Creating-and-using-an-interview-kit-scorecard), [Self-scheduled events](https://help.workable.com/hc/en-us/articles/360007483594-Self-scheduled-events) |
| Workable decisions and reporting | Official help describes offer approval workflows and reporting on time spent in/reaching recruitment stages. | Separate selection, offer and actual hire states. Capture reliable timestamps before reporting duration and funnel measures. [Offer approvals](https://help.workable.com/hc/en-us/articles/360001165533-E-signature-offer-approval-workflows), [Hiring velocity](https://help.workable.com/hc/en-us/articles/115012751307-Using-the-Hiring-velocity-report) |

Workable's [features overview](https://www.workable.com/features) also provides a benchmark for branded career pages, reusable templates, permissions, auditability and integrations. This plan uses those ideas selectively. Provider coverage, commercial tiers and feature availability can change; verify them again when implementing an integration. Workforce Africa's technology detail page could not be retrieved, so no private ATS features are inferred from it. Some Lever help material was accessible through indexed official content rather than direct rendering; its public developer documentation corroborates identity/opportunity separation.

## 4. Target architecture, access model and data contracts

### 4.1 Application architecture

Retain a modular Laravel monolith: session-authenticated Blade screens, Alpine/JavaScript for progressive enhancement, relational database transactions, Laravel policies/Form Requests, domain services, events, queued notifications and scheduled jobs. HTML requests and JSON enhancements must enforce identical authorization and validation. Basic forms should remain usable if an enhancement fails.

Extend existing `ApplicationFormService`, dashboard/report services, controllers and components where responsibility remains cohesive. Introduce focused services such as `OrganizationOnboardingService`, `CampaignService`, `JobPublicationService`, `InterviewSchedulingService`, `EvaluationService`, and `HiringDecisionService` within existing conventions. Keep controllers thin. Avoid a generic workflow engine, a new SPA stack, microservices or a new permission package unless an evidenced requirement justifies the added dependency.

Use after-commit dispatch for transaction-dependent work, following official [Laravel queue documentation](https://laravel.com/docs/12.x/queues#jobs-and-database-transactions) and [notification documentation](https://laravel.com/docs/12.x/notifications#queued-notifications-and-database-transactions). Persist critical delivery intents transactionally so a process failure after database commit cannot silently lose a candidate notification. After-commit dispatch alone does not provide exactly-once external delivery.

### 4.2 Roles, permissions and tenant boundaries

Keep authentication identity in `users`. Preserve global `admin`, `employer`, `applicant` compatibility initially; add organization membership roles rather than replacing every global role at once. One identity may retain candidate access while also holding organization memberships; accepting a team invitation must not overwrite its applicant role or destroy candidate history. Membership workspace routes use membership/capability checks rather than requiring legacy `role:employer`. Global roles remain compatibility/platform identity controls, while organization permissions come from memberships. Evaluate permission using active account + active organization + active membership + role capability + resource organization + campaign/vacancy assignment where applicable.

| Role | Scope and permitted work | Restrictions |
| --- | --- | --- |
| Guest | Public approved/published vacancies and organization/campaign career pages; register/login | No candidate records, private documents, interviews or internal metrics |
| Candidate (`applicant`) | Own profile/library, own application drafts/submissions/statuses, own interview response and withdrawal | No recruiter notes, other applicants, internal scorecards, salary approvals or unpublished decisions |
| Organization owner | Organization onboarding, team/settings, campaign access, assigned recruitment and reporting; ownership transfer | Cannot grant platform admin, bypass platform moderation or view other tenants |
| Organization administrator | Operational team/settings management and explicitly assigned recruitment permissions | Sensitive offers, ownership transfer and deletion require separate capability; cannot promote self to owner |
| Recruiter | Assigned campaigns/vacancies; review, shortlist, schedule and coordinate decisions | No unrestricted platform applicant search; cannot publish a selection unless authorized |
| Hiring manager / decision approver | Assigned vacancy requirements, feedback and selection approvals | Scope by assignment; compensation permissions are explicit |
| Interviewer | Assigned interview packet and own evaluations | Minimum candidate data; no identity documents, broad applicant search, pipeline decisions or team administration by default |
| Organization analyst | Read-only permitted aggregate reports | Exports and identifiable applicant details require separate capability |
| Platform administrator | Organization moderation, abuse handling, user administration and platform operations | Candidate/document support access requires an explicit capability, reason and audit record; no invisible blanket access through a policy bypass |

An agency recruiter can have explicit memberships in several client organizations and switch workspaces. The active organization is navigation context, never sufficient authorization by itself. Each request rechecks membership; revoked members immediately lose list, export, cache and document access. Once a vacancy has `organization_id`, that organization is authoritative: never authorize through membership OR the legacy `employer_id`. Null/ambiguous tenant records fail closed during migration, except a narrowly scoped, time-limited and audited legacy-only fallback explicitly approved in Stage 00. Do not grant agency-wide access to unrelated clients or expose a global candidate profile because that person applied elsewhere.

### 4.3 Entity map and schema ownership

| Model/table | Relationships and important fields | Delivery stage |
| --- | --- | --- |
| `User` / `users` (existing) | Authentication, account status, basic reusable identity; has memberships, candidate profile and applications | 02, 03, 06 |
| `Organization` / `organizations` | Name, slug, type, country, IANA timezone, branding, approval/status; has memberships/campaigns/settings | 03 |
| `OrganizationMembership`, `OrganizationInvitation` | Unique organization/user membership, role/status; invitations use hashed expiring tokens; at least one active owner invariant | 03 |
| `RecruitmentCampaign` / `recruitment_campaigns` | Belongs to organization; owner, code, title, lifecycle, opening/closing dates, description; has vacancies and assignments | 04 |
| `CampaignAssignment` / `campaign_assignments` | Membership assigned to campaign with responsibility; optional vacancy-specific assignment narrows access further | 04, 09 |
| `Job` / `job_posts` (existing) | Retain `employer_id` as legacy creator; add organization/campaign, requirements, responsibilities, headcount, publication metadata and workflow version | 03, 05, 08 |
| `CandidateProfile`, education/experience/skills tables | One profile per applicant; ordered education and experience; skills via pivot or validated structured records | 06 |
| `CandidateDocument` / `candidate_documents` | Owner, type, immutable file version, private path, MIME/size, checksum, scan state; reusable CV and supporting files | 06 |
| `ApplicationForm` / `application_forms` (existing) | Belongs to candidate and job; immutable submission snapshot, reference, public status, current internal stage/version and visibility timestamps | 07, 08 |
| `ApplicationDraft`, `ApplicationAnswer` | Draft per candidate/vacancy; answers bound to versioned vacancy questions; drafts excluded from recruiter reports | 05, 07 |
| `ApplicationDocument` (existing) | Application-scoped snapshot of a document/version; preserve existing identity/education references and private paths | 06, 07 |
| `ApplicationStatusHistory` (existing), stage history | Actor, old/new public status and internal stage, time, reason, event ID; append-only normal operation | 08 |
| `WorkflowTemplate`, `WorkflowVersion`, `PipelineStage` | Organization-owned templates; immutable published versions; stage order and canonical reporting/public category | 08 |
| `ApplicationNote`, `ApplicationTask`, reviewer assignments | Internal scoped collaboration, due dates, assignees, completion; explicit note visibility | 09 |
| `Interview`, `InterviewParticipant` | Application, round, UTC start/end, display timezone, location/link, schedule version and attendance/response | 10 |
| Evaluation template/version/criterion, `Evaluation`, `EvaluationScore` | Rubric version, assigned evaluator, interview/application, draft/submitted, criterion score/evidence | 11 |
| `HiringDecision`, `DecisionApproval`, `Offer`, `Hire` | Decision approval/publication, optional manual offer versions/responses, confirmed hire and start date | 12 |
| `NotificationIntent`, `NotificationDelivery`, notification preferences/templates | Transactional event/delivery key, channel, recipient, retries and timestamps; keep Laravel notifications table | 02 minimum, 13 full |
| `AuditEvent` / `audit_events` | Organization, actor/system, resource, action, reason, safe metadata and correlation ID; redact sensitive content | 02 minimum, 15 full |
| `ReportExport`, optional summary tables | Requester/organization/filter snapshot, private export lifecycle, expiry; summaries only when justified | 14 |
| Organization/platform settings and privacy requests | Typed validated settings; deletion/export request workflow and retention hold metadata | 15, 16 |

Create migrations incrementally. Reuse a suitable existing table instead of creating parallel document or history systems. Do not rename `Job` to `JobPost`, or `ApplicationForm` to `Application`, solely to match benchmark vocabulary. Laravel's existing `jobs` table stores queue jobs; vacancies remain in `job_posts`.

Prefer deriving an application's organization through its vacancy initially. If later measurements justify denormalized `organization_id` on applications or other children, backfill and enforce consistent parent ownership; never trust a posted organization ID. Index tenant-filtered access paths and foreign keys deliberately. Use stable IDs for relations; slugs are public routing keys, not authorization boundaries.

### 4.4 State machines and invariants

**Vacancies:** preserve `JobStatus` values `pending`, `approved`, `rejected` for platform moderation. Add a separate publication lifecycle such as `draft`, `scheduled`, `published`, `closed`, `archived`. Optional organizational approval has its own review record/status. Public visibility requires active approved organization + permitted campaign state + platform approval + organizational approval when enabled + publication/opening/deadline rules. Drafts can start with moderation pending without entering the moderation queue until submitted for review. Closing a vacancy stops applications but preserves recruitment records.

**Applications:** preserve documented public statuses `submitted`, `shortlisted`, `interview`, `selected`, `rejected`; add `withdrawn` deliberately. Current transition behavior is Submitted → Shortlisted/Rejected; Shortlisted → Interview/Rejected; Interview → Selected/Rejected; Selected/Rejected terminal. Keep this default. Allow candidate withdrawal from Submitted/Shortlisted/Interview with confirmation; after selection use a declined-offer/no-start outcome instead of silently changing historical selection. Withdrawal is terminal at launch, and does not permit a duplicate reapplication to the same vacancy.

Internal configurable stages may include screening or interview rounds, but map to the documented public categories. Evaluation, offer approval and reminders are tasks/substates rather than extra public statuses. Stage movement, history, decision publication and delivery intent must be atomic. Rejecting/withdrawing preserves the last reached internal stage for reporting. Only authorized, explicit correction/reopening workflows may override terminal decisions, with a new history event and published correction where needed; normal launch UI does not reopen terminal applications.

**Selected versus hired:** Selected is a recruitment decision. It is not proof of an accepted offer or employment start. Keep offer `draft/awaiting_approval/approved/sent/accepted/declined/expired/withdrawn` and hire `confirmed/started/cancelled` separately. Never backfill all Selected applications as confirmed hires.

**Core invariants:** one submitted application per candidate/vacancy; unique public reference; application snapshots do not change when profiles/files change; each actionable record belongs to one tenant; inactive accounts/memberships cannot act; history is ordered and attributable; queued retries cannot duplicate status changes; private files cannot be served by public paths; deleting a library item cannot break a submitted application's attachment.

### 4.5 Shared UX and dashboard contract

Use one coherent design language: consistent spacing/type, accessible contrast, labelled statuses, reusable form fields/tables/filters, visible loading/empty/error/success states, keyboard navigation, focus management and clear validation. Target WCAG 2.2 AA and test the main workflows with keyboard and screen reader. Maintain usable layouts at roughly 360px phone, tablet and desktop widths. Never rely on color, hover or drag-and-drop alone.

| Workspace | Layout and first actions |
| --- | --- |
| Candidate | Compact mobile navigation; own applications/status timeline and next actions first; interviews/reminders; profile completeness with links; documents and preferences |
| Organization owner/admin | Workspace switcher and onboarding progress; campaigns/vacancies; cards for open roles, new applications, overdue reviews, interviews and selections; approvals and team tasks |
| Recruiter | Assigned campaign/vacancy filters; actionable review queue; stage counts; candidate list plus application detail; notes/tasks and safe communication actions |
| Hiring manager/interviewer | Assigned vacancies/interviews, upcoming schedule, pending feedback and decision approvals; only necessary candidate packet |
| Platform admin | Organization/user/moderation queues, platform recruitment totals, abuse/security alerts and operational health; separate sensitive support actions |

Recruiter application detail: vacancy/campaign breadcrumb; candidate identity and submitted snapshot; primary authorized next action; tabs for application/answers, permitted documents, timeline, interviews, evaluations and decision. Include list view before optional board view. Candidate views use safe public explanations and candidate messages, not internal notes, decision deliberations or raw status-history remarks.

## 5. Sequential implementation stages

Each stage's security and test requirements supplement the shared invariants above. Exit criteria are mandatory; an unresolved earlier gate blocks dependent implementation.

### Stage 00 — Establish a trustworthy baseline

**Objective and outcome:** a reproducible, conflict-free starting point and an agreed supported runtime. State: Improve; prerequisite for everything else.

**Features and functionality:** reconcile unresolved merge entries with their owner; preserve unrelated changes; inventory effective routes, installed dependencies, migrations and seeded reference data. Resolve missing-document references deliberately. Decide whether the observed Laravel 12/PHP 8.2 baseline stays or whether a separately approved upgrade is required. Locate the mandatory Laravel skill before code work.

**Roles and permissions:** developer/release maintainer only; no application access changes. Record proposed organization roles and obtain product agreement on permissions without granting accounts new access.

**UI/UX and dashboards:** capture current candidate/employer/admin flows after the application can render; identify duplicate layouts, static metrics, inaccessible controls, route collisions and broken asset references. Establish the launch screen checklist.

**Database and migrations:** inspect schema and migration status through Boost/Artisan; compare a disposable fresh database to an anonymized upgrade fixture. Identify orphaned records and data-loss cascades. Do not edit already-applied migrations as a shortcut; use additive corrections where needed.

**Routes, controllers, services and components:** inspect `php artisan route:list --except-vendor --no-interaction`; confirm duplicate paths and actual middleware. Preserve existing named route compatibility in the later route consolidation.

**Integrations, notifications and automation:** inventory configured capabilities without logging secret values; verify mail, queue, storage and Google auth in test/staging. Do not enable new paid providers.

**Security, validation, testing and performance:** establish affected-test and full-suite results only after conflicts are resolved; verify SQLite isolation and production-engine test configuration. Run asset build and supported PHP syntax checks; record baseline response/query measurements. No feature may be declared verified solely from its test filename.

**Dependencies:** none. **Completion criteria:** no unmerged entries or conflict markers; versions and tooling reconciled; all baseline tests/build pass or accepted pre-existing failures are isolated with owners; migration upgrade/fresh results recorded; baseline blockers cannot be waived for production.

### Stage 01 — Product scope, navigation and reusable interface foundation

**Objective and outcome:** a consistent ATS shell and a testable launch backlog. State: Improve existing layouts; New campaign-oriented navigation.

**Features and functionality:** convert source requirements into acceptance stories for every role; confirm launch countries, agency/client scope, optional employer approval, sensitive-data requirements, headcount and decision approval defaults. Establish the release boundaries above and configurable feature availability.

**Roles and permissions:** define the capability matrix from Section 4.2; current global roles remain enforced while organization capabilities are built. Hide unavailable actions and deny direct requests server-side.

**UI/UX and dashboards:** reuse `resources/views/components`, existing role dashboards and layouts. Define app header, workspace selector placeholder, sidebar/mobile menu, breadcrumbs, cards, table/filter/pagination patterns, modal focus behavior and standardized status badges. Use honest empty states rather than demonstration data or nonfunctional action buttons.

**Database and migrations:** no new domain schema required. Document proposed entities and migration order inside the delivery record; never add a table merely to render navigation.

**Routes, controllers, services and components:** consolidate canonical role/workspace URL prefixes, for example `/candidate/*`, `/employer/*`, `/admin/*` and later `/organizations/{organization}/*`. Preserve existing named routes where practical; redirect obsolete GET pages safely. Update form targets explicitly; do not redirect POST mutations across roles. Add/reuse `dashboard-card`, `data-table`, `status-badge`, `empty-state` and accessible confirmation components.

**Integrations, notifications and automation:** shared flash messages and notification badge shell; no new external services. Add feature flags through existing configuration conventions, with defaults that hide unfinished modules.

**Security, validation, testing and performance:** test unique method/URI registration, intended named-route middleware, role-specific navigation and unauthorized requests. Validate query/sort allowlists. Build Vite assets; ensure large screens do not download multiple unused design frameworks.

**Dependencies:** Stage 00. **Completion criteria:** every launch screen has an agreed purpose/role/action; role routes are unambiguous; navigation and responsive shells work; existing public/auth flows and targeted route/view tests pass.

### Stage 02 — Identity, authorization and shared operational infrastructure

**Objective and outcome:** trustworthy identities, explicit capabilities and dependable infrastructure before more personal data is collected. State: Improve auth/RBAC/queues; New audit and delivery foundations.

**Features and functionality:** reverify registration, verification, password reset, Google account linking, suspension and session invalidation. Define least-privilege policy capabilities. Require MFA for privileged launch access using a reviewed implementation; dependency additions require approval. Establish structured audit events, correlation IDs, scheduler ownership and durable mail workers.

**Roles and permissions:** candidates cannot self-select employer/admin privileges; employer access follows the approved onboarding flow. Platform administration has separate capabilities for user management, moderation and sensitive support access. Disabled accounts lose active access and queued privileged work rechecks authorization where appropriate.

**UI/UX and dashboards:** clear verification/suspension states, resend/reset throttling messages, secure account/session settings and recovery flow. Keep technical infrastructure detail out of candidate screens; admin sees delivery/health failures with safe actionable summaries.

**Database and migrations:** preserve `users`, sessions, tokens, queue and notification tables. Add minimal `audit_events` and `notification_intents` with event/recipient/channel deduplication keys; MFA/recovery storage if selected. Protect and expire recovery secrets. Expand delivery metadata in Stage 13 without replacing the early schema.

**Routes, controllers, services and components:** extend existing auth controllers, `GlobalLogoutService`, `EmailVerificationService`, account middleware and policies. Add authenticated security settings endpoints with Form Requests; centralize capabilities without introducing a duplicate auth system.

**Integrations, notifications and automation:** use current mail/Google integrations with test credentials; queued mail after commit, retry/backoff/timeout/failed-job behavior and transactional intent reconciliation. Use provider idempotency where supported and recognize ambiguous SMTP outcomes. Configure scheduler tasks to avoid overlapping runs.

**Security, validation, testing and performance:** test login/reset/resend throttles, email uniqueness/linking, CSRF, session rotation, signed verification links, IDOR, revoked/suspended sessions, MFA/recovery, and queued retry behavior. Redact passwords/tokens/PII from logs; ensure public errors reveal no secrets. Budget auth queries and queue delays.

**Dependencies:** Stages 00–01. **Completion criteria:** targeted auth/RBAC tests pass; privileged access and recovery demonstrated; workers/scheduler restart safely; one committed event produces one logical intent and no rollback produces a notification; audit captures sensitive actions without secrets.

### Stage 03 — Employer onboarding, organizations and team membership

**Objective and outcome:** move from single-user vacancy ownership to secure organization workspaces. State: New organizations; Improve existing employer ownership.

**Features and functionality:** organization creation, type/country/timezone/branding, onboarding checklist, platform approval/suspension, owner/admin/team invitations, role changes and ownership transfer. Candidates retain their separate experience. Agencies use explicit client memberships, not an unrestricted global recruiter role.

**Roles and permissions:** approved owner manages organization and invitations; authorized admin manages operational members; only platform moderators approve/suspend organizations. An organization may have multiple owners but must retain at least one active owner. Prevent removal of the last owner, self-elevation, cross-tenant invitation acceptance and reuse of expired/revoked tokens. Invitation acceptance adds membership without replacing candidate identity/role; member routes authorize via membership capabilities rather than legacy employer-role middleware.

**UI/UX and dashboards:** employer onboarding wizard: organization details → verification/review → team → first campaign. Workspace switcher shows only active memberships; pending organizations see approval progress. Team table shows roles, status, assignments and invitation expiry.

**Database and migrations:** add organizations/memberships/invitations and nullable `job_posts.organization_id`. Backfill one explicitly reviewed organization per legacy employer, using a stable employer mapping rather than merging matching company names. Create owner memberships; flag ambiguous records for review. Initially retain `employer_id` as creator/compatibility metadata; populate organization for all new writes and make it authoritative whenever present. Null/ambiguous ownership fails closed unless the narrowly audited legacy-only fallback is explicitly enabled; never combine old/new authorization with an OR condition. After reconciliation, enforce the FK/non-null invariant in a second migration. Add unique membership and invitation constraints/indexes.

**Routes, controllers, services and components:** proposed `organizations.store/show/update`, `organizations.members.*`, invitation accept/revoke routes; `OrganizationController`, `OrganizationMemberController`, `OrganizationInvitationController`, `OrganizationOnboardingService` and policies. Extend current vacancy/application queries to derive organization ownership. Reuse profile/upload components for branding.

**Integrations, notifications and automation:** verified invitation emails, organization submission/approval notices and audit events. Expire invitations via scheduler. Internal onboarding does not require an external business-verification provider.

**Security, validation, testing and performance:** reject posted foreign organization IDs, slug guessing, revoked membership and cached tenant leakage. Test two organizations plus a multi-client recruiter. Backfill dry-run and rerun must preserve job/application/document counts and histories. Index membership lookup and tenant vacancy lists; cache keys include tenant and permission context.

**Dependencies:** Stage 02. **Completion criteria:** two tenants cannot access each other's lists/details/exports/files; legacy employers retain intended access; invitation/owner invariants pass; all vacancies have reconciled organizations before strict constraints; rollback/recovery evidence exists.

### Stage 04 — Recruitment campaigns and scoped assignments

**Objective and outcome:** implement the campaign layer required by the source lifecycle. State: New.

**Features and functionality:** create/edit campaigns with title/code, purpose, owner, dates, target headcount and status; group one or more vacancies; assign recruiters/managers; clone draft configuration; close/archive without deleting applications. A campaign may prepare vacancies before opening.

**Roles and permissions:** owners/admins create campaigns and assign active members; recruiters manage assigned campaigns; hiring managers view assigned decisions; analysts see permitted aggregate information. Membership alone need not grant all campaign access.

**UI/UX and dashboards:** campaign list with status/date/owner filters; detail tabs for overview, vacancies, candidates, interviews, reports and team. Overview shows real counts and next actions. Empty campaign prompts first vacancy; closed campaigns remain searchable for authorized users.

**Database and migrations:** add `recruitment_campaigns`, `campaign_assignments`; unique organization/code; indexes on organization/status/dates. Add nullable `job_posts.recruitment_campaign_id`; backfill legacy vacancies into clearly labelled legacy campaigns per organization, preserving historical dates rather than inventing activity. Enforce campaign/vacancy organization consistency before non-null constraints.

**Routes, controllers, services and components:** `organizations.campaigns.*`, campaign assignment and lifecycle actions; `RecruitmentCampaignController`, `CampaignAssignmentController`, `CampaignService`, campaign policy and filter/card components. Reuse dashboard aggregate queries after tenant scoping.

**Integrations, notifications and automation:** assignment and campaign lifecycle notifications; audit cloning and closure. Closing can close publication on child vacancies through an explicit previewed operation; it must not reject applicants automatically.

**Security, validation, testing and performance:** validate date order, same-organization assignees and immutable organization ownership after recruitment begins. Test stale/deactivated assignments, legacy backfill and closure behavior. Aggregate counts with database queries and bounded loading rather than loading all applications.

**Dependencies:** Stage 03. **Completion criteria:** each vacancy belongs to one matching organization/campaign; assigned-role access and campaign CRUD/lifecycle tests pass; archival preserves history and child visibility rules.

### Stage 05 — Vacancy authoring, approvals, publishing and discovery

**Objective and outcome:** professional vacancy creation and controlled public availability. State: Improve CRUD/moderation/discovery; New publication lifecycle and configurable requirements.

**Features and functionality:** structured title/summary, responsibilities/requirements, category, location/country, remote/hybrid/on-site, employment type, headcount, optional salary/currency visibility, opening/deadline/timezone and question/document requirements. Draft/save/preview, optional organizational approval, platform review, scheduled publication, close/archive and branded organization/campaign pages. Preserve current admin review semantics.

**Roles and permissions:** assigned recruiters draft/edit; designated organization approvers approve when required; platform moderators approve public listing; authorized publisher schedules/publishes. Applicants and guests see only effective public vacancies. Sensitive edits require renewed approval and version tracking; define whether the prior approved version stays public while revised content is reviewed.

**UI/UX and dashboards:** multi-section editor with save state and preview; approval checklist and rejection reason; list badges distinguish moderation from publication. Vacancy workspace tabs: overview, pipeline, interviews, team, reporting, settings. Public mobile page emphasizes requirements, deadline/timezone, organization authenticity and one clear apply action.

**Database and migrations:** extend `job_posts` without repurposing `status`; add publication state/timestamps, author/updater, organization approval records and immutable vacancy/form versions. Create versioned application questions/options and document requirements. Unique slug constraint with collision handling; indexes for organization/campaign/publication/deadline filters. Preserve legacy due dates through an explicit date-to-timezone conversion policy; do not guess that `start_date` means employment start.

**Routes, controllers, services and components:** extend `JobController` and admin moderation controller with Form Requests/policies; `JobPublicationService`, review/version service and scheduled publish/close jobs. Named publish/close/preview actions; public organization/campaign vacancy routes. Reuse job form and public browser components.

**Integrations, notifications and automation:** review/publish/close emails/in-app intents; scheduler respects approval and open/close times. Optional public JobPosting metadata only for truly available vacancies; no paid job-board distribution at launch.

**Security, validation, testing and performance:** sanitize permitted rich text; validate question definitions, salary ranges/currency, future scheduling and allowed URLs/files. Test visibility predicate across status/date/tenant/suspension combinations, deadline boundary and publication retries. Prevent deletion cascades through archive-first behavior. Paginate/filter discovery; inspect query plans before considering a search engine.

**Dependencies:** Stage 04. **Completion criteria:** draft → review → approve → publish → close/archive works; direct links and application endpoints agree on availability; unauthorized publication denied; scheduling/time-boundary/moderation regression tests pass.

### Stage 06 — Professional candidate profiles and secure reusable documents

**Objective and outcome:** reusable candidate information with safe application snapshots. State: Improve basic profile/identity/education uploads; New professional profile and CV library.

**Features and functionality:** contact details, education/qualifications, skills, employment experience, CV, optional cover letter/portfolio and document library. Separate account email from application contact email deliberately. Offer progressive profile completion; collect sensitive identity/demographic information only where the approved campaign requirements justify it. Reuse Nigeria state/LGA catalog and define broader location support without silently accepting inconsistent values.

**Roles and permissions:** candidates manage their own profile/documents. Recruiters see authorized application snapshots and permitted professional fields, not unrelated applications or every library document. Identity documents need an explicit restricted capability; interviewers receive a reduced packet.

**UI/UX and dashboards:** profile sections with completion guidance and autosave feedback where reliable; document list shows type/version/scan state/size and replace/remove actions. Explain what will be submitted before applying. Keep CV and identity-document choices distinct; avoid making a profile photograph or demographic field universal unless confirmed as necessary.

**Database and migrations:** add one-to-one profile and education/experience/skills relations; add private immutable document versions and scan metadata. Extend `ApplicationDocumentType` for CV/cover-letter where appropriate. Preserve existing identity/education rows and path snapshots; do not overwrite shared file bytes. Track references so library deletion cannot remove a file still attached to an application. Define retention and orphan cleanup before enabling deletion.

**Routes, controllers, services and components:** extend `ProfileController`/requests; proposed `CandidateEducationController`, `CandidateExperienceController`, `CandidateDocumentController`, profile/document policies and focused document service. Reuse existing private preview/download service and wizard fields; disable unsafe public previews for newly supported formats.

**Integrations, notifications and automation:** queued malware scanning through a reviewed deployment capability; quarantine until clean, retry safely and show service-unavailable state. Limit file types to supported safe formats; external CV parsing is later optional work.

**Security, validation, testing and performance:** verify MIME/content/size, filename normalization, path traversal prevention, quotas and rate limits; prevent HTML/SVG execution. Audit existing public profile-photo exposure and legacy private-file migration. Test two candidates and two employers, rejected scans, replacement, rollback cleanup, missing files and shared references. Use bounded previews and streamed downloads.

**Dependencies:** Stage 05 plus Stage 02 queues/storage. **Completion criteria:** professional profile and reusable CV work; submitted snapshots survive profile/document replacement; private file access/scan/quota tests pass; no sensitive documents remain reachable through public storage.

### Stage 07 — Application drafts, submission, tracking and withdrawal

**Objective and outcome:** candidates can reliably submit and track vacancy-specific applications. State: Improve wizard/submission/tracking; New drafts, dynamic answers and withdrawal.

**Features and functionality:** vacancy-specific wizard prefilled from reusable profile; secure drafts; required question/document validation; review/consent screen; submit once with reference and receipt; application detail/status timeline; withdrawal confirmation. Preserve one application per candidate/vacancy even after withdrawal. Later recruiter changes to questions cannot invalidate an already submitted application.

**Roles and permissions:** candidate owns draft/submission/withdrawal; recruiters cannot edit submitted candidate answers. Authorized correction requests can be a future explicit workflow. Candidate-facing status messages exclude internal notes, other applicants and unpublished decisions.

**UI/UX and dashboards:** progress indicator, inline and summary errors, Back/Next controls, save/resume state, upload progress and confirmation page. Test interrupted connections and retry messaging. Candidate dashboard shows application reference, vacancy/campaign, submitted time, public status, next action and interview links. Store drafts server-side; avoid placing sensitive form/file data in browser local storage.

**Database and migrations:** add draft and version-bound answer records; extend immutable application snapshot to professional fields and selected documents. Keep existing unique `(job_id,user_id)` and reference constraints; implement request idempotency metadata. Add `withdrawn` to enum/casts/history validation/report labels consistently; withdrawal reason/time and public-safe message. Drafts remain distinct from public Submitted state and recruitment metrics.

**Routes, controllers, services and components:** extend `JobApplicationController`, `StoreApplicationFormRequest`, `ApplicationFormService`, `ApplicationFormPolicy`, wizard components/JavaScript; add draft save/resume and `candidate.applications.withdraw` mutation routes. Recheck vacancy/organization availability inside the submission transaction; unique constraints settle concurrent attempts safely.

**Integrations, notifications and automation:** application receipt and assigned recruiter new-application intent; withdrawal acknowledgement and recruiter notice. Persist intent alongside submission/history; purge expired drafts through a monitored retention job.

**Security, validation, testing and performance:** dynamic fields validated server-side against the bound form version; enforce privacy notice acknowledgement and separate optional marketing consent. Only attach/submit immutable clean-scanned document versions; quarantine/pending/failed scans cannot be previewed or downloaded by recruiters or interviewers. Recheck scan state before submission and access. Test duplicate/concurrent submissions, closed deadline, suspended organization, foreign draft access, failed file writes, retry idempotency, quarantined attachments and withdrawal permissions. Limit payload/file count and avoid unbounded draft polling.

**Dependencies:** Stage 06. **Completion criteria:** a candidate can start/resume/submit/view/withdraw on mobile; rollback leaves no partial submission/orphaned new files; duplicate requests yield one application; receipts and status tracking are correct; updated workflow/auth/file tests pass.

### Stage 08 — Configurable pipeline and immutable transition history

**Objective and outcome:** a reliable, organization-scoped screening workflow with consistent public status. State: Improve fixed pipeline/history; New versioned internal stages.

**Features and functionality:** default documented stages; optional organization workflow templates with internal screening/interview rounds, stage order, canonical category and permitted transitions. Bind each vacancy to a published version. Show counts, filters and application detail; make list/move actions dependable before adding a board.

**Roles and permissions:** owners/admins configure templates; assigned recruiters move permitted applications; final selection delegates to Stage 12 approvals/publication. Keep new final-selection actions unavailable until Stage 12 gates pass; the existing Selected enum value is not permission to bypass decision approval. Interviewers cannot move candidates by default; candidates only withdraw. Workflow configuration cannot grant permissions.

**UI/UX and dashboards:** campaign/vacancy pipeline header, stage counts, searchable candidate list and detail panel; accessible move menu with reason/confirmation, optional board with equivalent keyboard controls. Show last update and stale-data conflict message. Public candidate labels remain Submitted/Shortlisted/Interview/Selected/Rejected/Withdrawn.

**Database and migrations:** create workflow templates/versions/stages and internal stage history, or extend existing status history without duplicating the same event. Add application current-stage/version and concurrency version. Backfill existing public statuses with deterministic matching stages; preserve old history timestamps and label unknown history as incomplete. Rejected/withdrawn retain last reached internal stage. Lock referenced workflow versions; new versions apply to new vacancies or an explicitly previewed migration.

**Routes, controllers, services and components:** extend `EmployerApplicationController`, `MoveCandidatePipelineRequest`, `ApplicationFormService::moveCandidate` and policy; add workflow configuration endpoints/services. Use one transition command path for UI, bulk and scheduled callers. Database transaction/row lock plus expected version prevents stale writes; append public/internal event and notification intent together.

**Integrations, notifications and automation:** status-change intent carries a separately curated candidate message. Internal stage movement need not send email if public status does not change. No autonomous rejection or selection rules at launch.

**Security, validation, testing and performance:** validate same-tenant stage/version, transition legality, terminal states and reason rules. Test concurrency, forged stages, withdrawal race, duplicate events, legacy backfill, public/internal visibility and immutable history. Paginate lists and load stage aggregates in bounded queries.

**Dependencies:** Stage 07. **Completion criteria:** default transitions remain compatible; configurable internal rounds do not corrupt public statuses; rejected/withdrawn stage attribution is preserved; stale requests fail safely; transition/policy/history tests pass.

### Stage 09 — Screening, shortlisting and hiring-team collaboration

**Objective and outcome:** recruiters can make consistent, accountable shortlist decisions at realistic volume. State: Improve filtering/shortlisting; New assignments, notes/tasks and safe bulk actions.

**Features and functionality:** filter by vacancy/campaign/stage, relevant qualifications/skills and submitted date; assigned reviewers; structured screening checklist and eligibility evidence; shortlist/reject reasons; internal notes, mentions, tasks and due dates. Search covers authorized applications, not all registered candidates. Saved filters are private or explicitly shared within a team.

**Roles and permissions:** assigned recruiters review/shortlist; hiring managers review within assigned scope; interviewers cannot read restricted deliberations. Mentions only resolve authorized active members. Analyst permissions do not imply record-level export or messaging rights.

**UI/UX and dashboards:** reviewer queue shows age in stage, assignment and outstanding task; application detail shows submitted evidence and separated internal/public messages. Bulk action preview states selected count/filter scope, action, notification effect and exclusions; present per-record result after completion.

**Database and migrations:** add reviewer assignments, `application_notes`, `application_tasks`, screening records/template versions and optionally saved views. Index organization/assignee/status/due-date paths; notes have explicit visibility. Preserve edit/deletion audit or version history for decision-relevant notes. Do not rank on sensitive demographic/identity fields by default.

**Routes, controllers, services and components:** `ApplicationScreeningController`, note/task/assignment controllers and policies; `ScreeningService`, `BulkApplicationActionService`; extend pipeline filter/detail/table components. Each bulk item passes the same authorization and transition service; use bounded queued batches for large sets with cancellation/progress.

**Integrations, notifications and automation:** assignment/mention/task-due notifications; SLA reminders respect organization timezone and deduplication. Deterministic checks identify missing information and support humans; do not silently reject from a rule.

**Security, validation, testing and performance:** escape notes, reject unauthorized mentions and mass-assignment, enforce bulk size/filter allowlists. Test partial failure, lost membership mid-batch, duplicate batch retry, note visibility and cross-tenant filters. Avoid N+1 loading and unrestricted `%term%` scans at scale; measure before adding indexed search infrastructure.

**Dependencies:** Stage 08. **Completion criteria:** recruiter can review evidence and shortlist with a traceable reason; assignments/tasks work; internal notes stay private; bulk results reconcile with individual histories; access/bulk/query tests pass.

### Stage 10 — Manual interviews, scheduling and attendance

**Objective and outcome:** actual interview records and reliable candidate coordination. State: New; extends existing Interview status.

**Features and functionality:** create one/multiple interview rounds, panel assignment, location/video URL, UTC time and IANA display timezone, invitation, candidate confirmation/decline, reschedule/cancel, reminders, attendance/no-show and calendar-file download. Record completed interviews; scheduled status alone is not completion.

**Roles and permissions:** assigned recruiters schedule; assigned managers/interviewers see necessary packet and submit attendance/feedback; candidate sees only their own interview details and response controls. Cancellation/rescheduling is limited and auditable. Do not reveal other candidates or private calendars.

**UI/UX and dashboards:** recruiter schedule list/calendar with conflict warnings and timezone labels; interviewer agenda/pending feedback; candidate upcoming interview card with local timezone, instructions and accessible response/reschedule-request controls. Always provide list view alongside calendar view.

**Database and migrations:** add interviews/participants, schedule version, UTC start/end, original IANA timezone, round, state, meeting details and response/attendance timestamps. Use evaluator/participant membership references and application ownership. Index upcoming/due/reminder queries; prevent participant duplication and concurrent schedule edits. Detect overlapping Marthire interviewer assignments transactionally where possible.

**Routes, controllers, services and components:** `InterviewController`, `InterviewResponseController`, policy and `InterviewSchedulingService`; proposed schedule/update/cancel/respond/download routes; agenda and interview-form components. Calendar download is generated from authorized current data; changing schedule invalidates old reminder versions.

**Integrations, notifications and automation:** queued invitations, reminders and cancellation/update emails; ICS generation with stable event UID/version. Manual video links supported. Google/Microsoft OAuth availability and self-scheduling remain Stage 19; launch cannot claim knowledge of conflicts outside Marthire.

**Security, validation, testing and performance:** validate positive duration, timezone, URL scheme, active assignees and interview-stage compatibility. Test timezones/DST, overlapping requests, stale reminders, cancellation, authorization, expired response links if used and candidate declined slots. Reminder scans use indexed windows/batches and do not send duplicate notices.

**Dependencies:** Stage 09. **Completion criteria:** schedule → invite → respond → reschedule/cancel → complete works; timezone display and ICS updates agree; obsolete reminders suppressed; assigned-only access and scheduling tests pass.

### Stage 11 — Structured evaluations and decision evidence

**Objective and outcome:** consistent, comparable interview/screening feedback attached to applications. State: New.

**Features and functionality:** organization/vacancy rubric templates, versioned criteria/weights/scales, interviewer assignments, draft/save/submit evaluations, evidence/comments and overall recommendation. Display deterministic summaries without making hiring decisions automatically. Require submitted feedback or a reasoned authorized override before decision publication.

**Roles and permissions:** authorized managers define rubrics before use; assigned evaluators edit their own drafts; submitted scorecards are immutable except a recorded revision workflow. Recruiter/approver access is scoped; candidates never receive internal scorecards. Default independent review hides peers' scores until the evaluator submits, with explicit manager capability exceptions.

**UI/UX and dashboards:** interviewer queue with due dates, rubric and candidate packet; accessible scored controls with evidence fields and missing-answer summary. Application evaluation tab shows completion, comparable criterion scores, recommendation and evidence, clearly identifying missing or non-comparable feedback.

**Database and migrations:** template/version/criteria, evaluations and scores; evaluation links to application, optional interview, rubric version and evaluator. Unique assignment/evaluation constraints; store submission/revision times. Validate weights/ranges; snapshot criteria so later template edits do not rewrite historical results. Do not combine different rubric versions into misleading aggregate scores.

**Routes, controllers, services and components:** `EvaluationTemplateController`, `EvaluationController`, policies/Form Requests, `EvaluationService`; evaluation draft/submit/revision endpoints and scorecard/summary components. Calculations live server-side with explicit missing-score rules and reproducible precision.

**Integrations, notifications and automation:** feedback assignments/reminders and evaluation-complete intent to authorized decision makers. No external assessment provider needed; later assessments attach separate provenance and do not overwrite interviewer evidence.

**Security, validation, testing and performance:** test assigned ownership, blind review, score range/weight rules, incomplete submission, immutable versions, revoked assignments and calculation edge cases. Escape comments; exclude identity/demographic data from evaluation packet unless a justified approved requirement applies. Eager-load bounded rubric/score relations.

**Dependencies:** Stage 10. **Completion criteria:** two assigned interviewers can independently submit a fixed-version rubric; summaries reconcile exactly; missing feedback is visible; privacy/scoring/versioning tests pass.

### Stage 12 — Hiring decisions, selection approval, optional offers and hires

**Objective and outcome:** auditable human decisions and an honest distinction between selection and actual hiring. State: Improve Selected/Rejected movement; New decision/approval/offer/hire records.

**Features and functionality:** decision packet, select/reject proposal and reason, optional organization approval, feedback completeness/headcount checks, candidate-safe publication, vacancy fill/close action and confirmed hire/start record. Provide a minimal manual offer record where required: approved terms/version, private attachment, sent date and candidate accepted/declined response. E-signature and HRIS handoff are later extensions.

**Roles and permissions:** assigned recruiter proposes; authorized hiring manager/approver approves; designated publisher commits public decision; separate capability controls compensation/offers and hire confirmation. Define self-approval policy and default to independent approval where configured. Candidates see published decisions and their own permitted offer only.

**UI/UX and dashboards:** manager approval queue; application decision panel summarizes evidence, outstanding feedback, headcount and reason. Preview exact candidate message and notification timing before publication. Candidate selection screen explains next step; Selected alone must not display “employment started.”

**Database and migrations:** add hiring decisions/approval steps, immutable decision publication event, optional offers/versions/responses, hires with confirmation/start/cancellation dates. Unique active decision/offer rules and idempotency keys; lock vacancy headcount/decision when publishing competing selections. Keep `application_forms.status` synchronized through the same transition service. Historic Selected rows become legacy selected decisions only when evidence supports it, never automatically hires.

**Routes, controllers, services and components:** `HiringDecisionController`, `DecisionApprovalController`, `OfferController`, `HireController`, scoped policies and `HiringDecisionService`. Remove any direct selection path that bypasses approval rules. Core manual selection must work without the optional offer module.

**Integrations, notifications and automation:** publish selection/rejection intents, approval reminders and offer response notices. HR onboarding handoff initially exports a minimal authorized record, if enabled. No automatic rejection of remaining candidates when a role fills; require a previewed explicit decision process.

**Security, validation, testing and performance:** test permission separation, unpublished visibility, missing feedback override, simultaneous headcount use, double approval/publication, declined offer/no-start, sensitive attachment access and event retry. Compensation never leaks to general pipeline/export users. Bound decision packet queries.

**Dependencies:** Stage 11. **Completion criteria:** proposal → approval → publication is atomic/auditable; candidate communication matches published state; Selected/hire metrics differ correctly; headcount/race/privacy/decision tests pass; hire confirmation is explicit.

### Stage 13 — Communication center, preferences and recruitment automation

**Objective and outcome:** dependable, understandable communication and safe administrative automation across the lifecycle. State: Improve existing notification infrastructure/screens; New persistent communication management.

**Features and functionality:** in-app inbox/read state, transactional email templates, candidate-safe stage updates, recruiter candidate messaging, delivery history, preference controls, task/feedback/interview reminders and overdue-review digests. Separate mandatory service notices from optional job alerts/marketing; do not suppress essential interview or account notices because marketing is disabled.

**Roles and permissions:** each user controls own permitted preferences; assigned recruiters message authorized applications; organization template administrators edit safe templates; platform operators troubleshoot redacted delivery data. Bulk messaging requires explicit capability, scope preview and per-recipient authorization.

**UI/UX and dashboards:** notification center with type/unread filters and deep links to permitted records; candidate action language is plain and consistent. Recruiter message composer shows recipient, application context and approved template. Admin health panel shows failed/queued/delivered/unknown states without exposing credentials.

**Database and migrations:** expand early intents into delivery attempts/provider IDs/status/error classification, templates/versions, preferences and optional application conversation/message records. Unique immutable event/recipient/channel/purpose key prevents logical duplicate sends; snapshot the chosen template version/content on the intent separately so template edits cannot resend old events. Define retention for message bodies and redact payloads. Existing Laravel notifications remain the in-app transport/store where appropriate.

**Routes, controllers, services and components:** `NotificationController`, `NotificationPreferenceController`, `MessageTemplateController`, `ApplicationMessageController`, policy and `RecruitmentNotificationService`; inbox/read-all/preferences and authorized send endpoints. Transactional outbox/intent reconciler handles a crash between commit and dispatch. No inbound email integration promised at launch.

**Integrations, notifications and automation:** current production mail transport, provider bounce/status callbacks if supported and explicitly configured; retry transient failures, quarantine permanent errors and alert on backlog. Sign/verify callbacks and deduplicate events. Rules may create reminders/tasks; selection/rejection stays human-controlled. SMS/WhatsApp stays disabled until Stage 19.

**Security, validation, testing and performance:** escape template values, allowlist placeholders, reject header injection, authorize links at click time, validate callback signatures and redact tokens. Test retry/crash reconciliation, revoked access, duplicate callbacks, preferences, template versions and bulk partial failures. Queue heavy sends, cap batches and monitor oldest pending age; document external-delivery uncertainty instead of promising exactly-once SMTP.

**Dependencies:** Stage 12, extending Stage 02 intent/queue infrastructure and earlier event emitters. **Completion criteria:** full recruitment smoke journey yields the correct recipient/message once logically; failures are visible/recoverable; preferences and unauthorized links behave correctly; delivery/privacy/idempotency tests pass.

### Stage 14 — Organization dashboards, operational reporting and exports

**Objective and outcome:** trustworthy recruitment visibility without cross-tenant leakage or misleading metrics. State: Improve existing dashboards/admin reports; New organization/campaign reports.

**Features and functionality:** open vacancies, submitted applications, awaiting review, current shortlist/interview/selection counts, upcoming interviews, missing feedback, overdue tasks, withdrawals and confirmed hires. Filter by organization/campaign/vacancy/date/owner. Launch reports include current funnel, stage age, review turnaround and basic export; advanced trend/cohort/source analytics later.

**Roles and permissions:** assigned recruiters/managers see permitted operational scope; organization analysts get authorized aggregates; identifiable exports require separate capability. Platform admins see platform totals and restricted drill-down only with appropriate support capability.

**UI/UX and dashboards:** consistent filter bar and date/timezone basis, cards linking to actionable lists, readable chart/table equivalents, last-refreshed labels and empty states. CSV/export screen shows fields, privacy scope, filters and expiry. Distinguish current counts from historical entries and decisions from hires.

**Database and migrations:** reuse transitions/interviews/hires as source of truth; add `report_exports` with requester/tenant/filter/schema version/path/expiry. Create summary tables only after measurement; include reconciliation/rebuild strategy and freshness. Index common organization/job/status/submitted and stage-time queries without duplicating existing indexes blindly.

**Routes, controllers, services and components:** extend `EmployerDashboardService`, `AdminDashboardService`, `AdminReportService` and report requests; add `OrganizationReportController`/service, authorized export jobs and download endpoint. Use common metric definitions for cards, tables and exports.

**Integrations, notifications and automation:** queued export-ready notification with private expiring authenticated download; scheduled export cleanup; no public BI connection at launch. Cache aggregates with tenant/role/filter-aware keys and correct invalidation.

**Security, validation, testing and performance:** validate filter ownership/date ranges/sort/format, cap export size, protect CSV formula-like cells, audit downloads and recheck membership when exporting/downloading. Test metric fixtures against histories, rejected-at-stage attribution, withdrawals and legacy incomplete timestamps. Avoid full-table collection processing and synchronous huge exports; measure representative data volumes.

**Dependencies:** Stage 13 and prior event/history quality. **Completion criteria:** figures reconcile to known fixtures and source records; all dashboard roles see correct scope; exports expire and cannot cross tenants; metric/query/export tests pass.

### Stage 15 — Administration, moderation, templates and platform settings

**Objective and outcome:** replace placeholder administration pages with governed operational controls. State: Improve user/job admin; New persistent settings/templates/organization operations.

**Features and functionality:** organization approval/suspension, abuse queue, account recovery/management, taxonomy management, workflow/interview/email templates, approved typed organization/platform settings, audit viewer and operational health. Permission UI initially manages supported capability mappings/roles; avoid arbitrary executable policy definitions. Billing/subscriptions remain out of launch unless separately approved.

**Roles and permissions:** platform permissions separated by function; organization admins manage only permitted tenant settings. Require step-up confirmation/MFA for privileged access changes, ownership transfer, destructive actions and secret rotation. Protect the last platform administrator and organization owner.

**UI/UX and dashboards:** moderation work queues with reason/history, search/filter user/organization lists, template preview/version diff, settings validation and saved-state feedback. Clearly distinguish organization defaults from platform settings; show impact preview for changes affecting active recruitment. No editable secret values in generic settings forms.

**Database and migrations:** typed settings with tenant/platform ownership, template version references, moderation decisions and audit events. Secrets remain in managed deployment configuration/secret storage; only safe operational metadata belongs in settings. Soft-disable/archive accounts/organizations first; deletion follows Stage 16 retention rules rather than cascading through recruitment history.

**Routes, controllers, services and components:** extend existing admin controllers; replace view-only endpoints with `PlatformSettingsController`, `OrganizationSettingsController`, template/moderation controllers, Form Requests, policies and settings services. Preserve `/admin/*` boundaries and reuse user/job management partials.

**Integrations, notifications and automation:** moderation/account/team-change notices, audit alerts and safe health checks. Mail configuration screen can show readiness and trigger an authorized test delivery; it must not expose `.env` or allow arbitrary transport execution.

**Security, validation, testing and performance:** validate setting keys/types/limits, safe taxonomy changes and template placeholders; test self-escalation, mass assignment, privileged action audit, last-admin protection, tenant overrides and secret redaction. Audit lists paginated/indexed; health checks use timeouts and expose limited detail.

**Dependencies:** Stage 14. **Completion criteria:** every advertised settings/template action persists correctly with authorization/audit; nonfunctional placeholders removed/hidden; account/org suspension revokes access; targeted admin/settings regression tests pass.

### Stage 16 — Privacy, security hardening, resilience and scale

**Objective and outcome:** launch can safely process recruitment data and recover from operational failures. State: Improve all completed slices; New privacy/retention operations where absent.

**Features and functionality:** privacy notice/version records, access/export/correction/deletion request workflow, retention schedules and holds, backup/restore, incident handling, file lifecycle cleanup, monitoring and capacity controls. Obtain jurisdiction-specific legal/privacy review for actual launch markets, data roles, hosting locations, contracts and cross-border providers; this plan does not certify legal compliance.

**Roles and permissions:** candidates request own data actions; organization privacy operators decide scoped requests; platform operators carry out authorized platform actions; retained/audited evidence is restricted. Deactivated accounts lose access immediately even when records are retained. Break-glass support access needs reason, time limit and review.

**UI/UX and dashboards:** clear privacy controls, export/deletion request state and understandable retention explanation. Admin shows operational/privacy queue, retention holds, storage/queue/error trends and recovery status. Never display raw sensitive logs to general organization users.

**Database and migrations:** privacy request/notice/hold metadata; safe anonymization markers where appropriate. Review current `cascadeOnDelete` FKs from users/jobs to applications/history and replace unsafe deletion behavior through reviewed migrations. Preserve legally justified audit evidence while removing unnecessary personal content according to approved policy. Shared document reference checks, quarantined-file lifecycle and backup retention must agree.

**Routes, controllers, services and components:** privacy request/export controllers and policies; retention/anonymization services and scheduled jobs with dry-run/reporting; hardened download/header/error handling. Use existing structure; no new infrastructure stack without a measured need.

**Integrations, notifications and automation:** encrypted database/private-file backups, restore drills, central error/availability monitoring, failed-job alerts and disk/capacity alerts; secret rotation and provider outage playbooks. Coordinate data deletion with backup expiry/restoration procedures and reapply deletion markers after restore where needed.

**Security, validation, testing and performance:** threat-model auth/tenant boundaries/uploads/exports/links/admin/integrations; dependency audit with reviewed exceptions; TLS/secure cookies/CSRF/security headers/CSP rollout, log redaction and least-privilege database/storage credentials. Test retention holds, export scope, anonymization, stale links, revoked memberships, backup restoration and storage failures. Load-test submission, pipeline and reports using synthetic data on the target database.

**Dependencies:** Stage 15; does not replace security work required in each earlier stage. **Completion criteria:** no unresolved critical/high security defects; privacy review complete; restore meets agreed RPO/RTO; private/public storage separation verified; retention respects references/holds; load and failure targets below pass.

### Stage 17 — Staging, end-to-end acceptance and release preparation

**Objective and outcome:** a reviewable release candidate proven against realistic recruitment scenarios. State: New release process; Improve all discovered defects.

**Features and functionality:** production-like staging using anonymized/synthetic data, full migration rehearsal, mail test inboxes, operational dashboards and role-specific UAT. Exercise an SME single vacancy, NGO multi-vacancy campaign, graduate/high-volume campaign and agency with separate clients. Confirm launch feature flags and support/training handover.

**Roles and permissions:** test guest/candidate/owner/admin/recruiter/manager/interviewer/analyst/platform-admin boundaries; release maintainer owns acceptance evidence. Real external sends and provider credentials are disabled or isolated in staging.

**UI/UX and dashboards:** UAT registration → onboarding → campaign → vacancy approvals → mobile application → shortlist → interview → independent evaluation → decision → notification → reporting. Include no-JavaScript/failure fallbacks where supported, slow connection, keyboard/screen reader and timezone use. Verify dashboards reflect real actions without stale counts.

**Database and migrations:** rehearse fresh install and upgrade from baseline snapshot; verify row counts, status mappings, tenant ownership, indexes and file references. Record backfill durations, expected locks and old/new application compatibility. No new product schema should be introduced here except tested release-blocking corrections.

**Routes, controllers, services and components:** inspect final effective route list/middleware, named links and asset manifest. Run installed static analysis if available; do not silently install Larastan/Rector. Review changed services/policies and remove debug routes, fixtures and unused duplicate screens.

**Integrations, notifications and automation:** staging queue/scheduler/retry/bounce tests, mail authentication readiness, storage permissions, monitoring alerts and restore drill. Provider failures must not lose applications or force resubmission.

**Security, validation, testing and performance:** affected tests per change plus full suite now; asset build; isolated production-engine migration/concurrency tests; browser end-to-end/accessibility and load tests. Triage by severity and test every fix. Resolve baseline failures from Stage 00 before release rather than carrying them as accepted production risk.

**Dependencies:** Stage 16. **Completion criteria:** release checklist and acceptance matrix signed off; all launch tests/build pass; no blocking defects/conflicts; recovery rehearsal and rollback plan approved; support/on-call owners and launch decision identified.

### Stage 18 — Production deployment, verification and operation

**Objective and outcome:** a controlled, recoverable production launch with accountable ownership. State: New operational delivery; no implied deployment authorization from this planning document.

**Features and functionality:** immutable release artifact, approved host/domain/TLS, production database/private object storage, mail, queue workers, scheduler, monitoring, backup automation and support process. Choose hosting from approved requirements for region, capacity, budget, availability and restore; do not assume Vercel, Supabase or Kubernetes is required for this Laravel application.

**Roles and permissions:** release maintainer deploys under explicit release authorization; infrastructure uses least-privilege service identities; bootstrap first admin/owner securely without default credentials. Platform operators perform documented smoke checks; temporary launch access expires.

**UI/UX and dashboards:** branded production pages, correct legal/help content, useful maintenance/error states and verified responsive dashboards. Admin sees current operational status; candidates receive clear maintenance messages if writes are temporarily paused.

**Database and migrations:** take verified pre-deploy database/file backup; apply reviewed additive migrations/backfills with monitored duration; compare reconciliation counts; enforce final constraints only after backfill succeeds. Freeze destructive schema contraction until a later proven release. Preserve backward-compatible code path for the rollback window.

**Routes, controllers, services and components:** build from lockfiles in CI with approved runtime; publish Vite artifact; configure production settings, `APP_DEBUG=false`, correct URL/timezone/trusted proxy and HTTPS/session settings. Run reviewed optimization/cache commands and `php artisan migrate --force --no-interaction`; restart workers for the new release and verify scheduler. Resolve project URLs through Boost `get-absolute-url` before sharing environment links.

**Integrations, notifications and automation:** validate sender domain/authentication and real delivery with controlled test accounts; check private storage access, queue latency, scheduled close/reminders, intent reconciler and alert routing. Keep Stage 19 providers disabled. Secret values never enter repository, browser build or logs.

**Security, validation, testing and performance:** production smoke: candidate auth, employer workspace, vacancy visibility, controlled application, document denial for another account, shortlist/interview/evaluation/decision, notification, report scope and admin suspension. Use authorized test records and clean them through normal retention-aware procedures. Monitor error rate, p95 latency, database load, queue age and disk/storage from deployment onward.

**Dependencies:** Stage 17 and explicit release approval. **Completion criteria:** smoke checks pass; no authorization/data-integrity regression; mail/queue/scheduler/backups/alerts healthy; rollback decision window observed; support handover complete. On failed security/data checks, stop affected writes and roll back code or forward-fix safely; never run destructive down migrations as an automatic rollback.

### Stage 19 — Post-launch improvements and separately gated integrations

**Objective and outcome:** grow from verified usage while preserving the operational core. State: New / Later; not a launch blocker.

**Features and functionality:** prioritize measured recruiter/candidate friction and production defects first. Later modules may include calendar OAuth/self-scheduling, assessment/verification, SMS/WhatsApp, public APIs/webhooks, consent-based talent pools, richer source attribution, advanced cross-campaign analytics, e-signature/HRIS handoff and explainable AI assistance. Saved jobs/job alerts are optional candidate conveniences, not substitutes for ATS work.

**Roles and permissions:** organization owner authorizes integrations/scopes; assigned recruiters use features within existing tenant boundaries; candidates manage optional channel/talent-pool consent. Platform administrators approve provider configuration and quotas. API clients get explicit tenant/capability scopes, revocation and audit; AI cannot grant access or make final hiring decisions.

**UI/UX and dashboards:** integration directory with connection health, permissions, disconnect and manual fallback; candidate self-scheduling shows timezone/availability and signed-link expiry. AI suggestions show evidence, uncertainty and human review/override. Talent-pool visibility is explicit and organization-scoped; never make candidates globally searchable without a separate justified product/consent model.

**Database and migrations:** add integration accounts with encrypted tokens/key references, external-ID mappings, webhook inbox/outbox/deduplication, sync cursors and connection audit. Provider schemas are additive. Consent provenance/expiry, source attribution, AI model/prompt/version/evidence and override records added only for enabled modules.

**Routes, controllers, services and components:** provider adapters behind interfaces; signed OAuth callback/webhook controllers with replay protection; versioned `/api/v1` routes and Eloquent Resources only when API work is authorized. Separate background sync from interactive recruitment services. Reuse existing authorization/state-transition contracts.

**Integrations, notifications and automation:** calendar refresh/revocation/availability reconciliation; assessment callback verification; channel opt-out/template/delivery requirements; API rate limits and signed webhooks; e-signature version/approval enforcement. Use sandbox credentials and provider contracts before production enablement.

**Security, validation, testing and performance:** test token expiry/revocation, duplicate/out-of-order webhooks, provider outage/rate limits, cross-tenant IDs, retries, disconnect and manual fallback. Complete provider/privacy review, budgets and load limits. AI requires fairness/evidence evaluation, prompt-injection defenses for uploaded content, data-minimization and human approval; no autonomous rejection/selection.

**Dependencies:** Stage 18 and module-specific prerequisites in Section 9. **Completion criteria:** each module has its own reviewed scope, approved dependency/provider budget, sandbox tests, access/privacy review, recovery path, observability and controlled rollout. Do not treat this entire stage as one all-or-nothing release.

## 6. Metric definitions and data-quality requirements

| Metric | Launch definition and guardrail |
| --- | --- |
| Open vacancies | Effective published/approved/open vacancies at observation time; distinguish roles from remaining headcount |
| New applications | Submitted applications in the selected submission window; exclude drafts and retry duplicates |
| Awaiting review | Submitted public category with unfinished screening task; label the exact filter |
| Current funnel | Current application counts by public category/internal stage; Rejected/Withdrawn shown as outcomes with last reached stage |
| Historical funnel | Distinct applications entering each stage during a stated cohort/window; later implementation must explain re-entry/deduplication |
| Age in stage | Now minus latest valid internal stage-entry time; terminal records stop accumulating; migrated unknowns shown as unavailable |
| Review turnaround | First authorized screening decision minus submitted time; define business versus elapsed time explicitly, default elapsed |
| Time to selection | Published selection time minus application submission; not called time-to-hire |
| Time to fill | Vacancy first publication to explicit filled/closed-as-filled timestamp; reopening/headcount rules must be stated |
| Time to hire | Confirmed hire time minus agreed recruitment entry timestamp; show only when explicit Hire records exist |
| Withdrawal/selection rate | Numerator and denominator belong to the same labelled submission cohort; show sample size and exclusions |
| Interview completion | Completed interviews versus scheduled eligible interviews for the stated period; cancellations/no-shows separate |

Never manufacture historical events from a current status or silently count legacy unknown timestamps as zero duration. Reports should label incomplete migration periods. Advanced comparisons require consistent rubric/workflow categories and cohort definitions.

## 7. Cross-stage verification, quality and operational targets

### Automated verification matrix

| Area | Required evidence |
| --- | --- |
| Authentication/account status | Extend `tests/Feature/Auth/*`, `RbacAccessTest`; session invalidation, verification/reset throttles and Google-link edge cases |
| Organizations/campaigns | New `OrganizationOnboardingTest`, `OrganizationAccessTest`, `RecruitmentCampaignTest`; multi-tenant/agency membership, last-owner and backfill invariants |
| Vacancies/public discovery | Extend `JobManagementTest`, `AdminJobManagementTest`, `PublicJobListingsTest`; moderation/publication/date rules and archival |
| Candidate profile/files | Extend `ProfileTest`, `ApplicantProfileAccessTest`, `ApplicationDocumentPreviewTest`, private migration command tests; shared references, scan/format rejection and denied file URLs |
| Applications | Extend `ApplicationFormWorkflowTest`, `ApplicantDashboardTest`; snapshot/draft/answer versions, duplicate request, withdrawal and deadline races |
| Pipeline/collaboration | Extend `CandidatePipelineTest`; add note/task/bulk tests; tenant/assignment scope, stale writes and immutable transition history |
| Interviews/evaluations/decisions | New feature tests for timezone/reschedule reminders, interviewer-only/blind evaluation, scoring/versioning, approval/headcount/publication and confirmed hires |
| Communications/reports/admin | Extend existing report/admin/dashboard tests; add notification reconciliation/preferences/settings tests, metric fixture/export privacy and settings persistence |
| Migrations/resilience | Fresh and upgrade fixture checks on isolated SQLite and production-matching database; multi-connection races, file failure and backup restore rehearsal |
| Browser/accessibility/performance | Complete lifecycle on phone/desktop, keyboard/screen reader, slow connection, build manifest, load/capacity and failure recovery evidence |

Suggested future tests are proposed names, not files created by this documentation task. Prefer meaningful feature coverage of business outcomes; unit tests are appropriate for deterministic scoring, transition mapping or date logic. Do not write tests that merely assert an implementation detail.

Run the smallest affected set during each slice, for example `php artisan test --compact tests/Feature/CandidatePipelineTest.php`. Run the full suite at integration/release gates. Use installed tooling only; adding browser runners, static analysis packages or observability dependencies requires the project's dependency approval process. The current conflicted tests must be repaired before test results can establish baseline correctness.

### Proposed measurable launch targets

These are starting acceptance targets to ratify in Stage 00/01 and measure on agreed hosting and synthetic datasets; they are not claims about present performance.

| Area | Initial target / measurement condition |
| --- | --- |
| Interactive performance | p95 server response under 800ms for paginated vacancy/pipeline/dashboard reads on agreed staging load; measure upload/network/large export separately |
| Submission correctness | Zero lost/duplicate applications in concurrent retry/deadline tests; accepted response only after durable record and intent commit |
| Reference load | At least 100,000 synthetic applications across multiple tenants, including a 10,000-application vacancy; 50 concurrent active sessions with mixed read/write traffic; raise based on expected launch volume |
| Queries | Bounded query count per paginated screen; no per-row N+1; inspect expensive query plans and record tenant-filter indexes |
| Background work | Normal transactional notifications queued within 60 seconds and processed within 2 minutes under reference load; alert on oldest-intent/queue age and persistent failures |
| Capacity | Explicit upload/file-count quotas, export limits, worker concurrency and storage alerts; reject overload clearly without corrupting state |
| Recovery | Proposed RPO at most 1 hour and RTO at most 4 hours, subject to hosting/budget approval; demonstrate database and private-file restoration together |
| Security | No unresolved critical/high defects; all negative tenant/document/privilege tests pass; privileged MFA and secret-redaction checks verified |
| Accessibility | Main launch journeys meet agreed WCAG 2.2 AA acceptance checks; keyboard completion and meaningful errors verified |

Do not introduce Redis/search clusters/read replicas merely to satisfy a theoretical scale goal. Start with indexes, pagination, eager loading, queueing, cached aggregates and sensible quotas; add infrastructure after measurements and dependency/cost approval. Horizontal scaling requires shared sessions/cache/private storage, duplicate-safe scheduler leadership and deployment coordination.

## 8. Migration and deployment safety checklist

1. **Inspect:** confirm runtime/lockfiles/schema and migration history; identify unsafe cascades, old enum values, missing constraints and private/public file locations.
2. **Expand:** add nullable organization/campaign/version fields and compatible new tables; deploy code that can read old and new records while always populating new writes correctly.
3. **Backfill:** use reviewed, bounded, resumable and idempotent commands with stable mapping; report counts/ambiguous records and avoid holding large transactions/locks.
4. **Reconcile:** compare users/jobs/applications/documents/history counts, tenant ownership and orphan checks; verify every private-file reference. Candidate identity stays global, recruiter access stays tenant-scoped.
5. **Constrain:** enforce required FKs/non-null/uniqueness after reconciliation. Verify same-tenant relations through service validation and database constraints where practical.
6. **Switch:** update policies/routes/reports to the new organization/campaign model; run multi-tenant negative tests and monitor fallback use. Populated organization ownership always takes precedence; a legacy employer ID never grants a second authorization path. Remove any temporary legacy-only fallback after reconciliation. Keep legacy creator fields during compatibility period.
7. **Recover:** rehearse restoring database and files together; record failed-deploy triggers and safe code rollback/forward-fix. Database rollback is a reviewed operation, not an automatic `migrate:rollback`.
8. **Contract later:** remove obsolete fallback columns/code only in a separately verified release after backups, monitoring and rollback window prove they are unused.

Production sequence: approved release artifact → verified backup → safe migrations/backfills → release/config/assets → cache/worker/scheduler refresh → controlled smoke checks → monitor → confirm release. Hosting-specific steps must be concrete before Stage 18 approval. Never regenerate `APP_KEY` during routine deployment or expose private documents through a storage symlink.

## 9. Later-release dependency map

| Module | Required foundations | Separate release gate |
| --- | --- | --- |
| External calendars/self-scheduling | Stage 10 manual scheduling/versioning; Stage 13 durable notifications; tenant-scoped OAuth | Availability recheck, conflict resolution, timezone/DST, token refresh/revocation, provider outage and manual fallback |
| Assessment/verification | Stage 11 versioned evaluation evidence; consent/document privacy; Stage 19 webhook foundation | Authorized provider data scope, signed/replayed callback handling, clear provenance and human interpretation |
| SMS/WhatsApp | Stage 13 preference/delivery model; approved provider/market requirements | Consent/opt-out, template approval where applicable, costs/rate limits, duplicate prevention and delivery status |
| API/webhooks | Proven tenant policies and domain services; scoped client auth/versioned Resources | Rate limits, idempotency, replay protection, audit, revocation, contract tests and secret rotation |
| Talent pools/referrals | Stage 06 professional profile; Stage 09 scoped search; privacy consent/retention | Candidate awareness/consent, per-organization visibility, unsubscribe/delete controls and no cross-client leakage |
| E-signature/HRIS handoff | Stage 12 offers/approvals/hire distinction; private files and provider callbacks | Correct approved document version, signatory authorization, callback reconciliation and minimal handoff payload |
| Advanced analytics/source attribution | Stage 14 metric dictionary; reliable histories and source provenance | Cohort definitions, legacy completeness labels, aggregate privacy, freshness and source-data reconciliation |
| AI assistance | Reliable profiles/evidence, tenant isolation, human workflow and audit | Provider/privacy approval, evaluation dataset, fairness/explanation, prompt-injection protection, cost limits and human final decisions |

## 10. Completion record for each implementation stage

Use this compact template in the stage's delivery/PR record, without creating another documentation file:

- Stage ID, scope and baseline classification: implemented behavior preserved, improvements delivered, new behavior delivered.
- Prerequisites and product decisions confirmed; remaining assumptions or deferred features explicitly identified.
- Models/migrations/backfills, routes/policies/requests/services and UI components changed.
- Role/workspace walkthrough and denied-access evidence, including a second organization/candidate.
- Automated test commands/results; build/format checks; browser/accessibility and performance evidence appropriate to scope.
- Notification/automation retry behavior, audit and privacy implications, operational ownership.
- Migration reconciliation and rollback/restore path; exit criteria met; next stage authorized to begin.

**Application completion means an organization can onboard its team, run a campaign, publish approved vacancies, receive secure applications, screen and shortlist candidates, coordinate interviews, collect evaluations, publish accountable decisions, confirm hires where appropriate, communicate reliably and report accurately—and the platform can operate, recover and protect tenant data in production.**
