<?php

use App\Console\Commands\UpdateAppVersion;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('version', function () {
    $this->comment(UpdateAppVersion::class);
});

Schedule::command('reminder:achievement')
    ->monthlyOn(1, '07:30')
    ->withoutOverlapping()
    ->runInBackground();

// Schedule::command('reminder:achievement')
//     ->everyMinute()
//     ->withoutOverlapping();

// Schedule::command('reminder:approval-achievement')
//     ->everyMinute()
//     ->withoutOverlapping();

Schedule::command('reminder:approval-achievement')
    ->monthlyOn(1, '07:30')
    ->withoutOverlapping()
    ->runInBackground();
    