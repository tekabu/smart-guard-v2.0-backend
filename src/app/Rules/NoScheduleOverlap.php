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
        $scheduleId = request('schedule_id');
        
        // If no schedule_id in request, try to get it from the existing record
        if (!$scheduleId && $this->schedulePeriodId) {
            $currentRecord = SchedulePeriod::find($this->schedulePeriodId);
            if ($currentRecord) {
                $scheduleId = $currentRecord->schedule_id;
            }
        }
        
        if (!$scheduleId) {
            return; // If no schedule id, can't do overlap check
        }

        // Get the schedule to access room_id and day_of_week
        $schedule = Schedule::find($scheduleId);
        if (!$schedule) {
            return; // If schedule doesn't exist, validation will catch this elsewhere
        }

        // Get the time values from request
        $requestStartTime = request('start_time');
        $requestEndTime = request('end_time');
        
        // For updates, we need to get the current record to use as fallback
        $currentRecord = null;
        if ($this->schedulePeriodId) {
            $currentRecord = SchedulePeriod::find($this->schedulePeriodId);
        }
        
        // Determine the new start and end times
        // Use validated value for the current attribute, request for the other
        if ($attribute === 'start_time') {
            $newStartTime = $value;
            $newEndTime = $requestEndTime ?: ($currentRecord ? $currentRecord->end_time : null);
        } elseif ($attribute === 'end_time') {
            $newStartTime = $requestStartTime ?: ($currentRecord ? $currentRecord->start_time : null);
            $newEndTime = $value;
        } else {
            // For other validations, get both from request or current record
            $newStartTime = $requestStartTime ?: ($currentRecord ? $currentRecord->start_time : null);
            $newEndTime = $requestEndTime ?: ($currentRecord ? $currentRecord->end_time : null);
        }

        // If we don't have both times, we can't check overlap
        if (!$newStartTime || !$newEndTime) {
            return;
        }

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
                $fail("The schedule period overlaps with an existing schedule period: {$existingStart} - {$existingEnd} on room {$schedule->room_id} for day {$schedule->day_of_week}.");
                return;
            }
        }
    }
}
