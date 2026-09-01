<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Real attendance data is owned by the server and pulled in by
     * PullTapsFromServer after login. The only thing seeded locally is the
     * offline demo dataset, and only when demo mode is on.
     */
    public function run(): void
    {
        if (config('app.demo_mode', false)) {
            $this->call(DemoSeeder::class);
        }
    }
}
