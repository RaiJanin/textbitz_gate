<?php

namespace App\Services\Data;

/**
 * Back-compat shim. The client's only outbound writes (notification-preference
 * changes + school-issued link requests) are now flushed SYNCHRONOUSLY by
 * FlushPendingSyncs, so they work on mobile where there is no queue worker.
 */
class DataSyncToJob
{
    public static function retryPendingSyncs(): void
    {
        FlushPendingSyncs::run();
    }
}
