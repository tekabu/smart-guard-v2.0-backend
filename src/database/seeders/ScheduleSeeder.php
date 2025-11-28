<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\User;
use App\Models\Room;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get faculty and students (those who teach/attend classes)
        $faculty = User::where('role', 'FACULTY')->get();
        $students = User::where('role', 'STUDENT')->get();
        $rooms = Room::all();
        $subjects = Subject::all();

        if ($faculty->isEmpty() || $students->isEmpty() || $rooms->isEmpty() || $subjects->isEmpty()) {
            throw new \Exception('Faculty, students, rooms, or subjects not found. Please run other seeders first.');
        }

        $daysOfWeek = ['MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY'];
        $createdSchedules = [];

        // Create faculty schedules (teachers)
        foreach ($faculty as $teacher) {
            // Each faculty teaches 2-4 subjects per week
            $numberOfClasses = rand(2, 4);

            for ($i = 0; $i < $numberOfClasses; $i++) {
                $attempts = 0;
                $created = false;

                // Try to create a unique schedule combination
                while (!$created && $attempts < 10) {
                    $day = $daysOfWeek[array_rand($daysOfWeek)];
                    $room = $rooms->random();
                    $subject = $subjects->random();

                    // Check if this combination already exists
                    $key = "{$teacher->id}_{$day}_{$room->id}_{$subject->id}";

                    if (!isset($createdSchedules[$key])) {
                        Schedule::create([
                            'user_id' => $teacher->id,
                            'day_of_week' => $day,
                            'room_id' => $room->id,
                            'subject_id' => $subject->id,
                            'active' => true,
                        ]);

                        $createdSchedules[$key] = true;
                        $created = true;
                    }

                    $attempts++;
                }
            }
        }

        // Create student schedules (attendees)
        foreach ($students as $student) {
            // Each student has 4-6 classes per week
            $numberOfClasses = rand(4, 6);

            for ($i = 0; $i < $numberOfClasses; $i++) {
                $attempts = 0;
                $created = false;

                // Try to create a unique schedule combination
                while (!$created && $attempts < 10) {
                    $day = $daysOfWeek[array_rand($daysOfWeek)];
                    $room = $rooms->random();
                    $subject = $subjects->random();

                    // Check if this combination already exists
                    $key = "{$student->id}_{$day}_{$room->id}_{$subject->id}";

                    if (!isset($createdSchedules[$key])) {
                        Schedule::create([
                            'user_id' => $student->id,
                            'day_of_week' => $day,
                            'room_id' => $room->id,
                            'subject_id' => $subject->id,
                            'active' => true,
                        ]);

                        $createdSchedules[$key] = true;
                        $created = true;
                    }

                    $attempts++;
                }
            }
        }
    }
}
