<?php

namespace App\Services\Remote;

use App\Events\ServerConnectionRestored;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;

class ServerConnectivityService extends RemoteApiClient
{
    protected static int $cacheSeconds = 15;
    protected static int $timeoutSeconds = 25;
    protected static string $lastKnownStateKey = 'remote_connectivity_last_known';

    protected static function healthUrl(): string
    {
        return self::url('/api/health');
    }

    public static function isOnline(bool $fresh = false): bool
    {
        if (!$fresh && Cache::has('remote_connectivity')) {
            return Cache::get('remote_connectivity');
        }

        $online = self::ping();

        Cache::put('remote_connectivity', $online, self::$cacheSeconds);

        return $online;
    }

    public static function ping(): bool
    {
        try {
            $response = Http::timeout(self::$timeoutSeconds)
                ->get(self::healthUrl());

            return $response->successful();

        } catch (ConnectionException $e) {
            Log::warning('Remote server unreachable', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Heartbeat entry point (scheduled every minute). Runs a fresh ping,
     * and fires ServerConnectionRestored the moment it detects the server
     * transitioning from unreachable to reachable.
     */
    public static function checkAndNotify(): bool
    {
        $wasOnline = Cache::get(self::$lastKnownStateKey, false);
        $isOnline = self::isOnline(fresh: true);

        Cache::forever(self::$lastKnownStateKey, $isOnline);

        if ($isOnline && !$wasOnline) {
            Log::info('Remote server connection restored');
            ServerConnectionRestored::dispatch(now());
        }

        return $isOnline;
    }
}
