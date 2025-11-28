<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'device_id' => fake()->unique()->bothify('DEV-####'),
            'door_open_duration_seconds' => fake()->numberBetween(3, 10),
            'active' => true,
        ];
    }
}
