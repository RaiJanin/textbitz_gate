<?php

namespace App\Listeners;

use App\Events\ServerConnectionRestored;
use App\Services\Data\DataSyncToJob;
use App\Services\Data\PullTapsFromServer;
use App\Services\Remote\RemoteAuthService;

class SyncPendingClientData
{
    public function handle(ServerConnectionRestored $event): void
    {
        RemoteAuthService::flushPendingAuth();
        RemoteAuthService::verifyAllTokens();
        DataSyncToJob::retryPendingSyncs();
        PullTapsFromServer::refreshLinkedStudents();
        PullTapsFromServer::refreshGates();
    }
}
