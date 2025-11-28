<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserFacultySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = ['College of Engineering', 'College of Science', 'College of Business'];

        // Create 50 faculty users using factory
        for ($i = 1; $i <= 50; $i++) {
            User::factory()->create([
                'role' => 'FACULTY',
                'student_id' => null,
                'faculty_id' => fake()->unique()->numerify('FAC-######'),
                'course' => null,
                'year_level' => null,
                'attendance_rate' => null,
                'department' => fake()->randomElement($departments),
            ]);
        }
    }
}
