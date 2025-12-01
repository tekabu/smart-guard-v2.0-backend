<?php

namespace Database\Factories;

use App\Models\ScheduleAttendance;
use App\Models\ScheduleSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleAttendanceFactory extends Factory
{
    protected $model = ScheduleAttendance::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 week', '+1 week');
        $end = (clone $start)->modify('+1 hour');
        $statuses = ['PRESENT', 'LATE', 'ABSENT'];

        return [
            'schedule_session_id' => ScheduleSession::factory(),
            'student_id' => User::factory()->state(['role' => 'STUDENT']),
            'date_in' => $start->format('Y-m-d'),
            'time_in' => $start->format('H:i:s'),
            'date_out' => $end->format('Y-m-d'),
            'time_out' => $end->format('H:i:s'),
            'attendance_status' => fake()->randomElement($statuses),
        ];
    }
}
