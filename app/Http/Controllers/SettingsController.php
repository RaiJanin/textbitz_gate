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
        $student = Student::query()->first();

        // Guarantee at least the guardian toggles are shown even before the
        // first sync from the server has populated preferences.
        NotificationPreference::firstOrCreate(['role' => NotificationPreference::ROLE_GUARDIAN]);

        return Inertia::render('Settings/Main', [
            'linkedStudents' => Student::query()
                ->orderBy('full_name')
                ->get(['id', 'remote_id', 'full_name', 'relationship', 'grade', 'section']),
            'defaultRelationship' => $request->user()->active_role ?: 'Guardian',
            'relationshipOptions' => \App\Support\Relationship::VALUES,
            'preferences' => NotificationPreference::all(['role', 'arrival', 'departure', 'late_alert', 'weekly_summary', 'sync_status']),
            'school' => $student ? [
                'name' => $student->school_name,
                'contact_phone' => $student->school_contact_phone,
                'contact_email' => $student->school_contact_email,
            ] : null,
        ]);
    }
}
