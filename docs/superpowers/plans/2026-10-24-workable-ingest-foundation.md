# Workable Ingest Foundation (W0+W1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the server-side foundation that ingests an employer's published Workable jobs into our database (thin owned client, encrypted per-account token, idempotent sync) and fixes candidate-account registration — all testable with zero network access.

**Architecture:** Thin lower layers of the design §3/§5: `WorkableClient` (only class that knows URLs/Bearer auth/raw shapes; returns DTOs; throws typed exceptions) → `SyncCompanyJobs` action → Eloquent models (`Company`, `JobPosting`, `SyncRun`) + a queued job + Artisan commands. Raw API arrays never cross the client boundary. The account token lives only in the DB, encrypted. Also repairs the broken Breeze registration so candidate accounts work (prerequisite for later apply phases).

**Tech Stack:** Laravel 12, PHP 8.2+, MySQL, database queue + cache (no Redis), Laravel HTTP client (`Http::fake` for tests), Pest.

**Spec:** `docs/superpowers/specs/2026-10-24-workable-integration-design.md` (supersedes the Greenhouse design). Executors read it; §2 carries the live Workable API facts.

## Global Constraints
- Base URL built per-account: `https://{subdomain}.workable.com/spi/v3` (from `config('workable.host_template')` = `https://%s.workable.com/spi/%s` and `api_version` = `v3`). No fixed base URL.
- Auth: `Authorization: Bearer <token>`; scopes `r_jobs` (read), `w_candidates` (apply, later phase).
- Models named `Company`, `JobPosting`, `SyncRun` — never `Job`/`Application`.
- Account token: `encrypted` cast, `$hidden`, never in `.env`/logs/exceptions/git.
- Jobs keyed by `shortcode` for sub-resources; store Workable `id` as `external_id` too. List with `state=published&limit=100&include_fields=description,full_description,requirements,benefits`, following `paging.next` (a full URL).
- No secrets or PII in logs — IDs only. CI never hits the live API (fixtures + `Http::fake`).
- Subdomain + token are per-company DB values; the app boots and runs with none configured (no jobs shown; apply redirect-only).
- Connect timeout 5s, total 15s; descriptive User-Agent with a contact address.
- Namespace `App\Integrations\Workable`. Superseded Greenhouse files are removed in the tasks that replace them (non-destructive — kept in branch history).
- Commit trailer on every commit: `Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>` (verbatim, regardless of the implementer's model).
- DRY, YAGNI (build only companies/job_postings/sync_runs here — `job_applications` is a later phase), TDD, frequent commits.

## Review Focus
1. **Pagination:** `listPublishedJobs` must follow `paging.next` across multiple pages and stop when it is null/absent — not just read page 1. Pinned in Task 6.
2. **Empty 200 with open postings** → run `suspicious`, alert, **no sweep**. Pinned in Task 9.
3. **Failed/throwing list** → run `failed`, **no sweep** (no mass false-closure). Pinned in Task 9.
4. **Disappeared published job** → swept `closed` with `closed_at`, only after a fully successful fetch; a reappearing job reopens. Pinned in Task 9.
5. **Token leaking** via model serialization or logs → `Company::toArray()/toJson()` never contains `api_token`; token registered in the log scrubber. Pinned in Task 7.

---

### Task 1: Fix candidate-account registration (unblocks accounts; clears known-red baseline)

**Files:**
- Create: migration `..._add_first_last_name_to_users_table.php`
- Modify: `app/Models/User.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`, `resources/views/auth/register.blade.php`, `database/factories/UserFactory.php`, `tests/Feature/Auth/RegistrationTest.php`, `tests/Feature/ProfileTest.php`, and any profile update form/controller that references `name`.

**Interfaces:**
- Produces: `User` has `first_name` and `last_name` string columns and fillable; registration writes them; `name` is derived/optional. Candidate display name = `trim("$first_name $last_name")`.

- [ ] **Step 1: Write/adjust the failing tests** — update `RegistrationTest > new users can register` to post `first_name`/`last_name` and assert the user is created + authenticated; update `ProfileTest` to use `first_name`/`last_name` instead of `name`.
- [ ] **Step 2: Run** `php artisan test --filter="RegistrationTest|ProfileTest"` → FAIL (columns/fields missing).
- [ ] **Step 3: Implement** — migration adding `first_name`, `last_name` (string) and making `name` nullable (keep the column for compatibility; set it to the full name on write, or drop it if nothing else reads it — check first). Set `User::$fillable = ['first_name','last_name','email','password']`. Update `RegisteredUserController::store()` validation + create to `first_name`/`last_name`. Update `register.blade.php` field names. Update `UserFactory` to define `first_name`/`last_name` (and `name` if retained). Keep BCRYPT/testing behavior intact.
- [ ] **Step 4: Run** the full suite → expect **all green, zero known-red** (this task eliminates the two pre-existing failures).
- [ ] **Step 5: Commit** — `fix(auth): first_name/last_name registration so candidate accounts work`.

---

### Task 2: Workable config and env

**Files:**
- Create: `config/workable.php`; Delete: `config/greenhouse.php`
- Modify: `.env.example`, `.env` (replace GREENHOUSE_* with WORKABLE_*); Delete: `tests/Feature/Greenhouse/ConfigTest.php`
- Test: `tests/Feature/Workable/ConfigTest.php`

**Interfaces:**
- Produces: `config('workable.api_version')` = `v3`; `.host_template` = `https://%s.workable.com/spi/%s`; `.user_agent`; `.fake` (bool); `.sync_empty_guard_min_open` (int 5); `.schema_cache_minutes` (int 15); `.application.max_file_mb` (int 5); `.application.payload_retention_days` (int 30).
- Env vars: `WORKABLE_API_VERSION` (v3), `WORKABLE_HOST_TEMPLATE`, `WORKABLE_USER_AGENT` (`Marthire/1.0 (+ops@marthire.test)`), `WORKABLE_FAKE` (false), `WORKABLE_SYNC_EMPTY_GUARD_MIN_OPEN` (5), `WORKABLE_SCHEMA_CACHE_MINUTES` (15), `APPLICATION_MAX_FILE_MB` (5), `APPLICATION_PAYLOAD_RETENTION_DAYS` (30). No token/subdomain in env.

- [ ] **Step 1: Write failing test** `config_has_expected_defaults()` asserting each key/default above (ints via `toBe`, fake `false`).
- [ ] **Step 2: Run** `--filter=Workable\\ConfigTest` → FAIL.
- [ ] **Step 3: Implement** `config/workable.php`; delete `config/greenhouse.php` and the Greenhouse `ConfigTest`; swap env vars.
- [ ] **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): config and env; remove greenhouse config`.

---

### Task 3: Typed exceptions

**Files:**
- Create: `app/Integrations/Workable/Exceptions/WorkableException.php` + `CredentialsRejected`, `JobNotFound`, `RateLimited`, `TransientFailure`, `AmbiguousOutcome`, `PayloadRejected`; Delete: `app/Integrations/Greenhouse/Exceptions/*` and `tests/Unit/Greenhouse/ExceptionsTest.php`
- Test: `tests/Unit/Workable/ExceptionsTest.php`

**Interfaces:**
- `WorkableException extends \RuntimeException`; the others extend it.
- `RateLimited`: `__construct(?int $retryAfter = null, string $message = '', int $code = 0, ?\Throwable $previous = null)`, `public readonly ?int $retryAfter`, `retryAfter(): ?int`.
- `PayloadRejected`: `__construct(array $validationErrors = [], string $message = '', int $code = 0, ?\Throwable $previous = null)`, `public readonly array $validationErrors`, `validationErrors(): array` (carries Workable's `validation_errors`).

- [ ] **Step 1: Write failing tests** — `rate_limited_carries_retry_after()`; `payload_rejected_carries_validation_errors()` (`(new PayloadRejected(['email'=>['has already been taken']]))->validationErrors()` equals that array); `credentials_rejected_is_a_workable_exception()`.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** the classes; delete the Greenhouse exceptions + their test.
- [ ] **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): typed exception hierarchy; remove greenhouse exceptions`.

---

### Task 4: Fixtures

**Files:**
- Create under `tests/Fixtures/workable/`: `jobs_page1.json`, `jobs_page2.json`, `jobs_published.json`, `job_detail.json`, `application_form.json`, `candidate_created.json`, `candidate_422.json`, `README.md`; Delete: `tests/Fixtures/greenhouse/*`

**Interfaces:** fixture paths consumed by Tasks 5, 6, 9.

- [ ] **Step 1: Create fixtures** matching design §2 shapes exactly:
  - `jobs_page1.json`: `{ "jobs":[{job A fully populated}], "paging": { "next": "https://sub.workable.com/spi/v3/jobs?...&since_id=..." } }` — job A has all fields, `state:"published"`, `shortcode`, `id`, `location`, `locations`, `salary`, `keywords`, and description fields (as if `include_fields`).
  - `jobs_page2.json`: `{ "jobs":[{job B with null/empty optional fields: no salary, empty keywords, minimal location}], "paging": { "next": null } }`.
  - `jobs_published.json`: a single-page `{ "jobs":[A,B], "paging":{"next":null} }` for non-pagination tests.
  - `job_detail.json`: one job object (shortcode of A) with full description fields.
  - `application_form.json`: `{ "form_fields":[ {key:"name",type:"string",required:true}, {key:"email",type:"string",required:true}, {key:"phone",type:"string",required:false}, {key:"resume",type:"file",required:true,supported_file_types:["pdf","doc","docx"],max_file_size:10485760}, {key:"cover_letter",type:"free_text",required:false}, {key:"summary",type:"free_text",required:false} ], "questions":[ one of each type: free_text, boolean, numeric, date, dropdown(choices), multiple_choice(single_answer+choices), file(supported_file_types,max_file_size) ] }`.
  - `candidate_created.json`: `{ "status":"created", "candidate": { "id":"...", "name":"TEST Applicant", "job":{"shortcode":"..."}, "sourced":false } }`.
  - `candidate_422.json`: `{ "validation_errors": { "email": ["has already been taken"] } }`.
  - `README.md`: representative stand-ins; replace with a live capture (`curl -H "Authorization:Bearer <token>" https://{subdomain}.workable.com/spi/v3/jobs?state=published` etc.) once the subdomain+token arrive; real payload wins; no real candidate PII.
- [ ] **Step 2:** Validate each JSON parses; delete `tests/Fixtures/greenhouse/*`.
- [ ] **Step 3: Commit** — `test(workable): representative fixtures; remove greenhouse fixtures`.

---

### Task 5: DTOs

**Files:**
- Create: `app/Integrations/Workable/DTO/JobPostData.php`, `QuestionData.php`, `ApplicationSchema.php`, `SubmissionResult.php`
- Test: `tests/Unit/Workable/DtoTest.php`

**Interfaces:**
- `JobPostData` readonly props + `fromApi(array $j): self`:
  - `shortcode` (string, required → throw `WorkableException` if missing/empty), `externalId` (string, from `id`), `title` (required), `fullTitle` (?string from `full_title`), `state` (string), `department` (?string), `workplaceType` (?string from `workplace_type`), `url`/`applicationUrl`/`shortlink` (?string), `location` (array `?? []`), `locations` (array `?? []`), `salary` (?array), `keywords` (array `?? []`), `contentHtml` (?string — concatenate present `full_description`/`description` + `requirements` + `benefits`, else null; HTML-entity-decode once if entity-encoded), `externalUpdatedAt` (?\DateTimeImmutable from `updated_at`).
- `QuestionData`: `__construct(public readonly string $id, public readonly string $label, public readonly string $type, public readonly bool $required, public readonly ?bool $singleAnswer, public readonly array $choices, public readonly array $supportedFileTypes, public readonly ?int $maxFileSize)`; `fromApi(array $q): self` (label from `body`; choices as `[{id,body}]`).
- `ApplicationSchema`: `__construct(public readonly array $formFields, public readonly array $questions)`; `fromApi(array $form): self` — `form_fields` kept as normalized arrays `{key,label,type,required,...}`; `questions` as `QuestionData[]`.
- `SubmissionResult`: `__construct(public readonly int $status, public readonly ?string $candidateId = null)`.

- [ ] **Step 1: Write failing tests** — `parses_job_from_fixture()` (load `jobs_published.json`, map job A, assert `shortcode`, `title`, `state==='published'`, `location`/`locations` arrays, `contentHtml` non-null); `parses_job_with_missing_optionals()` (job B: `salary===null`, `keywords===[]`, no crash); `throws_on_job_without_shortcode_or_title()`; `builds_schema_from_application_form()` (load `application_form.json`; assert `formFields` includes required `email`+`resume`; collect question types and assert all of `free_text,boolean,numeric,date,dropdown,multiple_choice,file` present; a dropdown question's `choices[0]` has `id`+`body`).
- [ ] **Step 2: Run** `--filter=Workable\\DtoTest` → FAIL.
- [ ] **Step 3: Implement** the DTOs.
- [ ] **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): boundary DTOs`.

---

### Task 6: WorkableClient

**Files:**
- Create: `app/Integrations/Workable/WorkableClient.php`
- Test: `tests/Unit/Workable/WorkableClientTest.php`

**Interfaces:**
- Consumes: DTOs (Task 5), exceptions (Task 3), config (Task 2), `Company` (Task 7 — injected `Company` provides `subdomain` + decrypted `api_token`). NOTE: Task 6 depends on Task 7's `Company`; implement Task 7 first if needed, or type-hint `Company` and let tests build one. **Ordering: do Task 7 before Task 6.**
- Produces:
  - `listPublishedJobs(Company $c): \Illuminate\Support\Collection` // JobPostData[], follows `paging.next`.
  - `getJob(Company $c, string $shortcode): JobPostData`
  - `getApplicationForm(Company $c, string $shortcode): ApplicationSchema`
  - `createCandidate(Company $c, string $shortcode, array $payload): SubmissionResult`
  - Constructed with an injected `Illuminate\Http\Client\Factory`.

- [ ] **Step 1: Write failing tests** (`Http::fake`): `list_sends_bearer_and_published_query()` (assert `Authorization: Bearer <token>` header and `state=published`, `limit=100`); `list_follows_paging_next_across_pages()` (fake `jobs_page1.json` then `jobs_page2.json` keyed by URL; assert 2 jobs total and page2 fetched via the `paging.next` URL); `list_stops_when_next_null()`; status mapping: 401/403→`CredentialsRejected`, 404→`JobNotFound`, 429→`RateLimited` (Retry-After), 422 on POST→`PayloadRejected` carrying `validation_errors` from `candidate_422.json`, 5xx GET→`TransientFailure`, read-timeout/5xx after POST→`AmbiguousOutcome`; `create_candidate_posts_to_shortcode_with_sourced_false()`; `post_attempted_exactly_once()`.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** — private `http(Company)` building base URL via `sprintf(config('workable.host_template'), $c->subdomain, config('workable.api_version'))`, `->withToken($c->api_token)` (Bearer), `acceptJson`, user-agent, 5s/15s timeouts; GETs `->retry(3,300,isTransient,throw:false)`; POST no retry; pagination loop following `paging.next`; `assertSuccessful($res, bool $isSubmission=false)` mapping status→exception (422 parses `validation_errors`); FAKE mode (config `workable.fake`) serves fixtures and returns 201 for create. Never log token/Authorization/POST bodies. No community package.
- [ ] **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): thin owned API client (bearer, pagination, error mapping)`.

---

### Task 7: Company model + migration + token secrecy

**Files:**
- Create: migration `..._create_companies_table.php`, `app/Models/Company.php`
- Modify: the app's log scrubber/context to redact `api_token`
- Test: `tests/Unit/Workable/CompanyModelTest.php`

**Interfaces:**
- `Company` columns (design §4): `name`, `slug` (unique), `subdomain` (unique), `api_token` (text nullable), `scopes` (json nullable), `apply_mode` enum native|redirect default redirect, `credentials_status` enum unverified|valid|invalid default unverified, `last_synced_at`, timestamps. `$casts`: `api_token`=`encrypted`, `scopes`=`array`. `$hidden`=`['api_token']`.

- [ ] **Step 1: Write failing tests** — `token_encrypted_at_rest()` (raw DB value ≠ plaintext; accessor returns plaintext); `token_hidden_from_array_and_json()`; `subdomain_unique()`.
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** migration + model; register `api_token` in the log scrubber (Review Focus #5); if no scrubber mechanism exists, ensure the model is never logged and note it (ruling R1-equivalent — no new error-tracker integration).
- [ ] **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): encrypted-token Company model`.

---

### Task 8: JobPosting + SyncRun models + migrations

**Files:**
- Create: migrations for `job_postings`, `sync_runs`; `app/Models/JobPosting.php`, `app/Models/SyncRun.php`
- Test: `tests/Unit/Workable/JobPostingModelTest.php`

**Interfaces:**
- `JobPosting` (design §4): FK `company_id`, `shortcode`, `external_id`, `title`, `full_title` null, `state`, `department` null, `workplace_type` null, `url`/`application_url`/`shortlink` null, `location` json, `locations` json, `salary` json null, `keywords` json, `content_html` longtext null, `content_hash`, `external_updated_at`, `status` enum open|closed, `last_seen_at`, `closed_at`, `apply_supported` null bool. unique `(company_id, shortcode)`; index `(status, external_updated_at)`. json casts.
- `SyncRun`: `company_id`, started/finished, `status` enum ok|failed|suspicious, `fetched/created/updated/closed` ints, `error` null; `SyncRun::start(Company): self`, `finish(array $counts): void`. Relations `company()`, `jobPostings()`.

- [ ] **Step 1: Write failing tests** — `job_posting_belongs_to_company()`; `shortcode_unique_per_company()`; `sync_run_start_finish_sets_counts_status()`.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): JobPosting and SyncRun models`.

---

### Task 9: SyncCompanyJobs action

**Files:**
- Create: `app/Actions/Workable/SyncCompanyJobs.php`
- Test: `tests/Feature/Workable/SyncCompanyJobsTest.php`

**Interfaces:**
- Consumes: `WorkableClient` (Task 6, `Http::fake`d), `Company`/`JobPosting`/`SyncRun`, `config('workable.sync_empty_guard_min_open')`.
- Produces: `__invoke(Company $company): SyncRun`.

- [ ] **Step 1: Write failing tests** (Review Focus #2–4 + idempotence): `ingests_published_jobs_and_sets_counts()`; `second_run_changes_nothing()` (content not rewritten when hash unchanged); `disappeared_job_is_swept_closed()`; `reappearing_job_reopens()`; `empty_response_with_open_postings_is_suspicious_and_does_not_sweep()`; `failed_fetch_does_not_sweep()` (client throws `TransientFailure`).
- [ ] **Step 2: Run** → FAIL.
- [ ] **Step 3: Implement** design §6 algorithm: `startedAt=now()`; `SyncRun::start`; `listPublishedJobs` (throw → failed, stop, no sweep); empty-guard; chunked upsert on `(company_id, shortcode)` setting fields + `status=open`, `closed_at=null`, `last_seen_at=startedAt`, and `content_html`+`content_hash` only when the hash changed; sweep open postings with `last_seen_at < startedAt` → closed; `finish(counts)`. Per-company cache lock for the duration.
- [ ] **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): idempotent SyncCompanyJobs action`.

---

### Task 10: workable:add-company command (safe token intake)

**Files:**
- Create: `app/Console/Commands/AddCompanyCommand.php`
- Test: `tests/Feature/Workable/AddCompanyCommandTest.php`

**Interfaces:**
- signature `workable:add-company {subdomain} {--name=} {--apply-mode=redirect}`; prompts **secretly** (`$this->secret(...)`) for the token (never an argument), stores it encrypted, runs one `r_jobs` GET (`listPublishedJobs`) to set `credentials_status`.

- [ ] **Step 1: Write failing tests** — `creates_company_with_encrypted_token_from_secret_prompt()`; `blank_token_creates_redirect_only_company_unverified()`; `token_never_written_to_output()`.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement** (on a token, call `listPublishedJobs` once; `valid`/`invalid`; blank → redirect/unverified). **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): add-company command with secret token intake`.

---

### Task 11: Queued sync job, workable:sync command, scheduler

**Files:**
- Create: `app/Jobs/Workable/SyncCompanyJobsJob.php`, `app/Console/Commands/SyncCommand.php`; Modify: `routes/console.php`
- Test: `tests/Feature/Workable/SyncCommandTest.php`

**Interfaces:**
- `SyncCompanyJobsJob` (`ShouldQueue`, ctor `Company`); `workable:sync {--company=}` dispatches one job per company; schedule every 30 min `withoutOverlapping()->onOneServer()`.

- [ ] **Step 1: Write failing tests** — `sync_command_dispatches_one_job_per_company()` (`Queue::fake`); `sync_command_can_target_one_company()`; `job_runs_the_action()`.
- [ ] **Step 2: Run** → FAIL. **Step 3: Implement.** **Step 4: Run** → PASS; full suite green.
- [ ] **Step 5: Commit** — `feat(workable): queued sync job, command, and schedule`.

---

### Task 12: Phase gate

- [ ] **Step 1:** `php artisan test` → all green, **zero known-red** (Task 1 fixed them), no network.
- [ ] **Step 2:** With `WORKABLE_FAKE=true`: `php artisan workable:add-company demo --name=Demo` then `php artisan workable:sync` twice; confirm the second run reports zero creates/updates/closes.
- [ ] **Step 3:** Add a "Workable ingest" section to `README.md` (env, add-company, sync, that live subdomain/token/fixtures are pending).
- [ ] **Step 4: Commit** — `docs(workable): ingest setup notes; W0+W1 gate green`.

---

## Self-Review
1. **Spec coverage (W0+W1):** §2 facts → Tasks 4/5/6; §3/§5 client → Task 6; §4 model (companies/job_postings/sync_runs; job_applications deferred) → Tasks 7/8; §6 sync → Tasks 9/11; token secrecy §8 → Task 7; config §11 → Task 2; auth-fix delta #3 → Task 1; §12 W0/W1 done-criteria → Tasks 9/12. W2 public site, W3 apply, W4 hardening, W5 polish → later plans.
2. **Step scan:** each code step carries a signature or exact command; bodies left to the implementer except the sync algorithm and the Bearer/pagination specifics.
3. **Type consistency:** `JobPostData`/`QuestionData`/`ApplicationSchema`/`SubmissionResult`, `WorkableClient` method signatures, `SyncCompanyJobs::__invoke(Company):SyncRun`, `Company` (`subdomain`,`api_token`) used identically across Tasks 5–11.
4. **Review Focus:** pagination→T6; empty/failed/sweep→T9; token leak→T7. Covered.
5. **Proportion:** signatures + test names, not full bodies; proportionate to ~14 files + an auth fix.

## Dependency note
Order: **1 (auth fix) → 2 → 3 → 4 → 5 → 7 → 6 → 8 → 9 → 10 → 11 → 12.** (Task 6 client depends on Task 7 `Company`, so 7 precedes 6.) Sequential, interface-coupled; never parallel implementers.
