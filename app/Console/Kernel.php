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
        $schedule->command('hour:update')
                ->everyMinute();
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
