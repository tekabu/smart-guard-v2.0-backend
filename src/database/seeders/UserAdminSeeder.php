<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 50 admin users using factory
        User::factory()->count(50)->create([
            'role' => 'ADMIN',
            'student_id' => null,
            'faculty_id' => null,
            'course' => null,
            'year_level' => null,
            'attendance_rate' => null,
            'department' => null,
        ]);
    }
}
