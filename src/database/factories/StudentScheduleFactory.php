<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\SchedulePeriod;
use App\Models\StudentSchedule;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class StudentScheduleFactory extends Factory
{
    protected $model = StudentSchedule::class;

    public function definition(): array
    {
        $subjectFactory = Subject::factory();
        $studentFactory = User::factory()->state(['role' => 'STUDENT']);

        return [
            'student_id' => $studentFactory,
            'subject_id' => $subjectFactory,
            'schedule_id' => Schedule::factory(),
            'schedule_period_id' => SchedulePeriod::factory(),
        ];
    }
}
