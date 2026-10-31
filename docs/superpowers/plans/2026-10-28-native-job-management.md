# Native Job Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give organizations full native job management (list/create/edit/show/close/delete) with a Quill rich-text description, reusing the existing `JobController`/`Job`/`JobPolicy`/`JobDescription` backend.

**Architecture:** Revive the orphaned `JobController` under a new `organisations.jobs.*` route group; extract validation into FormRequests; render `nw-*`-styled views using the shared `<x-form.actions>`/`<x-table.toolbar>` components; add a Quill editor (npm/Vite/Alpine) whose toolbar is scoped to formats that round-trip through the `JobDescription` sanitizer; add a `Draft` job status that is excluded from the public board, the candidate pipeline, and admin moderation.

**Tech Stack:** Laravel 12, Blade, custom `nw-*` CSS, Alpine.js, Quill, Vite (builds into `public/asset/marthire/build/`), Pest 3.

**Spec:** `docs/superpowers/specs/2026-10-28-native-job-management-design.md`

## Global Constraints

- Reuse existing `JobController` business logic (transaction, org lock, `JobPostMember`, logo, policy); only fix orphaned `route('jobs')`/`route('jobs.show')` refs + `employer.jobs.*` view names, the hardcoded `status`, and the index scope.
- No change to the Workable `JobPosting` system or `WorkableJobController`, and no change to the candidate pipeline / `ApplicationForm` beyond Draft being excluded (the `Open`-gated scopes already do this).
- Quill's enabled formats MUST be a subset of what `JobDescription` keeps, restricted to formats that round-trip without `class`/`<div>`: bold, italic, underline, strike, headings, ordered + bullet lists, links, blockquote, inline `code`. NO alignment, NO indent, NO code-block (those serialize as `ql-*` classes / `<div>` the sanitizer strips).
- The server ALWAYS re-sanitizes the submitted description via `App\Support\JobDescription` on save — client HTML is never trusted.
- Styling is the `nw-*` design system (not raw Tailwind) + `<x-form.actions>` and `<x-table.toolbar>`; every `nw-table` row's primary `<td>` keeps `class="nw-table__main"` and the action cell uses `nw-table__go nw-table__go--persist`.
- `{job}` route-model binds by slug (the `Job` route key).
- Decisions (resolved): jobs index shows all org jobs by role via `OrganizationPermissions::scopeJobs`; `store`/`update` redirect to the job's show page; `Draft` is org-private (excluded from `AdminReportService` and `Admin\JobManagementController`).
- Permissions (existing matrix): create/edit = Owner/HR/Recruiter; delete = Owner/HR only — the UI must gate Delete on `delete_jobs`.
- No DB migration: `jobs.status` is `varchar(20)` default `'open'`; `'draft'` needs no schema change or backfill.
- Every CSS/JS change is rebuilt (`npm run build`) and the served `public/asset/marthire/build/manifest.json` confirmed. No browser this session — visual/JS-interaction result needs human eyeball (state honestly).
- Known-baseline: `tests/Feature/Jobs/JobPagesTest` has ONE pre-existing unrelated failure ("This is a preview."). Only NEW failures are regressions.

## Review Focus

- **Draft leaks to the public board or candidate pipeline:** a Draft job must never appear on `/workable-jobs`-style listings or accept applications. → Task 2 asserts a Draft job is excluded from `Job::open()`/`acceptingApplications` and cannot be applied to.
- **Draft leaks into admin moderation/analytics:** adding `Draft` to the enum auto-includes it in `AdminReportService` series and the `Admin\JobManagementController` status dropdown/validation unless excluded. → Task 1 asserts admin surfaces exclude Draft.
- **XSS / sanitizer bypass via description:** submitted `<script>`/`onerror`/`<div>`/`class` must be stripped server-side regardless of what the client posts. → Task 2 asserts malicious description HTML is sanitized on save.
- **Enum ripple crash:** `JobStatus::label()`/`badgeClass()` are non-exhaustive `match` with no `default`; a Draft job's badge render would `UnhandledMatchError`. → Task 1 asserts the badge renders for Draft without error.
- **Mobile card-table unusable:** the new jobs index must keep `nw-table__main` on the primary `<td>` and `nw-table__go--persist` on the action cell, or it breaks at ≤767px (text tests don't catch this). → Task 5 grep-asserts both.

---

### Task 1: `JobStatus::Draft` foundation (enum, badge, admin exclusion)

**Files:**
- Modify: `app/Enums/JobStatus.php` (add case + `label()`/`badgeClass()` arms)
- Modify: `resources/css/app.css` (add `.nw-status--draft`)
- Modify: `app/Services/AdminReportService.php` (exclude Draft from status series ~241-242), `app/Http/Controllers/Admin/JobManagementController.php` (exclude Draft from the status dropdown ~165 and moderation target validation ~184)
- Test: `tests/Feature/DesignSystem/JobStatusDraftTest.php` (new)

**Interfaces:**
- Produces: `JobStatus::Draft` (value `'draft'`) with a `label()` ("Draft") and `badgeClass()` arm; a helper the admin surfaces use to list non-Draft statuses (e.g. `JobStatus::moderatableCases(): array` excluding Draft — implementer picks the exact name, used only by the two admin sites).

- [ ] **Step 1: Write the failing tests**

```php
test('a draft job status renders its badge without error', function () {
    $html = Blade::render('<x-status-badge :status="$s" />', ['s' => \App\Enums\JobStatus::Draft]);
    expect($html)->toContain('Draft')->toContain('nw-status--draft');
});

test('admin job moderation does not expose the draft status', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin)->get(route('admin.jobs.index'))->assertOk()->assertDontSee('>Draft<', false);
});
```

(Use the real `admin.jobs.*` index route name — verify it in `routes/web.php`; if the admin job list lives elsewhere, point the test at that route.)

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test tests/Feature/DesignSystem/JobStatusDraftTest.php`
Expected: FAIL — `UnhandledMatchError` on `JobStatus::Draft` in `label()`.

- [ ] **Step 3: Add the Draft case + match arms + badge CSS**

Add `case Draft = 'draft';` to `JobStatus`; add its arm to `label()` ("Draft") and `badgeClass()` (a muted/neutral class). Add `.nw-status--draft` to `app.css`. Grep-audit every `match` on `JobStatus` (`grep -rn 'match' app resources | grep -i jobstatus`, plus `JobStatus::` usages) and give any non-exhaustive one a Draft arm or `default`.

- [ ] **Step 4: Exclude Draft from admin surfaces**

In `AdminReportService` and `Admin\JobManagementController`, replace the `JobStatus::cases()`/`values()` uses that feed admin analytics + the status dropdown + moderation validation with the Draft-excluding helper, so an admin cannot see Draft in analytics or set a job to Draft.

- [ ] **Step 5: Run to verify pass + no new failures**

Run: `php artisan test tests/Feature/DesignSystem/JobStatusDraftTest.php tests/Feature/Admin` and `php artisan test` (whole suite)
Expected: the two new tests PASS; whole suite green except the known baseline.

- [ ] **Step 6: Rebuild (CSS changed) + commit**

Run: `npm run build`.

```bash
git add app/Enums/JobStatus.php resources/css/app.css app/Services/AdminReportService.php app/Http/Controllers/Admin/JobManagementController.php public/asset/marthire tests/Feature/DesignSystem/JobStatusDraftTest.php
git commit -m "feat(jobs): add org-private Draft job status with badge + admin exclusion"
```

---

### Task 2: FormRequests + controller write-actions + routes

**Files:**
- Create: `app/Http/Requests/StoreJobRequest.php`, `app/Http/Requests/UpdateJobRequest.php`
- Modify: `app/Http/Controllers/JobController.php` (`store`/`update` status + redirect; keep `close`/`destroy` logic, fix their redirects)
- Modify: `routes/web.php` (the `organisations.` group)
- Test: `tests/Feature/Organizations/JobManagementTest.php` (new)

**Interfaces:**
- Consumes: `JobStatus::Draft` (Task 1).
- Produces: routes `organisations.jobs.{index,create,store,show,edit,update,close,destroy}` (`{job}` by slug); `StoreJobRequest`/`UpdateJobRequest` each exposing `validated()` with the job fields + a resolved `status` (Open or Draft). Later tasks (views) consume these route names.

- [ ] **Step 1: Write the failing tests**

In `tests/Feature/Organizations/JobManagementTest.php` (model on `CandidateManagementTest` style; use `organizationWithOwner()`/`addOrganizationMember()` from `tests/Pest.php`):

```php
test('an owner can publish a job', function () {
    [$organization, $owner] = organizationWithOwner();
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['action' => 'publish']))
        ->assertRedirect(); // to the job show page
    $job = \App\Models\Job::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->latest('id')->first();
    expect($job->status)->toBe(\App\Enums\JobStatus::Open);
});

test('a job can be saved as a draft and is excluded from the public board and applications', function () {
    [$organization, $owner] = organizationWithOwner();
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['action' => 'save_draft']))->assertRedirect();
    $job = \App\Models\Job::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->latest('id')->first();
    expect($job->status)->toBe(\App\Enums\JobStatus::Draft);
    expect($job->isAcceptingApplications())->toBeFalse();
    expect(\App\Models\Job::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->open()->whereKey($job->id)->exists())->toBeFalse();
});

test('a recruiter can create but a user without create_jobs cannot', function () {
    [$organization, $owner] = organizationWithOwner();
    $recruiter = addOrganizationMember($organization, \App\Enums\OrganizationRole::Recruiter)->user;
    $interviewer = addOrganizationMember($organization, \App\Enums\OrganizationRole::Interviewer)->user;
    $this->actingAs($recruiter)->post(route('organisations.jobs.store'), jobPayload())->assertRedirect();
    $this->actingAs($interviewer)->post(route('organisations.jobs.store'), jobPayload())->assertForbidden();
});

test('job creation validates required fields and description text', function () {
    [$organization, $owner] = organizationWithOwner();
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['title' => '']))->assertSessionHasErrors('title');
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['description' => '<p>   </p>']))->assertSessionHasErrors('description');
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload(['employment_type' => 'bogus']))->assertSessionHasErrors('employment_type');
});

test('a submitted description is sanitized server-side', function () {
    [$organization, $owner] = organizationWithOwner();
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload([
        'description' => '<p>Hi</p><script>alert(1)</script><div class="x" onclick="e()">z</div>',
    ]))->assertRedirect();
    $job = \App\Models\Job::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->latest('id')->first();
    expect($job->description)->not->toContain('<script>')->not->toContain('onclick');
});
```

Add a `jobPayload(array $overrides = []): array` helper (valid title/description-with-text/company/location/employment_type=full_time/start_date=today/due_date=+30d/action=publish) local to this test file.

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test tests/Feature/Organizations/JobManagementTest.php`
Expected: FAIL — `organisations.jobs.store` route not defined.

- [ ] **Step 3: Create the FormRequests**

`StoreJobRequest`/`UpdateJobRequest`: `authorize()` returns `$this->user()->can('create'|'update', ...)` via `JobPolicy`. `rules()` carries the existing field rules from `JobController::rules()` — title required string max255; description bail+required+max:`JobDescription::MAX_LENGTH`+a closure using `app(JobDescription::class)->hasText($value)`; company required; category nullable; location required; employment_type `Rule::in(array_keys(Job::employmentTypeOptions()))`; logo (file image mimes when present else nullable string); start_date required date; due_date required date `after_or_equal:start_date`. In `StoreJobRequest` add `after_or_equal:today` to due_date UNCONDITIONALLY (do not copy the `routeIs('jobs.store')` branch). Add `action` => `['required', Rule::in(['publish','save_draft'])]` and expose a `resolvedStatus(): JobStatus` (publish→Open, save_draft→Draft).

- [ ] **Step 4: Rewire the controller write-actions + routes**

In `JobController`: `store`/`update` type-hint the new requests; set `$data['status'] = $request->resolvedStatus()` (replacing the hardcoded `JobStatus::Open`); re-sanitize `$data['description']` via `JobDescription` is already handled at render — but ensure the stored value is sanitized on save (sanitize in the request's `passedValidation()` or in the controller before create/update). Redirect `store`/`update` to `route('organisations.jobs.show', $job)`; fix `close`/`destroy` redirects to `organisations.jobs.show`/`organisations.jobs.index`. Add the 8 routes to the `organisations.` group (`{job}` binds by slug via the model route key — if the OrganizationScope interferes with binding like it did for `applicationForm`, add a `Route::bind('job', ...)` unscoped resolver).

- [ ] **Step 5: Run to verify pass**

Run: `php artisan test tests/Feature/Organizations/JobManagementTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/StoreJobRequest.php app/Http/Requests/UpdateJobRequest.php app/Http/Controllers/JobController.php routes/web.php tests/Feature/Organizations/JobManagementTest.php
git commit -m "feat(jobs): wire native job write-actions, FormRequests, routes, draft/publish"
```

---

### Task 3: Shared form partial + create/edit views + controller GET methods

**Files:**
- Create: `resources/views/organisations/jobs/_form.blade.php`, `resources/views/organisations/jobs/create.blade.php`, `resources/views/organisations/jobs/edit.blade.php`
- Modify: `app/Http/Controllers/JobController.php` (add `create()`/`edit()`; make `index()` use `scopeJobs`; pass `$categories`)
- Test: `tests/Feature/Organizations/JobManagementTest.php` (append)

**Interfaces:**
- Consumes: Task 2 routes; `<x-form.actions>`, `<x-text-input>`, `<x-input-label>`, `<x-input-error>`.
- Produces: `create()`/`edit()` returning views; the `_form` partial expecting `$job` (nullable for create), `$categories` (array of distinct org categories), `$employmentTypes`.

- [ ] **Step 1: Write the failing tests**

```php
test('the job create form renders for an authorized user', function () {
    [$organization, $owner] = organizationWithOwner();
    $this->actingAs($owner)->get(route('organisations.jobs.create'))
        ->assertOk()->assertSee('Save as draft')->assertSee('Publish');
});

test('the job edit form renders with the job values', function () {
    [$organization, $owner] = organizationWithOwner();
    $job = createOrgJob($organization, $owner, ['title' => 'Backend Engineer']);
    $this->actingAs($owner)->get(route('organisations.jobs.edit', $job))->assertOk()->assertSee('Backend Engineer');
});
```

Add a `createOrgJob($organization, $user, $overrides)` helper (creates a `Job` in org context) to the test file or `tests/Pest.php`.

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test tests/Feature/Organizations/JobManagementTest.php -t "create form"`
Expected: FAIL — `View [organisations.jobs.create] not found.`

- [ ] **Step 3: Build the views + controller GET methods**

`_form.blade.php`: `nw-*` stacked form with title/company/category(`<input list>` + `<datalist>` from `$categories`)/location/employment_type(select from `$employmentTypes`)/start_date/due_date/logo(file + Alpine FileReader preview, reusing the profile-form pattern) and the description region — a Quill mount container + a hidden `<input name="description">` + a visible length counter element; wire an Alpine component attribute (`x-data="jobForm({...})"`) the Task-4 JS defines, with a `<noscript>`/fallback `<textarea name="description">` so the form works pre-JS and in tests. Actions via `<x-form.actions>`: a `save_draft` submit (name="action" value="save_draft") in the cancel slot and a `publish` submit (value="publish") as primary. `create.blade.php`/`edit.blade.php` wrap `_form` in `<x-app-layout>` with the right route/method (`@method('PATCH')` on edit). Controller: add `create()`/`edit(Job $job)` passing `$categories` (the org's own distinct categories) + `$employmentTypes`; change `index()` to use `app(OrganizationPermissions::class)->scopeJobs(Job::query(), $request->user())` instead of the `employer_id` filter.

- [ ] **Step 4: Run to verify pass**

Run: `php artisan test tests/Feature/Organizations/JobManagementTest.php`
Expected: PASS.

- [ ] **Step 5: Human-eyeball note + commit**

Note: the form's visual layout + the Quill editor (once Task 4 ships the JS) need a human eyeball; tests only confirm the view renders and the fields/actions are present.

```bash
git add resources/views/organisations/jobs/_form.blade.php resources/views/organisations/jobs/create.blade.php resources/views/organisations/jobs/edit.blade.php app/Http/Controllers/JobController.php tests/Feature/Organizations/JobManagementTest.php tests/Pest.php
git commit -m "feat(jobs): add create/edit form views + shared _form partial"
```

---

### Task 4: Quill editor JS component

**Files:**
- Create: `resources/js/job-form.js`
- Modify: `resources/js/app.js` (register), `package.json` (quill dep)
- Test: build/ship verification + a server-side sanitizer test for Quill-shaped HTML

**Interfaces:**
- Consumes: the `_form` markup's Quill container + hidden `description` input + counter element.
- Produces: `registerJobForm(Alpine)` defining `Alpine.data('jobForm', ...)` — mounts Quill (toolbar = bold/italic/underline/strike, header, ordered+bullet list, link, blockquote, inline code ONLY), syncs Quill HTML → hidden input on `text-change`, updates the length counter vs `JobDescription::MAX_LENGTH` (passed in as a config value), autosaves form state to `localStorage` (key per create/edit) + restores on init, and sets a `beforeunload` guard that is suppressed on the form's own submit.

- [ ] **Step 1: Write the failing test (server tolerates Quill-shaped HTML)**

```php
test('quill alignment/indent/code-block html is stripped to clean markup on save', function () {
    [$organization, $owner] = organizationWithOwner();
    $this->actingAs($owner)->post(route('organisations.jobs.store'), jobPayload([
        'description' => '<p class="ql-align-center">A</p><div class="ql-code-block-container"><div class="ql-code-block">x</div></div><ul><li>b</li></ul>',
    ]))->assertRedirect();
    $job = \App\Models\Job::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->latest('id')->first();
    expect($job->description)->not->toContain('ql-align')->not->toContain('<div')->toContain('<ul>');
});
```

- [ ] **Step 2: Run to verify it fails or passes**

Run: `php artisan test tests/Feature/Organizations/JobManagementTest.php -t "quill"`
Expected: it likely PASSES already (the sanitizer strips class/div), confirming the server guarantee that makes the toolbar-scoping a UX concern, not a security one. If it FAILS, the server sanitize-on-save from Task 2 is incomplete — fix that first.

- [ ] **Step 3: Install + build the component**

`npm install quill` (pin a specific version). Create `resources/js/job-form.js` per the Interfaces block. Register it in `resources/js/app.js` (`import { registerJobForm } from './job-form'; registerJobForm(Alpine)`). Keep the toolbar strictly to the round-trip-safe formats.

- [ ] **Step 4: Rebuild + verify it ships**

Run: `npm run build` — Expected: builds into `public/asset/marthire/build/assets/`.
Run: assert the served JS bundle exists and the manifest updated (`grep` the new `app-*.js` in `public/asset/marthire/build/manifest.json`). Confirm `quill` is in `package.json`.

- [ ] **Step 5: Human-eyeball note + commit**

Note: Quill's actual editing, counter, autosave, and leave-guard are JS behaviors NOT exercised by PHP tests this session (no browser) — they need a human to open the create form, type, reload (autosave), and try to navigate away (guard). State this honestly.

```bash
git add resources/js/job-form.js resources/js/app.js package.json package-lock.json public/asset/marthire
git commit -m "feat(jobs): add Quill description editor (scoped toolbar, counter, autosave, leave-guard)"
```

---

### Task 5: Jobs index + show views

**Files:**
- Create: `resources/views/organisations/jobs/index.blade.php`, `resources/views/organisations/jobs/show.blade.php`
- Modify: `app/Http/Controllers/JobController.php` (`index()` view name + `show`/`employerShow` view for orgs — reuse/repoint to `organisations.jobs.show`)
- Test: `tests/Feature/Organizations/JobManagementTest.php` (append) + grep gate

**Interfaces:**
- Consumes: Task 2 routes, Task 1 Draft badge, `<x-table.toolbar>`, `<x-status-badge>`.
- Produces: nothing downstream.

- [ ] **Step 1: Write the failing tests**

```php
test('the jobs index lists org jobs by role and shows draft badges to the owner', function () {
    [$organization, $owner] = organizationWithOwner();
    $open = createOrgJob($organization, $owner, ['title' => 'Open Role', 'status' => \App\Enums\JobStatus::Open]);
    $draft = createOrgJob($organization, $owner, ['title' => 'Draft Role', 'status' => \App\Enums\JobStatus::Draft]);
    $this->actingAs($owner)->get(route('organisations.jobs.index'))
        ->assertOk()->assertSee('Open Role')->assertSee('Draft Role')->assertSee('Draft');
});

test('delete is hidden from a recruiter but shown to the owner', function () {
    [$organization, $owner] = organizationWithOwner();
    $recruiter = addOrganizationMember($organization, \App\Enums\OrganizationRole::Recruiter)->user;
    createOrgJob($organization, $recruiter, ['title' => 'R Job']);
    $this->actingAs($owner)->get(route('organisations.jobs.index'))->assertSee('nw-button--danger', false);
    $this->actingAs($recruiter)->get(route('organisations.jobs.index'))->assertDontSee('nw-button--danger', false);
});
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test tests/Feature/Organizations/JobManagementTest.php -t "jobs index"`
Expected: FAIL — `View [organisations.jobs.index] not found.`

- [ ] **Step 3: Build index + show**

`index.blade.php`: `<x-table.toolbar>` (a "New job" action linking to `organisations.jobs.create`, optional status filter) + an `nw-table` of the org's jobs — primary `<td class="nw-table__main">` (title + company), status via `<x-status-badge>`, applications count, and an action cell `<td class="nw-table__go nw-table__go--persist">` with Edit (gated `@can('update', $job)`), Close, and Delete (`@can('delete', $job)` so recruiters don't see it). `show.blade.php`: render the job detail incl. the sanitized description via `{!! app(JobDescription::class)->toHtml($job->description) !!}`. Repoint the controller's org-facing index/show to these views.

- [ ] **Step 4: Run tests + mobile-invariant grep**

Run: `php artisan test tests/Feature/Organizations/JobManagementTest.php` (Expected: PASS) and grep-assert `index.blade.php` has both `nw-table__main` and `nw-table__go--persist` (Expected: present).

- [ ] **Step 5: Human-eyeball note + commit**

```bash
git add resources/views/organisations/jobs/index.blade.php resources/views/organisations/jobs/show.blade.php app/Http/Controllers/JobController.php tests/Feature/Organizations/JobManagementTest.php
git commit -m "feat(jobs): add org jobs index + show views"
```

---

### Task 6: Close/delete wiring + nav link + final verification

**Files:**
- Modify: `resources/views/layouts/navigation.blade.php` (a "Jobs" link in the organization Recruitment group), any remaining controller redirect fixes
- Test: `tests/Feature/Organizations/JobManagementTest.php` (append close/delete) + full suite

**Interfaces:**
- Consumes: all prior tasks.

- [ ] **Step 1: Write the failing tests**

```php
test('an owner can close and delete a job, but a job with candidate email history cannot be deleted', function () {
    [$organization, $owner] = organizationWithOwner();
    $job = createOrgJob($organization, $owner, []);
    $this->actingAs($owner)->post(route('organisations.jobs.close', $job), ['confirm_closure' => true])->assertRedirect();
    expect($job->fresh()->status)->toBe(\App\Enums\JobStatus::Closed);
    $this->actingAs($owner)->delete(route('organisations.jobs.destroy', $job))->assertRedirect(route('organisations.jobs.index'));
    expect(\App\Models\Job::withoutGlobalScope(\App\Models\Scopes\OrganizationScope::class)->whereKey($job->id)->exists())->toBeFalse();
});

test('organization users see a Jobs link in navigation', function () {
    [$organization, $owner] = organizationWithOwner();
    $this->actingAs($owner)->get(route('organisations.dashboard'))->assertOk()->assertSee(route('organisations.jobs.index'), false);
});
```

(Confirm `CloseJobRequest`'s expected field name — use the real one, e.g. `confirm_closure` — from the request class.)

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test tests/Feature/Organizations/JobManagementTest.php -t "Jobs link"`
Expected: FAIL — nav link absent.

- [ ] **Step 3: Add the nav link + any leftover redirect fixes**

Add a "Jobs" entry (icon `briefcase`, active `organisations.jobs.*`) to the organization `Recruitment` group in `navigation.blade.php`, next to Candidates/Team. Verify `close`/`destroy` redirect to the right org routes (close → show, destroy → index).

- [ ] **Step 4: Full suite + clean rebuild**

Run: `npm run build`; then `php artisan test`
Expected: all green except the one known-baseline `JobPagesTest` failure. Assert the served manifest is fresh.

- [ ] **Step 5: Full human-eyeball pass (the real visual/JS sign-off)**

Open `/organisations/jobs`, create a job (type in Quill, watch the counter, save a draft, publish), edit it, reload mid-edit (autosave), try to leave with unsaved changes (guard), close and delete — at desktop and ≤767px. This is the only verification of the editor behavior and the visual result; automated tests cannot do it.

- [ ] **Step 6: Commit**

```bash
git add resources/views/layouts/navigation.blade.php app/Http/Controllers/JobController.php tests/Feature/Organizations/JobManagementTest.php
git commit -m "feat(jobs): add Jobs nav link; wire close/delete; final verification"
```
