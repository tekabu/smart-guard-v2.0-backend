<?php

namespace App\Rules;

use App\Models\ClassSession;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueClassSessionPerDay implements ValidationRule
{
    protected $excludeId;

    public function __construct($excludeId = null)
    {
        $this->excludeId = $excludeId;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // $value is schedule_period_id
        $today = Carbon::now()->format('Y-m-d');

        $query = ClassSession::where('schedule_period_id', $value)
            ->whereDate('created_at', $today);

        // Exclude current record if updating
        if ($this->excludeId) {
            $query->where('id', '!=', $this->excludeId);
        }

        if ($query->exists()) {
            $fail('A class session for this schedule period already exists today.');
        }
    }
}
