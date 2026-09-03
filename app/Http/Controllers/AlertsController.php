<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Remote\RemoteApiClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AlertsController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Alerts/Main', [
            'students' => Student::query()
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

        return response()->json(['alerts' => [], 'has_more' => false, 'stale' => true]);
    }
}
