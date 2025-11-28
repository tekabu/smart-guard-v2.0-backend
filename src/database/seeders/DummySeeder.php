<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Seed devices first (required by rooms)
        $this->call(DeviceSeeder::class);

        // Seed rooms (requires devices)
        $this->call(RoomSeeder::class);

        // Seed subjects
        $this->call(SubjectSeeder::class);

        // Seed users by role
        $this->call([
            UserAdminSeeder::class,
            UserStaffSeeder::class,
            UserStudentSeeder::class,
            UserFacultySeeder::class,
        ]);

        // Seed user authentication methods (requires users)
        $this->call([
            UserFingerprintSeeder::class,
            UserRfidSeeder::class,
        ]);

        // Seed logs (requires users, rooms, devices)
        $this->call([
            UserAuditLogSeeder::class,
            UserAccessLogSeeder::class,
        ]);
    }
}
