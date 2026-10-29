# Native job management (Create/Edit/List/Close/Delete) with Quill — design

## Intent and why

Organizations need to post and manage their own jobs natively on the platform —
distinct from the Workable `JobPosting` board, which is synced read-only from an
employer's external Workable account. These native jobs use the platform's own `Job`
model, which the candidate pipeline already runs on (applications → `Job`). The backend
for this (`JobController::store/update/close/destroy`, `JobPolicy`, the `JobDescription`
HTML sanitizer, the `Job` model with slug/employment-types) already exists but is
**unrouted and orphaned**: no `create()` method, no form views, and redirects point to
dead `route('jobs')`/`employer.jobs.*` names. This builds the missing UI + routing and
adds a Quill rich-text editor for the description, reusing the existing architecture.

## Goals

- Full native job CRUD for organizations under `organisations.jobs.*`: list, create,
  edit, show, close, delete — reusing the existing `JobController` logic.
- A Quill rich-text editor for the job description, with its toolbar/formats scoped to
  exactly what `App\Support\JobDescription` sanitizes, so WYSIWYG == stored output and
  the editor never drifts from the server allowlist.
- Four modern features (all chosen): Draft+Publish, autosave + unsaved-changes guard,
  category suggestions (datalist), and a live content-length counter.
- Production-ready: FormRequest validation, policy authorization, server-side
  re-sanitization, and feature tests.

## Non-goals / anti-goals

- No change to the Workable `JobPosting` system (external, synced, read-only) or to the
  `WorkableJobController` public board.
- No change to the candidate pipeline / `ApplicationForm` behavior beyond ensuring Draft
  jobs are excluded from it (which the existing `Open`-gated scopes already do).
- Not raw Tailwind utility classes in Blade — use the project's `nw-*` design system and
  the shared `x-form.actions` / `x-table.toolbar` components built this session.
- Not rewriting `JobController`'s business logic — reuse `store/update/close/destroy`,
  fixing only the orphaned route/view references and the Draft/Publish status handling.
- No RTE via CDN — Quill is bundled through npm + Vite (resolved decision).

## Constraints

- Laravel 12 + Blade + `nw-*` CSS + Alpine + Vite (builds into `public/asset/nearbyworks/build/`).
- Reuse: `JobController`, `Job` model (slug route key, `employmentTypeOptions`, scopes),
  `JobPolicy` (`create_jobs`/`edit_jobs`/`delete_jobs` permissions), `JobDescription`
  sanitizer, `JobPostMember`, `CloseJobRequest`, `OrganizationContext`.
- Quill's enabled formats must be a subset of `JobDescription`'s allowlist (headings,
  bold/italic/underline/strike, ordered/unordered lists, links, blockquote, code).
- Server always re-sanitizes the submitted description via `JobDescription` — the client
  HTML is never trusted.
- Drafts must not appear on the public board or accept applications (verified: `scopeOpen`
  and `isAcceptingApplications` both gate on `JobStatus::Open`, so a new `Draft` status is
  excluded automatically).
- Feature tests stay green; new CSS/JS must be rebuilt (`npm run build`) to ship.
- No browser/screenshot tool this session — visual result needs human eyeball.

## Approaches

**Quill integration (the one real fork — resolved):**
- *npm + Vite + Alpine (chosen):* `npm install quill`; a `resources/js/job-form.js`
  module registers an Alpine component (following the existing `registerApplicationForm`
  pattern in `resources/js/app.js`) that mounts Quill on a container, scopes its toolbar
  to the sanitizer allowlist, and syncs Quill's HTML into a hidden `description` input on
  every `text-change`. Versioned, bundled, offline-safe, consistent with the stack.
- *CDN script tag (rejected):* unversioned, outside Vite, breaks offline.

**CRUD structure (one sensible path each):**
- Reuse & revive `JobController` under a new `organisations.jobs.*` group; add the missing
  `create()`; fix the dead `route('jobs')`/`route('jobs.show')` redirects and the
  `employer.jobs.*` view names to the new `organisations.jobs.*` names.
- Extract the inline `rules()` into `StoreJobRequest` + `UpdateJobRequest` FormRequests
  (authorized via `JobPolicy`), keeping the current field rules and the `JobDescription::hasText`
  description check.
- One shared `resources/views/organisations/jobs/_form.blade.php` partial reused by create
  and edit (holds the Quill editor, logo preview, category datalist, live counter, and the
  Draft/Publish actions in `<x-form.actions>`).

**Draft handling:**
- Add `Draft` to `JobStatus` + a migration (the `jobs.status` column already stores the
  enum value). `store()`/`update()` set status from the submitted action (a `publish` vs
  `save_draft` submit), replacing the hardcoded `JobStatus::Open`. Drafts are excluded from
  the public board + pipeline by the existing `Open`-gated scopes.

## The parts of the how, as decided

### Routes (`routes/web.php`, inside the existing `organisations.` group)
`jobs` (index), `jobs/create` (create), `jobs` POST (store), `jobs/{job}` (show),
`jobs/{job}/edit` (edit), `jobs/{job}` PATCH (update), `jobs/{job}/close` POST (close),
`jobs/{job}` DELETE (destroy). `{job}` binds by slug (the model's route key). Names:
`organisations.jobs.{index,create,store,show,edit,update,close,destroy}`.

### Controller (`JobController`, reused)
- Add `create(): View`. Repoint `index()`/`employerShow()` to `organisations.jobs.*` views;
  fix `store/update/close/destroy` redirects from `route('jobs')`/`route('jobs.show')` to
  the new names. Honor a `publish`/`save_draft` status in `store`/`update`. Everything
  else (transaction, org lock, `JobPostMember`, logo, policy) stays.

### FormRequests
`StoreJobRequest` + `UpdateJobRequest` carrying the existing rules (title, description with
`JobDescription::hasText`, company, category nullable, location, employment_type `Rule::in`,
logo, start_date, due_date with `after_or_equal` rules) + a `status`/action field
(`publish|save_draft`). `authorize()` delegates to `JobPolicy`.

### Views (`resources/views/organisations/jobs/`)
`index.blade.php` (the org's own jobs, via `x-table.toolbar` + `nw-table` with
`nw-table__main`), `create.blade.php`, `edit.blade.php`, `show.blade.php`, and the shared
`_form.blade.php`. `nw-*` styling throughout; action buttons via `<x-form.actions>`.

### Quill + JS (`resources/js/job-form.js`, registered in `resources/js/app.js`)
Alpine `jobForm` component: mounts Quill (toolbar = sanitizer-allowed formats only), syncs
HTML → hidden `description` input, enforces/【shows a live length counter vs
`JobDescription::MAX_LENGTH`, autosaves the form to `localStorage` (keyed per create/edit)
and restores on load, and warns on `beforeunload` when there are unsaved changes. Logo live
preview reuses the profile-form FileReader pattern; category `<input list>` + `<datalist>`
is populated from existing distinct categories passed by the controller.

### Draft/Publish + sanitization
`JobStatus::Draft` added; `store/update` set status from the submit action; `JobDescription`
re-sanitizes the description server-side on every save.

## What's left to the builder

- Exact Quill toolbar config (within the sanitizer allowlist) and the Alpine sync wiring.
- The `_form` partial markup and the index/show/edit view layouts (within `nw-*` + the
  shared components).
- Exact FormRequest rule array and the `publish|save_draft` field handling.
- Migration mechanics for the `Draft` status (no schema type change — same string column).

## Decisions for the user (surfaced after builder-check)

[To be filled from the builder-check — e.g. who may create jobs (the `create_jobs`
permission is Owner/HR/Recruiter — confirm), where to land after create (the new jobs
index vs the job's show page), and whether Draft jobs are editable-only by their creator
or any team member with `edit_jobs`.]
