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

        // Create 20 devices via API
        for ($i = 1; $i <= 20; $i++) {
            $response = Http::post("{$baseUrl}/api/devices", [
                'device_id' => 'DEV-' . str_pad($i, 4, '0', STR_PAD_LEFT),
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
