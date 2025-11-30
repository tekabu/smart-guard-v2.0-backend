<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueMacAddress implements ValidationRule
{
    private ?int $ignoreId;
    
    public function __construct(?int $ignoreId = null)
    {
        $this->ignoreId = $ignoreId;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Allow null values
        if (is_null($value)) {
            return;
        }
        
        $query = DB::table('device_boards')->where('mac_address', $value);
        
        // If we're updating, ignore the current record
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }
        
        if ($query->exists()) {
            $fail('The :attribute has already been taken.');
        }
    }
}