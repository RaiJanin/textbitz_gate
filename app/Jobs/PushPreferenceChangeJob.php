<?php

namespace App\Jobs;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Data\PullTapsFromServer;
use App\Services\Remote\RemoteApiClient;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Flushes a locally-made notification-preference change up to the server. Uses
 * the same release/backoff retry pattern as the old blast push jobs.
 */
class PushPreferenceChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;
    public array $backoff = [10, 30, 60, 120, 300];

    public function retryUntil()
    {
        return now()->addDays(3);
    }

    public function __construct(public NotificationPreference $preference) {}

    public function handle(): void
    {
        if ($this->preference->sync_status === NotificationPreference::SYNC_STATUS_SYNCED) {
            return;
        }

        if (! ServerConnectivityService::isOnline()) {
            $this->release(30);

            return;
        }

        $user = PullTapsFromServer::activeUser()
            ?? User::whereNotNull('remote_token')->first();

        if (! $user || ! $user->remote_token) {
            $this->release($this->backoff[$this->attempts() - 1] ?? end($this->backoff));

            return;
        }

        $response = RemoteApiClient::put($user, '/api/notification-preferences', [
            'role' => $this->preference->role,
            'arrival' => $this->preference->arrival,
            'departure' => $this->preference->departure,
            'late_alert' => $this->preference->late_alert,
            'weekly_summary' => $this->preference->weekly_summary,
        ]);

        if ($response['result'] === RemoteApiClient::RESULT_SUCCESS) {
            $this->preference->update([
                'sync_status' => NotificationPreference::SYNC_STATUS_SYNCED,
                'synced_at' => now(),
            ]);

            return;
        }

        if ($response['result'] === RemoteApiClient::RESULT_FAILED) {
            $this->preference->update(['sync_status' => NotificationPreference::SYNC_STATUS_FAILED]);
            Log::warning('Preference change rejected by server', ['response' => $response]);
            $this->fail();

            return;
        }

        $this->release($this->backoff[$this->attempts() - 1] ?? end($this->backoff));
    }

    public function failed(\Throwable $exception): void
    {
        $this->preference->update(['sync_status' => NotificationPreference::SYNC_STATUS_FAILED]);
    }
}
