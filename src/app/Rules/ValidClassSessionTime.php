<?php

namespace App\Rules;

use App\Models\SchedulePeriod;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidClassSessionTime implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $value is schedule_period_id
        $schedulePeriod = SchedulePeriod::find($value);

        if (!$schedulePeriod) {
            return;
        }

        // Get current time
        $currentTime = Carbon::now()->format('H:i:s');

        // Check if current time is within the schedule period's time range
        if ($currentTime < $schedulePeriod->start_time || $currentTime > $schedulePeriod->end_time) {
            $fail("Class sessions can only be created between {$schedulePeriod->start_time} and {$schedulePeriod->end_time}. Current time is {$currentTime}.");
        }
    }
}
