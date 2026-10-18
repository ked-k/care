<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();

        // Batch 30: keeps every repeating care-plan task's future dates
        // actually populated (~30 days ahead) regardless of whether
        // anyone has completed, skipped, or even opened anything yet —
        // see GenerateRecurringTaskOccurrences and
        // Task::backfillOccurrencesUntil() for why the old
        // completion-only generation left future dates silently empty.
        //
        // IMPORTANT — this line alone does nothing on a Windows/WAMP box:
        // Laravel's scheduler only runs when something calls
        // `php artisan schedule:run` on a timer. There's no cron on
        // Windows, so this needs a Windows Task Scheduler entry running
        // that command every minute (Laravel itself decides daily/hourly
        // from there) — see CHANGES30.md for the exact setup.
        $schedule->command('tasks:generate-occurrences')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
