<?php

use App\Models\NotificationPreference;
use App\Models\Student;
use App\Models\TapEvent;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Hash;

it('seeds a working offline demo dataset', function () {
    $this->seed(DemoSeeder::class);

    $user = User::where('phone_number', '+639171234567')->first();

    expect($user)->not->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and(Student::count())->toBe(2)
        ->and(NotificationPreference::where('role', 'guardian')->exists())->toBeTrue()
        ->and(TapEvent::where('direction', 'in')->exists())->toBeTrue()
        ->and(TapEvent::where('is_late', true)->exists())->toBeTrue();
});

it('is safe to run twice', function () {
    $this->seed(DemoSeeder::class);
    $this->seed(DemoSeeder::class);

    expect(User::where('phone_number', '+639171234567')->count())->toBe(1)
        ->and(Student::count())->toBe(2);
});
