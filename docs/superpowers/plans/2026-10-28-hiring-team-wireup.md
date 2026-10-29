# Hiring Team Wire-Up Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the already-written Hiring Team backend (invite, accept/decline, manage members) actually reachable: routes, two Blade views, three bug fixes, and test coverage.

**Architecture:** `OrganizationTeamController` (authenticated, org-scoped) and `TeamInvitationController` (public, token-based) are wired into `routes/web.php` under the live `organisations.*`/`team-invitations.*` naming. No business logic changes to `OrganizationTeamService`, `OrganizationPolicy`, `OrganizationMemberPolicy`, or the form requests — only naming/bug fixes and the two views they already expect.

**Tech Stack:** Laravel 12, Pest 3 (`tests/Feature/`, SQLite in-memory via RefreshDatabase), Blade with the project's `nw-*` CSS component system (no Tailwind utility classes in templates).

**Spec:** `docs/superpowers/specs/2026-10-28-hiring-team-wireup-design.md`

## Global Constraints

- Route names follow the live `organisations.*` convention, not the leftover `employer.*` naming.
- `team-invitations.*` is the exact required name prefix for the three public invitation routes — `InitializeOrganizationContext` already special-cases `$request->routeIs('team-invitations.*')`.
- Every `nw-table`'s primary (first) `<td>` per row must carry `class="nw-table__main"` for the mobile card layout (established house rule this session).
- No changes to `OrganizationTeamService`, `OrganizationPolicy`, `OrganizationMemberPolicy`, `StoreTeamInvitationRequest`, `UpdateOrganizationMemberRequest`, or `AcceptTeamInvitationRequest` — they are correct as written.
- Reuse the existing `team-invitations` named rate limiter (`RateLimiter::for('team-invitations', ...)` in `app/Providers/AppServiceProvider.php:94`, 5/min by user-or-IP) via `throttle:team-invitations` middleware — do not define a new limiter.

## Review Focus

- Implicit `{organizationMember}` route-model binding runs before `InitializeOrganizationContext` sets the active organization (the same ordering issue already worked around for `{applicationForm}` in `routes/web.php:21`), so `OrganizationScope`'s `whereRaw('1 = 0')` would 404 every legitimate update/destroy unless bound unscoped the same way.
- An HR Manager inviting or re-assigning someone to the Owner role must be rejected (`OrganizationTeamService::authorizeRole`), not silently accepted.
- The sole active Owner must not be demotable or removable (`protectLastOwner`) — both through `updateMember` and `destroyMember`.
- An invitation token that's expired, already used, or presented with a mismatched email must be rejected (410/validation error), never silently accepted — on both `accept` and `decline`.
- A Recruiter/Hiring Manager/Interviewer (any role without `manage_team`) must get 403 on every one of the four `organisations.team.*` routes, not just the index page — permission must be enforced per-route, not just where it's convenient to test.

---

### Task 1: Routes, model binding, and the two pre-existing bugs

**Files:**
- Modify: `routes/web.php` (add `Route::bind('organizationMember', ...)` near line 21–22; add four routes inside the existing `organisations.` group at lines 78–89; add three top-level public routes near the `workable-jobs.*` routes at lines 38–47)
- Modify: `app/Http/Controllers/OrganizationTeamController.php:57,71,84,92` (view name + three `to_route` calls)
- Modify: `app/Notifications/TeamInvitationNotification.php:87` (`isEmployer()` → `isOrganization()`) and `:69` (cosmetic: `'Employer team invitation'` → `'Organization team invitation'`)
- Modify: `tests/Pest.php` (replace the unused placeholder `function something() {}` with the two shared helpers below, so they're available to every test file regardless of load order)
- Test: `tests/Feature/Organizations/TeamManagementTest.php` (new)

**Interfaces:**
- Consumes: `OrganizationTeamController::{index,storeInvitation,updateMember,destroyMember}`, `TeamInvitationController::{show,accept,decline}`, `OrganizationProvisioner::ensureForEmployer(User): Organization` (test helper dependency), `OrganizationMember`, `TeamInvitation`, `OrganizationRole`, `OrganizationMemberStatus`, `TeamInvitationStatus` — all exactly as they exist today, unmodified.
- Produces: route names `organisations.team.index`, `organisations.team.invitations.store`, `organisations.team.members.update`, `organisations.team.members.destroy`, `team-invitations.show`, `team-invitations.accept`, `team-invitations.decline` — every later task and view references these exact names.

- [ ] **Step 1: Add shared test helpers to `tests/Pest.php`**

Replace the unused `function something() {}` placeholder with:

```php
use App\Enums\OrganizationRole;
use App\Enums\OrganizationMemberStatus;
use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Services\OrganizationProvisioner;
use App\Support\OrganizationContext;

/** @return array{0: Organization, 1: User} */
function organizationWithOwner(): array
{
    $owner = User::factory()->organization()->create();
    $organization = app(OrganizationProvisioner::class)->ensureForEmployer($owner);

    return [$organization, $owner];
}

function addOrganizationMember(Organization $organization, OrganizationRole $role, OrganizationMemberStatus $status = OrganizationMemberStatus::Active): OrganizationMember
{
    $user = User::factory()->organization()->create();

    return app(OrganizationContext::class)->runInOrganization($organization, fn () => OrganizationMember::create([
        'organization_id' => $organization->id,
        'user_id' => $user->id,
        'role' => $role,
        'status' => $status,
    ]));
}
```

(Add the `use` statements to the top of `tests/Pest.php` alongside its existing ones.)

- [ ] **Step 2: Write the failing tests**

Create `tests/Feature/Organizations/TeamManagementTest.php`:

```php
<?php

use App\Enums\OrganizationRole;
use App\Models\OrganizationMember;
use App\Models\TeamInvitation;
use Illuminate\Support\Facades\Notification;

test('a recruiter cannot view or manage the hiring team', function () {
    [$organization, $owner] = organizationWithOwner();
    $recruiterMember = addOrganizationMember($organization, OrganizationRole::Recruiter);
    $otherMember = addOrganizationMember($organization, OrganizationRole::HiringManager);

    $this->actingAs($recruiterMember->user)->get(route('organisations.team.index'))->assertForbidden();
    $this->actingAs($recruiterMember->user)->post(route('organisations.team.invitations.store'), ['email' => 'new@example.com', 'role' => 'recruiter'])->assertForbidden();
    $this->actingAs($recruiterMember->user)->patch(route('organisations.team.members.update', $otherMember), ['role' => 'recruiter'])->assertForbidden();
    $this->actingAs($recruiterMember->user)->delete(route('organisations.team.members.destroy', $otherMember))->assertForbidden();
});

test('an owner can invite a new teammate', function () {
    Notification::fake();
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)
        ->post(route('organisations.team.invitations.store'), ['email' => 'teammate@example.com', 'role' => 'recruiter'])
        ->assertRedirect(route('organisations.team.index'))
        ->assertSessionHasNoErrors();

    expect(TeamInvitation::where('organization_id', $organization->id)->where('email', 'teammate@example.com')->exists())->toBeTrue();
});

test('an hr manager cannot invite someone as owner', function () {
    [$organization, $owner] = organizationWithOwner();
    $hr = addOrganizationMember($organization, OrganizationRole::HrManager)->user;

    $this->actingAs($hr)
        ->post(route('organisations.team.invitations.store'), ['email' => 'new@example.com', 'role' => 'owner'])
        ->assertSessionHasErrors();
});

test('an owner can change a member role and status', function () {
    [$organization, $owner] = organizationWithOwner();
    $member = addOrganizationMember($organization, OrganizationRole::Recruiter);

    $this->actingAs($owner)
        ->patch(route('organisations.team.members.update', $member), ['role' => 'hiring_manager', 'status' => 'suspended'])
        ->assertRedirect(route('organisations.team.index'));

    expect($member->fresh()->role)->toBe(OrganizationRole::HiringManager);
    expect($member->fresh()->status)->toBe(OrganizationMemberStatus::Suspended);
});

test('an owner can remove a member', function () {
    [$organization, $owner] = organizationWithOwner();
    $member = addOrganizationMember($organization, OrganizationRole::Recruiter);

    $this->actingAs($owner)
        ->delete(route('organisations.team.members.destroy', $member))
        ->assertRedirect(route('organisations.team.index'));

    expect($member->fresh()->trashed())->toBeTrue();
});

test('the sole active owner cannot be demoted or removed', function () {
    [$organization, $owner] = organizationWithOwner();
    $ownerMembership = OrganizationMember::where('organization_id', $organization->id)->where('user_id', $owner->id)->first();

    $this->actingAs($owner)
        ->patch(route('organisations.team.members.update', $ownerMembership), ['role' => 'recruiter', 'status' => 'active'])
        ->assertSessionHasErrors();

    $this->actingAs($owner)
        ->delete(route('organisations.team.members.destroy', $ownerMembership))
        ->assertSessionHasErrors();

    expect($ownerMembership->fresh()->role)->toBe(OrganizationRole::Owner);
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Organizations/TeamManagementTest.php`
Expected: FAIL — routes not defined (`RouteNotFoundException`).

- [ ] **Step 4: Add the `organizationMember` unscoped binding in `routes/web.php`**

Immediately after the existing `Route::bind('applicationForm', ...)` block (around line 22), add:

```php
// Same reasoning as the applicationForm binding above: implicit binding runs
// before InitializeOrganizationContext sets the active organization.
Route::bind('organizationMember', fn (string $value) => OrganizationMember::withoutGlobalScope(OrganizationScope::class)
    ->findOrFail($value));
```

Add `use App\Models\OrganizationMember;` to the top `use` block.

- [ ] **Step 5: Add the four routes inside the existing `organisations.` group**

Inside `Route::middleware(['auth', 'verified', 'role:organization'])->prefix('organisations')->name('organisations.')->group(...)` (routes/web.php:78), after the existing `candidates.*` routes:

```php
Route::get('team', [OrganizationTeamController::class, 'index'])->name('team.index');
Route::post('team/invitations', [OrganizationTeamController::class, 'storeInvitation'])
    ->middleware('throttle:team-invitations')->name('team.invitations.store');
Route::patch('team/members/{organizationMember}', [OrganizationTeamController::class, 'updateMember'])->name('team.members.update');
Route::delete('team/members/{organizationMember}', [OrganizationTeamController::class, 'destroyMember'])->name('team.members.destroy');
```

Add `use App\Http\Controllers\OrganizationTeamController;` to the top `use` block.

- [ ] **Step 6: Add the three public `team-invitations.*` routes**

Near the `workable-jobs.*` routes (routes/web.php, after line 47), add:

```php
Route::get('team-invitations/{token}', [TeamInvitationController::class, 'show'])->name('team-invitations.show');
Route::post('team-invitations/{token}/accept', [TeamInvitationController::class, 'accept'])
    ->middleware('throttle:team-invitations')->name('team-invitations.accept');
Route::post('team-invitations/{token}/decline', [TeamInvitationController::class, 'decline'])
    ->middleware('throttle:team-invitations')->name('team-invitations.decline');
```

Add `use App\Http\Controllers\TeamInvitationController;` to the top `use` block.

- [ ] **Step 7: Fix `OrganizationTeamController`**

In `app/Http/Controllers/OrganizationTeamController.php`: change `view('employer.team.index', ...)` (line 57) to `view('organisations.team.index', ...)`; change all three `to_route('employer.team.index')` calls (lines 71, 84, 92) to `to_route('organisations.team.index')`.

- [ ] **Step 8: Fix `TeamInvitationNotification`**

In `app/Notifications/TeamInvitationNotification.php`: line 87, change `$notifiable->isEmployer()` to `$notifiable->isOrganization()`. Line 69, change `'title' => 'Employer team invitation'` to `'title' => 'Organization team invitation'`.

- [ ] **Step 9: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Organizations/TeamManagementTest.php`
Expected: PASS (the `index` route's 200 path isn't tested yet — only 403/redirects — so the still-missing view does not block this task).

- [ ] **Step 10: Commit**

```bash
git add routes/web.php app/Http/Controllers/OrganizationTeamController.php app/Notifications/TeamInvitationNotification.php tests/Pest.php tests/Feature/Organizations/TeamManagementTest.php
git commit -m "feat(team): wire hiring team routes and fix naming/isEmployer bugs"
```

---

### Task 2: The team roster view

**Files:**
- Create: `resources/views/organisations/team/index.blade.php`
- Test: `tests/Feature/Organizations/TeamManagementTest.php` (append)

**Interfaces:**
- Consumes: view data exactly as `OrganizationTeamController::index` passes it — `organization` (Organization), `members` (LengthAwarePaginator of `OrganizationMember`, each with `->user` eager-loaded and `->jobs_count` set as an int attribute), `invitations` (LengthAwarePaginator of `TeamInvitation`, each with `->invitedBy` eager-loaded), `currentOrganizationRole` (OrganizationRole), `roleOptions` (list<OrganizationRole>).
- Produces: nothing consumed by later tasks (leaf view).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Organizations/TeamManagementTest.php`:

```php
test('the team page lists members and pending invitations', function () {
    Notification::fake();
    [$organization, $owner] = organizationWithOwner();
    $member = addOrganizationMember($organization, OrganizationRole::Recruiter);
    $this->actingAs($owner)->post(route('organisations.team.invitations.store'), ['email' => 'pending@example.com', 'role' => 'interviewer']);

    $this->actingAs($owner)
        ->get(route('organisations.team.index'))
        ->assertOk()
        ->assertSee($member->user->email)
        ->assertSee('pending@example.com');
});

test('a recruiter viewing the team page is never reached because the route denies it first', function () {
    // covered by Task 1's "a recruiter cannot view or manage the hiring team" test; no view-dependent assertion needed here.
})->skip('covered in Task 1');
```

(The second test is a placeholder documenting why no additional 403+view test is needed; remove it if the plan reviewer prefers — it adds no coverage.)

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Organizations/TeamManagementTest.php -t "lists members"`
Expected: FAIL — `View [organisations.team.index] not found.`

- [ ] **Step 3: Build `resources/views/organisations/team/index.blade.php`**

Follow the structure of `resources/views/organisations/candidates/index.blade.php` and the per-row modal pattern of `resources/views/admin/users/index.blade.php`:

- `<x-app-layout>` with a `title` slot and a `header` slot (`nw-heading` "Hiring team" + a short description paragraph).
- An invite form (`<form method="POST" action="{{ route('organisations.team.invitations.store') }}">`) rendered only `@if (in_array($currentOrganizationRole, [\App\Enums\OrganizationRole::Owner, \App\Enums\OrganizationRole::HrManager], true))`: an email `<input type="email" name="email">`, a role `<select name="role">` populated from `$roleOptions` (value `$role->value`, label `$role->label()`), and a submit button. Show validation errors via `$errors->get('email')` / `$errors->get('role')` with `<x-input-error>`, and the standard `nw-notice--error` banner pattern from `admin/users/index.blade.php` for session-flashed errors (the `ValidationException` thrown by `authorizeRole`/duplicate-member checks lands in `email` or a generic key — surface `$errors->first()` the same way `admin/users/index.blade.php:57` does).
- A pending invitations table (`nw-table`) inside an `nw-panel`: columns Email, Role, Invited by, Expires; first `<td>` per row gets `class="nw-table__main"`; paginate with `{{ $invitations->onEachSide(1)->links() }}` using the `invitations_page` query-string name the controller already uses (Laravel's paginator handles this automatically from `$invitations`).
- A members table (`nw-table`) inside its own `nw-panel`: first `<td>` (`class="nw-table__main"`) shows the member's name/email like `admin/users/index.blade.php:86-94` (avatar initial + name + email), then Role (`<x-status-badge :status="$member->role->label()" />`), Status (`<x-status-badge :status="$member->status" />`), Jobs assigned (`$member->jobs_count`), and an actions cell (`class="nw-table__go"`) with a "Manage" button opening a per-row `<x-modal :name="'manage-member-'.$member->id">` — same `x-dispatch('open-modal', ...)` pattern as `admin/users/index.blade.php:100-102`.
- Each member's modal: a form `PATCH` to `route('organisations.team.members.update', $member)` with role and status selects (from `$roleOptions` and `\App\Enums\OrganizationMemberStatus::cases()`), and — only when `! $member->user->is(auth()->user())` — a separate `DELETE` form to `route('organisations.team.members.destroy', $member)` with a `confirm()` prompt, matching `admin/users/index.blade.php:186-194`.
- Paginate the members table the same way (`members_page` query-string name, already set by the controller).

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Organizations/TeamManagementTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/organisations/team/index.blade.php tests/Feature/Organizations/TeamManagementTest.php
git commit -m "feat(team): add the hiring team roster view"
```

---

### Task 3: The public invitation page and accept/decline flow

**Files:**
- Create: `resources/views/team-invitations/show.blade.php`
- Test: `tests/Feature/Organizations/TeamInvitationFlowTest.php` (new)

**Interfaces:**
- Consumes: `TeamInvitationController::show` view data — `invitation` (TeamInvitation, with `organization`/`invitedBy` loaded), `organization` (Organization), `token` (string), `existingAccount` (bool), `canAcceptExisting` (bool). `AcceptTeamInvitationRequest` fields: `first_name`, `last_name`, `password`, `password_confirmation`, `email` (all required only when no authenticated user). Reuses `organizationWithOwner()` from `tests/Pest.php` (Task 1).
- Produces: nothing consumed by later tasks (leaf view).

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Organizations/TeamInvitationFlowTest.php`:

```php
<?php

use App\Enums\OrganizationRole;
use App\Enums\TeamInvitationStatus;
use App\Models\Organization;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Services\OrganizationTeamService;
use Illuminate\Support\Str;

/**
 * OrganizationTeamService::invite() hashes the token before persisting and never
 * returns the plaintext, so tests pin Str::random() for the duration of the call
 * to recover it, rather than reaching into the service's internals.
 *
 * @return array{token: string, model: TeamInvitation}
 */
function captureInvitationToken(OrganizationTeamService $service, Organization $organization, User $actor, string $email, OrganizationRole $role): array
{
    $plain = null;
    Str::createRandomStringsUsing(function (int $length) use (&$plain): string {
        return $plain ??= Str::createRandomStringsNormally($length);
    });
    $model = $service->invite($organization, $actor, $email, $role);
    Str::createRandomStringsNormally();

    return ['token' => $plain, 'model' => $model];
}

test('an invited new person can view and accept the invitation', function () {
    [$organization, $owner] = organizationWithOwner();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, 'brandnew@example.com', OrganizationRole::Recruiter);

    $this->get(route('team-invitations.show', $invitation['token']))->assertOk()->assertSee('brandnew@example.com');

    $this->post(route('team-invitations.accept', $invitation['token']), [
        'first_name' => 'Brandnew', 'last_name' => 'Person',
        'password' => 'Str0ng!Passw0rd', 'password_confirmation' => 'Str0ng!Passw0rd',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    expect(User::where('email', 'brandnew@example.com')->exists())->toBeTrue();
});

test('an invited existing organization member can accept and is logged in', function () {
    [$organization, $owner] = organizationWithOwner();
    $invitee = User::factory()->organization()->create();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, $invitee->email, OrganizationRole::Recruiter);

    $this->get(route('team-invitations.show', $invitation['token']))->assertOk()->assertSee($organization->name);

    $this->actingAs($invitee)
        ->post(route('team-invitations.accept', $invitation['token']))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($invitee);
    expect($invitee->organizationMemberships()->where('organization_id', $organization->id)->exists())->toBeTrue();
});

test('declining an invitation marks it declined', function () {
    [$organization, $owner] = organizationWithOwner();
    $invitee = User::factory()->organization()->create();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, $invitee->email, OrganizationRole::Recruiter);

    $this->actingAs($invitee)->post(route('team-invitations.decline', $invitation['token']))->assertRedirect(route('dashboard'));

    expect(TeamInvitation::find($invitation['model']->id)->status)->toBe(TeamInvitationStatus::Declined);
});

test('a wrong-email accept attempt is rejected', function () {
    [$organization, $owner] = organizationWithOwner();
    $otherUser = User::factory()->organization()->create();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, 'target@example.com', OrganizationRole::Recruiter);

    $this->actingAs($otherUser)->post(route('team-invitations.accept', $invitation['token']))->assertForbidden();
});

test('an expired invitation cannot be accepted', function () {
    [$organization, $owner] = organizationWithOwner();
    $invitee = User::factory()->organization()->create();
    $invitation = captureInvitationToken(app(OrganizationTeamService::class), $organization, $owner, $invitee->email, OrganizationRole::Recruiter);
    $invitation['model']->update(['expires_at' => now()->subDay()]);

    $this->actingAs($invitee)->post(route('team-invitations.accept', $invitation['token']))->assertStatus(410);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test tests/Feature/Organizations/TeamInvitationFlowTest.php`
Expected: FAIL — `View [team-invitations.show] not found.` for the `show`-touching tests; others may already pass since `accept`/`decline` don't render a view (confirm which actually fail before proceeding).

- [ ] **Step 3: Build `resources/views/team-invitations/show.blade.php`**

Use `<x-layout>` (the public layout — see `resources/views/Apply.blade.php` for the pattern), inside an `nw-container`:

- A card showing `$organization->name`, the invited role (`$invitation->role->label()`), and `{{ __('Expires :date', ['date' => $invitation->expires_at->format('M j, Y, g:i A')]) }}`.
- `@if ($existingAccount && ! $canAcceptExisting)`: a notice telling them to log in with the invited email (`$invitation->email`), with a link to `route('login')` (carry `url.intended` the same way the controller already sets it in session).
- `@elseif (! $existingAccount)`: a signup-style form (first name, last name, password, password confirmation — matching `resources/views/auth/register.blade.php`'s field markup) posting to `route('team-invitations.accept', $token)`.
- `@else` (an authenticated matching user, `$canAcceptExisting` true): just an Accept button, no extra fields, posting to the same `accept` route with no body.
- A Decline button/form in all branches, posting to `route('team-invitations.decline', $token)`.
- Surface validation errors with the standard `<x-input-error>` pattern.

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test tests/Feature/Organizations/TeamInvitationFlowTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/team-invitations/show.blade.php tests/Feature/Organizations/TeamInvitationFlowTest.php
git commit -m "feat(team): add the public invitation accept/decline page"
```

---

### Task 4: Navigation entry point

**Files:**
- Modify: `resources/views/layouts/navigation.blade.php` (organization `Recruitment` group, after the `Candidates` link, around line 25)
- Test: `tests/Feature/Organizations/TeamManagementTest.php` (append)

**Interfaces:**
- Consumes: route `organisations.team.index` (from Task 1).
- Produces: nothing (leaf).

- [ ] **Step 1: Write the failing test**

Append to `tests/Feature/Organizations/TeamManagementTest.php`:

```php
test('organization users see a Team link in navigation', function () {
    [$organization, $owner] = organizationWithOwner();

    $this->actingAs($owner)
        ->get(route('organisations.dashboard'))
        ->assertOk()
        ->assertSee(route('organisations.team.index'), false);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/Organizations/TeamManagementTest.php -t "see a Team link"`
Expected: FAIL — link not present.

- [ ] **Step 3: Add the nav link**

In `resources/views/layouts/navigation.blade.php`, inside the `$navUser->isOrganization()` branch's `__('Recruitment')` group (line 24), after the `Candidates` entry:

```php
['label' => __('Team'), 'href' => route('organisations.team.index'), 'icon' => 'users', 'active' => request()->routeIs('organisations.team.*')],
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Feature/Organizations/TeamManagementTest.php`
Expected: PASS.

- [ ] **Step 5: Run the full suite**

Run: `php artisan test`
Expected: all green except the single pre-existing unrelated failure already known in this branch (`tests/Feature/Jobs/JobPagesTest.php`, the "This is a preview." assertion — unrelated to this work).

- [ ] **Step 6: Commit**

```bash
git add resources/views/layouts/navigation.blade.php tests/Feature/Organizations/TeamManagementTest.php
git commit -m "feat(team): add Team link to organization navigation"
```
