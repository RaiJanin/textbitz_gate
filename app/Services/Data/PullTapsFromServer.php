<?php

namespace App\Services\Data;

use App\Models\Gate;
use App\Models\NotificationPreference;
use App\Models\Student;
use App\Models\TapEvent;
use App\Models\User;
use App\Services\Remote\RemoteApiClient;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pulls the read-only attendance data owned by the server into the local
 * SQLite cache so the app works offline. Each recurring run leaves a "sync
 * report" in the cache (`gate.last_sync_report`) that the frontend polls via
 * `GET /api/sync/status` to toast "N attendance updates synced".
 */
class PullTapsFromServer
{
    public const REPORT_CACHE_KEY = 'gate.last_sync_report';

    /**
     * Recurring pull (connectivity heartbeat + on ServerConnectionRestored).
     */
    public static function refreshLinkedStudents(): void
    {
        if (! ServerConnectivityService::isOnline()) {
            return;
        }

        $totals = ['new_taps' => 0, 'updated_taps' => 0, 'students' => []];

        User::whereNotNull('remote_token')->each(function (User $user) use (&$totals) {
            $report = self::forUser($user);

            $totals['new_taps'] += $report['new_taps'];
            $totals['updated_taps'] += $report['updated_taps'];
            $totals['students'] = array_values(array_unique([...$totals['students'], ...$report['students']]));
        });

        self::cacheReport($totals);
    }

    /**
     * Recurring pull (connectivity heartbeat). Gates aren't per-user like
     * students are, so this just uses whichever authenticated account is
     * available to make the /api/gates call. No /api/me equivalent exists
     * for gates, so this is the only path that keeps local Gate rows
     * (name, status, last_seen_at) from silently going stale — previously
     * they were only ever set once, by DemoSeeder, and never touched again.
     */
    public static function refreshGates(): void
    {
        if (! ServerConnectivityService::isOnline()) {
            return;
        }

        $user = User::whereNotNull('remote_token')->first();

        if (! $user) {
            return;
        }

        $response = RemoteApiClient::get($user, '/api/gates');

        if ($response['result'] !== RemoteApiClient::RESULT_SUCCESS) {
            Log::info('PullTapsFromServer: /api/gates not available', ['result' => $response['result']]);

            return;
        }

        collect($response['data']['gates'] ?? [])->each(fn (array $remote) => self::upsertGate($remote));
    }

    private static function upsertGate(array $remote): Gate
    {
        return Gate::updateOrCreate(
            ['remote_id' => $remote['id']],
            [
                'name' => $remote['name'],
                'status' => $remote['status'] ?? null,
                'last_seen_at' => $remote['last_seen_at'] ?? null,
                'synced_at' => now(),
            ],
        );
    }

    /**
     * One-off pull for a single account (link flow, manual pull-to-refresh).
     *
     * @return array{new_taps:int, updated_taps:int, students:array<int,string>}
     */
    public static function forUser(User $user): array
    {
        $report = ['new_taps' => 0, 'updated_taps' => 0, 'students' => []];

        $me = RemoteApiClient::get($user, '/api/me');

        if ($me['result'] !== RemoteApiClient::RESULT_SUCCESS) {
            Log::info('PullTapsFromServer: /api/me not available', ['result' => $me['result']]);

            return $report;
        }

        self::syncPreferences($me['data']['guardian'] ?? null, $me['data']['student'] ?? null, $user);

        $students = collect($me['data']['guardian']['students'] ?? []);

        if ($studentSelf = ($me['data']['student']['student'] ?? null)) {
            $students->push($studentSelf);
        }

        $students->unique('id')->each(function (array $remote) use ($user, &$report) {
            $student = self::upsertStudent($remote);
            $counts = self::pullStudentTaps($user, $student);

            $report['new_taps'] += $counts['new'];
            $report['updated_taps'] += $counts['updated'];

            if ($counts['new'] > 0 || $counts['updated'] > 0) {
                $report['students'][] = $student->full_name;
            }
        });

        return $report;
    }

    /**
     * Runs a pull now and returns the fresh cached report (used by the
     * manual /api/sync/pull endpoint).
     */
    public static function pullNow(): ?array
    {
        $user = User::whereNotNull('remote_token')->first();

        if (! $user || ! ServerConnectivityService::isOnline()) {
            return Cache::get(self::REPORT_CACHE_KEY);
        }

        return self::cacheReport(self::forUser($user));
    }

    /**
     * @param  array{new_taps:int, updated_taps:int, students:array<int,string>}  $totals
     * @return array<string, mixed>
     */
    private static function cacheReport(array $totals): array
    {
        $report = [
            'at' => now()->toIso8601String(),
            'new_taps' => $totals['new_taps'],
            'updated_taps' => $totals['updated_taps'],
            'students' => $totals['students'],
        ];

        Cache::put(self::REPORT_CACHE_KEY, $report, now()->addHours(6));

        return $report;
    }

    private static function upsertStudent(array $remote): Student
    {
        return Student::updateOrCreate(
            ['remote_id' => $remote['id']],
            [
                'full_name' => $remote['full_name'],
                'grade' => $remote['grade'] ?? null,
                'section' => $remote['section'] ?? null,
                'avatar_path' => $remote['avatar_path'] ?? null,
                'relationship' => $remote['relationship'] ?? null,
                'school_remote_id' => $remote['school']['id'] ?? null,
                'school_name' => $remote['school']['name'] ?? null,
                'school_timezone' => $remote['school']['timezone'] ?? 'Asia/Manila',
                'school_cutoff_time' => $remote['school']['attendance_cutoff_time'] ?? null,
                'school_contact_phone' => $remote['school']['contact_phone'] ?? null,
                'school_contact_email' => $remote['school']['contact_email'] ?? null,
                'synced_at' => now(),
            ],
        );
    }

    /**
     * @return array{new:int, updated:int}
     */
    private static function pullStudentTaps(User $user, Student $student): array
    {
        $status = RemoteApiClient::get($user, "/api/students/{$student->remote_id}/status");

        if ($status['result'] !== RemoteApiClient::RESULT_SUCCESS) {
            return ['new' => 0, 'updated' => 0];
        }

        $new = 0;
        $updated = 0;

        foreach ($status['data']['timeline'] ?? [] as $tap) {
            $row = TapEvent::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'tapped_at' => Carbon::parse($status['data']['date'].' '.$tap['at']),
                    'direction' => $tap['direction'],
                ],
                [
                    'gate_name' => $tap['gate'] ?? null,
                    'is_late' => $tap['is_late'] ?? false,
                    'synced_at' => now(),
                ],
            );

            if ($row->wasRecentlyCreated) {
                $new++;
            } elseif ($row->wasChanged(['gate_name', 'is_late'])) {
                $updated++;
            }
        }

        return ['new' => $new, 'updated' => $updated];
    }

    private static function syncPreferences(?array $guardian, ?array $student, User $user): void
    {
        $prefs = RemoteApiClient::get($user, '/api/notification-preferences');

        if ($prefs['result'] !== RemoteApiClient::RESULT_SUCCESS) {
            return;
        }

        foreach ($prefs['data']['preferences'] ?? [] as $preference) {
            $local = NotificationPreference::firstOrNew(['role' => $preference['role']]);

            // Don't clobber a change the user made offline that hasn't synced yet.
            if ($local->sync_status === NotificationPreference::SYNC_STATUS_PENDING) {
                continue;
            }

            $local->fill([
                'arrival' => $preference['arrival'],
                'departure' => $preference['departure'],
                'late_alert' => $preference['late_alert'],
                'weekly_summary' => $preference['weekly_summary'],
                'sync_status' => NotificationPreference::SYNC_STATUS_SYNCED,
                'synced_at' => now(),
            ])->save();
        }
    }
}