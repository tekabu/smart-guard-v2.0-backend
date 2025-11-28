<?php

namespace App\Rules;

use App\Models\SchedulePeriod;
use App\Models\Schedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class NoScheduleOverlap implements ValidationRule
{
    protected $schedulePeriodId;
    protected $currentScheduleId;

    public function __construct($schedulePeriodId = null, $currentScheduleId = null)
    {
        $this->schedulePeriodId = $schedulePeriodId;
        $this->currentScheduleId = $currentScheduleId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Get the schedule_id from the request data or from the existing record
        $scheduleId = $this->currentScheduleId ?? request('schedule_id');
        
        if (!$scheduleId) {
            return; // If no schedule id, can't do overlap check
        }

        // Get the schedule to access room_id and day_of_week
        $schedule = Schedule::find($scheduleId);
        if (!$schedule) {
            return; // If schedule doesn't exist, validation will catch this elsewhere
        }

        $newStartTime = request('start_time');
        $newEndTime = request('end_time');

        // If updating, exclude the current record from the check
        $query = SchedulePeriod::whereHas('schedule', function($query) use ($schedule) {
            $query->where('room_id', $schedule->room_id)
                  ->where('day_of_week', $schedule->day_of_week);
        });

        if ($this->schedulePeriodId) {
            $query->where('id', '!=', $this->schedulePeriodId);
        }

        $existingPeriods = $query->get();

        foreach ($existingPeriods as $existingPeriod) {
            $existingStart = $existingPeriod->start_time;
            $existingEnd = $existingPeriod->end_time;

            // Check for overlap: new start < existing end AND new end > existing start
            if ($newStartTime < $existingEnd && $newEndTime > $existingStart) {
                $fail("The schedule period overlaps with an existing schedule period: {$existingStart} - {} on room {->room_id} for day {->day_of_week}.");
                return;
            }
        }
    }
}
