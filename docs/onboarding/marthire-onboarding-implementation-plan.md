# Marthire Organization Onboarding — Implementation Plan

**Status:** Discovery complete; implementation approved and completed on 2026-11-09. See [implementation notes](marthire-onboarding-implementation.md).

## 1. Executive summary

Build a progressive, resumable first-run experience for Organization accounts and their new organization memberships. It will reuse Marthire's existing authentication, organization provisioning, membership permissions, team invitation, company settings, job creation, and dashboard flows.

The onboarding steps will be:

1. Welcome and recruitment-focus preference.
2. Organization profile and a career-page preview that uses saved organization branding; color changes remain in Organization Settings.
3. Optional hiring-team invitations.
4. Finish setup and open the organization dashboard.

Recruitment-focus answers are preferences only. The account type and the user's real `OrganizationMember.role` continue to control access. The onboarding must not create an organization from a browser-supplied organization ID, accept a client-supplied role as permission, or duplicate the existing invitation/job workflows.

## 2. Audit method and existing system

Three read-only project agents reviewed authentication/organization context, UI/workflows, and security/data integrity. No agent edited files or ran tests. I also inspected the local routes, models, services, requests, middleware, migrations, settings views, job request, and public-job presentation.

### Authentication and account creation

- `UserRole` has Admin, Organization, and Applicant account types.
- Public email/password registration currently creates **Applicant** accounts only, initially pending email verification. Google sign-in is already integrated through Socialite and creates or links Applicant accounts by default.
- Organization accounts currently come from the admin user-management flow or an accepted team invitation. There is no public employer account-type selection today.
- The existing role-specific dashboard entry point sends Admin, Organization, and Applicant accounts to separate dashboards.

### Organization creation, membership, and context

- `InitializeOrganizationContext` initializes the active organization from the authenticated user's active memberships and session context.
- For an Organization account with no current or historical membership, `OrganizationProvisioner` transactionally creates one organization and an Owner membership. It locks the user and refuses to create a duplicate workspace where a historical membership exists.
- Team invitations use `OrganizationTeamService`; invitation acceptance creates or restores the invited user's actual organization membership and sets the active organization in the session.
- Organization data is scoped through `OrganizationContext`, organization global scopes, and `OrganizationPermissions`.
- `OrganizationContextController` and `SwitchOrganizationRequest` exist, but the controller has no registered route or user-facing switch control. The onboarding must rely on the existing middleware's active context, not assume switching is currently available.

### Roles and permissions

`OrganizationRole` is assigned per membership (Owner, HR Manager, Recruiter, Hiring Manager, Interviewer). `OrganizationPermissions`, gates, policies, and job/candidate requests are the authority for each operation. A selected onboarding persona must be stored separately and must never update `users.role`, `organization_members.role`, or a permission list.

### Settings, team, jobs, and dashboard

- `OrganizationSetting` is one-to-one with an organization. It already stores the company logo, description (`about`), external careers URL/contact fields, and hiring defaults. Logo validation/storage, version checks, audit logging, transaction handling, and cleanup are already implemented.
- Industry, organization size, primary, and an internal Marthire career page are not present today. Public job detail presentation uses organization logo/description, but there is no organization career-page route.
- Team invitations already validate roles, protect Owner privileges, reject existing members, revoke prior pending invitations for the same organization/email, hash invitation tokens, and send the existing notification. The current UI/API accepts one email per request.
- Job creation already provides its own validated form and permissions. `publish` immediately opens a job; `save_draft` creates a draft. There is no separate job approval workflow today. No AI job-description generation or manual candidate-creation feature was found.
- The organization dashboard already reads statistics under the active organization and effective membership permissions.

### Assets and design system

Use a dedicated full-screen onboarding layout with the existing `nw-*` components and responsive styling in `resources/css/app.css`. The Webflow URL is a community showcase, not a blanket license for its contents. The recruiting collection was reviewed for direction, but no community asset was copied without a specific license. A lightweight Marthire-native recruiting illustration is stored locally at `public/asset/marthire/onboarding/hiring-workspace.svg`; see [asset notes](assets.md).

## 3. Proposed account and onboarding behavior

### Account entry

Keep authentication, verification, and Google sign-in security intact. To support the stated new-employer journey, the proposed registration change is an explicit **Applicant / Employer account type** choice; Admin is never self-registerable. Employer selection creates a pending Organization account, and the existing trusted provisioning path creates its initial Owner membership after verification. Existing Google accounts keep their existing account type. For a new Google registration, an intended account type may be carried through the existing OAuth state/session flow and must be validated server-side; callback linking must never change an existing user's role.

After authentication, show a separate **“How will you be using Marthire?”** preference. Suggested choices: Recruitment lead, HR/People team, Recruiter, Hiring manager, and Other. This preference only personalizes copy and recommended next actions. The server always uses the persisted account role and active organization membership for authorization.

### Role-aware steps

- **Owner:** can edit organization profile/branding, invite teammates, create jobs, or open the dashboard.
- **HR Manager:** can use team invitations and job creation where existing permissions allow; organization profile and branding remain Owner-only.
- **Recruiter:** can see job setup only if `create_jobs` is granted; no team/settings rights are inferred from onboarding answers.
- **Hiring Manager and Interviewer:** receive a concise workspace orientation and dashboard/candidate-review actions that their existing role can access. They do not see Owner-only setup controls.
- **Applicant and Admin:** retain their current dashboard/authentication experience. They are not redirected into organization onboarding.

Every step checks the current membership and existing policies on the server. Hiding a step in the browser is not authorization.

## 4. Progress storage and legacy rollout

Add `organization_onboarding_progress`, uniquely keyed to `organization_member_id`, with:

- current step;
- non-authoritative recruitment-focus preference;
- completed and skipped step keys;
- completion/dismissal timestamps;
- timestamps and an optional version for concurrent updates.

The organization is derived from the related membership and active `OrganizationContext`; no submitted organization ID is trusted. Progress is stored in the database so it resumes across browser sessions and devices.

The migration marks existing memberships as completed to avoid forcing current customers through onboarding on deployment. A new progress row is created transactionally/idempotently only for a newly provisioned Owner membership or a newly accepted team membership. Until completion, organization workspace routes and shared authenticated dashboard routes redirect to onboarding. Welcome must be completed; visible profile/team steps may be completed or skipped. Finish validates all step state, marks completion, and always redirects to the organization dashboard. Refreshes and new sessions resume from the database-backed current step.

Completed actions should be reconciled against server data where possible: existing organization profile values, invitation/member records, and jobs are authoritative. Progress flags cannot grant permission or claim that a job/invitation succeeded when the existing service failed.

## 5. Organization profile, branding, and career preview

Extend organization settings with industry, organization size, and two validated brand colors. Values remain organization-specific. Colors are exact hex values or a fixed palette; raw CSS is never accepted. Reuse `OrganizationSettingsService` for versioned updates, owner checks, audit records, logo storage, and cleanup.

Because no internal organization career page currently exists, add a small public organization-careers page so the live preview represents a real candidate-facing destination. This requires a stable unique organization slug, a public page that exposes only published/unexpired organization jobs, and a shared Blade presentation partial used by both preview and public page. The existing external `careers_url` setting remains an external link and must not be silently repurposed.

Public presentation must reuse the established job listing contract and keep organization-private contacts, membership, application records, and drafts out of the response. Logos continue through the existing public-media URL path. The preview uses a selected local image only after its usage rights are clear; otherwise it uses the existing Marthire icons and CSS illustration style.

## 6. Hiring-team invitations

Use the existing invitation notification, role enum, and `OrganizationTeamService`. For the onboarding step, add a batch-input wrapper that normalizes and validates unique email addresses and role choices, then calls the existing single-invitation service per recipient. Report per-address success/errors and do not insert `TeamInvitation` rows directly. This preserves the existing transaction, membership checks, token hashing, duplicate handling, activity log, and notification behavior.

The invitation step is optional. If the active role cannot manage the team, it is omitted or shown as unavailable with an explanation; it never grants team permissions.

## 7. First recruitment action

The activation screen offers:

- **Create your first job** → existing `organisations.jobs.create` route and current `StoreJobRequest`/`JobPolicy` flow.
- **Explore the dashboard** → existing role- and active-organization-aware dashboard.

Do not add AI controls: no AI job-generation or improvement service is currently implemented. Do not promise approval-before-publish: the current publish action changes a job to Open immediately; draft is available. Any future approval requirement should be a separate product decision and authorization workflow.

## 8. Expected file changes

### New files

- `app/Models/OrganizationOnboardingProgress.php`
- `app/Services/OrganizationOnboardingService.php`
- `app/Http/Controllers/OrganizationOnboardingController.php`
- Form Requests for onboarding step updates and batch invitations.
- `app/Http/Controllers/PublicOrganizationCareerController.php` and a small public-careers presentation service if needed.
- Migrations for onboarding progress, organization profile/brand fields, and organization slug.
- `resources/views/organisations/onboarding/show.blade.php` plus reusable career-preview/public-careers partials.
- `resources/views/organisations/careers/show.blade.php`.
- Focused onboarding styles and a separate `resources/js/organization-onboarding.js` only for live preview and multi-email input behavior.
- Pest feature tests for account entry, progress, permissions, public career visibility, profile isolation, invitations, and job/dashboard routing.

### Existing files likely to change

- `routes/auth.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`, `resources/views/auth/register.blade.php`, and narrowly scoped Google signup handling to support the Employer registration path without allowing Admin registration or changing existing linked accounts.
- `routes/web.php` for authenticated onboarding and public careers routes.
- `app/Services/OrganizationProvisioner.php` and `app/Services/OrganizationTeamService.php` to create progress for newly created memberships inside their existing transactions.
- `app/Http/Controllers/OrganizationDashboardController.php` for the eligible-membership onboarding redirect.
- `app/Models/Organization.php`, `app/Models/OrganizationSetting.php`, `app/Http/Requests/UpdateOrganizationSettingsRequest.php`, `app/Services/OrganizationSettingsService.php`, and `resources/views/settings/settings.blade.php` for new organization-only fields.
- `app/Http/Requests/StoreTeamInvitationRequest.php` or an additional batch request, plus onboarding controller/service for invitation results.
- `resources/css/app.css` and `resources/js/app.js` to integrate the responsive wizard and separate preview behavior into the current build.

The existing account/member permission enums, auth verification behavior, `OrganizationContext`, organization scopes, policies, team invite service, and job creation form remain the authorities. They should not be replaced.

## 9. Security and data-integrity controls

- Require `auth`, `verified`, `active.account`, `role:organization`, and an active membership for all onboarding mutations.
- Use the active `OrganizationContext`; verify membership ownership in services and policies. Never accept a client-controlled `organization_id`, membership role, completed permission, or onboarding persona as access authority.
- Reuse Owner/HR/team/job gates for each action. Career-page queries are explicitly restricted to the chosen organization and public job scope.
- Keep all organization updates and progress transitions transactional. Use existing version checks for settings and idempotent membership/progress creation.
- Validate batch invite addresses, normalize case, reject duplicates in one submission, and use existing invitation service behavior for persisted duplicates.
- Validate uploaded logos with the existing image rules and store through the existing public media flow; remove orphaned uploads if the database update fails.
- Validate colors against exact hex syntax/fixed choices and HTML-escape every organization profile field.
- Preserve CSRF and OAuth state protections. Existing Google auth accounts are never silently converted from Applicant to Organization.

## 10. Testing and regression plan

After approval and implementation, use the project's existing Pest conventions for:

1. Applicant registration/login remains unchanged; employer signup creates only an Organization account and provisions one Owner workspace after verification; Admin self-registration is rejected.
2. Existing Google-linked account role is never changed; new Google signup respects only a validated session account-type intent.
3. Existing memberships are not forced through onboarding; new Owner and invited memberships receive resumable progress.
4. Progress persists across sessions, Back/Next, validation failures, optional skips, and duplicate submissions. Incomplete setup cannot be dismissed into the organization dashboard.
5. Persona does not alter `users.role`, `organization_members.role`, or authorization results.
6. Owner-only profile/branding changes and team/job actions enforce existing gates; HR/recruiter/hiring manager/interviewer cannot access unauthorized steps or submit unauthorized actions.
7. Cross-organization progress/profile/career data cannot be read or changed. Public career pages show only that organization's published, unexpired jobs.
8. Batch invitations preserve existing duplicate checks, pending-token replacement behavior, role protections, and notification outcomes.
9. Activation uses the existing job form and dashboard. Draft/open behavior remains unchanged; unsupported AI and approval claims are absent.
10. Existing organization switching, job, team, candidate pipeline, authentication, and public job regression suites continue to pass.

Do not run migrations against production or alter existing data during implementation. Migration backfills are to be exercised against the test database first.

## 11. Phased implementation

1. **Foundation:** onboarding progress schema/model/service, legacy backfill, idempotent initialization hooks, and authorization tests.
2. **Organization entry and profile:** employer account-type path (if approved), persona preference, profile fields, validated logo/color settings, and live preview.
3. **Team and activation:** batch invitation UI backed by current service; job/dashboard actions reuse current routes.
4. **Public careers page:** stable slug, public-only query, shared preview presentation.
5. **Quality pass:** responsive/accessibility review, full targeted feature/regression tests, and deployment notes.

Each phase is reviewed before moving to the next if implementation uncovers a compatibility or security issue.

## 12. Risks, dependencies, and decisions for approval

- **Employer registration is new product capability.** Current public registration is Applicant-only; exposing employer signup creates Organization accounts whose first provisioned membership is Owner. Approve this account-entry change explicitly. If employer accounts should remain admin/invitation-only, the onboarding starts after those existing flows instead.
- **Career-page URL and slug stability.** No public organization page exists. Adding one needs a stable slug and a public-data contract; do not overwrite the current external `careers_url` field.
- **Brand colors affect public rendering.** Scope colors only to the organization careers page and its preview in this phase, not the whole Marthire platform.
- **Batch invitation partial success.** Reuse the existing per-address invite service and show results per address; do not create invitations through a parallel path.
- **Job approval is absent.** The current publish action opens jobs immediately; onboarding should preserve this behavior unless a separate approval feature is approved.
- **Asset licensing.** Webflow's Made in Webflow gallery is community work and does not itself grant rights to every listed asset. Use existing Marthire components/cleared assets or verify the exact asset license before inclusion.
- **Working tree condition.** Before this task, the checkout already contained modifications and deletions in unrelated views and tests. They were left untouched; implementation must preserve them and review the exact diff before editing overlapping files.

## Review gate

The user approved implementation on 2026-11-09. The onboarding flow, employer signup, per-membership progress, profile/branding fields, invitations, public careers page, and tests have been implemented. Deployment and test details are in [the implementation notes](marthire-onboarding-implementation.md).

## Reference

- [Webflow Made in Webflow — Assets showcase](https://webflow.com/made-in-webflow/assets) (visual inspiration only; check individual rights before reuse).
