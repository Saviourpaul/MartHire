<?php

use App\Http\Controllers\LocationController;
use App\Http\Middleware\ThrottleLocationRequests;
use Illuminate\Support\Facades\Route;

Route::prefix('locations')->middleware(ThrottleLocationRequests::class)->name('locations.')->group(function () {
    Route::get('countries', [LocationController::class, 'countries'])->name('countries');
    Route::get('states', [LocationController::class, 'states'])->name('states');
    Route::get('cities', [LocationController::class, 'cities'])->name('cities');
});
