<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\Student;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $student = Student::query()->where('user_id', $userId)->first();

        // Guarantee at least the guardian toggles are shown even before the
        // first sync from the server has populated preferences.
        NotificationPreference::firstOrCreate(['user_id' => $userId, 'role' => NotificationPreference::ROLE_GUARDIAN]);

        return Inertia::render('Settings/Main', [
            'linkedStudents' => Student::query()
                ->where('user_id', $userId)
                ->orderBy('full_name')
                ->get(['id', 'remote_id', 'full_name', 'relationship', 'grade', 'section']),
            'defaultRelationship' => $request->user()->active_role ?: 'Guardian',
            'relationshipOptions' => \App\Support\Relationship::VALUES,
            'preferences' => NotificationPreference::where('user_id', $userId)->get(['role', 'arrival', 'departure', 'late_alert', 'weekly_summary', 'sync_status']),
            'school' => $student ? [
                'name' => $student->school_name,
                'contact_phone' => $student->school_contact_phone,
                'contact_email' => $student->school_contact_email,
            ] : null,
        ]);
    }
}
