<?php

namespace App\Console;

use App\Jobs\QueueSubscriptionCleanupJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use \App\Jobs\QueueSubscriptionRenewalsJob;
use \App\Jobs\SendNotificationEmailsJob;
use \App\Jobs\RefreshFinicityAuthToken;

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
        $schedule->job(new QueueSubscriptionRenewalsJob)->daily('8:00');
        $schedule->job(new QueueSubscriptionCleanupJob)->daily('8:00');
        $schedule->job(new SendNotificationEmailsJob)->dailyAt('14:00');
        $schedule->job(new RefreshFinicityAuthToken)->everyFifteenMinutes();
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
