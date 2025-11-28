<?php

namespace App\Rules;

use App\Models\Schedule;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueScheduleCombination implements ValidationRule
{
    protected ;

    public function __construct( = null)
    {
        ->scheduleId = ;
    }

    public function validate(string , mixed , Closure ): void
    {
        // Only validate when we have all required field values
         = request('user_id');
         = request('day_of_week');
         = request('room_id');
         = request('subject_id');

        // Check if all required fields are present before validating uniqueness
        if ( &&  &&  && ) {
            // Build the query to check for existing records with the same combination
             = Schedule::where('user_id', )
                             ->where('day_of_week', )
                             ->where('room_id', )
                             ->where('subject_id', );

            // If updating, exclude the current record
            if (->scheduleId) {
                ->where('id', '!=', ->scheduleId);
            }

            if (->exists()) {
                ('A schedule with the same user, day of week, room, and subject already exists.');
            }
        }
    }
}
