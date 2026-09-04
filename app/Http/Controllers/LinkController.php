<?php

namespace App\Http\Controllers;

use App\Jobs\PushLinkRequestJob;
use App\Models\LinkRequest;
use App\Models\Student;
use App\Services\Remote\RemoteApiClient;
use App\Services\Remote\ServerConnectivityService;
use App\Support\Relationship;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LinkController extends Controller
{
    /**
     * Queue a school-issued link code. Pushed immediately when online, held for
     * the next reconnect otherwise. The relationship ('Parent' | 'Guardian') is
     * always sent — defaulting to the guardian's `active_role`.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64',
            'relationship' => ['sometimes', 'nullable', Rule::in(Relationship::VALUES)],
        ]);

        $user = $request->user();
        $relationship = Relationship::normalize($validated['relationship'] ?? $user->active_role);

        $user->update(['active_role' => $relationship]);

        $linkRequest = LinkRequest::create([
            'user_id' => $user->id,
            'code' => strtoupper(trim($validated['code'])),
            'relationship' => $relationship,
            'sync_status' => LinkRequest::SYNC_STATUS_PENDING,
        ]);

        if (ServerConnectivityService::isOnline()) {
            PushLinkRequestJob::dispatch($linkRequest);
        }

        return back()->with('success', 'Link request submitted.');
    }

    /**
     * Change this account's relationship to an already-linked child (per-student
     * override). Applied locally now; pushed to the server (or deferred and
     * retried by FlushPendingSyncs). Does not change the guardian's default.
     */
    public function updateRelationship(Request $request, int $remoteId)
    {
        $validated = $request->validate([
            'relationship' => ['required', Rule::in(Relationship::VALUES)],
        ]);

        $student = Student::byRemoteId($remoteId)->firstOrFail();

        $student->update([
            'relationship' => $validated['relationship'],
            'relationship_pending' => true,
        ]);

        if (ServerConnectivityService::isOnline()) {
            $response = RemoteApiClient::put(
                $request->user(),
                "/api/students/{$remoteId}/relationship",
                ['relationship' => $validated['relationship']],
            );

            if ($response['result'] === RemoteApiClient::RESULT_SUCCESS) {
                $student->update(['relationship_pending' => false]);
            }
        }

        return back()->with('success', 'Relationship updated.');
    }
}
