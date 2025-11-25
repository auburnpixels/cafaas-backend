<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * @class Kernel
 */
class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('sitemap:generate')->daily();
        $schedule->command('competition:draw')->everyFifteenMinutes();
        $schedule->command('competition:drop-draw')->dailyAt('00:01'); // Run one minute past midnight.
        $schedule->command('prizes:unaccepted')->everyMinute();
        $schedule->command('competition:ended')->everyMinute();

        $schedule->command('prizes:accept-reminder 1 day')->everyMinute();
        $schedule->command('prizes:accept-reminder 1 hour')->everyMinute();
        $schedule->command('prizes:accept-reminder 1 week')->everyMinute();
        $schedule->command('prizes:accept-reminder 2 weeks')->everyMinute();

        // Access link reminders.
        // $schedule->command('access-links:reminder 1 hour')->everyMinute();
        // $schedule->command('access-links:reminder 1 day')->everyMinute();
        // $schedule->command('access-links:reminder 1 week')->everyMinute();

        // Draw event logging system
        $schedule->command('draw-events:export-digest')->dailyAt('01:00'); // Export daily digest at 1 AM
        $schedule->command('draw-events:verify')->weekly()->sundays()->at('02:00'); // Weekly integrity check
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
