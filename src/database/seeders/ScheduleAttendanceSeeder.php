<?php

namespace Database\Seeders;

use App\Models\ScheduleAttendance;
use App\Models\ScheduleSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleAttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sessions = ScheduleSession::all();

        if ($sessions->isEmpty()) {
            $sessions = ScheduleSession::factory()->count(3)->create();
        }

        $students = User::where('role', 'STUDENT')->get();

        if ($students->isEmpty()) {
            throw new \Exception('Students not found. Please seed student users first.');
        }

        foreach ($sessions as $session) {
            $attendeeCount = min($students->count(), rand(1, 30));
            $selectedStudents = $attendeeCount === 1
                ? collect([$students->random()])
                : $students->random($attendeeCount);

            $dateIn = $session->start_date ?? Carbon::now()->toDateString();
            $dateOut = $session->end_date ?? $dateIn;
            $timeIn = $session->start_time ?? '08:00:00';
            $timeOut = $session->end_time ?? '09:00:00';

            foreach ($selectedStudents as $student) {
                ScheduleAttendance::firstOrCreate(
                    [
                        'schedule_session_id' => $session->id,
                        'student_id' => $student->id,
                        'date_in' => $dateIn,
                    ],
                    [
                        'time_in' => $timeIn,
                        'date_out' => $dateOut,
                        'time_out' => $timeOut,
                        'attendance_status' => fake()->randomElement(['PRESENT', 'LATE', 'ABSENT']),
                    ]
                );
            }
        }
    }
}
