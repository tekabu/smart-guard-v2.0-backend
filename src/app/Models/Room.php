<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = ['room_number', 'device_id', 'active', 'last_opened_by_user_id', 'last_opened_at', 'last_closed_by_user_id', 'last_closed_at'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_opened_at' => 'datetime',
            'last_closed_at' => 'datetime',
        ];
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function lastOpenedByUser()
    {
        return $this->belongsTo(User::class, 'last_opened_by_user_id');
    }

    public function lastClosedByUser()
    {
        return $this->belongsTo(User::class, 'last_closed_by_user_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(UserAccessLog::class);
    }
}
