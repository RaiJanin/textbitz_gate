<?php

namespace App\Services\Data;

use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class DefaultDataSeeder
{
    private static bool $demoChecked = false;

    public static function ensureDatabaseMigrated(): void
    {
        if (self::hasPendingMigrations()) {
            Artisan::call('migrate', ['--force' => true]);
        }
    }

    public static function hasPendingMigrations(): bool
    {
        if (! Schema::hasTable('migrations')) {
            return true;
        }

        $ran = DB::table('migrations')->pluck('migration')->all();

        $files = collect(app('migrator')->getMigrationFiles(
            database_path('migrations')
        ))->keys();

        return $files->diff($ran)->isNotEmpty();
    }

    /**
     * TextBitz Gate keeps no local seed data of its own — attendance is owned by
     * the server and pulled in by PullTapsFromServer.
     *
     * The one exception is demo mode: when APP_DEMO_MODE is on and the local DB
     * is a fresh install (no accounts yet), seed the offline demo dataset so the
     * app is usable on first launch without running an Artisan command.
     */
    public static function dataSeed(bool $seedDemoData = false): void
    {
        if (app()->runningInConsole()) {
            self::ensureDatabaseMigrated();
        }

        if ($seedDemoData) {
            self::seedDemoDataIfFresh();
        }
    }

    /**
     * Seeds DemoSeeder once, only when the users table exists and is empty — so
     * it never reseeds and never clobbers a real login.
     *
     * The `$demoChecked` latch is only set once the schema is actually ready:
     * on the very first NativePHP boot this can run before migrations have
     * finished, and latching then would mean the demo data never appears.
     */
    private static function seedDemoDataIfFresh(): void
    {
        if (self::$demoChecked) {
            return;
        }

        try {
            if (! Schema::hasTable('users')) {
                self::ensureDatabaseMigrated();
            }

            if (! Schema::hasTable('users')) {
                return; // schema not ready yet — retry on a later request
            }

            self::$demoChecked = true;

            if (DB::table('users')->exists()) {
                return; // a real (or already-seeded) account exists
            }

            (new DemoSeeder)->run(connectRemote: false);

            Log::info('APP_DEMO_MODE: demo dataset seeded on boot.');
        } catch (\Throwable $e) {
            Log::warning('APP_DEMO_MODE: demo boot-seed failed', ['error' => $e->getMessage()]);
        }
    }
}
