<?php

namespace Database\Seeders\Api;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class LogApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = env('APP_URL', 'http://localhost');

        // Get all necessary data
        $usersResponse = Http::get("{$baseUrl}/api/users");
        $roomsResponse = Http::get("{$baseUrl}/api/rooms");
        $devicesResponse = Http::get("{$baseUrl}/api/devices");

        if ($usersResponse->failed() || $roomsResponse->failed() || $devicesResponse->failed()) {
            $this->command->error("Failed to fetch required data");
            return;
        }

        $users = $usersResponse->json();
        $rooms = $roomsResponse->json();
        $devices = $devicesResponse->json();

        if (empty($users) || empty($rooms) || empty($devices)) {
            $this->command->error('Required data not found. Please run other seeders first.');
            return;
        }

        $auditActions = [
            'User logged in successfully',
            'User logged out',
            'User profile updated',
            'Password changed',
            'Email updated',
            'User account activated',
            'User account deactivated',
            'Failed login attempt',
            'Security settings updated',
            'Profile picture updated',
            'Fingerprint registered',
            'RFID card registered',
        ];

        // Create audit logs
        $this->command->info('Creating audit logs...');
        for ($i = 0; $i < 500; $i++) {
            $user = $users[array_rand($users)];

            Http::post("{$baseUrl}/api/user-audit-logs", [
                'user_id' => $user['id'],
                'description' => $auditActions[array_rand($auditActions)],
            ]);
        }
        $this->command->info('Created 500 audit logs');

        // Create access logs
        $this->command->info('Creating access logs...');

        $nonAdminUsers = array_filter($users, fn($u) => in_array($u['role'], ['STUDENT', 'FACULTY', 'STAFF']));
        $accessMethods = ['FINGERPRINT', 'RFID'];

        for ($i = 0; $i < 1000; $i++) {
            $user = $nonAdminUsers[array_rand($nonAdminUsers)];
            $room = $rooms[array_rand($rooms)];
            $device = $devices[array_rand($devices)];

            Http::post("{$baseUrl}/api/user-access-logs", [
                'user_id' => $user['id'],
                'room_id' => $room['id'],
                'device_id' => $device['id'],
                'access_used' => $accessMethods[array_rand($accessMethods)],
            ]);
        }
        $this->command->info('Created 1000 access logs');

        // Create admin access logs
        $admins = array_filter($users, fn($u) => $u['role'] === 'ADMIN');

        if (!empty($admins)) {
            for ($i = 0; $i < 50; $i++) {
                $admin = $admins[array_rand($admins)];
                $room = $rooms[array_rand($rooms)];
                $device = $devices[array_rand($devices)];

                Http::post("{$baseUrl}/api/user-access-logs", [
                    'user_id' => $admin['id'],
                    'room_id' => $room['id'],
                    'device_id' => $device['id'],
                    'access_used' => 'ADMIN',
                ]);
            }
            $this->command->info('Created 50 admin access logs');
        }
    }
}
