<?php

namespace App\Support\Lifecycle;

use App\Support\Workers;
use Djurovicigoor\AppLifecycle\Events\AppForegrounded;
use Illuminate\Support\Facades\Cache;

/**
 * A device has no `schedule:work`. Every time the app returns to the
 * foreground, run the EXACT same task set the desktop scheduler runs
 * (App\Support\Workers) — synchronously.
 */
class RunScheduledWorkers
{
    public function handle(AppForegrounded $event): void
    {
        Cache::put('gate.last_foreground_at', $event->timestamp);

        Workers::runAll();
    }
}
