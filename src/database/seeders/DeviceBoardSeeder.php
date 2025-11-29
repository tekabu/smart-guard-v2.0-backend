<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\DeviceBoard;
use Illuminate\Database\Seeder;

class DeviceBoardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all devices
        $devices = Device::all();

        if ($devices->isEmpty()) {
            $this->command->warn('No devices found. Creating devices first...');
            Device::factory()->count(5)->create();
            $devices = Device::all();
        }

        // Create multiple boards for each device
        foreach ($devices as $device) {
            // Each device gets 2-4 boards with different functions
            $boardTypes = ['FINGERPRINT', 'RFID', 'LOCK', 'CAMERA', 'DISPLAY'];
            $numBoards = rand(2, 4);
            $selectedTypes = array_rand(array_flip($boardTypes), $numBoards);

            foreach ((array)$selectedTypes as $boardType) {
                DeviceBoard::factory()->create([
                    'device_id' => $device->id,
                    'board_type' => $boardType,
                ]);
            }
        }
    }
}
