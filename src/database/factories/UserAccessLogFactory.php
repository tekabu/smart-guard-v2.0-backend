<?php

namespace Database\Factories;

use App\Models\UserAccessLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Room;
use App\Models\Device;

class UserAccessLogFactory extends Factory
{
    protected $model = UserAccessLog::class;

    public function definition(): array
    {
        $methods = ['FINGERPRINT', 'RFID', 'ADMIN', 'MANUAL'];

        return [
            'user_id' => User::factory(),
            'room_id' => Room::factory(),
            'device_id' => Device::factory(),
            'access_used' => fake()->randomElement($methods),
        ];
    }
}
