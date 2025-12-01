<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduleSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_subject_schedule_id',
        'faculty_id',
        'day_of_week',
        'room_id',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
    ];

    public function sectionSubjectSchedule()
    {
        return $this->belongsTo(SectionSubjectSchedule::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function attendanceRecords()
    {
        return $this->hasMany(ScheduleAttendance::class);
    }
}
