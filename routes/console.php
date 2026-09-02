<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Commands
|--------------------------------------------------------------------------
|
| proctoring:flush-heartbeats
|   Drains the Redis heartbeat buffer and bulk-inserts into the DB.
|   Runs every minute (Laravel's minimum). For per-second granularity
|   in production, use the Octane tick or a dedicated Horizon heartbeat
|   worker on the `heartbeats` queue.
|
*/

Schedule::command('proctoring:flush-heartbeats')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/heartbeat-flush.log'));
