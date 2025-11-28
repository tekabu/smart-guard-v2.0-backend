<?php

namespace Database\Seeders\Api;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class RoomApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = env('APP_URL', 'http://localhost');

        // Get all devices from API
        $devicesResponse = Http::get("{$baseUrl}/api/devices");

        if ($devicesResponse->failed()) {
            $this->command->error("Failed to fetch devices: " . $devicesResponse->body());
            return;
        }

        $devices = $devicesResponse->json();

        if (empty($devices)) {
            $this->command->error('No devices found. Please run DeviceApiSeeder first.');
            return;
        }

        $rooms = [
            // Building A
            'A-101', 'A-102', 'A-103', 'A-104',
            'A-201', 'A-202', 'A-203', 'A-204',
            'A-301', 'A-302', 'A-303', 'A-304',

            // Building B
            'B-101', 'B-102', 'B-103', 'B-104',
            'B-201', 'B-202', 'B-203', 'B-204',
            'B-301', 'B-302', 'B-303', 'B-304',

            // Special Rooms
            'LAB-01', 'LAB-02', 'LAB-03',
            'LIB-MAIN', 'ADMIN-OFFICE', 'FACULTY-ROOM',
            'COMP-LAB-1', 'COMP-LAB-2',
            'ENG-LAB', 'SCI-LAB',
            'CONF-ROOM', 'AUDITORIUM',
            'CAFETERIA', 'GYMNASIUM', 'STUDENT-CENTER',
        ];

        foreach ($rooms as $index => $roomNumber) {
            $randomDevice = $devices[array_rand($devices)];

            $response = Http::post("{$baseUrl}/api/rooms", [
                'room_number' => $roomNumber,
                'device_id' => $randomDevice['id'],
                'active' => true,
            ]);

            if ($response->failed()) {
                $this->command->error("Failed to create room {$roomNumber}: " . $response->body());
            } else {
                $this->command->info("Created room {$roomNumber}");
            }
        }
    }
}
