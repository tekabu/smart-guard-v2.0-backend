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
        // Seed devices first (required by rooms and device boards)
        $this->call(DeviceSeeder::class);

        // Seed device boards (requires devices)
        $this->call(DeviceBoardSeeder::class);

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

        // Seed schedules (requires users, rooms, subjects)
        $this->call(ScheduleSeeder::class);

        // Seed schedule periods (requires schedules)
        $this->call(SchedulePeriodSeeder::class);

        // Seed student schedules (requires students, schedules, schedule periods)
        $this->call(StudentScheduleSeeder::class);

        // Seed schedule sessions and attendance (requires section subject schedules and students)
        $this->call([
            ScheduleSessionSeeder::class,
            ScheduleAttendanceSeeder::class,
        ]);

        // Seed logs (requires users, rooms, devices)
        $this->call([
            UserAuditLogSeeder::class,
            UserAccessLogSeeder::class,
        ]);
    }
}
