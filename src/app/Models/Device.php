<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    protected $fillable = ['device_id', 'api_token', 'door_open_duration_seconds', 'active', 'last_accessed_by_user_id', 'last_accessed_at', 'last_accessed_used', 'last_seen_at'];

    protected static function booted(): void
    {
        static::creating(function (Device $device) {
            if (!isset($device->attributes['api_token']) || empty($device->attributes['api_token'])) {
                $device->api_token = Str::random(64);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_accessed_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function lastAccessedByUser()
    {
        return $this->belongsTo(User::class, 'last_accessed_by_user_id');
    }

    public function rooms()
    {
        return $this->hasMany(Room::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(UserAccessLog::class);
    }

    public function boards()
    {
        return $this->hasMany(DeviceBoard::class);
    }
}
