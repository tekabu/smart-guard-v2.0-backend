<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $roles = ['ADMIN', 'STAFF', 'STUDENT', 'FACULTY'];

        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => fake()->randomElement($roles),
            'active' => true,
            'last_access_at' => null,
            'student_id' => fake()->optional()->numerify('STU-######'),
            'faculty_id' => fake()->optional()->numerify('FAC-######'),
            'course' => fake()->optional()->randomElement(['Computer Science', 'Information Technology', 'Engineering', 'Business']),
            'year_level' => fake()->optional()->numberBetween(1, 4),
            'attendance_rate' => fake()->optional()->randomFloat(2, 0, 100),
            'department' => fake()->optional()->randomElement(['College of Engineering', 'College of Science', 'College of Business']),
        ];
    }
}
