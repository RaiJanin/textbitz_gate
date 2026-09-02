<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\ConnectRemoteAccountJob;
use App\Models\User;
use App\Rules\PhilippineMobileNumber;
use App\Services\Data\PullTapsFromServer;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Landing/Main', [
            'showSignUp' => true
        ]);
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'phone_number' => ['required', 'string', new PhilippineMobileNumber, 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user, remember: true);

        // A brand-new account starts with an empty local cache — clear anything
        // a previous login (or demo mode) left in the shared SQLite tables.
        PullTapsFromServer::purgeCache();

        ConnectRemoteAccountJob::dispatch($user, $request->password, isNewRegistration: true);

        return redirect(route('app.dashboard', absolute: false));
    }
}
