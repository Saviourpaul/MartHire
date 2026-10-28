<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OrganizationApplicationController;
use App\Http\Controllers\OrganizationDashboardController;
use App\Http\Controllers\OrganizationTeamController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\WorkableJobController;
use App\Models\ApplicationForm;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Support\Facades\Route;

// Implicit {applicationForm} binding runs before InitializeOrganizationContext
// sets the active organization, so the model's OrganizationScope would block
// every lookup (including the owning organization's). Resolve it unscoped here;
// OrganizationApplicationController/MoveCandidatePipelineRequest already check
// real ownership via OrganizationPermissions before anything is shown or moved.
Route::bind('applicationForm', fn (string $value) => ApplicationForm::withoutGlobalScope(OrganizationScope::class)
    ->findOrFail($value));

// Same reasoning as the applicationForm binding above: implicit binding runs
// before InitializeOrganizationContext sets the active organization.
Route::bind('organizationMember', fn (string $value) => OrganizationMember::withoutGlobalScope(OrganizationScope::class)
    ->findOrFail($value));

Route::get('/', [WorkableJobController::class, 'home']);


Route::get('About', function () {
    return view('About');
});

Route::get('ContactUs', function () {
    return view('ContactUs');
})->name('contact');

// Public job board — reads synced Workable postings from our database.
// Kept at its own path/name (workable-jobs.*) so it doesn't collide with
// JobController's own (non-Workable) job postings under "jobs".
Route::get('workable-jobs', [WorkableJobController::class, 'index'])->name('workable-jobs.index');
Route::get('workable-jobs/{id}', [WorkableJobController::class, 'show'])->name('workable-jobs.show');

// Hosted applications keep their destination; roles without one show the form preview.
Route::get('workable-jobs/{id}/apply', [WorkableJobController::class, 'apply'])
    ->middleware('auth')->name('workable-jobs.apply');

// Native apply submission: validates, queues the application, dispatches the outbox job.
Route::post('workable-jobs/{id}/apply', [WorkableJobController::class, 'store'])
    ->middleware('auth')->name('workable-jobs.apply.store');

// Public, token-based: reachable whether or not the invited person already
// has an account. InitializeOrganizationContext special-cases this prefix.
Route::get('team-invitations/{token}', [TeamInvitationController::class, 'show'])->name('team-invitations.show');
Route::post('team-invitations/{token}/accept', [TeamInvitationController::class, 'accept'])
    ->middleware('throttle:team-invitations')->name('team-invitations.accept');
Route::post('team-invitations/{token}/decline', [TeamInvitationController::class, 'decline'])
    ->middleware('throttle:team-invitations')->name('team-invitations.decline');

// Generic entry point: every login/registration/verification redirect keeps
// using route('dashboard'); it just forwards each role to its own dashboard.
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/analytics', [AdminDashboardController::class, 'analytics'])->name('dashboard.analytics');
    Route::get('dashboard/active-users', [AdminDashboardController::class, 'activeUsers'])->name('dashboard.active-users');

    // User management: one shared table view (admin/users/index.blade.php) reused
    // across every category; AccountLifecycleService owns the actual guard rules
    // (last-admin protection, self-demotion, organization-owner protection).
    Route::get('users/organizations', [UserManagementController::class, 'organizations'])->name('users.organizations');
    Route::get('users/applicants', [UserManagementController::class, 'applicants'])->name('users.applicants');
    Route::get('users/administrators', [UserManagementController::class, 'administrators'])->name('users.administrators');
    Route::get('users/suspended', [UserManagementController::class, 'suspended'])->name('users.suspended');
    Route::get('users/organizations/export', [UserManagementController::class, 'exportOrganizations'])->name('users.organizations.export');
    Route::get('users/applicants/export', [UserManagementController::class, 'exportApplicants'])->name('users.applicants.export');
    Route::get('users/administrators/export', [UserManagementController::class, 'exportAdministrators'])->name('users.administrators.export');
    Route::get('users/suspended/export', [UserManagementController::class, 'exportSuspended'])->name('users.suspended.export');
    Route::post('users', [UserManagementController::class, 'store'])->name('users.store');
    Route::patch('users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('users.suspend');
    Route::post('users/{user}/activate', [UserManagementController::class, 'activate'])->name('users.activate');
    Route::delete('users/{user}', [UserManagementController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/password-reset', [UserManagementController::class, 'sendPasswordReset'])->name('users.password-reset');
});

Route::middleware(['auth', 'verified', 'role:organization'])->prefix('organisations')->name('organisations.')->group(function () {
    Route::get('dashboard', [OrganizationDashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/analytics', [OrganizationDashboardController::class, 'analytics'])->name('dashboard.analytics');

    // Candidate pipeline: a single filterable table (applied()/?stage=) plus a
    // detail view; movePipeline() already enforces legal stage transitions and
    // per-role permissions via MoveCandidatePipelineRequest + OrganizationPermissions.
    Route::get('candidates', [OrganizationApplicationController::class, 'applied'])->name('candidates.index');
    Route::get('candidates/export', [OrganizationApplicationController::class, 'export'])->name('candidates.export');
    Route::get('candidates/{applicationForm}', [OrganizationApplicationController::class, 'show'])->name('candidates.show');
    Route::patch('candidates/{applicationForm}/move', [OrganizationApplicationController::class, 'movePipeline'])->name('candidates.move');

    // Hiring team roster: OrganizationPolicy::manageTeam gates every action.
    Route::get('team', [OrganizationTeamController::class, 'index'])->name('team.index');
    Route::post('team/invitations', [OrganizationTeamController::class, 'storeInvitation'])
        ->middleware('throttle:team-invitations')->name('team.invitations.store');
    Route::patch('team/members/{organizationMember}', [OrganizationTeamController::class, 'updateMember'])->name('team.members.update');
    Route::delete('team/members/{organizationMember}', [OrganizationTeamController::class, 'destroyMember'])->name('team.members.destroy');
});

Route::middleware(['auth', 'verified', 'role:applicant'])->prefix('client')->name('client.')->group(function () {
    Route::get('dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Cascading country -> state -> city lookups for the profile form's
    // location picker (and anywhere else that needs them).
    Route::get('locations/states', [LocationController::class, 'states'])->name('locations.states');
    Route::get('locations/cities', [LocationController::class, 'cities'])->name('locations.cities');
});

require __DIR__.'/auth.php';
