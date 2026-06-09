<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use App\Console\Commands\FetchAndStoreEmployees;

class ScheduleServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(Schedule $schedule)
    {
        // $schedule->command("fetch:employees")->dailyAt("00:10");
        // $schedule->command('update:employee-access-menu')->dailyAt('00:01');
        $schedule->command("update:employee-access-menu")->everyMinute();
        $schedule->command("app:reminderSchedules")->dailyAt("08:00");
        $schedule->command("app:inactive-employees")->dailyAt("00:20");
        $schedule->command("app:update-designations")->dailyAt("01:00");
        // $schedule->command('app:daily-update-schedulepa')->dailyAt('00:05');
        // $schedule->command("app:daily-update-schedulepa")->everyMinute();

        // $schedule->command("app:reminder:achievement")->monthlyOn(1, '07:30')->withoutOverlapping()->runInBackground();
        // $schedule->command("app:reminder:achievement")->everyMinute();
        $schedule->command("app:reminder:achievement")->dailyAt("08:05")->withoutOverlapping()->runInBackground();
        // $schedule->command("app:reminder:approval-achievement")->monthlyOn(1, '07:30')->withoutOverlapping()->runInBackground();
        // $schedule->command("app:reminder:approval-achievement")->everyMinute();
        $schedule->command("app:reminder:approval-achievement")->dailyAt("08:05")->withoutOverlapping()->runInBackground();
        // $schedule
        //     ->command("update:bt-to-db")
        //     ->dailyAt("00:15")
        //     ->withoutOverlapping();
    }
}