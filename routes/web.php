<?php

use App\Http\Controllers\Admin\JobManagementController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\ApplicationDocumentDownloadController;
use App\Http\Controllers\ApplicationDocumentPreviewController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployerApplicationController;
use App\Http\Controllers\EmployerApplicationDocumentController;
use App\Http\Controllers\JobApplicationController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\ProfileController;
use App\Models\Job;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    return view('Home');
})->name('home');



Route::get('job-details/{job}', [JobController::class, 'show'])->name('job-details');
Route::get('jobs/{job}/apply', [JobApplicationController::class, 'create'])
    ->middleware('active.account')
    ->name('applications.create');
Route::get('about', fn () => view('about'))->name('about');
Route::view('services', 'services')->name('services');
Route::view('pricing', 'pricing')->name('pricing');
Route::view('how-it-works', 'how-it-works')->name('how-it-works');
Route::view('our-team', 'our-team')->name('our-team');
Route::view('contact', 'contact')->name('contact');
Route::get('locations/states/{nigeriaState}/local-government-areas', [LocationController::class, 'localGovernmentAreas'])
    ->middleware('throttle:60,1')
    ->name('locations.states.local-government-areas');

Route::get('Dashboard', DashboardController::class)
    ->middleware('auth')
    ->name('dashboard');

Route::get('client/Job-Application.', DashboardController::class)
    ->middleware(['auth', 'role:applicant'])
    ->name('client.Job-Application.');

Route::middleware(['auth', 'active.account', 'role:applicant'])->group(function () {
    Route::get('client/profile', fn () => view('client.profile'))->name('client.profile');
    Route::get('Client/Application', [JobApplicationController::class, 'index'])->name('Client.Application');
    Route::post('jobs/{job}/apply', [JobApplicationController::class, 'store'])
        ->middleware('throttle:application-submit')
        ->name('applications.store');
    Route::get('client/applications/{applicationForm}', [JobApplicationController::class, 'show'])->name('client.applications.show');
    Route::get('client/documents', [JobApplicationController::class, 'documents'])->name('client.documents');
    Route::get('client/jobs', [JobApplicationController::class, 'index'])->name('client.jobs');
    Route::get('client/notifications', [JobApplicationController::class, 'notifications'])->name('client.notifications');
    Route::get('client/settings', fn () => view('client.settings'))->name('client.settings');
});

Route::middleware(['auth', 'active.account'])->group(function () {
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
        Route::get('jobs', [JobController::class, 'index'])->name('jobs');
        Route::post('jobs', [JobController::class, 'store'])
            ->middleware('throttle:uploads')
            ->name('jobs.store');
        Route::get('jobs/{job}', [JobController::class, 'employerShow'])->name('jobs.show');
        Route::put('jobs/{job}', [JobController::class, 'update'])
            ->middleware('throttle:uploads')
            ->name('jobs.update');
        Route::delete('jobs/{job}', [JobController::class, 'destroy'])->name('jobs.destroy');
        Route::get('employer/profile', fn () => view('employer.profile'))->name('employer.profile');
        Route::get('employer/Applied-Candidates', [EmployerApplicationController::class, 'applied'])->name('employer.Applied-Candidates');
        Route::get('employer/Approved-Candidates', [EmployerApplicationController::class, 'approved'])->name('employer.Approved-Candidates');
        Route::get('employer/Rejected-Candidate', [EmployerApplicationController::class, 'rejected'])->name('employer.Rejected-Candidate');
        Route::get('employer/applications/{applicationForm}', [EmployerApplicationController::class, 'show'])->name('employer.applications.show');
        Route::patch('employer/applications/{applicationForm}/status', [EmployerApplicationController::class, 'review'])->name('employer.applications.review');
        Route::patch('employer/application-documents/{applicationDocument}/status', [EmployerApplicationDocumentController::class, 'update'])->name('employer.application-documents.review');
        Route::get('employer/dashboard/analytics', [DashboardController::class, 'employerAnalytics'])
            ->middleware('throttle:60,1')
            ->name('employer.dashboard.analytics');
        Route::get('employer/notifications', fn () => view('employer.notifications'))->name('employer.notifications');
        Route::get('employer/settings', fn () => view('employer.settings'))->name('employer.settings');

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
        Route::get('admin/jobs/{job}', [JobManagementController::class, 'show'])->name('admin.jobs.show');
        Route::patch('admin/jobs/{job}/status', [JobManagementController::class, 'review'])->name('admin.jobs.review');
        Route::get('approved-jobs', [JobManagementController::class, 'approved'])->name('approved-jobs');
        Route::get('rejected-jobs', [JobManagementController::class, 'rejected'])->name('rejected-jobs');
        Route::get('pending-jobs', [JobManagementController::class, 'pending'])->name('pending-jobs');
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

Route::middleware(['auth', 'active.account'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])
        ->middleware('throttle:uploads')
        ->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
