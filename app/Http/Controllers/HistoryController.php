<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Remote\RemoteApiClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HistoryController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('History/Main', [
            'students' => Student::query()
                ->orderBy('full_name')
                ->get(['id', 'remote_id', 'full_name', 'grade', 'section']),
        ]);
    }

    /**
     * Month calendar + daily records for one student, proxied from the server.
     */
    public function month(Request $request, int $remoteId)
    {
        $month = $request->query('month');

        $response = RemoteApiClient::get(
            $request->user(),
            "/api/students/{$remoteId}/history",
            $month ? ['month' => $month] : [],
        );

        if ($response['result'] === RemoteApiClient::RESULT_SUCCESS) {
            return response()->json($response['data']);
        }

        // Offline: fold the local tap cache into day records for the requested month.
        $student = Student::byRemoteId($remoteId)->firstOrFail();
        $tz = $student->school_timezone ?: 'Asia/Manila';
        $anchor = $month ? \Illuminate\Support\Carbon::createFromFormat('Y-m', $month, $tz) : now($tz);

        $days = $student->tapEvents()
            ->whereBetween('tapped_at', [$anchor->copy()->startOfMonth(), $anchor->copy()->endOfMonth()])
            ->orderBy('tapped_at')
            ->get()
            ->groupBy(fn ($tap) => $tap->tapped_at->toDateString())
            ->map(function ($taps, $date) {
                $firstIn = $taps->firstWhere('direction', 'in');

                return [
                    'date' => $date,
                    'first_in' => optional($firstIn)->tapped_at?->format('H:i'),
                    'last_out' => optional($taps->where('direction', 'out')->last())->tapped_at?->format('H:i'),
                    'state' => $firstIn?->is_late ? 'late' : ($firstIn ? 'on_time' : 'none'),
                    'taps' => $taps->map(fn ($tap) => [
                        'direction' => $tap->direction,
                        'at' => $tap->tapped_at->format('H:i'),
                        'gate' => $tap->gate_name,
                        'is_late' => $tap->is_late,
                    ])->values()->all(),
                ];
            })
            ->values();

        return response()->json([
            'month' => $anchor->format('Y-m'),
            'days' => $days,
            'stale' => true,
        ]);
    }
}
