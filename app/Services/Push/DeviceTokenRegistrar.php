<?php

namespace App\Services\Push;

use App\Models\User;
use App\Services\Remote\RemoteApiClient;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Support\Facades\Log;

/**
 * Stores this device's FCM token locally and forwards it to the server so
 * `SendPushNotification` can reach the device while the app is backgrounded or
 * killed. Safe no-op on web (no token) and when the server is unreachable —
 * the token is re-sent on the next successful pull / lifecycle event.
 */
class DeviceTokenRegistrar
{
    public static function register(?User $user, ?string $token, string $platform = 'android'): void
    {
        if (! $user || ! $token || $user->fcm_token === $token) {
            return;
        }

        $user->update(['fcm_token' => $token]);

        if (! $user->remote_token || ! ServerConnectivityService::isOnline()) {
            return;
        }

        $response = RemoteApiClient::post($user, '/api/device-tokens', [
            'token' => $token,
            'platform' => $platform,
        ]);

        if ($response['result'] !== RemoteApiClient::RESULT_SUCCESS) {
            Log::info('Device token not yet registered with server', ['result' => $response['result']]);
        }
    }
}
