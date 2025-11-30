<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class DeviceBoard extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'device_id',
        'board_type',
        'mac_address',
        'firmware_version',
        'active',
        'last_seen_at',
        'last_ip',
    ];

    public static $rules = [
        'device_id' => 'required|exists:devices,id',
        'board_type' => 'required|in:FINGERPRINT,RFID,LOCK,CAMERA,DISPLAY',
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
}
