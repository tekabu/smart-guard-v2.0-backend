<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\UserAuditLog;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserAuditLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $auditActions = [
            'User logged in successfully',
            'User logged out',
            'User profile updated',
            'Password changed',
            'Email updated',
            'User account activated',
            'User account deactivated',
            'Failed login attempt',
            'Two-factor authentication enabled',
            'Two-factor authentication disabled',
            'Security settings updated',
            'Profile picture updated',
            'User preferences modified',
            'Account recovery requested',
            'Account recovery completed',
            'Session expired',
            'User credentials verified',
            'User role updated by administrator',
            'Fingerprint registered',
            'Fingerprint removed',
            'RFID card registered',
            'RFID card removed',
            'Access permissions updated',
            'User created by administrator',
        ];

        // Generate 500 audit logs
        for ($i = 0; $i < 500; $i++) {
            $randomUser = $users->random();
            $randomDate = Carbon::now()->subDays(rand(0, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            UserAuditLog::create([
                'user_id' => $randomUser->id,
                'description' => fake()->randomElement($auditActions),
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        }
    }
}
