<?php

namespace Database\Seeders\Api;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ScheduleApiSeeder extends Seeder
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
        $subjectsResponse = Http::get("{$baseUrl}/api/subjects");

        if ($usersResponse->failed() || $roomsResponse->failed() || $subjectsResponse->failed()) {
            $this->command->error("Failed to fetch required data");
            return;
        }

        $allUsers = $usersResponse->json();
        $rooms = $roomsResponse->json();
        $subjects = $subjectsResponse->json();

        $faculty = array_filter($allUsers, fn($u) => $u['role'] === 'FACULTY');
        $students = array_filter($allUsers, fn($u) => $u['role'] === 'STUDENT');

        if (empty($faculty) || empty($students) || empty($rooms) || empty($subjects)) {
            $this->command->error('Required data not found. Please run other seeders first.');
            return;
        }

        $daysOfWeek = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'];
        $timeSlots = [
            ['start' => '07:00:00', 'end' => '08:30:00'],
            ['start' => '08:30:00', 'end' => '10:00:00'],
            ['start' => '10:00:00', 'end' => '11:30:00'],
            ['start' => '11:30:00', 'end' => '13:00:00'],
            ['start' => '13:00:00', 'end' => '14:30:00'],
            ['start' => '14:30:00', 'end' => '16:00:00'],
            ['start' => '16:00:00', 'end' => '17:30:00'],
            ['start' => '17:30:00', 'end' => '19:00:00'],
        ];

        $this->command->info('Creating faculty schedules...');
        $createdCount = 0;

        // Create faculty schedules
        foreach ($faculty as $teacher) {
            $numberOfClasses = rand(2, 4);

            for ($i = 0; $i < $numberOfClasses; $i++) {
                $attempts = 0;

                while ($attempts < 5) {
                    $day = $daysOfWeek[array_rand($daysOfWeek)];
                    $room = $rooms[array_rand($rooms)];
                    $subject = $subjects[array_rand($subjects)];

                    $response = Http::post("{$baseUrl}/api/schedules", [
                        'user_id' => $teacher['id'],
                        'day_of_week' => $day,
                        'room_id' => $room['id'],
                        'subject_id' => $subject['id'],
                        'active' => true,
                    ]);

                    if ($response->successful()) {
                        $schedule = $response->json();

                        // Create schedule period
                        $timeSlot = $timeSlots[array_rand($timeSlots)];

                        Http::post("{$baseUrl}/api/schedule-periods", [
                            'schedule_id' => $schedule['id'],
                            'start_time' => $timeSlot['start'],
                            'end_time' => $timeSlot['end'],
                            'active' => true,
                        ]);

                        $createdCount++;
                        break;
                    }

                    $attempts++;
                }
            }
        }

        $this->command->info("Created {$createdCount} faculty schedules");

        // Create student schedules
        $this->command->info('Creating student schedules...');
        $studentScheduleCount = 0;

        foreach ($students as $student) {
            $numberOfClasses = rand(4, 6);

            for ($i = 0; $i < $numberOfClasses; $i++) {
                $attempts = 0;

                while ($attempts < 5) {
                    $day = $daysOfWeek[array_rand($daysOfWeek)];
                    $room = $rooms[array_rand($rooms)];
                    $subject = $subjects[array_rand($subjects)];

                    $response = Http::post("{$baseUrl}/api/schedules", [
                        'user_id' => $student['id'],
                        'day_of_week' => $day,
                        'room_id' => $room['id'],
                        'subject_id' => $subject['id'],
                        'active' => true,
                    ]);

                    if ($response->successful()) {
                        $schedule = $response->json();

                        // Create schedule period
                        $timeSlot = $timeSlots[array_rand($timeSlots)];

                        Http::post("{$baseUrl}/api/schedule-periods", [
                            'schedule_id' => $schedule['id'],
                            'start_time' => $timeSlot['start'],
                            'end_time' => $timeSlot['end'],
                            'active' => true,
                        ]);

                        $studentScheduleCount++;
                        break;
                    }

                    $attempts++;
                }
            }
        }

        $this->command->info("Created {$studentScheduleCount} student schedules");
    }
}
