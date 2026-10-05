<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use Illuminate\Support\Facades\Schedule;

Schedule::command('attendance:close-day')->weekdays()->at('10:00');
Schedule::command('fees:remind')->dailyAt('09:00');

// Public trial only: start again from clean sample data every few hours.
Schedule::command('demo:reset')->cron('0 */'.config('school.demo_reset_hours').' * * *')->when(fn () => config('school.demo_mode'));
