<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'active',
        'last_accessed_at',
        'student_id',
        'faculty_id',
        'course',
        'year_level',
        'attendance_rate',
        'department',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'last_accessed_at' => 'datetime',
            'attendance_rate' => 'decimal:2',
        ];
    }

    public function fingerprints()
    {
        return $this->hasMany(UserFingerprint::class);
    }

    public function rfids()
    {
        return $this->hasMany(UserRfid::class);
    }

    public function accessLogs()
    {
        return $this->hasMany(UserAccessLog::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(UserAuditLog::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }
}
