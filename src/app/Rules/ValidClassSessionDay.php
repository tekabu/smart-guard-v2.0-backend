<?php

namespace App\Rules;

use App\Models\SchedulePeriod;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidClassSessionDay implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $value is schedule_period_id
        $schedulePeriod = SchedulePeriod::with('schedule')->find($value);

        if (!$schedulePeriod) {
            return;
        }

        // Get current day of week in uppercase format (MONDAY, TUESDAY, etc.)
        $currentDayOfWeek = strtoupper(Carbon::now()->format('l'));

        // Check if current day matches the schedule's day of week
        if ($schedulePeriod->schedule->day_of_week !== $currentDayOfWeek) {
            $fail("Class sessions can only be created on {$schedulePeriod->schedule->day_of_week}. Today is {$currentDayOfWeek}.");
        }
    }
}
