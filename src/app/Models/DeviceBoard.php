<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DeviceBoard extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_id',
        'board_type',
        'api_token',
        'mac_address',
        'firmware_version',
        'active',
        'last_seen_at',
        'last_ip',
    ];

    public static $rules = [
        'device_id' => 'required|exists:devices,id',
        'board_type' => 'required|in:FINGERPRINT,RFID,LOCK,CAMERA,DISPLAY',
        'api_token' => 'nullable|string|max:80|unique:device_boards,api_token',
        'mac_address' => 'nullable|string|unique:device_boards,mac_address,NULL,id,deleted_at,NULL',
        'firmware_version' => 'nullable|string',
        'active' => 'boolean',
        'last_seen_at' => 'nullable|date',
        'last_ip' => 'nullable|string',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the device that owns this board.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    protected static function booted(): void
    {
        static::creating(function (DeviceBoard $deviceBoard) {
            if (empty($deviceBoard->attributes['api_token'] ?? null)) {
                $deviceBoard->api_token = Str::random(64);
            }
        });

        static::updating(function (DeviceBoard $deviceBoard) {
            if (empty($deviceBoard->attributes['api_token'] ?? null)) {
                $deviceBoard->api_token = Str::random(64);
            }
        });
    }
}
