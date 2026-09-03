<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\Data\PullTapsFromServer;
use App\Services\Remote\RemoteAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Landing/Main', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Drop a previous account's cached students/taps up front so they never
        // flash on screen — even when we're offline and can't reach the server.
        if (! PullTapsFromServer::cacheBelongsTo($user)) {
            PullTapsFromServer::purgeCache();
        }

        RemoteAuthService::authenticateOrDefer($user, $request->string('password')->toString());

        // Pull this account's students/taps now so the first screen isn't empty
        // after an account switch (no-op offline; the heartbeat retries).
        rescue(fn () => PullTapsFromServer::pullNow(), report: false);

        return redirect()->intended(route('app.dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        RemoteAuthService::logout($request->user());

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
