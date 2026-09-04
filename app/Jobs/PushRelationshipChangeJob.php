<?php

namespace App\Jobs;

use App\Models\Student;
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
 * Flushes a locally-made per-student relationship change up to the server.
 * Cleared once accepted; the /api/me sync is otherwise authoritative.
 */
class PushRelationshipChangeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 0;
    public array $backoff = [10, 30, 60, 120, 300];

    public function retryUntil()
    {
        return now()->addDays(3);
    }

    public function __construct(public Student $student) {}

    public function handle(): void
    {
        if (! $this->student->relationship_pending) {
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

        $response = RemoteApiClient::put(
            $user,
            "/api/students/{$this->student->remote_id}/relationship",
            ['relationship' => $this->student->relationship],
        );

        if ($response['result'] === RemoteApiClient::RESULT_SUCCESS) {
            $this->student->update(['relationship_pending' => false]);

            return;
        }

        if ($response['result'] === RemoteApiClient::RESULT_FAILED) {
            // The server rejected it — let the next /api/me pull reconcile.
            $this->student->update(['relationship_pending' => false]);
            Log::warning('Relationship change rejected by server', ['response' => $response]);

            return;
        }

        $this->release($this->backoff[$this->attempts() - 1] ?? end($this->backoff));
    }
}
