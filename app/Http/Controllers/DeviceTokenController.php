<?php

namespace App\Http\Controllers;

use App\Services\Remote\RemoteApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Client-side FCM device-token endpoint. Stores the token on the local user and
 * forwards it to the server's device_tokens store. Plain JSON (`api/*`) so a
 * failure never turns into a login redirect.
 */
class DeviceTokenController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'sometimes|string|max:32',
        ]);

        $request->user()->update(['fcm_token' => $validated['token']]);

        $response = RemoteApiClient::post($request->user(), '/api/device-tokens', [
            'token' => $validated['token'],
            'platform' => $validated['platform'] ?? 'android',
        ]);

        return response()->json([
            'registered' => $response['result'] === RemoteApiClient::RESULT_SUCCESS,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $token = $request->user()->fcm_token;

        if ($token) {
            RemoteApiClient::delete($request->user(), '/api/device-tokens', ['token' => $token]);
            $request->user()->update(['fcm_token' => null]);
        }

        return response()->json(['removed' => true]);
    }
}
