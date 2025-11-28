<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserStudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $courses = ['Computer Science', 'Information Technology', 'Engineering', 'Business'];
        $departments = ['College of Engineering', 'College of Science', 'College of Business'];

        // Create 50 student users using factory
        for ($i = 1; $i <= 50; $i++) {
            User::factory()->create([
                'role' => 'STUDENT',
                'student_id' => fake()->unique()->numerify('STU-######'),
                'faculty_id' => null,
                'course' => fake()->randomElement($courses),
                'year_level' => fake()->numberBetween(1, 4),
                'attendance_rate' => fake()->randomFloat(2, 70, 100),
                'department' => fake()->randomElement($departments),
            ]);
        }
    }
}
