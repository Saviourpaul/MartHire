<?php

use App\Http\Controllers\Admin\JobManagementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ApplicationDocumentDownloadController;
use App\Http\Controllers\ApplicationDocumentPreviewController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployerApplicationController;
use App\Http\Controllers\EmployerNotificationController;
use App\Http\Controllers\GoogleAuthController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\JobTeamController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\OrganizationContextController;
use App\Http\Controllers\OrganizationTeamController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TeamInvitationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::get('team-invitations/{token}', [TeamInvitationController::class, 'show'])->middleware('throttle:60,1')->name('team-invitations.show');
Route::post('team-invitations/{token}/accept', [TeamInvitationController::class, 'accept'])
    ->middleware('throttle:6,1')->name('team-invitations.accept');
Route::post('team-invitations/{token}/decline', [TeamInvitationController::class, 'decline'])
    ->middleware('throttle:6,1')->name('team-invitations.decline');

Route::get('Dashboard', DashboardController::class)
    ->middleware(['auth', 'verified', 'active.account'])
    ->name('dashboard');
Route::get('/', function () {

    return view('Home');
})->name('home');

Route::get('job-details/{job}', [JobController::class, 'show'])->name('job-details');
Route::get('jobs/{job}/apply', [JobApplicationController::class, 'create'])
    ->middleware(['auth', 'verified', 'active.account'])
    ->name('applications.create');
Route::get('about', fn () => view('about'))->name('about');
Route::view('services', 'services')->name('services');
Route::view('pricing', 'pricing')->name('pricing');
Route::view('how-it-works', 'how-it-works')->name('how-it-works');
Route::view('our-team', 'our-team')->name('our-team');
Route::get('Browse-jobs', [JobController::class, 'browse'])->name('Browse-jobs');
Route::view('contact', 'contact')->name('contact');
Route::get('jobs-listings', [JobController::class, 'listings'])->name('client.jobs-listings');
Route::get('locations/states/{nigeriaState}/local-government-areas', [LocationController::class, 'localGovernmentAreas'])
    ->middleware('throttle:60,1')
    ->name('locations.states.local-government-areas');

Route::get('Job-Application.', DashboardController::class)
    ->middleware(['auth', 'verified', 'active.account', 'role:applicant'])
    ->name('client.Job-Application.');

Route::middleware(['auth', 'verified', 'active.account', 'role:applicant'])->group(function () {
    Route::get('profile', fn () => view('client.profile'))->name('client.profile');
    Route::get('Application', [JobApplicationController::class, 'index'])->name('Client.Application');
    Route::post('jobs/{job}/apply', [JobApplicationController::class, 'store'])
        ->middleware('throttle:application-submit')
        ->name('applications.store');
    Route::get('applications/{applicationForm}', [JobApplicationController::class, 'show'])->name('client.applications.show');
    Route::get('documents', [JobApplicationController::class, 'documents'])->name('client.documents');
    Route::get('jobs', [JobApplicationController::class, 'index'])->name('client.jobs');
    Route::get('client/notifications', [JobApplicationController::class, 'notifications'])->name('client.notifications');
    Route::get('settings', fn () => view('client.settings'))->name('client.settings');
});
/**Google Login */
Route::controller(GoogleAuthController::class)->group(function () {
    Route::get('auth/google', [GoogleAuthController::class, 'googleLogin'])->name('auth.google');
    Route::get('auth/google-callback', 'googleAuthentication')->name('auth.google-callback');

});

Route::middleware(['auth', 'verified', 'active.account'])->group(function () {
    Route::get('application-documents/{applicationDocument}/preview', ApplicationDocumentPreviewController::class)
        ->middleware('throttle:downloads')
        ->name('application-documents.preview');

    Route::get('application-documents/{applicationDocument}/download', ApplicationDocumentDownloadController::class)
        ->middleware('throttle:downloads')
        ->name('application-documents.download');

    Route::get('applicants/{user}/profile', [ProfileController::class, 'showApplicant'])
        ->middleware('role:admin,employer')
        ->name('applicants.profile.show');

    Route::middleware('role:employer')->group(function () {
        Route::get('employer/team', [OrganizationTeamController::class, 'index'])->name('employer.team.index');
        Route::post('employer/team/invitations', [OrganizationTeamController::class, 'storeInvitation'])
            ->middleware('throttle:team-invitations')->name('employer.team.invitations.store');
        Route::patch('employer/team/members/{organizationMember}', [OrganizationTeamController::class, 'updateMember'])->name('employer.team.members.update');
        Route::delete('employer/team/members/{organizationMember}', [OrganizationTeamController::class, 'destroyMember'])->name('employer.team.members.destroy');
        Route::post('employer/organizations/switch', OrganizationContextController::class)->name('employer.organizations.switch');
        Route::put('jobs/{job}/team', [JobTeamController::class, 'update'])->name('jobs.team.update');
        Route::get('jobs', [JobController::class, 'index'])->name('jobs');
        Route::post('jobs', [JobController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('jobs.store');
        Route::get('jobs/{job}', [JobController::class, 'employerShow'])->name('jobs.show');
        Route::put('jobs/{job}', [JobController::class, 'update'])
            ->middleware('throttle:uploads')
            ->name('jobs.update');
        Route::delete('jobs/{job}', [JobController::class, 'destroy'])->name('jobs.destroy');
        Route::patch('jobs/{job}/close', [JobController::class, 'close'])->can('close', 'job')->name('jobs.close');
        Route::get('profile', fn () => view('employer.profile'))->name('employer.profile');
        Route::get('Applied-Candidates', [EmployerApplicationController::class, 'applied'])->name('employer.Applied-Candidates');
        Route::get('employer/applications/{applicationForm}', [EmployerApplicationController::class, 'show'])->name('employer.applications.show');
        Route::patch('employer/applications/{applicationForm}/pipeline', [EmployerApplicationController::class, 'movePipeline'])->name('employer.applications.pipeline.move');
        Route::get('dashboard/analytics', [DashboardController::class, 'employerAnalytics'])
            ->middleware('throttle:60,1')
            ->name('employer.dashboard.analytics');
        Route::get('employer/notifications', [EmployerNotificationController::class, 'index'])->name('employer.notifications');
        Route::post('employer/notifications/read-all', [EmployerNotificationController::class, 'readAll'])->name('employer.notifications.read-all');
        Route::patch('employer/notifications/{notification}/read', [EmployerNotificationController::class, 'read'])->name('employer.notifications.read');
        Route::get('settings', fn () => view('employer.settings'))->name('employer.settings');

    });

    Route::middleware('role:admin')->group(function () {
        Route::get('Employers', [UserManagementController::class, 'employers'])->name('Employers');
        Route::get('administrators', [UserManagementController::class, 'administrators'])->name('administrators');
        Route::get('applicants', [UserManagementController::class, 'applicants'])->name('applicants');
        Route::get('suspended-accounts', [UserManagementController::class, 'suspended'])->name('suspended-accounts');

        Route::post('admin/users', [UserManagementController::class, 'store'])->name('admin.users.store');
        Route::put('admin/users/{user}', [UserManagementController::class, 'update'])->name('admin.users.update');
        Route::patch('admin/users/{user}/suspend', [UserManagementController::class, 'suspend'])->name('admin.users.suspend');
        Route::patch('admin/users/{user}/activate', [UserManagementController::class, 'activate'])->name('admin.users.activate');
        Route::delete('admin/users/{user}', [UserManagementController::class, 'destroy'])->name('admin.users.destroy');
        Route::post('admin/users/{user}/password-reset', [UserManagementController::class, 'sendPasswordReset'])->name('admin.users.password-reset');

        Route::get('admin/jobs', [JobManagementController::class, 'index'])->name('admin.jobs.index');
        Route::get('admin/jobs/{job}', [JobManagementController::class, 'show'])->can('viewModeration', 'job')->name('admin.jobs.show');
        Route::patch('admin/jobs/{job}/suspend', [JobManagementController::class, 'suspend'])->can('suspend', 'job')->name('admin.jobs.suspend');
        Route::delete('admin/jobs/{job}', [JobManagementController::class, 'destroy'])->can('deleteAsModerator', 'job')->name('admin.jobs.destroy');
        Route::get('suspended-jobs', [JobManagementController::class, 'suspended'])->name('suspended-jobs');
        Route::get('open-jobs', [JobManagementController::class, 'open'])->name('open-jobs');
        Route::get('closed-jobs', [JobManagementController::class, 'closed'])->name('closed-jobs');
        Route::get('Reports', [ReportController::class, 'index'])->name('Reports');
        Route::get('Reports/export', [ReportController::class, 'export'])->name('Reports.export');
        Route::get('admin/dashboard/analytics', [DashboardController::class, 'adminAnalytics'])
            ->middleware('throttle:60,1')
            ->name('admin.dashboard.analytics');
        Route::get('admin/dashboard/active-users', [DashboardController::class, 'adminActiveUsers'])
            ->middleware('throttle:120,1')
            ->name('admin.dashboard.active-users');
        Route::get('assessment-templates', fn () => view('admin/assessment-templates'))->name('assessment-templates');
        Route::get('interview-templates', fn () => view('admin/interview-templates'))->name('interview-templates');
        Route::get('email-templates', fn () => view('admin/email-templates'))->name('email-templates');
        Route::get('general-settings', fn () => view('admin/general-settings'))->name('general-settings');
        Route::get('email-configuration', fn () => view('admin/email-configuration'))->name('email-configuration');
        Route::get('notifications', fn () => view('admin/notifications'))->name('notifications');
        Route::get('permission-management', fn () => view('admin/permission-management'))->name('permission-management');
    });
});

Route::middleware(['auth', 'verified', 'active.account'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:uploads')
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
