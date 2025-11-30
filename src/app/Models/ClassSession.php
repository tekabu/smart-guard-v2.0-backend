<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassSession extends Model
{
    use HasFactory;

    protected $fillable = ['schedule_period_id', 'start_time', 'end_time'];

    public function schedulePeriod()
    {
        return $this->belongsTo(SchedulePeriod::class);
    }
}
