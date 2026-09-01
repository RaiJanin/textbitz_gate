<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Remote\RemoteApiClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Home/Main', [
            'students' => Student::query()
                ->orderBy('full_name')
                ->get(['id', 'remote_id', 'full_name', 'grade', 'section', 'relationship', 'school_name']),
        ]);
    }

    /**
     * Today's status for one student — served from the local cache, refreshed
     * from the server when reachable.
     */
    public function status(Request $request, int $remoteId)
    {
        $user = $request->user();

        $response = RemoteApiClient::get($user, "/api/students/{$remoteId}/status");

        if ($response['result'] === RemoteApiClient::RESULT_SUCCESS) {
            // The scheduled PullTapsFromServer keeps the local cache warm — don't
            // run a write-heavy pull inside this (often realtime-triggered) request.
            return response()->json($response['data']);
        }

        // Offline fallback: rebuild a minimal status from the local tap cache.
        $student = Student::byRemoteId($remoteId)->firstOrFail();
        $today = now(($student->school_timezone ?: 'Asia/Manila'))->toDateString();

        $taps = $student->tapEvents()
            ->whereDate('tapped_at', $today)
            ->orderBy('tapped_at')
            ->get(['direction', 'tapped_at', 'gate_name', 'is_late']);

        $last = $taps->last();

        return response()->json([
            'date' => $today,
            'presence' => match (true) {
                $last === null => 'not_arrived',
                $last->direction === 'in' => 'at_school',
                default => 'left',
            },
            'first_in' => optional($taps->firstWhere('direction', 'in'))->tapped_at?->format('H:i'),
            'last_out' => optional($taps->where('direction', 'out')->last())->tapped_at?->format('H:i'),
            'timeline' => $taps->map(fn ($tap) => [
                'direction' => $tap->direction,
                'at' => $tap->tapped_at->format('H:i'),
                'gate' => $tap->gate_name,
                'is_late' => $tap->is_late,
            ])->all(),
            'stale' => true,
        ]);
    }
}
