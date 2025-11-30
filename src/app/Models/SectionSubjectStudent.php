<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SectionSubjectStudent extends Model
{
    use HasFactory;

    protected $fillable = [
        'section_subject_id',
        'student_id',
    ];

    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class);
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
