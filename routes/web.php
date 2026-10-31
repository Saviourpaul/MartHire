<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ApplicationDocumentDownloadController;
use App\Http\Controllers\ApplicationDocumentPreviewController;
use App\Http\Controllers\ClientDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InterviewResponseController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OrganizationApplicationController;
use App\Http\Controllers\OrganizationDashboardController;
use App\Http\Controllers\OrganizationInterviewController;
use App\Http\Controllers\OrganizationTeamController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicJobController;
use App\Http\Controllers\TeamInvitationController;
use App\Http\Controllers\WorkableJobController;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use App\Models\CandidateMessage;
use App\Models\Interview;
use App\Models\Job;
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

// Bind private resources before organization middleware, then authorize them in
// the existing document/interview policies after the context is initialized.
Route::bind('applicationDocument', fn (string $value) => ApplicationDocument::withoutGlobalScope(OrganizationScope::class)->findOrFail($value));
Route::bind('candidateMessage', fn (string $value) => CandidateMessage::withoutGlobalScope(OrganizationScope::class)->findOrFail($value));
Route::bind('interview', fn (string $value, Illuminate\Routing\Route $route) => str_starts_with($route->getName() ?? '', 'interviews.response')
    ? (int) $value
    : Interview::withoutGlobalScope(OrganizationScope::class)->findOrFail($value));

// Same reasoning as the applicationForm binding above: implicit binding runs
// before InitializeOrganizationContext sets the active organization.
Route::bind('organizationMember', fn (string $value) => OrganizationMember::withoutGlobalScope(OrganizationScope::class)
    ->findOrFail($value));

// Native jobs bind by slug, unscoped (same ordering reason as above); JobPolicy /
// OrganizationPermissions re-check real org ownership on every job action.
Route::bind('job', fn (string $value) => Job::withoutGlobalScope(OrganizationScope::class)
    ->where('slug', $value)->firstOrFail());

Route::get('/', [PublicJobController::class, 'home'])->name('home');

Route::get('About', function () {
    return view('About');
});

Route::get('ContactUs', function () {
    return view('ContactUs');
})->name('contact');

Route::view('how-it-works', 'HowItWorks')->name('how-it-works');

// One public board includes published organization jobs and imported postings.
// Preserve Workable URLs while native jobs use their own slug-based destinations.
Route::get('jobs', [PublicJobController::class, 'index'])->name('jobs.index');
Route::get('jobs/{job}', [PublicJobController::class, 'show'])->name('jobs.show');
Route::get('jobs/{job}/apply', [JobApplicationController::class, 'create'])
    ->middleware(['auth', 'verified', 'active.account', 'role:applicant'])->name('applications.create');
Route::post('jobs/{job}/apply', [JobApplicationController::class, 'store'])
    ->middleware(['auth', 'verified', 'active.account', 'role:applicant', 'throttle:10,1'])->name('applications.store');
Route::get('workable-jobs', [PublicJobController::class, 'index'])->name('workable-jobs.index');
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

// Versioned signed links work for candidates without requiring an account login.
Route::middleware(['signed', 'throttle:30,1'])->group(function () {
    Route::get('interviews/{interview}/{response}', [InterviewResponseController::class, 'show'])
        ->whereNumber('interview')->where('response', 'confirmed|declined')->name('interviews.response');
    Route::post('interviews/{interview}/{response}', [InterviewResponseController::class, 'store'])
        ->whereNumber('interview')->where('response', 'confirmed|declined')->name('interviews.response.store');
});

Route::middleware(['auth', 'verified', 'active.account', 'throttle:downloads'])->group(function () {
    Route::get('application-documents/{applicationDocument}/preview', ApplicationDocumentPreviewController::class)->name('application-documents.preview');
    Route::get('application-documents/{applicationDocument}/download', ApplicationDocumentDownloadController::class)->name('application-documents.download');
});

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

    Route::middleware('active.account')->group(function () {
        Route::get('candidates/{applicationForm}/interviews/create', [OrganizationInterviewController::class, 'create'])->name('interviews.create');
        Route::get('interviews/{interview}/edit', [OrganizationInterviewController::class, 'edit'])->name('interviews.edit');
        Route::patch('interviews/{interview}/complete', [OrganizationInterviewController::class, 'complete'])->name('interviews.complete');
        Route::middleware('throttle:candidate-email')->group(function () {
            Route::post('candidates/{applicationForm}/interviews', [OrganizationInterviewController::class, 'store'])->name('interviews.store');
            Route::post('candidates/{applicationForm}/interviews/preview', [OrganizationInterviewController::class, 'preview'])->name('interviews.preview');
            Route::patch('interviews/{interview}', [OrganizationInterviewController::class, 'update'])->name('interviews.update');
            Route::post('interviews/{interview}/cancel', [OrganizationInterviewController::class, 'cancel'])->name('interviews.cancel');
            Route::post('candidate-messages/{candidateMessage}/retry', [OrganizationInterviewController::class, 'retry'])->name('interviews.retry');
        });
    });

    // Hiring team roster: OrganizationPolicy::manageTeam gates every action.
    Route::get('team', [OrganizationTeamController::class, 'index'])->name('team.index');
    Route::post('team/invitations', [OrganizationTeamController::class, 'storeInvitation'])
        ->middleware('throttle:team-invitations')->name('team.invitations.store');
    Route::patch('team/members/{organizationMember}', [OrganizationTeamController::class, 'updateMember'])->name('team.members.update');
    Route::delete('team/members/{organizationMember}', [OrganizationTeamController::class, 'destroyMember'])->name('team.members.destroy');

    // Native job postings (the org's own jobs, the candidate pipeline runs on these).
    // JobPolicy gates every action; {job} binds by slug (unscoped bind above).
    Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
    Route::get('jobs/create', [JobController::class, 'create'])->name('jobs.create');
    Route::post('jobs', [JobController::class, 'store'])->name('jobs.store');
    Route::get('jobs/{job}', [JobController::class, 'employerShow'])->name('jobs.show');
    Route::get('jobs/{job}/edit', [JobController::class, 'edit'])->name('jobs.edit');
    Route::patch('jobs/{job}', [JobController::class, 'update'])->name('jobs.update');
    Route::post('jobs/{job}/close', [JobController::class, 'close'])->name('jobs.close');
    Route::delete('jobs/{job}', [JobController::class, 'destroy'])->name('jobs.destroy');
});

Route::middleware(['auth', 'verified', 'role:applicant'])->prefix('client')->name('client.')->group(function () {
    Route::get('dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
    Route::get('applications/{applicationForm}', [JobApplicationController::class, 'show'])
        ->middleware('active.account')->name('applications.show');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Cascading country -> state -> city lookups for the profile form's
    // location picker (and anywhere else that needs them).
    // Three segments avoid World's optional-prefix /{prefix?}/states|cities routes.
    Route::get('lookups/locations/states', [LocationController::class, 'states'])->name('locations.states');
    Route::get('lookups/locations/cities', [LocationController::class, 'cities'])->name('locations.cities');
});

require __DIR__.'/auth.php';
