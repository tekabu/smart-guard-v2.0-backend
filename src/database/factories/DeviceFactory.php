<?php

namespace Database\Factories;

use App\Models\Device;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DeviceFactory extends Factory
{
    protected $model = Device::class;

    public function definition(): array
    {
        return [
            'device_id' => fake()->unique()->bothify('DEV-####'),
            'api_token' => Str::random(64),
            'door_open_duration_seconds' => fake()->numberBetween(3, 10),
            'active' => true,
        ];
    }
}
