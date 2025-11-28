<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Device;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all available devices
        $deviceIds = Device::pluck('id')->toArray();

        if (empty($deviceIds)) {
            throw new \Exception('No devices found. Please run DeviceSeeder first.');
        }

        // Create 39 rooms using factory with random device assignment
        Room::factory()->count(39)->create([
            'device_id' => function () use ($deviceIds) {
                return $deviceIds[array_rand($deviceIds)];
            },
        ]);
    }
}
