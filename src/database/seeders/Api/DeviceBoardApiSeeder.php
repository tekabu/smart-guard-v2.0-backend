<?php

namespace Database\Seeders\Api;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class DeviceBoardApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = env('APP_URL', 'http://localhost');

        // First, get all devices from the API
        $devicesResponse = Http::get("{$baseUrl}/api/devices");
        
        if ($devicesResponse->failed()) {
            $this->command->error('Failed to fetch devices: ' . $devicesResponse->body());
            return;
        }

        $devices = $devicesResponse->json('data');

        if (empty($devices)) {
            $this->command->warn('No devices found. Please seed devices first.');
            return;
        }

        $boardTypes = ['FINGERPRINT', 'RFID', 'LOCK', 'CAMERA', 'DISPLAY'];
        $totalBoards = 0;

        // Create 2-4 boards for each device
        foreach ($devices as $device) {
            $deviceId = $device['id'];
            $numBoards = rand(2, 4);
            $selectedTypes = array_rand(array_flip($boardTypes), $numBoards);

            foreach ((array)$selectedTypes as $index => $boardType) {
                // Generate unique MAC address
                $macAddress = strtoupper(sprintf(
                    '%02X:%02X:%02X:%02X:%02X:%02X',
                    rand(0, 255),
                    rand(0, 255),
                    rand(0, 255),
                    rand(0, 255),
                    rand(0, 255),
                    rand(0, 255)
                ));

                $response = Http::post("{$baseUrl}/api/device-boards", [
                    'device_id' => $deviceId,
                    'board_type' => $boardType,
                    'mac_address' => $macAddress,
                    'firmware_version' => 'v' . rand(1, 3) . '.' . rand(0, 9) . '.' . rand(0, 9),
                    'active' => true,
                ]);

                if ($response->failed()) {
                    $this->command->error("Failed to create board for device {$deviceId}: " . $response->body());
                } else {
                    $totalBoards++;
                    $this->command->info("Created {$boardType} board for device {$deviceId}");
                }
            }
        }

        $this->command->info("Total boards created: {$totalBoards}");
    }
}
