<?php

use App\Models\Student;
use App\Models\User;
use App\Services\Data\DefaultDataSeeder;

beforeEach(function () {
    // The "seed once per process" guard would otherwise carry between tests.
    $flag = new ReflectionProperty(DefaultDataSeeder::class, 'demoChecked');
    $flag->setAccessible(true);
    $flag->setValue(null, false);
});

it('seeds the demo dataset on boot when demo mode is on and the DB is fresh', function () {
    expect(User::count())->toBe(0);

    DefaultDataSeeder::dataSeed(seedDemoData: true);

    expect(User::where('phone_number', '+639171234567')->exists())->toBeTrue()
        ->and(Student::count())->toBe(2);
});

it('does nothing when demo mode is off', function () {
    DefaultDataSeeder::dataSeed(seedDemoData: false);

    expect(User::count())->toBe(0);
});

it('never reseeds or clobbers an existing account', function () {
    $real = User::factory()->create(['phone_number' => '+639170000111', 'name' => 'Real User']);

    DefaultDataSeeder::dataSeed(seedDemoData: true);

    expect(User::count())->toBe(1)
        ->and(User::first()->name)->toBe('Real User')
        ->and(Student::count())->toBe(0);
});
