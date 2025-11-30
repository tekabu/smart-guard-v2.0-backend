<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\SectionSubject;
use App\Models\SectionSubjectSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionSubjectScheduleFactory extends Factory
{
    protected $model = SectionSubjectSchedule::class;

    public function definition(): array
    {
        $days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];

        return [
            'section_subject_id' => SectionSubject::factory(),
            'day_of_week' => fake()->randomElement($days),
            'room_id' => Room::factory(),
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
        ];
    }
}
