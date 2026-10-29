# Greenhouse Job Board Integration — Design

Date: 2026-10-24
Status: Approved 2026-10-24 (board token pending — see Open items #1)
Authoritative source spec: `Greenhouse Job Board Integration — Implementation Spec.docx` (repo root, v1.0). This document does **not** restate that spec; it confirms it against the actual NearbyWorks codebase, records the decisions made in brainstorming, and lists what the builder still needs. Where this document and the spec disagree, this document wins; where a real Greenhouse payload and either document disagree, the payload wins (spec §0).

## Intent and why

Turn NearbyWorks into a Greenhouse-backed job board: pull one or more employers' published Greenhouse jobs into our DB, show them on public list/detail pages, and let registered candidates apply — natively (a form we build from Greenhouse's question schema and POST server-side with the employer's key) when we can guarantee a correct submission, and by redirect to the Greenhouse-hosted form otherwise. The browser never talks to Greenhouse; the employer's key never leaves the server. Submission is at-most-once, with ambiguous outcomes resolved by a human, because a duplicate application damages the employer relationship more than a rare manual resubmit.

## Goals
- Full candidate journey: discover → account → view → apply → track status.
- Multi-employer, switchable without a redeploy.
- The whole public site works with **zero credentials** (redirect-apply everywhere).
- Native apply where credentials + schema allow it.

## Non-goals / anti-goals (from spec §1, §14 — do not build)
- Scraping `my.greenhouse.io` or the hosted form; defeating its reCAPTCHA/fingerprint.
- The Harvest API or any non–Job-Board Greenhouse API.
- Calling Greenhouse from browser JS or exposing the key to the client.
- Auto-retrying a POST after timeout/5xx. Trusting Greenhouse to enforce required fields.
- Rendering employer HTML unsanitized. Naming models `Job`/`Application`. Keys in `.env`.

## Constraints
- API contract per spec §2 (base URL, board token, GET endpoints are unauthenticated, POST uses HTTP Basic with the Job Board key as username + blank password).
- CI never touches or POSTs to the real Greenhouse API (spec §10).
- No secrets or candidate PII in logs; log IDs only (spec §0, §8).

## Approaches considered (credential storage — the one open design choice)

The overall build approach is the spec itself (a thin owned client → actions → models/queue). The one decision brainstorming surfaced was **where the employer API key lives**:

- **A. DB, encrypted per company (CHOSEN).** `companies.gh_api_key` with Laravel `encrypted` cast, in `$hidden`, scrubbed from logs/error tracker. Switching employers = add/activate a `companies` row via `php artisan greenhouse:add-company`; no redeploy. Matches spec §8/§11/§14.
- B. Single key in `.env`. Rejected: holds only one employer (defeats the switching goal), needs a redeploy to change, and is explicitly forbidden by spec §14 and leak-prone per §8.
- C. DB source-of-truth + a dev-only, gitignored bootstrap seeder. Available later as a local-demo convenience; production still reads keys only from the DB.

Decision: **A**, confirmed by the user on 2026-10-24.

## Decisions and codebase deltas (these amend the spec's stack assumptions)

1. **Stack.** Laravel 12 / PHP 8.2+ (spec assumed 11+/8.3 — fine). MySQL (`nearbyworks`).
2. **Queue + cache = database driver** (already configured; `QUEUE_CONNECTION=database`, `CACHE_STORE=database`). **No Redis.** The spec's cache-lock, `withoutOverlapping()`, and scheduling all work on the database driver. *My call.*
3. **No existing job UI to "reuse."** The spec references "our existing job listing and job detail interfaces"; the codebase has none (only Home/About/Contact/auth/dashboard/profile). We will **build** list/detail/apply/status pages using the existing NearbyWorks design system — `components/site-header`, `site-footer`, `layout`, Tailwind, and the `build-frontend.mjs` pipeline into `public/asset/nearbyworks/`. *My call.*
4. **Candidate accounts = existing Breeze `User` — but registration is currently BROKEN (verified 2026-10-24).** The `users` migration defines only a non-nullable `name`, while `User::$fillable` and `RegisteredUserController::store()` write `firstName`/`lastName` (columns that exist in no migration) and never set `name`. So `User::create(...)` fails on every registration. This is a *broken write path*, not the cosmetic `name`-vs-first/last mismatch the earlier draft implied. P2 (accounts) and all of P3 (apply-form prefill, the `unique(job_posting_id, email)` dedupe) sit on working accounts, so this must be fixed before P2. **Recommended fix (needs user approval — scope):** add a migration for `first_name`/`last_name` (drop or backfill `name`), align the model/validation, and add a registration feature test. See Open item #3.
5. **Config lives in `config/greenhouse.php`** driven by env for non-secrets only (base URL, user-agent, FAKE flag, guards, cache minutes, file cap, retention) per spec §11. Keys never in env.
6. **Build order = spec §12 phases P0–P5.** P0–P2 is the demo floor (no credentials). P3 (native apply) is the differentiator and needs the employer key. We do not start P3 until P2 is solid, nor the optional AI layer (§13) until P3 works or is dropped.

## What's left to the builder
- Everything in spec §3–§13, following the file layout in §3 and the state machine in §7.5, built phase by phase per §12 with the acceptance criteria as the done-gates.
- Capture live fixtures (spec §0/P0) into `tests/Fixtures/greenhouse/` from the pilot board **once the board token is provided**, and reconcile the "Unverified" list in spec §2 against them.

## Open items for the user (answers change what gets built)
1. **Board token** (the public slug) for the pilot employer — **not ready yet**. The system must boot and run fully without it: the board token is a configurable per-company slot (empty/placeholder until provided), and with no valid credentials the whole site runs in redirect-apply mode (and `GREENHOUSE_FAKE=true` serves fixtures for local dev/demo). P0 live-fixture capture and P1 live-sync verification are deferred until the real token arrives; until then we work against committed sample/`FAKE` fixtures. Provide the token later via `php artisan greenhouse:add-company {token}`.
2. **API key handover timing.** Plan: build the encrypted `companies` store + `greenhouse:add-company` first, then take the key via the command (prompted, never pasted into a file/chat/logs). Confirm that's how you'll hand it over.
3. **Broken registration (delta #4) — needed before P2.** Fold the fix into this project (recommended: add `first_name`/`last_name` migration + align model/controller/validation + a registration test), or is there unshipped work that already makes accounts function? This is not optional-cosmetic; accounts do not work as committed.
4. **Native-apply authorization granularity — needed before P3.** Is native apply all-or-nothing per employer (current data model: `companies.apply_mode` + `job_postings.apply_supported`), or must it be per-job (a subset of an employer's published jobs)? Per-job needs an extra gating column + an admin way to set it. Spec §12 requires "which jobs we may accept applications for" in writing before P3.
5. **Data-protection regime + consent — needed before P3/P4.** GDPR, CCPA, both, or other? This sets the actual privacy-notice text, the starting `consent_version`, how `data_compliance` consent is rendered/returned, and whether an explicit candidate-data-deletion workflow is required (vs. relying on the 30-day purge).
6. **Ops channel + admin surface — needed before P4.** Where do `needs_review`/`failed_credentials`/`suspicious` alerts go (email address or Slack webhook)? Is an admin UI in scope, or is CLI + a status endpoint enough? The at-most-once safety property depends on a human actually receiving alerts and running `applications:requeue`.

### Builder-check outcome (2026-10-24)
A fresh reviewer read this design + the source spec. Confirmed real: the broken registration (above; upgraded from cosmetic to blocker). Confirmed false and rejected: a claim that the NearbyWorks frontend pipeline (`resources/FRONTEND.md`, `resources/build-frontend.mjs`, `public/asset/nearbyworks/`) does not exist — all three exist and delta #3 stands; P2 uses that custom pipeline, not stock `public/build`. Items 4–6 above are the reviewer's remaining questions, deferred to their phases; none blocks Phase 0–1.
