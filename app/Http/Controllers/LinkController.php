<?php

namespace App\Http\Controllers;

use App\Jobs\PushLinkRequestJob;
use App\Models\LinkRequest;
use App\Services\Remote\ServerConnectivityService;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    /**
     * Queue a school-issued link code. Pushed immediately when online, held for
     * the next reconnect otherwise. Reuses the same deferred-sync mechanism as
     * the password-reset deep link. Responds with an Inertia redirect.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|max:64',
            'relationship' => 'sometimes|string|max:40',
        ]);

        $linkRequest = LinkRequest::create([
            'code' => strtoupper(trim($validated['code'])),
            'relationship' => $validated['relationship'] ?? null,
            'sync_status' => LinkRequest::SYNC_STATUS_PENDING,
        ]);

        if (ServerConnectivityService::isOnline()) {
            PushLinkRequestJob::dispatch($linkRequest);
        }

        return back()->with('success', 'Link request submitted.');
    }
}
