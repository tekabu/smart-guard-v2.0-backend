<?php

namespace Database\Factories;

use App\Models\Section;
use App\Models\SectionSubject;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionSubjectFactory extends Factory
{
    protected $model = SectionSubject::class;

    public function definition(): array
    {
        return [
            'section_id' => Section::factory(),
            'subject_id' => Subject::factory(),
            'faculty_id' => User::factory()->state(['role' => 'FACULTY']),
        ];
    }
}
