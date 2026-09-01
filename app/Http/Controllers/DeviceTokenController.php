<?php

namespace App\Http\Controllers;

use App\Services\Remote\RemoteApiClient;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function store(Request $request)
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

    public function destroy(Request $request)
    {
        $token = $request->user()->fcm_token;

        if ($token) {
            RemoteApiClient::delete($request->user(), '/api/device-tokens', ['token' => $token]);
            $request->user()->update(['fcm_token' => null]);
        }

        return response()->json(['removed' => true]);
    }
}
