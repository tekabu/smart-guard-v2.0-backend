<?php

namespace Database\Seeders\Api;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class UserAuthApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = env('APP_URL', 'http://localhost');

        // Get all users except admins
        $usersResponse = Http::get("{$baseUrl}/api/users");

        if ($usersResponse->failed()) {
            $this->command->error("Failed to fetch users: " . $usersResponse->body());
            return;
        }

        $allUsers = $usersResponse->json();
        $users = array_filter($allUsers, function($user) {
            return in_array($user['role'], ['STUDENT', 'FACULTY', 'STAFF']);
        });

        if (empty($users)) {
            $this->command->error('No users found. Please run UserApiSeeder first.');
            return;
        }

        $this->command->info('Creating fingerprints and RFID cards...');

        foreach ($users as $user) {
            // Create 1-2 fingerprints per user
            $fingerprintCount = rand(1, 2);

            for ($i = 0; $i < $fingerprintCount; $i++) {
                $response = Http::post("{$baseUrl}/api/user-fingerprints", [
                    'user_id' => $user['id'],
                    'fingerprint_id' => fake()->unique()->numberBetween(10000, 99999),
                    'active' => true,
                ]);

                if ($response->failed()) {
                    $this->command->error("Failed to create fingerprint for user {$user['id']}: " . $response->body());
                }
            }

            // Create 1 RFID card per user
            $cardId = strtoupper(fake()->unique()->regexify('[0-9A-F]{10}'));

            $response = Http::post("{$baseUrl}/api/user-rfids", [
                'user_id' => $user['id'],
                'card_id' => $cardId,
                'active' => true,
            ]);

            if ($response->failed()) {
                $this->command->error("Failed to create RFID for user {$user['id']}: " . $response->body());
            }
        }

        $this->command->info('Created fingerprints and RFID cards for ' . count($users) . ' users');
    }
}
