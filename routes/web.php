<?php

use App\Http\Controllers\JobController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', [JobController::class, 'home']);


Route::get('About', function () {
    return view('About');
});

Route::get('ContactUs', function () {
    return view('ContactUs');
});

// Public job board — reads synced Workable postings from our database.
Route::get('jobs', [JobController::class, 'index'])->name('jobs.index');
Route::get('jobs/{id}', [JobController::class, 'show'])->name('jobs.show');

// Hosted applications keep their destination; roles without one show the form preview.
Route::get('jobs/{id}/apply', [JobController::class, 'apply'])
    ->middleware('auth')->name('jobs.apply');

Route::get('/dashboard', [JobController::class, 'dashboard'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
