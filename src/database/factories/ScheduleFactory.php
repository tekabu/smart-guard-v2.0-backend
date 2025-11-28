<?php

namespace Database\Factories;

use App\Models\Schedule;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Room;
use App\Models\Subject;

class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $days = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];

        return [
            'user_id' => User::factory(),
            'day_of_week' => fake()->randomElement($days),
            'room_id' => Room::factory(),
            'subject_id' => Subject::factory(),
            'active' => true,
        ];
    }
}
