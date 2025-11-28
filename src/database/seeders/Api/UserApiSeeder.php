<?php

namespace Database\Seeders\Api;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class UserApiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $baseUrl = env('APP_URL', 'http://localhost');

        $courses = ['Computer Science', 'Information Technology', 'Engineering', 'Business'];
        $departments = ['College of Engineering', 'College of Science', 'College of Business'];

        // Create 50 Admin users
        $this->command->info('Creating Admin users...');
        for ($i = 1; $i <= 50; $i++) {
            $response = Http::post("{$baseUrl}/api/users", [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password',
                'role' => 'ADMIN',
                'active' => true,
            ]);

            if ($response->failed()) {
                $this->command->error("Failed to create admin user {$i}: " . $response->body());
            }
        }
        $this->command->info('Created 50 Admin users');

        // Create 50 Staff users
        $this->command->info('Creating Staff users...');
        for ($i = 1; $i <= 50; $i++) {
            $response = Http::post("{$baseUrl}/api/users", [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password',
                'role' => 'STAFF',
                'active' => true,
            ]);

            if ($response->failed()) {
                $this->command->error("Failed to create staff user {$i}: " . $response->body());
            }
        }
        $this->command->info('Created 50 Staff users');

        // Create 50 Student users
        $this->command->info('Creating Student users...');
        for ($i = 1; $i <= 50; $i++) {
            $response = Http::post("{$baseUrl}/api/users", [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password',
                'role' => 'STUDENT',
                'student_id' => fake()->unique()->numerify('STU-######'),
                'course' => $courses[array_rand($courses)],
                'year_level' => rand(1, 4),
                'attendance_rate' => round(rand(7000, 10000) / 100, 2),
                'department' => $departments[array_rand($departments)],
                'active' => true,
            ]);

            if ($response->failed()) {
                $this->command->error("Failed to create student user {$i}: " . $response->body());
            }
        }
        $this->command->info('Created 50 Student users');

        // Create 50 Faculty users
        $this->command->info('Creating Faculty users...');
        for ($i = 1; $i <= 50; $i++) {
            $response = Http::post("{$baseUrl}/api/users", [
                'name' => fake()->name(),
                'email' => fake()->unique()->safeEmail(),
                'password' => 'password',
                'role' => 'FACULTY',
                'faculty_id' => fake()->unique()->numerify('FAC-######'),
                'department' => $departments[array_rand($departments)],
                'active' => true,
            ]);

            if ($response->failed()) {
                $this->command->error("Failed to create faculty user {$i}: " . $response->body());
            }
        }
        $this->command->info('Created 50 Faculty users');
    }
}
