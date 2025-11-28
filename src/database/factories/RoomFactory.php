<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Device;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'room_number' => fake()->unique()->numerify('###'),
            'device_id' => null,
            'active' => true,
        ];
    }
}
