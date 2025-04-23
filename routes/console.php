<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Configuration\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

return function (Schedule $schedule) {
    // $schedule->command('update:trestle-properties --geocode --hours=2')
    //         ->everyTwoHours()
    //         ->withoutOverlapping()
    //         ->appendOutputTo(storage_path('logs/trestle-update.log'));
    $schedule->command('update:trestle-properties --geocode --hours=2')
        ->everyMinute() // Run every minute during testing
        ->withoutOverlapping()
        ->appendOutputTo(storage_path('logs/trestle-update.log'));
};