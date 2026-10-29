# Greenhouse Ingest Foundation (Phase 0–1) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the server-side foundation that ingests published Greenhouse jobs for one or more employers into our database, with a thin owned API client, encrypted per-employer key storage, and an idempotent sync — all testable with zero network access.

**Architecture:** Thin controllers are out of scope here; this plan builds the lower layers of spec §3: `GreenhouseClient` (the only class that knows URLs/auth/raw shapes, returns DTOs, throws typed exceptions) → `SyncCompanyJobs` action → Eloquent models + a queued job + an Artisan command. Raw API arrays never cross the client boundary. Employer keys live only in the DB, encrypted.

**Tech Stack:** Laravel 12, PHP 8.2+, MySQL, database queue + database cache (no Redis), Laravel HTTP client (`Http::fake` for tests), Pest.

**Spec:** `docs/superpowers/specs/2026-10-24-greenhouse-integration-design.md` (and its authoritative source, `Greenhouse Job Board Integration — Implementation Spec.docx`). Executors read both.

## Global Constraints

- Base URL `https://boards-api.greenhouse.io/v1`; GET endpoints need no auth; POST uses HTTP Basic (Job Board key as username, empty password). *(Not exercised in this plan — no POST here — but the client is built for it.)*
- Models are named `Company`, `JobPosting`, `SyncRun` — **never** `Job` or `Application` (collides with framework classes). Spec §3/§14.
- Employer API key: `encrypted` cast, in `$hidden`, **never** in `.env`, logs, exceptions, or git. Spec §8/§11/§14.
- No secrets or candidate PII in logs — log IDs only. Spec §0/§8.
- CI never touches the real Greenhouse API. All tests use fixtures or `Http::fake`. Spec §10.
- Board token is a per-company DB value; the app must boot and run with none configured. Design Open-item #1.
- Connect timeout 5s, total timeout 15s; descriptive User-Agent with a contact address. Spec §5.
- DRY, YAGNI (build only companies/job_postings/sync_runs here — `job_applications` belongs to Phase 3), TDD, frequent commits.

## Review Focus

Inputs/failure modes the spec implies that these tasks must pin with tests:

1. **Empty `jobs` 200 response** while the company still has ≥ N open postings → treat as suspicious, alert, **do not sweep** (spec §6 guard). Pinned in Task 8.
2. **A failed/throwing `listJobs`** → the run fails and the sweep never runs (no mass false-closure). Pinned in Task 8.
3. **A job that disappears** from the board → swept to `closed` with `closed_at`, but only after a fully successful fetch; a reappearing job reopens. Pinned in Task 8.
4. **Malformed payloads** (missing `jobs` array; a job with no `id`/`title`) → `GreenhouseException`, while unknown extra fields are ignored so the API can evolve. Pinned in Task 4.
5. **The API key leaking** via model serialization or logs → `toArray()`/`toJson()` on a `Company` never contains the key, and the key is registered in the log scrubber. Pinned in Task 6.

---

### Task 1: Greenhouse config and env

**Files:**
- Create: `config/greenhouse.php`
- Modify: `.env.example` (append non-secret keys), `.env` (append non-secret keys)
- Test: `tests/Feature/Greenhouse/ConfigTest.php`

**Interfaces:**
- Produces: `config('greenhouse.base_url')`, `.user_agent`, `.fake` (bool), `.sync_empty_guard_min_open` (int, default 5), `.schema_cache_minutes` (int, default 15), `.application.max_file_mb` (int, default 5), `.application.payload_retention_days` (int, default 30).

- [ ] **Step 1: Write the failing test** — `config_has_expected_defaults()`: asserts `base_url === 'https://boards-api.greenhouse.io/v1'`, `sync_empty_guard_min_open === 5`, `schema_cache_minutes === 15`, `fake === false` by default.
- [ ] **Step 2: Run test, verify it fails** — `php artisan test --filter=ConfigTest` → FAIL (file/config missing).
- [ ] **Step 3: Create `config/greenhouse.php`** returning the keys above, each reading its `GREENHOUSE_*` / `APPLICATION_*` env var with the spec §11 default. Append the non-secret vars to `.env.example` and `.env` (no key value — keys are never in env).
- [ ] **Step 4: Run test, verify it passes.**
- [ ] **Step 5: Commit** — `feat(greenhouse): add config and env scaffolding`.

---

### Task 2: Typed exceptions

**Files:**
- Create: `app/Integrations/Greenhouse/Exceptions/GreenhouseException.php` and subclasses `CredentialsRejected`, `JobNotFound`, `RateLimited`, `TransientFailure`, `AmbiguousOutcome`, `PayloadRejected`.
- Test: `tests/Unit/Greenhouse/ExceptionsTest.php`

**Interfaces:**
- Produces: `GreenhouseException extends \RuntimeException`; all others extend it. `RateLimited` has `public readonly ?int $retryAfter` via constructor `__construct(?int $retryAfter = null, ...)` and `retryAfter(): ?int`.

- [ ] **Step 1: Write the failing test** — `rate_limited_carries_retry_after()`: `(new RateLimited(retryAfter: 30))->retryAfter() === 30`; and `credentials_rejected_is_a_greenhouse_exception()`: `new CredentialsRejected() instanceof GreenhouseException`.
- [ ] **Step 2: Run, verify fail.**
- [ ] **Step 3: Implement the exception classes** in the namespace `App\Integrations\Greenhouse\Exceptions`.
- [ ] **Step 4: Run, verify pass.**
- [ ] **Step 5: Commit** — `feat(greenhouse): typed exception hierarchy`.

---

### Task 3: Capture/seed fixtures

**Files:**
- Create: `tests/Fixtures/greenhouse/jobs.json` (a `GET /jobs?content=true` body, ≥ 2 jobs incl. one with missing optional fields and HTML-entity-encoded `content`), `tests/Fixtures/greenhouse/job_with_questions.json` (a `GET /jobs/{id}?questions=true` body with `questions`, `location_questions`, and at least one of each observed field type: `input_text`, `input_hidden`, `input_file`, `textarea`, `multi_value_single_select`, `multi_value_multi_select`), `tests/Fixtures/greenhouse/README.md`.
- Test: none (data files).

**Interfaces:**
- Produces: fixture paths consumed by Tasks 4, 5, 8.

- [ ] **Step 1:** Build the fixtures from the documented shapes in spec §2. In `README.md` record that these are representative stand-ins and **must be replaced by a live capture** (`curl` the pilot board) once the real board token arrives — "where this document and a real payload disagree, the real payload wins" (spec §0).
- [ ] **Step 2: Commit** — `test(greenhouse): representative board fixtures (replace with live capture)`. (No real candidate PII — spec §14.)

---

### Task 4: DTOs

**Files:**
- Create: `app/Integrations/Greenhouse/DTO/JobPostData.php`, `QuestionData.php`, `ApplicationSchema.php`, `SubmissionResult.php`
- Test: `tests/Unit/Greenhouse/DtoTest.php`

**Interfaces:**
- Produces:
  - `JobPostData::fromApi(array $j): self` with readonly props `int $externalId, string $title, ?string $locationName, string $absoluteUrl, ?string $contentHtml, array $departments, array $offices, array $metadata, ?\DateTimeImmutable $externalUpdatedAt`. Throws `GreenhouseException` if `id` or `title` missing. Ignores unknown fields.
  - `QuestionData` with `string $label, bool $required, array $fields` where each field is `['name'=>string,'type'=>string,'options'=>array<{label,value}>]`.
  - `ApplicationSchema::fromApi(array $job): self` holding `QuestionData[]` from `questions` + `location_questions`.
  - `SubmissionResult` with `int $status`.

- [ ] **Step 1: Write failing tests** — `parses_job_from_fixture()` (load `jobs.json`, map first job, assert fields incl. decoded content); `parses_job_with_missing_optional_fields()` (location null, no crash); `throws_on_job_without_id_or_title()`; `builds_schema_with_all_field_types()` from `job_with_questions.json`.
- [ ] **Step 2: Run, verify fail.**
- [ ] **Step 3: Implement the DTOs.** `content` is HTML-entity-decoded once here (`html_entity_decode`) per spec §6. No sanitization (that is render-time, Phase 2).
- [ ] **Step 4: Run, verify pass.**
- [ ] **Step 5: Commit** — `feat(greenhouse): boundary DTOs`.

---

### Task 5: GreenhouseClient

**Files:**
- Create: `app/Integrations/Greenhouse/GreenhouseClient.php`
- Test: `tests/Unit/Greenhouse/GreenhouseClientTest.php`

**Interfaces:**
- Consumes: DTOs (Task 4), exceptions (Task 2), config (Task 1).
- Produces:
  - `listJobs(string $boardToken): \Illuminate\Support\Collection` // of `JobPostData`
  - `getJob(string $boardToken, int $jobId, bool $withQuestions = false): JobPostData`
  - `submitApplication(string $boardToken, int $jobId, string $apiKey, array $payload): SubmissionResult`
  - Constructed with an injected `Illuminate\Http\Client\Factory`.

- [ ] **Step 1: Write failing tests** (all with `Http::fake`): `list_jobs_calls_correct_url_with_content_true()`; `get_job_adds_questions_param_when_requested()`; `submit_sends_basic_auth_key_as_username_blank_password()` (assert `Authorization` header equals `'Basic '.base64_encode($key.':')`); status-mapping table test asserting 401/403→`CredentialsRejected`, 404→`JobNotFound`, 429→`RateLimited` (with `Retry-After`), 422→`PayloadRejected`, read-timeout/5xx-on-POST→`AmbiguousOutcome`, 5xx-on-GET→`TransientFailure`, connection-failure-before-send→`TransientFailure`; `post_is_attempted_exactly_once()` (fake asserts a single request); `missing_jobs_array_throws()`.
- [ ] **Step 2: Run, verify fail.**
- [ ] **Step 3: Implement the client** per the spec §5 sketch: private `http()` with base URL, `acceptJson`, user-agent, 5s/15s timeouts; GETs `->retry(3, 300, fn($e)=>$this->isTransient($e), throw:false)`; POST never retries in the client; `assertSuccessful($res, bool $isSubmission=false)` maps status→exception; never log request bodies, the `Authorization` header, or POST response bodies. When `config('greenhouse.fake')` is true, short-circuit to fixtures and treat submissions as 201. Do **not** use a community package.
- [ ] **Step 4: Run, verify pass.**
- [ ] **Step 5: Commit** — `feat(greenhouse): thin owned API client with error mapping`.

---

### Task 6: Company model + migration + key secrecy

**Files:**
- Create: migration `..._create_companies_table.php`, `app/Models/Company.php`
- Modify: `config/logging.php` or a log context scrubber registration (whichever the app uses) to redact the key.
- Test: `tests/Unit/Greenhouse/CompanyModelTest.php`

**Interfaces:**
- Produces: `Company` with columns from spec §4 (`name`, `slug` unique, `board_token` unique, `gh_api_key` nullable text, `apply_mode` enum native|redirect default redirect, `credentials_status` enum unverified|valid|invalid default unverified, `last_synced_at`, timestamps). `$casts['gh_api_key'='encrypted']`, `$hidden=['gh_api_key']`.

- [ ] **Step 1: Write failing tests** — `api_key_is_encrypted_at_rest()` (raw DB value ≠ plaintext, accessor returns plaintext); `api_key_hidden_from_array_and_json()` (`$company->toArray()` and `toJson()` omit `gh_api_key`); `board_token_is_unique()`.
- [ ] **Step 2: Run, verify fail.**
- [ ] **Step 3: Implement** the migration and model; register the key name in the log scrubber (Review Focus #5).
- [ ] **Step 4: Run, verify pass.**
- [ ] **Step 5: Commit** — `feat(greenhouse): encrypted-key Company model`.

---

### Task 7: JobPosting + SyncRun models + migrations

**Files:**
- Create: migrations `..._create_job_postings_table.php`, `..._create_sync_runs_table.php`; `app/Models/JobPosting.php`, `app/Models/SyncRun.php`
- Test: `tests/Unit/Greenhouse/JobPostingModelTest.php`

**Interfaces:**
- Consumes: `Company` (Task 6).
- Produces: `JobPosting` per spec §4 (FK `company_id`, `external_id` bigint, `title`, `location_name` nullable, `absolute_url`, `content_html` longtext nullable, `departments`/`offices`/`metadata` json, `external_updated_at`, `content_hash`, `status` enum open|closed, `last_seen_at`, `closed_at`, `apply_supported` nullable bool; unique `(company_id, external_id)`; index `(status, external_updated_at)`). `SyncRun` per §4 with `SyncRun::start(Company): self` and `finish(array $counts): void` and `status` enum ok|failed|suspicious. `company()`/`jobPostings()` relations.

- [ ] **Step 1: Write failing tests** — `job_posting_belongs_to_company()`; `external_id_unique_per_company()`; `sync_run_start_and_finish_sets_counts_and_status()`.
- [ ] **Step 2: Run, verify fail.**
- [ ] **Step 3: Implement** migrations, models, relations, and the `SyncRun` helpers.
- [ ] **Step 4: Run, verify pass.**
- [ ] **Step 5: Commit** — `feat(greenhouse): JobPosting and SyncRun models`.

---

### Task 8: SyncCompanyJobs action (upsert + sweep + guards)

**Files:**
- Create: `app/Actions/Greenhouse/SyncCompanyJobs.php`
- Test: `tests/Feature/Greenhouse/SyncCompanyJobsTest.php`

**Interfaces:**
- Consumes: `GreenhouseClient` (Task 5, injected, `Http::fake`d in tests), `Company`/`JobPosting`/`SyncRun` (Tasks 6–7), `config('greenhouse.sync_empty_guard_min_open')`.
- Produces: `__invoke(Company $company): SyncRun`.

- [ ] **Step 1: Write failing tests** covering Review Focus #1–4:
  - `ingests_pilot_board_and_sets_counts()` (from fixtures).
  - `second_run_changes_nothing()` (idempotent — counts all zero, no content rewrite when hash unchanged).
  - `disappeared_job_is_swept_closed()` (sets `closed_at`).
  - `reappearing_job_reopens()` (status back to open, `closed_at` null).
  - `empty_response_with_open_postings_is_suspicious_and_does_not_sweep()` (run `suspicious`, existing open postings untouched).
  - `failed_fetch_does_not_sweep()` (client throws `TransientFailure` → run `failed`, postings untouched).
- [ ] **Step 2: Run, verify fail.**
- [ ] **Step 3: Implement** the algorithm from spec §6: record `startedAt`; `SyncRun::start`; `listJobs` (throw → run failed, stop, no sweep); empty-guard (`jobs==0 && open>=min_open` → suspicious, stop, no sweep); chunked upsert on `(company_id, external_id)` setting fields + `status=open`, `closed_at=null`, `last_seen_at=startedAt`, and `content_html`+`content_hash` only when the hash changed; sweep open postings with `last_seen_at < startedAt` → closed; `run.finish(counts)`. Hold a per-company cache lock for the duration (database cache).
- [ ] **Step 4: Run, verify pass.**
- [ ] **Step 5: Commit** — `feat(greenhouse): idempotent SyncCompanyJobs action`.

---

### Task 9: greenhouse:add-company command (safe key intake)

**Files:**
- Create: `app/Console/Commands/AddCompanyCommand.php`
- Test: `tests/Feature/Greenhouse/AddCompanyCommandTest.php`

**Interfaces:**
- Consumes: `Company` (Task 6), `GreenhouseClient` (Task 5).
- Produces: signature `greenhouse:add-company {token} {--name=} {--apply-mode=redirect}`; prompts **secretly** for the API key (`$this->secret(...)`, never an argument/option so it stays out of shell history), stores it encrypted, runs one GET-level validation (`listJobs`) and sets `credentials_status` accordingly.

- [ ] **Step 1: Write failing tests** — `creates_company_with_encrypted_key_from_secret_prompt()` (`expectsQuestion`/secret, assert row created, key decrypts, `toArray` omits key); `blank_key_creates_redirect_only_company_unverified()`; `key_is_never_written_to_output()` (command output contains no key substring).
- [ ] **Step 2: Run, verify fail.**
- [ ] **Step 3: Implement** the command; on a provided key call `listJobs` once to confirm the token resolves (sets `credentials_status`; a key can't be fully validated without a real application per §2, so GET-reachability + stored-for-later is the v1 check). Empty key → `apply_mode=redirect`, `credentials_status=unverified`.
- [ ] **Step 4: Run, verify pass.**
- [ ] **Step 5: Commit** — `feat(greenhouse): add-company command with secret key intake`.

---

### Task 10: Queued sync job, greenhouse:sync command, scheduler

**Files:**
- Create: `app/Jobs/Greenhouse/SyncCompanyJobsJob.php`, `app/Console/Commands/SyncCommand.php`
- Modify: `routes/console.php` (schedule) 
- Test: `tests/Feature/Greenhouse/SyncCommandTest.php`

**Interfaces:**
- Consumes: `SyncCompanyJobs` (Task 8), `Company` (Task 6).
- Produces: `SyncCompanyJobsJob` (queued, `ShouldQueue`, constructor `Company`); `greenhouse:sync {--company=}` dispatches one job per company; a scheduled entry every 30 min guarded by `withoutOverlapping()` and `onOneServer()`.

- [ ] **Step 1: Write failing tests** — `sync_command_dispatches_one_job_per_company()` (`Queue::fake`, assert N pushed); `sync_command_can_target_one_company()`; `job_runs_the_action()` (`Bus`/action spy).
- [ ] **Step 2: Run, verify fail.**
- [ ] **Step 3: Implement** the job, command, and schedule registration (every 30 min, `withoutOverlapping()->onOneServer()`).
- [ ] **Step 4: Run, verify pass.**
- [ ] **Step 5: Commit** — `feat(greenhouse): queued sync job, command, and schedule`.

---

### Task 11: Phase gate — full suite + idempotence proof

**Files:** none (verification + docs).

- [ ] **Step 1:** Run `php artisan test` — expect all green, no network.
- [ ] **Step 2:** With `GREENHOUSE_FAKE=true`, run `php artisan greenhouse:add-company demo --name="Demo"` then `php artisan greenhouse:sync` twice; confirm the second run reports zero creates/updates/closes (idempotence, Review Focus #3 + spec P1 done-criteria).
- [ ] **Step 3:** Add a short "Greenhouse ingest" section to `README.md`: env vars, adding an employer, running sync, and that live fixtures/token are still pending.
- [ ] **Step 4: Commit** — `docs(greenhouse): ingest setup notes; phase 0–1 gate green`.

---

## Self-Review

1. **Spec coverage (Phase 0–1 scope):** §2 ground-truth → Tasks 4/5; §3 layout (client/actions/jobs/models/commands) → Tasks 2,4,5,8,9,10; §4 data model (companies/job_postings/sync_runs; job_applications deferred to P3 per YAGNI) → Tasks 6,7; §5 client → Task 5; §6 sync → Tasks 8,10; §8 key secrecy/log scrubbing → Task 6; §10 testing (Http::fake, no network, exactly-once POST) → Tasks 5,8; §11 config → Task 1; §12 P0/P1 done-criteria → Tasks 3,8,11. Phases §7 (apply), §8 render-sanitization, §9 health/alerts, §13 AI → **out of scope, later plans.**
2. **Step scan:** each code step carries a signature or exact command; bodies are left to the implementer except where the spec fixes an algorithm (sync, §6) or exact values (Basic-auth header). OK.
3. **Type consistency:** `JobPostData`, `QuestionData`, `ApplicationSchema`, `SubmissionResult`, `SyncRun::start/finish`, `SyncCompanyJobs::__invoke(Company): SyncRun`, client method signatures are used identically across tasks. OK.
4. **Review Focus:** all five mapped to Tasks 4, 6, 8. OK.
5. **Proportion:** plan uses signatures + test names, not full bodies; length is proportionate to a foundation that spans ~11 files. OK.

## Dependency note
Tasks are sequential and interface-coupled (each builds on the prior task's types): 1→2→3→(4)→5→6→7→8→9→10→11.
