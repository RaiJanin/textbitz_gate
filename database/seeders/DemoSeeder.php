<?php

namespace Database\Seeders;

use App\Models\Gate;
use App\Models\NotificationPreference;
use App\Models\Student;
use App\Models\TapEvent;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

/**
 * Local, offline-capable demo dataset for TextBitz Gate. Runs automatically
 * from DatabaseSeeder when APP_DEMO_MODE=true, and can be re-run any time:
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Produces:
 *   - one guardian login  ( 09171234567 / password )
 *   - two linked children + two gates
 *   - ~7 weeks of realistic weekday history (on-time / late / absent mix,
 *     each child with its own punctuality bias) so History colours in and the
 *     Alerts feed shows late, absence and weekly-summary entries
 *   - default guardian notification preferences
 *
 * Everything is written straight into the local SQLite cache, so the app is
 * fully usable with no server running. Idempotent.
 */
class DemoSeeder extends Seeder
{
    private const TZ = 'Asia/Manila';
    private const WEEKDAYS_OF_HISTORY = 35;

    public function run(): void
    {
        $user = User::updateOrCreate(
            ['phone_number' => '+639171234567'],
            [
                'name' => 'Elena Reyes',
                'email' => 'parent@textbitzgate.test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'remote_id' => 1,
                'active_role' => 'guardian',
            ],
        );

        NotificationPreference::updateOrCreate(
            ['role' => NotificationPreference::ROLE_GUARDIAN],
            [
                'arrival' => true,
                'departure' => true,
                'late_alert' => true,
                'weekly_summary' => true,
                'sync_status' => NotificationPreference::SYNC_STATUS_SYNCED,
                'synced_at' => now(),
            ],
        );

        Gate::updateOrCreate(['remote_id' => 1], [
            'name' => 'Main Gate',
            'status' => 'online',
            'last_seen_at' => now(),
            'synced_at' => now(),
        ]);
        Gate::updateOrCreate(['remote_id' => 2], [
            'name' => 'Side Gate',
            'status' => 'offline',
            'synced_at' => now(),
        ]);

        $students = collect([
            // [remote_id, name, grade, section, punctuality bias: chance of being on time]
            ['remote_id' => 1, 'full_name' => 'Diana Reyes', 'grade' => '9', 'section' => 'Rizal', 'onTime' => 0.88],
            ['remote_id' => 2, 'full_name' => 'Marco Reyes', 'grade' => '11', 'section' => 'Mabini', 'onTime' => 0.62],
        ])->map(function (array $attributes) {
            $onTime = $attributes['onTime'];
            unset($attributes['onTime']);

            $student = Student::updateOrCreate(
                ['remote_id' => $attributes['remote_id']],
                [
                    ...$attributes,
                    'relationship' => 'Mom',
                    'school_name' => 'Sampaguita National High School',
                    'school_timezone' => self::TZ,
                    'school_cutoff_time' => '07:45:00',
                    'school_contact_phone' => '+63288887777',
                    'school_contact_email' => 'office@sampaguita.example',
                    'synced_at' => now(),
                ],
            );

            return [$student, $onTime];
        });

        $students->each(fn (array $pair, int $index) => $this->seedTaps($pair[0], $pair[1], $index));

        if ($this->command) {
            $this->command->info('── TextBitz Gate demo seed ──────────────────────────');
            $this->command->info('Login    : 09171234567  /  password');
            $this->command->info('Children : Diana Reyes (punctual), Marco Reyes (often late)');
            $this->command->info('History  : last '.self::WEEKDAYS_OF_HISTORY.' days · on-time / late / absent mix');
            $this->command->info('─────────────────────────────────────────────────────');
        }
    }

    private function seedTaps(Student $student, float $onTimeChance, int $index): void
    {
        $student->tapEvents()->delete();

        // Reproducible per child, but different between children.
        mt_srand(20260828 + $index);

        $today = Carbon::today(self::TZ);

        for ($day = $today->copy()->subDays(self::WEEKDAYS_OF_HISTORY); $day->lte($today); $day->addDay()) {
            if (! $day->isWeekday()) {
                continue;
            }

            $roll = mt_rand(0, 9999) / 10000;

            // ~7% absent, then the child's late/on-time split.
            if ($roll < 0.07) {
                continue;
            }

            $isLate = $roll >= (0.07 + $onTimeChance * 0.93);

            $arriveAt = $isLate
                ? $day->copy()->setTime(7, mt_rand(46, 59))->addMinutes(mt_rand(0, 25))
                : $day->copy()->setTime(7, mt_rand(18, 43));

            $student->tapEvents()->create([
                'direction' => TapEvent::DIRECTION_IN,
                'tapped_at' => $arriveAt,
                'is_late' => $isLate,
                'gate_name' => 'Main Gate',
                'synced_at' => now(),
            ]);

            // Departure on past days only — today's child is still at school.
            if ($day->lt($today)) {
                $student->tapEvents()->create([
                    'direction' => TapEvent::DIRECTION_OUT,
                    'tapped_at' => $day->copy()->setTime(15, mt_rand(0, 40)),
                    'is_late' => false,
                    'gate_name' => 'Main Gate',
                    'synced_at' => now(),
                ]);
            }
        }

        mt_srand();
    }
}
