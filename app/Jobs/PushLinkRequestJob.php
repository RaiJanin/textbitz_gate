<?php

namespace App\Jobs;

use App\Models\LinkRequest;
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
 * Flushes a queued guardian↔student link request (school-issued code) to the
 * server. Same retry pattern as the preference-change job.
 */
class PushLinkRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;
    public array $backoff = [10, 30, 60, 120, 300];

    public function retryUntil()
    {
        return now()->addDays(3);
    }

    public function __construct(public LinkRequest $linkRequest) {}

    public function handle(): void
    {
        if ($this->linkRequest->sync_status === LinkRequest::SYNC_STATUS_SYNCED) {
            return;
        }

        if (! ServerConnectivityService::isOnline()) {
            $this->release(30);

            return;
        }

        $user = User::whereNotNull('remote_token')->first();

        if (! $user) {
            $this->release($this->backoff[$this->attempts() - 1] ?? end($this->backoff));

            return;
        }

        $response = RemoteApiClient::post($user, '/api/link/request', [
            'code' => $this->linkRequest->code,
            'relationship' => $this->linkRequest->relationship,
        ]);

        if ($response['result'] === RemoteApiClient::RESULT_SUCCESS) {
            $this->linkRequest->update([
                'sync_status' => LinkRequest::SYNC_STATUS_SYNCED,
                'synced_at' => now(),
            ]);

            PullTapsFromServer::forUser($user);

            return;
        }

        if ($response['result'] === RemoteApiClient::RESULT_FAILED) {
            $this->linkRequest->update([
                'sync_status' => LinkRequest::SYNC_STATUS_FAILED,
                'last_error' => $response['data']['message'] ?? 'Link code rejected.',
            ]);
            Log::warning('Link request rejected by server', ['response' => $response]);
            $this->fail();

            return;
        }

        $this->release($this->backoff[$this->attempts() - 1] ?? end($this->backoff));
    }

    public function failed(\Throwable $exception): void
    {
        $this->linkRequest->update(['sync_status' => LinkRequest::SYNC_STATUS_FAILED]);
    }
}
