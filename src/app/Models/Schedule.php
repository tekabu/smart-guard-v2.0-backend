<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'day_of_week', 'room_id', 'subject_id', 'active'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function periods()
    {
        return $this->hasMany(SchedulePeriod::class);
    }
}
