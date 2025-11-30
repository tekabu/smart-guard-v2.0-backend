<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionSubject extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_id',
        'subject_id',
        'faculty_id',
    ];

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function faculty()
    {
        return $this->belongsTo(User::class, 'faculty_id');
    }

    public function students()
    {
        return $this->hasMany(SectionSubjectStudent::class);
    }

    public function schedules()
    {
        return $this->hasMany(SectionSubjectSchedule::class);
    }
}
