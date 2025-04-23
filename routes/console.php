<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Log;

// Schedule::call(function () {
//     Log::info('✅ Scheduled task executed at: ' . now());
// })->everyMinute();

Schedule::command('update:trestle-properties --geocode --hours=2')
    ->everyTwoHours()
    ->appendOutputTo(storage_path('logs/trestle-update.log'));

// Define custom Artisan command
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
