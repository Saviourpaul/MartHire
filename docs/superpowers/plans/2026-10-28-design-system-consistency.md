# Design-System Consistency Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give every form, button, and table in Marthire one shadcn-style visual and structural language, via CSS consolidation plus two shared Blade components, without changing any behavior.

**Architecture:** Markup + CSS only on the existing Laravel Blade + `nw-*` CSS + Alpine stack (no React). Consolidate the control sizing/spacing tokens in `resources/css/app.css`, add `<x-form.actions>` (action footer) and `<x-table.toolbar>` (list toolbar) components, and migrate the enumerated views onto those plus the already-shipped `.nw-form-bar` inline pattern.

**Tech Stack:** Laravel 12, Blade, custom `nw-*` CSS, Alpine.js, Vite (builds into `public/asset/marthire/`), Pest 3 feature tests (`php artisan test`).

**Spec:** `docs/superpowers/specs/2026-10-28-design-system-consistency-design.md`

## Global Constraints

- Markup + CSS only. NO changes to controllers, routes, validation, or form behavior.
- Preserve, in every migrated view: visible button/label TEXT, field `name`/`value`/`id` attributes, `action`/`method`, `@csrf`/`@method`, and all `aria-*` / `for` / `nw-sr-only` accessibility.
- Preserve Alpine wiring verbatim where present: `x-data`, `x-on:click="$dispatch('open-modal'|'close-modal', ...)"`, `x-model`, `x-show`, `onsubmit="return confirm(...)"`.
- Every `nw-table` row's primary (first) `<td>` keeps `class="nw-table__main"`; action cells keep `nw-table__go` (with `nw-table__go--persist` where the action must stay reachable on mobile).
- Keep all existing CSS class names and button variants (`--primary/--outline/--light/--danger/--small/--block`); this is value consolidation, not renaming.
- Control sizing target: unify `.nw-button` (50px), `.nw-input` (49px), `.nw-select` (44px) to a single ~40px default height + one small size; button icon gap `22px`/`15px` → `8px`; one border-radius + one focus-ring across the three.
- Every CSS change must be rebuilt (`npm run build`) to ship, and the serving manifest `public/asset/marthire/manifest.json` must update (vite.config `buildDirectory` is set to `asset/marthire`).
- **Verification honesty:** no browser/screenshot tool is available. Automated tests confirm no content/behavior regression and that views render without error; they CANNOT confirm the visual result looks right. Every task ends with an explicit **human-eyeball** checklist item that is NOT automatable this session.
- Known-baseline failure: `tests/Feature/Jobs/JobPagesTest` has ONE pre-existing unrelated red test (the "This is a preview." assertion) that predates this work. Treat only *new* failures as regressions.

## Review Focus

- **Mobile card-table breakage:** a migrated/restyled table losing `nw-table__main` on its primary `<td>` silently breaks the ≤767px card layout; `assertSee` text tests never catch it. → Task 7 asserts every migrated table's primary cell keeps `nw-table__main` (grep gate).
- **CSS never ships (stale bundle):** the session's core bug — editing `app.css` without a rebuild that lands in `public/asset/marthire/` means nothing reaches the browser. → every CSS-touching task asserts the rebuilt serving manifest changed AND the served CSS contains the new value.
- **Accessibility dropped during markup churn:** removing `aria-label`/`for`/`nw-sr-only`/`role` while swapping wrappers degrades screen-reader use; text tests don't check it. → Tasks 3/4/6/8 assert the migrated view still contains its key `aria`/`for` attributes.
- **Alpine wiring severed:** a modal or cascading select whose `x-on`/`x-data`/`x-model` is lost renders but stops working; no JS runs in tests. → Tasks 4 and 8 assert the migrated markup still contains the exact `$dispatch(...)` / `x-model` strings.
- **Silent button-text change:** consolidating two action buttons into the footer component drops or reworders a label a user relies on (and may break a content assertion elsewhere). → each migration task asserts the pre-migration visible action labels are all still present.

---

### Task 1: CSS sizing/spacing foundation

**Files:**
- Modify: `resources/css/app.css` (`.nw-button` 181–205, `.nw-button--small` ~247, `.nw-input` ~2541/2572, `.nw-select` 2111–2121, and their focus rules)
- Test: `tests/Feature/DesignSystem/ControlTokensTest.php` (new) + a shell/grep gate

**Interfaces:**
- Consumes: nothing.
- Produces: the unified control scale every later task relies on — a single default control height (~40px, exact value the implementer picks within 38–42px and uses consistently), `.nw-button` icon `gap: 8px`, one radius + focus-ring. Later tasks assume `.nw-input`/`.nw-select`/`.nw-button` already share height; they do not re-touch these base rules.

- [ ] **Step 1: Write the characterization test (render smoke across representative controls)**

Create `tests/Feature/DesignSystem/ControlTokensTest.php`. It renders a view containing a button, input, and select and asserts they still render with their classes (proves no Blade breakage); the numeric token check is the grep gate in Step 2.

```php
test('core controls still render with their nw classes', function () {
    $html = Blade::render('<button class="nw-button nw-button--primary">Go</button>'
        .'<input class="nw-input" /><select class="nw-select"><option>x</option></select>');
    expect($html)->toContain('nw-button--primary')->toContain('nw-input')->toContain('nw-select');
});
```

- [ ] **Step 2: Run the test + establish the current-values baseline**

Run: `php artisan test tests/Feature/DesignSystem/ControlTokensTest.php`
Expected: PASS (it only checks rendering).
Then capture current values for the diff: `grep -nE 'min-height: *(50|49|44)px|gap: *(22|15)px' resources/css/app.css` — Expected: shows the button 50 / input 49 / select 44 and gap 22/15 lines that this task changes.

- [ ] **Step 3: Consolidate the tokens in `resources/css/app.css`**

Set `.nw-button`, `.nw-input`, `.nw-select` to one shared default `min-height` in the 38–42px range (pick one value, use it for all three); change `.nw-button` `gap: 22px` → `gap: 8px` and `.nw-button--small` `gap: 15px` → `gap: 8px`; unify their `border-radius` to one value and give input/select/button the same focus-ring treatment. Do not rename classes or change variants. Keep `.nw-button--small` as the one smaller size (pick its height below the default).

- [ ] **Step 4: Rebuild and assert the new values ship**

Run: `npm run build` — Expected: output lists `public/asset/marthire/assets/app-*.css`.
Run: `CSS=$(grep -o 'assets/app-[^"]*\.css' public/asset/marthire/manifest.json | head -1); grep -c 'gap:8px' "public/asset/marthire/$CSS"` — Expected: ≥1 (the tightened gap is in the SERVED bundle, confirming the manifest now points at the new CSS).

- [ ] **Step 5: Run the full suite (no content regression)**

Run: `php artisan test`
Expected: all green except the one known-baseline `JobPagesTest` "This is a preview." failure. Any other new failure is a regression — fix before proceeding.

- [ ] **Step 6: Human-eyeball (not automatable this session)**

Open `/admin/users`, `/organisations/team`, and `/login`; confirm buttons/inputs/selects now share one height and the button icon gaps look tight. Record the check; this is the only verification of the *visual* result.

- [ ] **Step 7: Commit**

```bash
git add resources/css/app.css tests/Feature/DesignSystem/ControlTokensTest.php
git commit -m "refactor(ui): unify control height + button icon gap to shadcn scale"
```

---

### Task 2: `<x-form.actions>` component

**Files:**
- Create: `resources/views/components/form/actions.blade.php`
- Test: `tests/Feature/DesignSystem/FormActionsComponentTest.php` (new)

**Interfaces:**
- Consumes: Task 1's control scale (buttons already sized).
- Produces: `<x-form.actions>` — a footer wrapper. The **default slot** holds the primary action (a `<button>`/`x-primary-button`); an optional named **`cancel`** slot holds a secondary/ghost action rendered before the primary. Renders a flex row, right-aligned on desktop, auto-width buttons, stacking full-width on mobile, under a single wrapper class (implementer picks the class name, e.g. `nw-form-actions`, and defines/reuses its CSS). Tasks 3, 4, 9 consume this exact slot API.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DesignSystem/FormActionsComponentTest.php`:

```php
test('form.actions renders the primary slot and an optional cancel slot', function () {
    $html = Blade::render('<x-form.actions><x-slot:cancel><button>Cancel</button></x-slot:cancel><button>Save</button></x-form.actions>');
    expect($html)->toContain('Save')->toContain('Cancel');
    // cancel renders before the primary action
    expect(strpos($html, 'Cancel'))->toBeLessThan(strpos($html, 'Save'));
});

test('form.actions renders without a cancel slot', function () {
    $html = Blade::render('<x-form.actions><button>Send</button></x-form.actions>');
    expect($html)->toContain('Send');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test tests/Feature/DesignSystem/FormActionsComponentTest.php`
Expected: FAIL — component view `components.form.actions` not found.

- [ ] **Step 3: Create the component**

Create `resources/views/components/form/actions.blade.php` with the wrapper described in Interfaces: optional `$cancel` slot first, then `{{ $slot }}` (primary). Add/define its wrapper CSS in `resources/css/app.css` (right-aligned flex, auto-width buttons, full-width stack ≤767px) — reuse the existing `.nw-form-actions` rules if suitable, else add a focused rule.

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test tests/Feature/DesignSystem/FormActionsComponentTest.php`
Expected: PASS.

- [ ] **Step 5: Rebuild (if CSS changed) and commit**

Run: `npm run build` (if you added/edited CSS).

```bash
git add resources/views/components/form/actions.blade.php tests/Feature/DesignSystem/FormActionsComponentTest.php resources/css/app.css
git commit -m "feat(ui): add x-form.actions footer component"
```

---

### Task 3: Migrate stacked-form footers to `<x-form.actions>`

**Files:**
- Modify: `resources/views/auth/{confirm-password,forgot-password,login,register,reset-password,verify-email}.blade.php` (6, `nw-form-footer`)
- Modify: `resources/views/profile/partials/{delete-user-form,update-password-form,update-profile-information-form}.blade.php` (3, `nw-form-row--actions`)
- Modify: `resources/views/organisations/candidates/show.blade.php` (`nw-button-row` form footer)
- Test: `tests/Feature/DesignSystem/AuthFormsRenderTest.php` (new characterization); existing `tests/Feature/ProfileTest.php`; existing `tests/Feature/Organizations/CandidateManagementTest.php`

**Interfaces:**
- Consumes: `<x-form.actions>` (Task 2).
- Produces: nothing downstream.

- [ ] **Step 1: Add characterization smoke tests for the auth views (no existing coverage of their render)**

Create `tests/Feature/DesignSystem/AuthFormsRenderTest.php` asserting each public auth GET route returns 200 and shows its submit label, e.g.:

```php
test('login page renders with its submit action', function () {
    $this->get(route('login'))->assertOk()->assertSee('Log in');
});
test('register page renders with its submit action', function () {
    $this->get(route('register'))->assertOk()->assertSee('Create account');
});
test('forgot-password page renders', function () {
    $this->get(route('password.request'))->assertOk();
});
```

(Use the real visible submit text from each view; `confirm-password`, `reset-password`, `verify-email` require an auth/flow state — assert the two public ones plus any already reachable, and rely on ProfileTest/manual for the gated ones.)

- [ ] **Step 2: Run the new + relevant existing tests (green baseline before refactor)**

Run: `php artisan test tests/Feature/DesignSystem/AuthFormsRenderTest.php tests/Feature/ProfileTest.php tests/Feature/Organizations/CandidateManagementTest.php`
Expected: PASS (characterization baseline; these must still pass after the migration).

- [ ] **Step 3: Migrate the footers**

In each listed view, replace the `nw-form-footer` / `nw-form-row--actions` / `nw-button-row` *action row* with `<x-form.actions>` (primary action in the default slot, any secondary/cancel in the `cancel` slot). Preserve exact button text, `type`, `name`, any `x-on`/`onsubmit`, and the surrounding `nw-form-hint`/status markup. Do not touch the form's fields or `@csrf`/`@method`.

- [ ] **Step 4: Run the tests — must stay green**

Run: `php artisan test tests/Feature/DesignSystem/AuthFormsRenderTest.php tests/Feature/ProfileTest.php tests/Feature/Organizations/CandidateManagementTest.php`
Expected: PASS (same as Step 2).

- [ ] **Step 5: Assert accessibility + action text preserved (Review Focus)**

Run a grep over the migrated views confirming each still contains its `for=`/`aria-`/`nw-sr-only` attributes and its original action label text. Expected: all present.

- [ ] **Step 6: Human-eyeball**

Open `/login`, `/register`, `/profile`, and a candidate detail page; confirm the action buttons are right-aligned, consistent, and stack on mobile. Record the check.

- [ ] **Step 7: Commit**

```bash
git add resources/views/auth resources/views/profile/partials resources/views/organisations/candidates/show.blade.php tests/Feature/DesignSystem/AuthFormsRenderTest.php
git commit -m "refactor(ui): migrate stacked-form footers to x-form.actions"
```

---

### Task 4: Migrate modal footers to `<x-form.actions>`

**Files:**
- Modify: `resources/views/admin/users/index.blade.php` (modal `nw-modal-actions`, ~156–195)
- Modify: `resources/views/organisations/team/index.blade.php` (modal `nw-modal-actions`, ~173–189)
- Test: existing `tests/Feature/Admin/UserManagementTest.php`, `tests/Feature/Organizations/TeamManagementTest.php`

**Interfaces:**
- Consumes: `<x-form.actions>` (Task 2).
- Produces: nothing downstream.

- [ ] **Step 1: Run the owning tests (green baseline)**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php tests/Feature/Organizations/TeamManagementTest.php`
Expected: PASS.

- [ ] **Step 2: Migrate the modal action rows**

Replace the `nw-modal-actions` button rows with `<x-form.actions>`, keeping the Cancel button's `x-on:click="$dispatch('close-modal', ...)"` verbatim in the `cancel` slot and the primary (Save changes / Send password reset / Suspend / Delete / Remove) actions intact. The secondary-actions block (`nw-modal-actions--secondary`, each its own `<form>`) may stay as separate forms but each form's button row uses `<x-form.actions>`; preserve every `action`, `@method`, `onsubmit="return confirm(...)"`, and the `@unless/@if` visibility guards exactly.

- [ ] **Step 3: Run the owning tests — must stay green**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php tests/Feature/Organizations/TeamManagementTest.php`
Expected: PASS (same count as Step 1).

- [ ] **Step 4: Assert Alpine + confirm wiring preserved (Review Focus)**

Grep the two views for `$dispatch('close-modal'` and `onsubmit="return confirm` and each action `route(...)`. Expected: all still present, unchanged.

- [ ] **Step 5: Human-eyeball**

Open a Manage-user modal (`/admin/users`) and a Manage-member modal (`/organisations/team`); confirm the buttons open/close/submit and look consistent. Record the check.

- [ ] **Step 6: Commit**

```bash
git add resources/views/admin/users/index.blade.php resources/views/organisations/team/index.blade.php
git commit -m "refactor(ui): migrate modal footers to x-form.actions"
```

---

### Task 5: `<x-table.toolbar>` component

**Files:**
- Create: `resources/views/components/table/toolbar.blade.php`
- Test: `tests/Feature/DesignSystem/TableToolbarComponentTest.php` (new)

**Interfaces:**
- Consumes: Task 1's control scale.
- Produces: `<x-table.toolbar>` — named slots **`tabs`** (segmented nav), default slot (search field), **`filters`** (selects), **`actions`** (right-aligned, e.g. Export). Renders the existing `nw-toolbar`/`nw-search-field`/`nw-toolbar__actions` structure. The component is a layout wrapper only — callers still pass their own `<form>` wrapping or the component renders inside the caller's form (implementer picks; keep the GET-search behavior identical to current markup). Task 6 consumes this slot API.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/DesignSystem/TableToolbarComponentTest.php`:

```php
test('table.toolbar renders all four slot regions', function () {
    $html = Blade::render(
        '<x-table.toolbar>'
        .'<x-slot:tabs>TABS</x-slot:tabs>SEARCH'
        .'<x-slot:filters>FILTERS</x-slot:filters>'
        .'<x-slot:actions>ACTIONS</x-slot:actions>'
        .'</x-table.toolbar>'
    );
    expect($html)->toContain('TABS')->toContain('SEARCH')->toContain('FILTERS')->toContain('ACTIONS');
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test tests/Feature/DesignSystem/TableToolbarComponentTest.php`
Expected: FAIL — `components.table.toolbar` not found.

- [ ] **Step 3: Create the component**

Create `resources/views/components/table/toolbar.blade.php` rendering the four regions with the existing `nw-toolbar`/`nw-search-field`/`nw-toolbar__actions` classes (reuse, don't restyle beyond Task 1's scale). `tabs` renders above or inline per current `nw-segmented` placement; `actions` is right-aligned.

- [ ] **Step 4: Run to verify it passes**

Run: `php artisan test tests/Feature/DesignSystem/TableToolbarComponentTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/table/toolbar.blade.php tests/Feature/DesignSystem/TableToolbarComponentTest.php
git commit -m "feat(ui): add x-table.toolbar component"
```

---

### Task 6: Migrate app toolbars to `<x-table.toolbar>`

**Files:**
- Modify: `resources/views/admin/users/index.blade.php` (toolbar 14–51 + segmented 7–12)
- Modify: `resources/views/organisations/candidates/index.blade.php` (toolbar 20–46 + segmented 11–18)
- Test: existing `tests/Feature/Admin/UserManagementTest.php`, `tests/Feature/Organizations/CandidateManagementTest.php`

**Interfaces:**
- Consumes: `<x-table.toolbar>` (Task 5).
- Produces: nothing downstream.

- [ ] **Step 1: Run the owning tests (green baseline — they assert search/filter/Export text + behavior)**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php tests/Feature/Organizations/CandidateManagementTest.php`
Expected: PASS.

- [ ] **Step 2: Migrate both toolbars**

Move the segmented nav into the `tabs` slot, the search field into the default slot, the role/status/job selects into `filters`, and the Filter button + Export link into `actions`. Preserve the enclosing `<form method="GET">`, every `name`, `value`, `@selected`, `aria-label`, `nw-sr-only` label, hidden `stage` input, and the `route($exportRoute, request()->query())` Export URL exactly.

- [ ] **Step 3: Run the owning tests — must stay green**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php tests/Feature/Organizations/CandidateManagementTest.php`
Expected: PASS (same counts as Step 1, including the CSV-export and filter tests).

- [ ] **Step 4: Assert accessibility preserved (Review Focus)**

Grep both views for their search-label `for=`/`aria-label`, each filter select's `aria-label`, and the `nw-sr-only` search labels. Expected: all still present after the toolbar swap.

- [ ] **Step 5: Human-eyeball**

Open `/admin/users` and `/organisations/candidates`; confirm tabs + search + filters + Export render as one consistent toolbar and search/filter/export still work. Record the check.

- [ ] **Step 6: Commit**

```bash
git add resources/views/admin/users/index.blade.php resources/views/organisations/candidates/index.blade.php
git commit -m "refactor(ui): migrate app toolbars to x-table.toolbar"
```

---

### Task 7: Table density + mobile-card consistency across the 7 table views

**Files:**
- Modify: `resources/views/admin/users/index.blade.php`, `resources/views/organisations/candidates/index.blade.php`, `resources/views/organisations/team/index.blade.php`, `resources/views/admin/dashboard.blade.php`, `resources/views/organisations/dashboard.blade.php`, `resources/views/client/dashboard.blade.php`, `resources/views/dashboard.blade.php`
- Modify: `resources/css/app.css` (table cell density only, if needed, to match the ~40px scale)
- Test: existing admin/org/client feature tests + a grep gate

**Interfaces:**
- Consumes: Task 1 scale.
- Produces: nothing downstream.

- [ ] **Step 1: Grep-assert the current mobile-card invariant (baseline)**

Run: for each of the 7 views, confirm every `<table class="nw-table">` has exactly one `nw-table__main` cell per row template. Record current state. Expected: all 7 already carry `nw-table__main` (added earlier this session) — this task must not lose it.

- [ ] **Step 2: Run the owning feature tests (green baseline)**

Run: `php artisan test tests/Feature/Admin/UserManagementTest.php tests/Feature/Organizations/CandidateManagementTest.php tests/Feature/Organizations/TeamManagementTest.php`
Expected: PASS. (Dashboards have no dedicated table test; rely on render + grep.)

- [ ] **Step 3: Apply consistent table density**

Harmonize header/cell padding and row borders to the ~40px scale in `resources/css/app.css` (one `.nw-table` density, not per-view overrides). Touch the 7 view files only if a cell needs `nw-table__main`/`nw-table__go` correction; otherwise this is CSS-only.

- [ ] **Step 4: Rebuild + re-assert the invariant and tests**

Run: `npm run build`; then re-grep that all 7 views still have `nw-table__main` on the primary `<td>` (Expected: unchanged) and `php artisan test tests/Feature/Admin/UserManagementTest.php tests/Feature/Organizations/CandidateManagementTest.php tests/Feature/Organizations/TeamManagementTest.php` (Expected: PASS).

- [ ] **Step 5: Human-eyeball (desktop + mobile width)**

Open each of the 4 dashboards and the 3 list pages at desktop and at ≤767px; confirm consistent density and that the mobile card layout still works. Record the check.

- [ ] **Step 6: Commit**

```bash
git add resources/css/app.css resources/views/admin resources/views/organisations resources/views/client resources/views/dashboard.blade.php
git commit -m "refactor(ui): unify table density, preserve mobile card layout"
```

---

### Task 8: Public marketing pages (Home, Jobs, Job Detail)

**Files:**
- Modify: `resources/views/Home.blade.php` (hero `nw-search`, `nw-button-row`)
- Modify: `resources/views/Jobs.blade.php` (hero `nw-search`, filter sidebar `nw-filters`, `nw-sort`, results bar `nw-results`, `nw-button-row`)
- Modify: `resources/views/JobDetail.blade.php` (action buttons / apply)
- Modify: `resources/css/app.css` (align `nw-search`/`nw-filters`/`nw-sort`/`nw-results` to the shared scale)
- Test: existing `tests/Feature/Jobs/JobPagesTest.php` + new render smokes

**Interfaces:**
- Consumes: Task 1 scale, `<x-table.toolbar>` (Task 5, for the Jobs filter/sort/results bar variant), `<x-form.actions>` where a public form has a footer.
- Produces: nothing downstream.

- [ ] **Step 1: Add render-smoke characterization tests for the three public routes**

Add to `tests/Feature/Jobs/JobPagesTest.php` (or a new `tests/Feature/DesignSystem/PublicPagesRenderTest.php`): GET `/` (Home), GET `/workable-jobs` (Jobs), GET `/workable-jobs/{shortcode}` (Job Detail) each return 200 and show a known anchor string (e.g. a section heading / the search placeholder / a job title). Use existing JobPagesTest fixtures for the Job Detail shortcode.

- [ ] **Step 2: Run the new + existing Jobs tests (baseline)**

Run: `php artisan test tests/Feature/Jobs/JobPagesTest.php`
Expected: PASS except the one known-baseline "This is a preview." failure.

- [ ] **Step 3: Migrate the public controls**

Align the hero search inputs/buttons to the shared scale; migrate the Jobs filter sidebar + sort + results bar to a public variant of `<x-table.toolbar>` (or align their CSS to the toolbar's tokens if the markup is too divergent to componentize — implementer's call, but prefer the component); convert any public form footers (`nw-button-row`) to `<x-form.actions>`. Preserve all filter/sort `name`/`value`/`@selected`, the search action, and visible headings/labels.

- [ ] **Step 4: Rebuild + run the Jobs tests — must stay green**

Run: `npm run build`; then `php artisan test tests/Feature/Jobs/JobPagesTest.php`
Expected: PASS except the same one known-baseline failure (no NEW failures).

- [ ] **Step 5: Human-eyeball**

Open `/`, `/workable-jobs` (desktop + mobile), and a job detail page; confirm the hero search, filters/sort/results bar, and buttons are consistent with the app pages and still filter/sort/search. Record the check.

- [ ] **Step 6: Commit**

```bash
git add resources/views/Home.blade.php resources/views/Jobs.blade.php resources/views/JobDetail.blade.php resources/css/app.css tests/Feature/Jobs/JobPagesTest.php
git commit -m "refactor(ui): harmonize public pages onto the design system"
```

---

### Task 9: Apply wizard full conversion

**Files:**
- Modify: `resources/views/Apply.blade.php` (`nw-form-section` layout kept; `nw-form-actions` + `nw-button-row` footers → `<x-form.actions>`; controls to scale)
- Test: existing `tests/Feature/Jobs/JobPagesTest.php` (Apply assertions)

**Interfaces:**
- Consumes: `<x-form.actions>` (Task 2), Task 1 scale.
- Produces: nothing downstream.

- [ ] **Step 1: Run the Apply tests (baseline)**

Run: `php artisan test tests/Feature/Jobs/JobPagesTest.php`
Expected: PASS except the one known-baseline "This is a preview." failure. Note which Apply-specific assertions exist (e.g. `assertViewIs('Apply')`, field labels) — they must stay green.

- [ ] **Step 2: Convert Apply's footers + controls**

Keep the `nw-form-section` multi-section layout. Replace the `nw-form-actions` / `nw-button-row` action rows (Send application, Cancel, and the done-state button row) with `<x-form.actions>`, preserving the `x-bind:disabled="submitting"`, `x-text` submit label, the confirm checkbox field, and all `x-ref`/`x-show`/`x-on:submit` Alpine wiring verbatim. Align its inputs/selects to the shared scale (should already follow from Task 1; fix any Apply-local overrides).

- [ ] **Step 3: Run the Apply tests — must stay green**

Run: `php artisan test tests/Feature/Jobs/JobPagesTest.php`
Expected: PASS except the same one known-baseline failure.

- [ ] **Step 4: Assert Alpine wiring preserved (Review Focus)**

Grep `Apply.blade.php` for `x-ref="form"`, `x-on:submit="submit`, `x-bind:disabled="submitting"`, `$refs.success`. Expected: all still present.

- [ ] **Step 5: Human-eyeball**

Open an apply page (native-apply posting), step through the form and the success state; confirm the action buttons render/submit and the sections look consistent. Record the check.

- [ ] **Step 6: Commit**

```bash
git add resources/views/Apply.blade.php
git commit -m "refactor(ui): convert Apply wizard footers to x-form.actions"
```

---

### Task 10: Final whole-system verification

**Files:**
- Modify: none (verification only; `resources/css/app.css` only if a stray inconsistency is found)
- Test: full suite + rebuild

**Interfaces:**
- Consumes: all prior tasks.
- Produces: the shippable result.

- [ ] **Step 1: Clean rebuild + manifest assertion**

Run: `npm run build`; then assert `public/asset/marthire/manifest.json` points at a fresh `app-*.css` (mtime now) and that CSS contains `gap:8px`. Expected: both true.

- [ ] **Step 2: Full suite**

Run: `php artisan test`
Expected: all green except the single known-baseline `JobPagesTest` "This is a preview." failure. Any other failure is a regression to fix.

- [ ] **Step 3: Grep the retired patterns are gone from migrated views**

Run a grep confirming no migrated view still uses `nw-form-footer`, `nw-form-row--actions`, `nw-modal-actions`, `nw-form-actions`, or a form-footer `nw-button-row` (the `<x-form.actions>` component is used instead), and that `nw-form-row--fields` is no longer used as a *form wrapper*. Expected: only the component and `.nw-form-bar` remain. (If a view legitimately still needs one, record why.)

- [ ] **Step 4: Full human-eyeball pass (the only visual sign-off)**

Walk every migrated area at desktop and ≤767px: the 3 list pages + their modals, the 4 dashboards, all auth + profile forms, Home/Jobs/JobDetail, and Apply. Confirm one consistent look and nothing broke. This is the real acceptance check — automated tests cannot do it.

- [ ] **Step 5: Commit any final fixes**

```bash
git add -A
git commit -m "refactor(ui): final design-system consistency pass"
```
