<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\SchedulePeriod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ClassSession>
 */
class ClassSessionFactory extends Factory
{
    protected $model = ClassSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_period_id' => SchedulePeriod::factory(),
            'start_time' => '08:00:00',
            'end_time' => '09:30:00',
        ];
    }
}
