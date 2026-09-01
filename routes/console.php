<?php

use App\Services\Data\PullTapsFromServer;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => ServerConnectivityService::checkAndNotify())
    ->everyMinute()
    ->name('server-connectivity-heartbeat')
    ->withoutOverlapping(1);

Schedule::call(fn () => PullTapsFromServer::refreshLinkedStudents())
    ->everyMinute()
    ->name('refresh-linked-students')
    ->withoutOverlapping(1);
