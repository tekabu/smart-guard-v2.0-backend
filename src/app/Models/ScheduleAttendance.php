<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleAttendance extends Model
{
    use HasFactory;

    protected $table = 'schedule_attendance';

    protected $fillable = [
        'schedule_session_id',
        'student_id',
        'date_in',
        'time_in',
        'date_out',
        'time_out',
        'attendance_status',
    ];

    public function scheduleSession()
    {
        return $this->belongsTo(ScheduleSession::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
