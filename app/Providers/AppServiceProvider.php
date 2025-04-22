<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //  Register scheduled tasks (since Laravel 12 removed Console Kernel)
        // if (App::runningInConsole()) {
        //     $this->app->booted(function () {
        //         $schedule = app(Schedule::class);
                
        //         // Schedule the Trestle property fetch command
        //         $schedule->command('fetch:trestle-properties')->hourly();
        //     });
        // }
    }
}
