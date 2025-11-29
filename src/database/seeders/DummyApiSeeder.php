<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DummyApiSeeder extends Seeder
{
    /**
     * Run the database seeds via API endpoints.
     *
     * This seeder uses HTTP requests to create records through the API,
     * testing API functionality while populating the database.
     */
    public function run(): void
    {
        $this->command->info('Starting API-based seeding...');
        $this->command->warn('Make sure your application server is running!');
        $this->command->info('Using APP_URL: ' . env('APP_URL', 'http://localhost'));
        $this->command->newLine();

        // Seed devices first (required by rooms and device boards)
        $this->command->info('=== Seeding Devices ===');
        $this->call(Api\DeviceApiSeeder::class);
        $this->command->newLine();

        // Seed device boards (requires devices)
        $this->command->info('=== Seeding Device Boards ===');
        $this->call(Api\DeviceBoardApiSeeder::class);
        $this->command->newLine();

        // Seed rooms (requires devices)
        $this->command->info('=== Seeding Rooms ===');
        $this->call(Api\RoomApiSeeder::class);
        $this->command->newLine();

        // Seed subjects
        $this->command->info('=== Seeding Subjects ===');
        $this->call(Api\SubjectApiSeeder::class);
        $this->command->newLine();

        // Seed users (all roles)
        $this->command->info('=== Seeding Users ===');
        $this->call(Api\UserApiSeeder::class);
        $this->command->newLine();

        // Seed user authentication methods (requires users)
        $this->command->info('=== Seeding User Authentication ===');
        $this->call(Api\UserAuthApiSeeder::class);
        $this->command->newLine();

        // Seed schedules and periods (requires users, rooms, subjects)
        $this->command->info('=== Seeding Schedules ===');
        $this->call(Api\ScheduleApiSeeder::class);
        $this->command->newLine();

        // Seed logs (requires users, rooms, devices)
        $this->command->info('=== Seeding Logs ===');
        $this->call(Api\LogApiSeeder::class);
        $this->command->newLine();

        $this->command->info('✅ API-based seeding completed successfully!');
    }
}
