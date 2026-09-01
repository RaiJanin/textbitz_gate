<?php

namespace App\Http\Controllers;

use App\Jobs\PushPreferenceChangeJob;
use App\Models\NotificationPreference;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    /**
     * Update a preference locally (instant), then push it to the server — or
     * queue it for the next reconnect if offline. Responds with an Inertia
     * redirect so the Settings page re-renders with fresh props.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'role' => 'required|in:guardian,student',
            'arrival' => 'sometimes|boolean',
            'departure' => 'sometimes|boolean',
            'late_alert' => 'sometimes|boolean',
            'weekly_summary' => 'sometimes|boolean',
        ]);

        $preference = NotificationPreference::firstOrNew(['role' => $validated['role']]);
        $preference->fill(collect($validated)->except('role')->all());
        $preference->sync_status = NotificationPreference::SYNC_STATUS_PENDING;
        $preference->save();

        if (ServerConnectivityService::isOnline()) {
            PushPreferenceChangeJob::dispatch($preference);
        }

        return back()->with('success', 'Notification preferences updated.');
    }
}
