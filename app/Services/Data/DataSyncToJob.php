<?php

namespace App\Services\Data;

use App\Jobs\PushLinkRequestJob;
use App\Jobs\PushPreferenceChangeJob;
use App\Models\LinkRequest;
use App\Models\NotificationPreference;
use Illuminate\Support\Facades\Log;

/**
 * Flushes the only two things the client ever writes back to the server:
 * notification-preference changes and school-issued link requests. Keeps the
 * retry-on-reconnect mechanism the old blast sync used.
 */
class DataSyncToJob
{
    public static function retryPendingSyncs(): void
    {
        foreach (self::syncTasks() as $name => $task) {
            try {
                $task();
            } catch (\Throwable $e) {
                Log::error("Sync retry failed for [{$name}]", ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * @return array<string, callable>
     */
    protected static function syncTasks(): array
    {
        return [
            'preferences' => fn () => self::retryPreferences(),
            'link_requests' => fn () => self::retryLinkRequests(),
        ];
    }

    protected static function retryPreferences(): void
    {
        NotificationPreference::whereIn('sync_status', [
            NotificationPreference::SYNC_STATUS_PENDING,
            NotificationPreference::SYNC_STATUS_FAILED,
        ])->each(fn (NotificationPreference $preference) => PushPreferenceChangeJob::dispatch($preference));
    }

    protected static function retryLinkRequests(): void
    {
        LinkRequest::whereIn('sync_status', [
            LinkRequest::SYNC_STATUS_PENDING,
            LinkRequest::SYNC_STATUS_FAILED,
        ])->each(fn (LinkRequest $linkRequest) => PushLinkRequestJob::dispatch($linkRequest));
    }
}
