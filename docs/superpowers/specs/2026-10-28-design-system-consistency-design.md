# Project-wide form / button / table consistency — design

## Intent and why

Forms, buttons, and tables look and behave inconsistently across NearbyWorks.
The same kind of control is built three different ways in three places: form
field-grouping uses `nw-form-stack` in some views, a 2-column `nw-form-row--fields`
grid in others (which strands controls and forces full-width buttons — the
`/organisations/team` invite form was a live example), and the Apply page's own
`nw-form-section` system elsewhere. Action buttons land in a footer, inline, or
full-width depending on the view. Control heights disagree three ways (button
`min-height: 50px`, input `min-height: 49px`, select `min-height: 44px`), and the
button icon gap is a wide `22px` (`15px` on the small variant). The user wants one
consistent visual and structural
language for **forms, buttons, and tables across the whole project**, modeled on the
shadcn/ui conventions seen in the Apex dashboard reference.

"shadcn-style" here means its **visual and structural conventions reproduced in the
existing Blade + `nw-*` CSS stack** — not adopting the React library. The app stays
Laravel Blade + `nw-*` CSS + Alpine.

## Goals

- One canonical way to lay out: (a) a stacked form, (b) a compact inline form, (c) a
  form's action buttons, (d) a list/table toolbar, (e) a data table.
- Harmonized control sizing (input / select / button share one height + radius scale)
  and a consistent button icon gap.
- Migrate every form, table, and toolbar in the app onto these patterns (the exact
  view list is enumerated in the Appendix), fixing the layout bugs (grid misuse,
  stranded controls, full-width buttons, uneven spacing) as part of the migration.
- Consistency is enforceable where divergence actually happens, via a small set of
  shared Blade components, not just convention.

## Non-goals / anti-goals

- No React, no shadcn/ui library, no frontend rebuild.
- No change to form behavior, validation, routes, or controllers — this is markup +
  CSS only. Tests that assert rendered content must still pass unchanged.
- Not a brand/visual-identity redesign — colors, typography, logo, and the overall
  page shell stay. Only the structure of forms/buttons/tables changes.
- Not rebuilding the input layer: the existing `x-text-input` / `x-input-label` /
  `x-input-error` components stay and are reused.
- The Apply page keeps its multi-section *layout*, but is otherwise fully migrated:
  its controls align to the shared scale AND its footers convert to `<x-form.actions>`
  (resolved decision — see Decisions).
- Public marketing pages (Home, Jobs, Job Detail) ARE in scope (resolved decision):
  their hero search, filter sidebar, sort control, and results bar are harmonized into
  the system. This is an explicit scope expansion over an "app-only" reading.

## Constraints

- Blade + `nw-*` CSS (`resources/css/app.css`) + Alpine only.
- Assets build into `public/asset/nearbyworks/` (vite.config `buildDirectory`, fixed
  this session); every CSS change must be rebuilt to ship, and verification is by
  code audit — no browser/screenshot tool is available this session.
- The mobile card-table pattern must be preserved: each `nw-table` row's primary `<td>`
  carries `class="nw-table__main"`, and action cells use `nw-table__go`
  (with `nw-table__go--persist` when the action must stay reachable on mobile).
- Keep existing accessibility (labels, `aria-*`, `nw-sr-only`).
- Class names stay stable where reused, so unrelated views keep working.

## Approaches

Three ways to deliver project-wide consistency were considered:

- **A — CSS-only, minimal markup churn.** Fix/consolidate `nw-*` rules so existing
  classes render the shadcn conventions; touch Blade only where structure is broken.
  Fastest, lowest risk. Rejected as the primary approach because nothing *enforces*
  consistency — views drift again, and the real inconsistency is structural (which
  wrapper/footer/toolbar a view uses), which CSS alone can't unify.

- **B — Full component layer.** Build canonical Blade components for fields, actions,
  tables, and toolbars, and migrate every view onto them. Most durable and truest to
  how shadcn composes. Rejected as too much churn: the input layer already has working
  components, so rebuilding fields is wasted motion and 25+ views change more than
  needed.

- **C — Hybrid (chosen).** Consolidate the CSS sizing/spacing, and add components only
  for the two patterns that actually diverge — a form **action footer** and a **table
  toolbar** — plus codify the inline **form bar** (already added as `.nw-form-bar`).
  Keep the existing input components. Migrate views to these. Chosen because it fixes
  the real (structural) inconsistency with far less churn than B, and is enforceable
  where it matters, while A alone wouldn't hold.

## The parts of the how, as decided

### 1. CSS sizing/spacing consolidation
- Unify the three disagreeing control heights (`.nw-button` 50px, `.nw-input` 49px,
  `.nw-select` 44px) to a single shadcn-tight **~40px default** height, plus one small
  size (resolved decision: tighten to shadcn).
- Tighten the `.nw-button` icon gap from `22px` (and `.nw-button--small`'s `15px`) to
  the shadcn ~`8px`.
- One border-radius and one focus-ring treatment across input/select/button.
- Keep all existing class names and variants (`--primary/--outline/--light/--danger/
  --small/--block`); this is value consolidation, not a rename.

### 2. New shared Blade components (the enforcement layer)
- **`<x-form.actions>`** (`resources/views/components/form/actions.blade.php`): the
  canonical action footer — right-aligned on desktop, auto-width buttons, primary
  action plus optional ghost/cancel; full-width stacked on mobile. Proposed slot API
  (builder's call): default slot for the primary action, named `cancel`/`secondary`
  slot. Replaces **all** the ad-hoc footer patterns: `nw-form-footer`,
  `nw-form-row--actions`, `nw-modal-actions`, `nw-form-actions`, and the form-footer
  uses of `nw-button-row` (resolved: the component subsumes all five).
- **`<x-table.toolbar>`** (`resources/views/components/table/toolbar.blade.php`): the
  canonical list toolbar. Proposed slot API (builder's call, recorded here): named
  `tabs` (segmented), default slot (search field), named `filters` (selects), named
  `actions` (right-aligned Export etc.). Formalizes the pattern `/admin/users` and
  `/organisations/candidates` already share, and the public Jobs filter/sort/results
  bar is migrated to a variant of it.
- **`.nw-form-bar`** (CSS, already added): the inline 1–3-field quick-action form
  (grow field + fixed field + auto-width button; stacks on mobile). Already live on
  the invite form; documented here as the canonical compact-form pattern.

### 3. Canonical form-layout rules
- **Stacked form** (profile, password, auth, job-posting-sized forms): label above
  input, fields stacked with one consistent gap (`nw-form-stack`), paired fields via a
  responsive 2-col grid that collapses on mobile, action buttons in `<x-form.actions>`.
- **Inline form** (1–3 fields, quick action): `.nw-form-bar`.
- Retire `nw-form-row--fields` as a *form wrapper* (its misuse caused the invite bug);
  keep a responsive paired-field grid only for genuinely paired inputs inside a stacked
  form.

### 4. Migration
- Migrate all 18 form views, 7 table views, and 3 toolbars to the patterns above,
  fixing layout defects encountered. Each migration preserves the view's existing
  fields, names, routes, and `aria`/accessibility, and must keep its feature tests
  green.

## Decisions (resolved with the user)

1. **Control height / density → tighten to shadcn.** Unify to ~40px controls and an
   8px button icon gap. Changes the look of every control (crisper, denser).
2. **Public marketing pages (Home, Jobs, Job Detail) → in scope.** Their hero search,
   filter sidebar, sort, and results bar are harmonized, not left out.
3. **Apply wizard → convert fully.** Controls aligned to the scale AND footers
   converted to `<x-form.actions>`; only its multi-section layout is preserved.
4. **Auth forms → in scope** (standard stacked forms; no separate question needed).
5. **`<x-form.actions>` subsumes all five footer patterns** (`nw-form-footer`,
   `nw-form-row--actions`, `nw-modal-actions`, `nw-form-actions`, form-footer
   `nw-button-row`) — not just the three first listed.

### Builder's-call items settled in this doc
- Component slot APIs proposed inline above (form actions: default + `cancel`; table
  toolbar: `tabs` / default / `filters` / `actions`).
- The 4 dashboard views (admin/org/client/`dashboard.blade.php`) ARE included in the
  table-standardization set, not just the 3 list views.
- Exact spacing/height/radius token values within the ~40px scale are left to the
  builder.

## What's left to the builder

- Exact spacing/height/radius token values within the chosen ~40px scale.
- The per-view markup→component/slot mapping, following the canonical rules and the
  Appendix list.

## Appendix — enumerated migration scope

Grounded by grep against `resources/views/` (this session). Each view keeps its
fields, names, routes, `aria`, and visible button/label TEXT (so content assertions
stay green).

**CSS consolidation (global, no view list):** control heights 50/49/44 → ~40px;
button icon gap 22/15 → 8px; one radius + focus ring.

**Tables → `nw-table` consistency + ~40px density (7 views):**
`admin/users/index`, `organisations/candidates/index`, `organisations/team/index`
(list views); `admin/dashboard`, `organisations/dashboard`, `client/dashboard`,
`dashboard.blade.php` (dashboards).

**Table toolbar → `<x-table.toolbar>` (2 app views + segmented):**
`admin/users/index`, `organisations/candidates/index` (both `nw-toolbar`). Segmented
navs also appear in `dashboard.blade.php`. (There is no third `nw-toolbar`; the earlier
"3 toolbars" figure is corrected — the third list-filtering UI is the public Jobs bar,
handled under public pages.)

**Form action footer → `<x-form.actions>` (13 views):**
auth: `confirm-password`, `forgot-password`, `login`, `register`, `reset-password`,
`verify-email` (6, `nw-form-footer`); profile: `delete-user-form`,
`update-password-form`, `update-profile-information-form` (3, `nw-form-row--actions`);
modals: `admin/users/index`, `organisations/team/index` (2, `nw-modal-actions`);
`Apply` (`nw-form-actions` + `nw-button-row`); `organisations/candidates/show`
(`nw-button-row`).

**Inline form bar (`.nw-form-bar`, already shipped):** `organisations/team/index`
invite form; apply to other 1–3-field quick actions where they fit.

**Public marketing pages (in scope, 3 views):**
`Home` (hero search `nw-search`, `nw-button-row`), `Jobs` (hero search, filter sidebar
`nw-filters`, sort `nw-sort`, results bar `nw-results`, `nw-button-row`), `JobDetail`
(action buttons / apply). The Jobs filter/sort/results bar migrates to a public variant
of `<x-table.toolbar>`; hero searches align to the input/button scale.

**Explicitly out:** `layouts/app.blade.php` logout forms (not a form view; buttons only),
and `team-invitations/show` keeps its current stacked form (already built this session
to the stacked + action pattern — gets the ~40px scale only).
