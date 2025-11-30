<?php

namespace App\Traits;

use App\Rules\UniqueClassSessionPerDay;
use App\Rules\ValidClassSessionDay;
use App\Rules\ValidClassSessionTime;
use Illuminate\Validation\ValidationException;

trait HandlesClassSessions
{
    /**
     * Build validation rules for class session create/update operations.
     */
    protected function classSessionValidationRules(bool $isUpdate = false, ?int $ignoreId = null): array
    {
        $scheduleRules = [
            $isUpdate ? 'sometimes' : 'required',
            'exists:schedule_periods,id',
            new ValidClassSessionDay,
            new ValidClassSessionTime,
            new UniqueClassSessionPerDay($ignoreId),
        ];

        return [
            'schedule_period_id' => $scheduleRules,
            'start_time' => [
                $isUpdate ? 'sometimes' : 'nullable',
                'date_format:H:i:s',
            ],
            'end_time' => [
                'nullable',
                'date_format:H:i:s',
            ],
        ];
    }

    /**
     * Ensure end time is never before start time.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function ensureEndTimeNotBeforeStart(?string $startTime, ?string $endTime): void
    {
        if ($startTime && $endTime && $endTime < $startTime) {
            throw ValidationException::withMessages([
                'end_time' => ['End time must not be earlier than start time.'],
            ]);
        }
    }
}
