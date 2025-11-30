<?php

namespace Database\Seeders;

use App\Models\SchedulePeriod;
use App\Models\StudentSchedule;
use App\Models\User;
use Illuminate\Database\Seeder;

class StudentScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $students = User::where('role', 'STUDENT')->get();
        $schedulePeriods = SchedulePeriod::with('schedule')->get();

        if ($students->isEmpty() || $schedulePeriods->isEmpty()) {
            throw new \Exception('Students or schedule periods not found. Please run related seeders first.');
        }

        foreach ($students as $student) {
            $assignCount = min($schedulePeriods->count(), rand(2, 4));
            $selectedPeriods = $schedulePeriods->random($assignCount);

            if (!$selectedPeriods instanceof \Illuminate\Support\Collection) {
                $selectedPeriods = collect([$selectedPeriods]);
            }

            foreach ($selectedPeriods as $period) {
                $schedule = $period->schedule;

                if (!$schedule) {
                    continue;
                }

                StudentSchedule::firstOrCreate([
                    'student_id' => $student->id,
                    'subject_id' => $schedule->subject_id,
                    'schedule_id' => $schedule->id,
                    'schedule_period_id' => $period->id,
                ]);
            }
        }
    }
}
