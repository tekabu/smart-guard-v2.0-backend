<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAccessLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'room_id', 'device_id', 'access_used'];

    protected function casts(): array
    {
        return [

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

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
