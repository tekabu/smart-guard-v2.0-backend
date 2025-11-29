<?php

namespace Database\Seeders\Api;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class DeviceApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = env('APP_URL', 'http://localhost');
        $existingDeviceIds = \App\Models\Device::pluck('device_id')->toArray();

        // Create 20 devices via API
        for ($i = 1; $i <= 20; $i++) {
            // Generate unique random device ID that doesn't exist in database
            do {
                $deviceId = 'DEV-' . str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            } while (in_array($deviceId, $existingDeviceIds));

            $response = Http::post("{$baseUrl}/api/devices", [
                'device_id' => $deviceId,
                'door_open_duration_seconds' => rand(3, 10),
                'active' => true,
            ]);

            if ($response->failed()) {
                $this->command->error("Failed to create device {$i}: " . $response->body());
            } else {
                $this->command->info("Created device {$i}");
            }
        }
    }
}
