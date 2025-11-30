<?php

namespace App\Rules;

use App\Models\Schedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueScheduleCombination implements ValidationRule
{
    protected $scheduleId;

    public function __construct($scheduleId = null)
    {
        $this->scheduleId = $scheduleId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Only validate when we have all required field values
        $userId = request('user_id');
        $dayOfWeek = request('day_of_week');
        $roomId = request('room_id');
        $subjectId = request('subject_id');

        // Check if all required fields are present before validating uniqueness
        if ($userId && $dayOfWeek && $roomId && $subjectId) {
            // Build the query to check for existing records with the same combination
            $query = Schedule::where('user_id', $userId)
                            ->where('day_of_week', $dayOfWeek)
                            ->where('room_id', $roomId)
                            ->where('subject_id', $subjectId);

            // If updating, exclude the current record
            if ($this->scheduleId) {
                $query->where('id', '!=', $this->scheduleId);
            }

            if ($query->exists()) {
                $fail('A schedule with the same user, day of week, room, and subject already exists.');
            }
        }
    }
}