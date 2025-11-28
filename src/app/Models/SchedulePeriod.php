<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchedulePeriod extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_id', 'start_time', 'end_time', 'active'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function schedule()
    {
        return $this->belongsTo(Schedule::class);
    }
}
