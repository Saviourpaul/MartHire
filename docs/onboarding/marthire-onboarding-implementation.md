# Marthire Organization Onboarding — Implementation Notes

**Completed:** 2026-11-09

**Plan:** [Marthire onboarding implementation plan](marthire-onboarding-implementation-plan.md)

## Delivered flow

Organization accounts receive a database-backed setup wizard after email verification and workspace provisioning. Refreshes, sign-in sessions, and devices resume the saved step. The wizard contains:

1. **Welcome:** save a recruitment-focus preference. This is personalization data only and never changes the account type, organization membership role, or permissions.
2. **Organization profile:** set the organization name, industry, size, about text, and logo. A responsive candidate-facing preview uses the same profile/listing partial as the public careers page and reflects the organization's saved branding. Brand colors remain editable in Organization Settings.
3. **Hiring team:** optionally invite up to ten teammates in one submission. Each address is processed by the existing `OrganizationTeamService`; partial successes are preserved and failed addresses remain available to retry.
4. **Ready:** finish setup and continue to the organization dashboard.

Steps are selected from the active membership's existing `OrganizationPermissions`. Owners can edit the profile and manage the team. HR Managers can invite where their current permissions allow. Recruiters, Hiring Managers, and Interviewers see only the setup steps supported by their roles. Applicants and administrators keep their existing dashboards.

The progress indicator advances only after a successful server-validated step submission. Back and optional-step skips preserve progress in the database. There is no dashboard-dismiss action: an incomplete organization remains in the full-screen onboarding experience until setup is finished. Historical `dismissed_at` values do not bypass the gate.

### Completion criteria and redirect behavior

- The Welcome preference must be submitted successfully; it cannot be skipped.
- The organization profile and team-invitation steps appear only when the active membership has the corresponding existing permissions. Each visible step must be saved successfully or explicitly skipped.
- Finish is accepted only when the current step is `finish`, Welcome is completed, and every role-specific step is completed or skipped. The completion write is row-locked and sets `current_step=complete`, adds `finish` to completed steps, and records `completed_at`.
- Until completion, organization workspace routes and shared authenticated dashboard routes redirect to onboarding. Public pages, authentication and logout, and non-organization accounts keep their existing behavior.
- After completion, both the Finish action and revisiting the onboarding URL redirect to the active organization dashboard. There is no alternate first-job redirect; job creation remains available through the normal organization dashboard navigation and permission checks.
- Existing memberships remain marked complete by the migration. New or restored active memberships resume their own progress record.

### Full-screen layout and visual assets

Onboarding now uses a dedicated Blade component layout with the Marthire wordmark, sign-out control, responsive recruitment-workspace illustration, and a single workflow column. It does not render the regular application header, sidebar, navigation, dashboard content, or widgets. A full-screen background, responsive breakpoints, keyboard focus styles, reduced-motion support, and the existing shared toast component are included.

The requested Webflow page is a community showcase of sites, not a licensed download pack. The recruiting collection was reviewed for visual direction; no community artwork is copied or hot-linked. Instead, `public/asset/marthire/onboarding/hiring-workspace.svg` is a locally authored, lightweight vector in Marthire's navy/lavender/lime palette, depicting candidate cards and review progress. Its source and rationale are recorded in [asset notes](assets.md).

## Authentication and organization lifecycle

- Registration offers Applicant and Employer account types. A missing account type still defaults to Applicant for compatibility; Admin is not a self-registration option.
- Employer accounts stay pending until email verification. The organization workspace and Owner membership are provisioned only after the account is verified and active.
- Google OAuth carries a validated signup intent only for a newly created account. Existing accounts retain their current role, even when they sign in through an Employer registration link.
- `OrganizationProvisioner` enforces the verified/active guard itself, in addition to the middleware guard.

## Data and public careers page

Two migrations were added:

- `2026_11_09_120000_create_organization_onboarding_progress_table.php` creates one progress record per organization membership. Existing memberships are backfilled as complete so deployment does not force current teams through onboarding.
- `2026_11_09_120100_add_organization_career_branding.php` adds stable organization slugs and organization-specific industry, size, primary-color, and accent-color fields.

The public career page is available at `/organizations/{slug}/careers`. It uses the organization slug and settings, and calls `PublicJobService::forOrganization()` to show only that organization's Open jobs whose application deadline has not passed. Draft, Suspended, Closed, and expired jobs are excluded. Job detail and apply URLs continue to use the existing native job routes. Organization-private contacts, member data, applications, and applicant documents are not included.

The existing organization logo validation and public-media delivery are reused. Color values are accepted only as six-digit hex values and are escaped/validated before use in scoped CSS custom properties.

## Architecture and security

- Onboarding progress is keyed by `organization_member_id`, not by a browser-provided organization ID. The current organization comes from `OrganizationContext`, and the membership is rechecked with `OrganizationPermissions` on every request.
- The server determines each step from the existing role permission map. Hiding a button in the view is not treated as authorization.
- Profile writes use `UpdateOrganizationSettingsRequest` and `OrganizationSettingsService`, preserving Owner checks, version checks, audit logging, logo cleanup, and organization-scoped broadcasts.
- Invitations reuse the existing role enum, authorization, token hashing, duplicate handling, expiry, activity log, and notification service.
- Public careers queries are explicitly limited to the route's organization and the public job scope. The existing external `careers_url` remains separate.
- The frontend uses the repository's Blade/Tailwind/Alpine stack. Wizard behavior is in `resources/js/organization-onboarding.js`; it supports the live preview, team rows, keyboard-operable controls, validation feedback, and responsive layouts.

## Main files

- New: `OrganizationOnboardingProgress`, `OrganizationOnboardingService`, `OrganizationOnboardingController`, the batch invitation request, public career controller/views, onboarding views, `resources/js/organization-onboarding.js`, two migrations, and onboarding feature tests.
- Updated in the onboarding UI follow-up: registration now has one Google button whose signup intent follows the selected account type; organization dashboard and shared shell routes use an onboarding-completion gate; completion always returns to the organization dashboard.
- Updated: registration and Google OAuth intent handling, organization provisioning/context middleware, team invitation acceptance, dashboard routing, organization settings validation/service/view, `PublicJobService`, Vite entry registration, routes, and responsive CSS.
- Vite manifest paths in the app, guest, and public layouts now use the same lowercase `asset/marthire/build` path as `vite.config.js`. Windows path handling hid this mismatch locally; the fix prevents a case-sensitive production server from looking for a different manifest directory.
- Test fixtures now treat `organizationWithOwner()` as an existing workspace; onboarding-specific tests explicitly reset its progress to the new-owner state.

## Validation run

- Focused Pest suite: rerun after the onboarding UI follow-up; results are recorded below.
- `php artisan view:cache`: passed.
- `npm run build`: passed; Vite emitted the existing absolute font-path warning and left that URL for runtime resolution.
- Laravel Pint `--test` on implementation PHP files and directly affected tests: passed.
- `git diff --check` on the implementation files: passed.

## Deployment notes

Build the frontend assets and deploy the generated `public/asset/marthire/build` directory with the updated application files. Apply both migrations before exposing onboarding routes:

```bash
php artisan migrate --force
```

Do not run migrations from a public HTTP route. If the production host does not provide a shell, use a trusted deployment runner or obtain a supported one-time migration path from the host before enabling this release. The production database was not modified during this implementation.

## Agent and documentation tools

The project checkout exposes the `code-review` skill only; no Context7 MCP tool, Superpowers skill, or Agent Agency skill was available. I attempted the available subagent tasks, but their implementation runs stopped because of usage/network limits. The work was completed from the checked-in Laravel code and the discovery document; agents did not make partial code edits.

## Onboarding UI correction — 2026-11-09

- Replaced the duplicated registration OAuth links with one button. Its label and validated `account_type` intent follow the Applicant/Employer radio selection through the existing Google OAuth endpoint.
- Replaced the onboarding view's `x-app-layout` wrapper with a dedicated full-screen component. The normal dashboard sidebar, header, search, navigation, and widgets are absent while setup is incomplete. The new layout includes a Marthire recruitment illustration, branding, sign-out, responsive styles, keyboard focus states, and the existing toast mechanism.
- Added `EnsureOrganizationOnboardingIsComplete` to organization workspace routes and shared authenticated settings/profile/notification routes. A verified active organization member with incomplete setup is redirected to the current saved step. Non-organization users, public pages, logout, and completed memberships keep their existing flow.
- Removed the “Finish setup later” dashboard bypass and first-job alternate redirect. Completion validates the required and optional step state under a row lock. Welcome must be completed; permission-dependent profile/team steps may be explicitly skipped; Finish records completion and always redirects to the organization dashboard. Visiting onboarding after completion also redirects there.
- Removed the primary and accent color controls from the onboarding profile step. Existing organization colors remain untouched when onboarding saves other profile fields and remain editable from Organization Settings; the onboarding preview continues to use the saved colors.
- Historical `dismissed_at` data is preserved for compatibility, but it no longer grants dashboard access. Existing memberships are already migrated as complete.
- Added tests for the one-button registration markup, full registration/onboarding/dashboard progression, direct-route gating, saved progress, old dismissed records, optional skips, and the deterministic finish redirect.

Validation for this correction: the onboarding and dashboard-access regression run passed **17 tests and 124 assertions**. After removing the color controls, the focused onboarding suite passed **9 tests and 106 assertions**, including a check that saved brand colors survive an onboarding update without color fields. `php artisan view:cache` passed, `npm run build` passed, `vendor/bin/pint --test` passed for the changed PHP files, and `php artisan route:list --path=organisations/onboarding` showed the expected seven routes. Vite emitted only the existing warning for `/asset/fonts/fa-brands-400.woff2`, which remains configured for runtime resolution.
