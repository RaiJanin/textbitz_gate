<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Data\PullTapsFromServer;
use App\Services\Remote\RemoteApiClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlertsController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Alerts/Main', [
            'students' => Student::query()
                ->where('user_id', $request->user()->id)
                ->orderBy('full_name')
                ->get(['id', 'remote_id', 'full_name']),
        ]);
    }

    public function feed(Request $request, int $remoteId)
    {
        $response = RemoteApiClient::get(
            $request->user(),
            "/api/students/{$remoteId}/alerts",
            $request->only('page'),
        );

        if ($response['result'] === RemoteApiClient::RESULT_SUCCESS) {
            return response()->json($response['alerts'] ?? $response['data']);
        }

        if ($response['result'] === RemoteApiClient::RESULT_FORBIDDEN) {
            // An admin detached this student from the guardian — drop the local
            // copy instead of continuing to show stale, no-longer-theirs data.
            PullTapsFromServer::detachStudent($request->user(), $remoteId);

            return response()->json(['message' => 'This child is no longer linked to your account.'], 404);
        }

        return response()->json(['alerts' => [], 'has_more' => false, 'stale' => true]);
    }
}
