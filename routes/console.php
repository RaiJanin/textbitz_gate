<?php

use App\Support\Workers;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| The periodic client workers. One definition (App\Support\Workers::tasks()),
| two runners: the desktop scheduler below, and — on a device, where there is
| no scheduler — the app-lifecycle plugin's foreground event
| (App\Support\Lifecycle\RunScheduledWorkers).
*/
foreach (Workers::tasks() as $name => $task) {
    Schedule::call($task)
        ->everyMinute()
        ->name($name)
        ->withoutOverlapping(1);
}
