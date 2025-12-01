<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\ScheduleSession;
use App\Models\SectionSubjectSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ScheduleSessionFactory extends Factory
{
    protected $model = ScheduleSession::class;

    public function definition(): array
    {
        $days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];
        $start = fake()->dateTimeBetween('-1 week', '+1 week');
        $end = (clone $start)->modify('+1 hour');

        return [
            'section_subject_schedule_id' => SectionSubjectSchedule::factory(),
            'faculty_id' => User::factory()->state(['role' => 'FACULTY']),
            'day_of_week' => fake()->randomElement($days),
            'room_id' => Room::factory(),
            'start_date' => $start->format('Y-m-d'),
            'start_time' => $start->format('H:i:s'),
            'end_date' => $end->format('Y-m-d'),
            'end_time' => $end->format('H:i:s'),
        ];
    }
}
