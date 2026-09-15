<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\NotificationPreference;
use App\Models\Student;
use App\Services\Data\PullTapsFromServer;
use App\Services\Resolvers\PlatformService;
use App\Services\Remote\ServerConnectivityService;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'platform' => fn () => PlatformService::detect(),
            'server' => [
                'connectivity' => fn () => rescue(fn () => ServerConnectivityService::isOnline(), false, false),
            ],
            'gate' => [
                // Global list so the realtime channel manager always has the
                // linked students, regardless of which page is showing. Scoped
                // to the signed-in account — every cached row carries its own
                // owner, so other accounts cached on this device never leak in.
                'linkedStudentIds' => fn () => $request->user()
                    ? Student::query()->where('user_id', $request->user()->id)->whereNotNull('remote_id')->orderBy('id')->pluck('remote_id')->values()
                    : [],
                'sync' => fn () => $request->user() ? cache(PullTapsFromServer::reportCacheKey($request->user())) : null,
                // Guardian toggles so local notifications can be filtered client-side.
                'notificationPreferences' => fn () => $request->user()
                    ? NotificationPreference::where('user_id', $request->user()->id)
                        ->where('role', NotificationPreference::ROLE_GUARDIAN)
                        ->first(['arrival', 'departure', 'late_alert', 'weekly_summary'])
                    : null,
            ],
        ];
    }
}
