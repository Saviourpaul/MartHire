# Hiring Team wire-up — design

## Intent and why

Organizations need a way to invite teammates (Owner, HR Manager, Recruiter, Hiring
Manager, Interviewer) and manage who's on their hiring team. The backend for this
already exists in full — `OrganizationTeamService`, `OrganizationTeamController`,
`TeamInvitationController`, their form requests, `OrganizationPolicy`/
`OrganizationMemberPolicy`, and a rate limiter and middleware carve-out purpose-built
for it — but none of it is reachable: no routes, no views, and (until just now) a
missing notification class. This closes that gap.

## Goals

- An organization Owner/HR Manager can invite a teammate by email + role, see
  pending invitations, and change a member's role/status or remove them.
- An invited person (existing user or brand new) can view the invitation and
  accept or decline it from a public page.
- Reuse the existing service/policy/notification layer exactly as written —
  this task is routing, views, and naming consistency, not new business logic.

## Non-goals / anti-goals

- Per-job team assignment (`JobPostMember`, the legacy `JobController`) is
  explicitly out of scope — separate, currently also-unrouted system.
- No changes to `OrganizationTeamService`'s business rules (last-owner
  protection, role-escalation guard, etc.) — it's already correct.
- No new rate limiter — `team-invitations` (`RateLimiter::for` in
  `AppServiceProvider`, 5/min by user-or-IP) already exists for this and is
  currently unused; this task is what finally applies it.

## Constraints

- Must follow the live `organisations.*` route/view naming convention, not
  the leftover `employer.*` naming found in `OrganizationTeamController`.
- Must reuse the existing Apex Dashboard UI system (`nw-*` CSS classes,
  `x-icon`, `x-modal`, `nw-table` with `nw-table__main` on the primary `<td>`
  for the mobile card layout) — no new design patterns.
- `team-invitations.*` route names are load-bearing: `InitializeOrganizationContext`
  already special-cases `$request->routeIs('team-invitations.*')` to skip
  auto-provisioning a brand-new organization for the invited user before they've
  accepted anything.

## Approach

Single approach (no real alternative): wire the existing controllers into
`routes/web.php`, fix the `employer.*` → `organisations.*` naming mismatch in
`OrganizationTeamController`, fix the `isEmployer()` → `isOrganization()` bug in
`TeamInvitationNotification`, build the two missing views, add a nav link, and
add feature test coverage (currently zero). Tests follow the existing
`tests/Feature/Organizations/CandidateManagementTest.php` convention: plain Pest
functions, a local helper to provision the organization via
`OrganizationProvisioner::ensureForEmployer()`, no model factories for
`OrganizationMember`/`TeamInvitation` (none exist in this codebase for
organization-domain models; `::create()` via helpers is the established pattern).

## What's left to the builder

- Exact Blade markup (within the established `nw-*` component vocabulary).
- Exact wording of validation/notification copy not already fixed by the
  spec (e.g. button labels).
- Test case ordering and exact helper function names, as long as coverage
  matches the Review Focus list in the plan.
