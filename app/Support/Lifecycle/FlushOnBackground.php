<?php

namespace App\Support\Lifecycle;

use App\Services\Data\FlushPendingSyncs;
use Djurovicigoor\AppLifecycle\Events\AppBackgrounded;
use Illuminate\Support\Facades\Cache;

/**
 * Last chance before the OS suspends the app: push any pending preference /
 * link-code writes so they don't sit until the next foreground. Fast + sync.
 */
class FlushOnBackground
{
    public function handle(AppBackgrounded $event): void
    {
        Cache::put('gate.last_background_at', $event->timestamp);

        FlushPendingSyncs::run();
    }
}
