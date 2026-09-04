<?php

namespace App\Services\Data;

use App\Jobs\PushLinkRequestJob;
use App\Jobs\PushPreferenceChangeJob;
use App\Jobs\PushRelationshipChangeJob;
use App\Models\LinkRequest;
use App\Models\NotificationPreference;
use App\Models\Student;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Support\Facades\Log;

/**
 * Pushes the only two things the client writes back to the server —
 * notification-preference changes and school-issued link requests —
 * SYNCHRONOUSLY.
 *
 * NativePHP Mobile has no queue worker, so the push jobs are run inline
 * (dispatchSync). Anything that doesn't go through stays `pending` / `failed`
 * and is retried on the next trigger: ServerConnectionRestored, an app
 * foreground/background lifecycle event, or the desktop scheduler.
 */
class FlushPendingSyncs
{
    public static function run(): int
    {
        if (! ServerConnectivityService::isOnline()) {
            return 0;
        }

        $flushed = 0;

        NotificationPreference::whereIn('sync_status', [
            NotificationPreference::SYNC_STATUS_PENDING,
            NotificationPreference::SYNC_STATUS_FAILED,
        ])->get()->each(function (NotificationPreference $preference) use (&$flushed) {
            try {
                PushPreferenceChangeJob::dispatchSync($preference);
                $flushed++;
            } catch (\Throwable $e) {
                Log::warning('FlushPendingSyncs: preference push failed', ['error' => $e->getMessage()]);
            }
        });

        LinkRequest::whereIn('sync_status', [
            LinkRequest::SYNC_STATUS_PENDING,
            LinkRequest::SYNC_STATUS_FAILED,
        ])->get()->each(function (LinkRequest $linkRequest) use (&$flushed) {
            try {
                PushLinkRequestJob::dispatchSync($linkRequest);
                $flushed++;
            } catch (\Throwable $e) {
                Log::warning('FlushPendingSyncs: link request push failed', ['error' => $e->getMessage()]);
            }
        });

        Student::where('relationship_pending', true)->get()->each(function (Student $student) use (&$flushed) {
            try {
                PushRelationshipChangeJob::dispatchSync($student);
                $flushed++;
            } catch (\Throwable $e) {
                Log::warning('FlushPendingSyncs: relationship push failed', ['error' => $e->getMessage()]);
            }
        });

        return $flushed;
    }
}
