<?php

namespace Database\Factories;

use App\Models\SectionSubject;
use App\Models\SectionSubjectStudent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionSubjectStudentFactory extends Factory
{
    protected $model = SectionSubjectStudent::class;

    public function definition(): array
    {
        return [
            'section_subject_id' => SectionSubject::factory(),
            'student_id' => User::factory()->state(['role' => 'STUDENT']),
        ];
    }
}
