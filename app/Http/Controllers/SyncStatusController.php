<?php

namespace App\Http\Controllers;

use App\Models\LinkRequest;
use App\Models\NotificationPreference;
use App\Services\Data\PullTapsFromServer;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Lightweight JSON status surface the frontend polls to toast "N attendance
 * updates synced" after the recurring PullTapsFromServer run. Pure `api/*` JSON —
 * a 401 here renders as JSON, never a redirect.
 */
class SyncStatusController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->payload());
    }

    /**
     * Manual pull-to-refresh — runs a pull now, returns the fresh report.
     */
    public function pull(Request $request): JsonResponse
    {
        PullTapsFromServer::pullNow();

        return response()->json($this->payload());
    }

    private function payload(): array
    {
        return [
            'online' => ServerConnectivityService::isOnline(),
            'report' => cache(PullTapsFromServer::REPORT_CACHE_KEY),
            'pending_writes' => NotificationPreference::where('sync_status', '!=', NotificationPreference::SYNC_STATUS_SYNCED)->count()
                + LinkRequest::where('sync_status', '!=', LinkRequest::SYNC_STATUS_SYNCED)->count(),
        ];
    }
}
