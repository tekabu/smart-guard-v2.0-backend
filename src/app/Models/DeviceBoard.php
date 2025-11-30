<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class DeviceBoard extends Authenticatable
{
    use HasFactory;
    use HasApiTokens;
    use Notifiable;

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

    protected static function booted(): void
    {
        static::creating(function (DeviceBoard $deviceBoard) {
            $deviceBoard->ensureApiToken();
        });

        static::updating(function (DeviceBoard $deviceBoard) {
            $deviceBoard->ensureApiToken();
        });

        static::saved(function (DeviceBoard $deviceBoard) {
            $deviceBoard->syncSanctumToken();
        });
    }

    /**
     * Get the device that owns this board.
     */
    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    protected function ensureApiToken(): void
    {
        if (empty($this->api_token)) {
            $this->api_token = Str::random(64);
        }
    }

    public function syncSanctumToken(): void
    {
        if (empty($this->api_token)) {
            return;
        }

        // Maintain a single stateless token per board for hardware communication.
        $this->tokens()->delete();

        $this->tokens()->create([
            'name' => 'device-board-api-token',
            'token' => hash('sha256', $this->api_token),
            'abilities' => ['device-board:communicate'],
        ]);
    }
}
