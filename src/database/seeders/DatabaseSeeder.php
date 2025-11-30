<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->count(1)->create([
            'role' => 'ADMIN',
            'student_id' => null,
            'faculty_id' => null,
            'course' => null,
            'year_level' => null,
            'attendance_rate' => null,
            'department' => null,
            'email' => 'admin@example.com'
        ]);
    }
}
