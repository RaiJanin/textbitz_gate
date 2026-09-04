<?php

namespace App\Services\Data;

use App\Models\Gate;
use App\Models\LinkRequest;
use App\Models\NotificationPreference;
use App\Models\Student;
use App\Models\TapEvent;
use App\Models\User;
use App\Services\Remote\RemoteApiClient;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Pulls the read-only attendance data owned by the server into the local
 * SQLite cache so the app works offline. Each recurring run leaves a "sync
 * report" in the cache (`gate.last_sync_report`) that the frontend polls via
 * `GET /api/sync/status` to toast "N attendance updates synced".
 *
 * The local cache mirrors exactly ONE remote account at a time (the device is
 * single-user). `adoptAccount()` wipes it whenever the signed-in account
 * changes so demo data / a previous login never bleeds across accounts.
 */
class PullTapsFromServer
{
    public const REPORT_CACHE_KEY = 'gate.last_sync_report';

    /** Remote user id the local read-cache currently belongs to. */
    public const CACHE_OWNER_KEY = 'gate.cache_owner_remote_id';

    /**
     * The single account whose data the local cache mirrors: the signed-in user
     * when we're in a request, otherwise the most recently connected account.
     */
    public static function activeUser(): ?User
    {
        $user = Auth::user();

        if ($user instanceof User && $user->remote_token) {
            return $user;
        }

        return User::whereNotNull('remote_token')
            ->orderByDesc('remote_synced_at')
            ->first();
    }

    /**
     * True when the local cache belongs to the given account (or to nobody yet,
     * e.g. a fresh install still showing demo data). Used by the read layer to
     * avoid flashing another account's students between login and the first sync.
     */
    public static function cacheBelongsTo(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $owner = Cache::get(self::CACHE_OWNER_KEY);

        return $owner === null || (int) $owner === (int) $user->remote_id;
    }

    /**
     * Claim the local read-cache for this account. When it was demonstrably
     * owned by a *different* remote account, wipe what that account left behind
     * first. An unknown owner (fresh install / first run after this shipped) is
     * adopted silently — `pruneStudentsNotIn()` on the next `/api/me` still
     * reconciles any stale students.
     */
    public static function adoptAccount(User $user): void
    {
        if (! $user->remote_id) {
            return; // not connected to the server yet — nothing to scope to
        }

        $owner = Cache::get(self::CACHE_OWNER_KEY);

        if ($owner !== null && (int) $owner === (int) $user->remote_id) {
            return;
        }

        if ($owner !== null) {
            self::purgeCache();
        }

        Cache::forever(self::CACHE_OWNER_KEY, (int) $user->remote_id);
    }

    /**
     * Wipe the local read-cache (server-owned data only — never touches users
     * or auth). Safe to call offline.
     */
    public static function purgeCache(): void
    {
        TapEvent::query()->delete();
        Student::query()->delete();
        Gate::query()->delete();
        LinkRequest::query()->delete();
        NotificationPreference::query()->delete();
        Cache::forget(self::REPORT_CACHE_KEY);
    }

    /**
     * Recurring pull (connectivity heartbeat + on ServerConnectionRestored).
     */
    public static function refreshLinkedStudents(): void
    {
        if (! ServerConnectivityService::isOnline()) {
            return;
        }

        $user = self::activeUser();

        if (! $user) {
            return;
        }

        self::cacheReport(self::forUser($user));
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

        $user = self::activeUser();

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

        // Make sure the local cache belongs to this account before we write to it.
        self::adoptAccount($user);

        $me = RemoteApiClient::get($user, '/api/me');

        if ($me['result'] !== RemoteApiClient::RESULT_SUCCESS) {
            Log::info('PullTapsFromServer: /api/me not available', ['result' => $me['result']]);

            return $report;
        }

        self::syncPreferences($me['data']['guardian'] ?? null, $me['data']['student'] ?? null, $user);

        // Adopt the guardian's default relationship from the server, unless a
        // link request that would set it is still waiting to sync.
        $role = $me['data']['guardian']['role'] ?? null;
        if ($role && ! LinkRequest::where('sync_status', LinkRequest::SYNC_STATUS_PENDING)->exists()) {
            $user->update(['active_role' => \App\Support\Relationship::normalize($role)]);
        }

        $students = collect($me['data']['guardian']['students'] ?? []);

        if ($studentSelf = ($me['data']['student']['student'] ?? null)) {
            $students->push($studentSelf);
        }

        $students = $students->unique('id')->values();

        // The server is authoritative: drop any locally cached student the
        // account is no longer linked to (an empty list clears them all).
        self::pruneStudentsNotIn($students->pluck('id')->all());

        $students->each(function (array $remote) use ($user, &$report) {
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
     * Delete cached students whose remote id isn't in the given set. Model
     * deletes so `tap_events` cascade. An empty set clears every row.
     *
     * @param  array<int, int>  $remoteIds
     */
    private static function pruneStudentsNotIn(array $remoteIds): void
    {
        $query = Student::query();

        if ($remoteIds !== []) {
            $query->whereNotIn('remote_id', $remoteIds);
        }

        $query->get()->each->delete();
    }

    /**
     * Runs a pull now and returns the fresh cached report (used by the
     * manual /api/sync/pull endpoint).
     */
    public static function pullNow(): ?array
    {
        $user = self::activeUser();

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
        $student = Student::firstOrNew(['remote_id' => $remote['id']]);

        $student->fill([
            'full_name' => $remote['full_name'],
            'grade' => $remote['grade'] ?? null,
            'section' => $remote['section'] ?? null,
            'avatar_path' => $remote['avatar_path'] ?? null,
            'school_remote_id' => $remote['school']['id'] ?? null,
            'school_name' => $remote['school']['name'] ?? null,
            'school_timezone' => $remote['school']['timezone'] ?? 'Asia/Manila',
            'school_cutoff_time' => $remote['school']['attendance_cutoff_time'] ?? null,
            'school_contact_phone' => $remote['school']['contact_phone'] ?? null,
            'school_contact_email' => $remote['school']['contact_email'] ?? null,
            'synced_at' => now(),
        ]);

        // Don't clobber a per-student relationship change the user made offline
        // that hasn't reached the server yet.
        if (! $student->relationship_pending) {
            $student->relationship = $remote['relationship'] ?? null;
        }

        $student->save();

        return $student;
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