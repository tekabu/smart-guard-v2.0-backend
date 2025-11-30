<?php

namespace App\Rules;

use App\Models\StudentSchedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueStudentScheduleCombination implements ValidationRule
{
    public function __construct(
        private readonly ?int $ignoreId = null
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $studentId = request('student_id');
        $subjectId = request('subject_id');
        $scheduleId = request('schedule_id');
        $schedulePeriodId = request('schedule_period_id');

        if (!$studentId || !$subjectId || !$scheduleId || !$schedulePeriodId) {
            return;
        }

        $query = StudentSchedule::where([
            'student_id' => $studentId,
            'subject_id' => $subjectId,
            'schedule_id' => $scheduleId,
            'schedule_period_id' => $schedulePeriodId,
        ]);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('This student schedule already exists.');
        }
    }
}
