<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('workable:sync')->everyThirtyMinutes()->withoutOverlapping()->onOneServer();
Schedule::command('workable:dispatch-applications')->everyMinute()->withoutOverlapping()->onOneServer();
Schedule::command('notifications:drain-outbox')->everyMinute()->withoutOverlapping()->onOneServer();
Schedule::command('accounts:process-inactivity')->everyFifteenMinutes()->withoutOverlapping()->onOneServer();
