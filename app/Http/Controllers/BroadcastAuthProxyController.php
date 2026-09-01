<?php

namespace App\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BroadcastAuthProxyController extends Controller
{
    /**
     * Proxies Echo's private-channel auth to the server using the bridged
     * remote token.
     *
     * Never returns 401 and never clears the local remote token: a transient
     * failure here must not look like "your session expired" to the app or wipe
     * the token the rest of the client depends on. The scheduled
     * `RemoteAuthService::verifyAllTokens()` is the single authority on token
     * death.
     */
    public function authorize(Request $request)
    {
        $user = $request->user();
        $token = $user?->remote_token;

        if (! $token) {
            return response()->json(['message' => 'No remote session'], 403);
        }

        $request->validate([
            'channel_name' => 'required|string',
            'socket_id' => 'required|string',
        ]);

        $url = rtrim((string) config('services.textbitz.server_url'), '/').'/api/broadcasting/auth';

        try {
            $response = Http::withToken($token)
                ->acceptJson()
                ->timeout(10)
                ->asForm()
                ->post($url, [
                    'channel_name' => $request->input('channel_name'),
                    'socket_id' => $request->input('socket_id'),
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Broadcast auth proxy: remote unreachable', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Remote server unreachable'], 503);
        }

        if ($response->successful()) {
            return response($response->body(), 200)->header('Content-Type', 'application/json');
        }

        Log::warning('Broadcast auth proxy: server rejected channel auth', [
            'url' => $url,
            'status' => $response->status(),
            'channel' => $request->input('channel_name'),
            'server_body' => mb_substr($response->body(), 0, 500),
        ]);

        // Map every failure to 403 so Echo stops retrying that channel and no
        // upstream layer treats it as an auth-expired redirect.
        return response()->json([
            'message' => 'Channel authorization denied',
            'upstream_status' => $response->status(),
        ], 403);
    }
}
