<?php

namespace App\Listeners;

use App\Services\Data\PullTapsFromServer;
use Lumi\NativePush\Events\PushNotificationReceived;

/**
 * `fatlum/nativephp-push` dispatches PushNotificationReceived in the ephemeral
 * background runtime for FCM *data* messages (the server names the event class
 * in the FCM `data.event` key).
 *
 * A tap/alert push arriving while the app is backgrounded is a cue to pull
 * fresh attendance so the app is current when reopened. Fast + synchronous —
 * the ephemeral runtime has no queue worker.
 *
 * Registered explicitly in AppServiceProvider.
 */
class HandlePushMessage
{
    public function handle(PushNotificationReceived $event): void
    {
        PullTapsFromServer::pullNow();
    }
}
