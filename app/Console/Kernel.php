<?php

namespace App\Console;

use App\Console\Commands\BookingReminderFeedbackCommand;
use App\Console\Commands\EZEEGetBookingsCommand;
use App\Console\Commands\HistoricalApi;
use App\Console\Commands\UpdateVersionCommand;
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
        UpdateVersionCommand::class,
        BookingReminderFeedbackCommand::class,
        EZEEGetBookingsCommand::class,
        HistoricalApi::class,
        \App\Console\Commands\BackfillEzeeFolioNo::class,
        \App\Console\Commands\BackfillEzeeFolioNumbers::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
        // Runs for several minutes against the EZEE API, so it must never
        // overlap itself — everyMinute() previously stacked one run per minute
        // and saturated CPU.
        // Assignment runs on a rolling fortnight rather than from today, so a
        // missed day heals itself on the next run instead of leaving those
        // bookings unassigned for ever — which is how August came to have 506
        // outstanding. It does not rescan history, so it stays cheap.
        $schedule->command('ezee:auto-assign', ['--from' => now()->subDays(14)->toDateString()])
            ->dailyAt('06:00')
            ->withoutOverlapping()
            ->runInBackground();

        // EZEE's notification queue is the only feed that reports a cancellation
        // by name. Read after the hourly sync, so a cancellation lands before
        // the reconcile could act on the reservation.
        $schedule->command('ezee:notifications')->hourlyAt(10)->withoutOverlapping();

        // EZEE never reports a cancellation; a cancelled reservation just stops
        // appearing. Without this sweep they accumulate silently, occupying
        // units and blocking real bookings.
        $schedule->command('ezee:sweep-cancelled')
            ->weeklyOn(1, '05:00')
            ->withoutOverlapping()
            ->runInBackground();

        $schedule->command('hour:update')
                ->hourly()
                ->withoutOverlapping(120);
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
