<?php

namespace Database\Factories;

use App\Models\SchedulePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Schedule;

class SchedulePeriodFactory extends Factory
{
    protected $model = SchedulePeriod::class;

    public function definition(): array
    {
        return [
            'schedule_id' => Schedule::factory(),
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
            'active' => true,
        ];
    }
}
