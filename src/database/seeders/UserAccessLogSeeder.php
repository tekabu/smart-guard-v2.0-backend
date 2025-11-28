<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Room;
use App\Models\Device;
use App\Models\UserAccessLog;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class UserAccessLogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users who have authentication methods (students, faculty, staff)
        $users = User::whereIn('role', ['STUDENT', 'FACULTY', 'STAFF'])->get();
        $rooms = Room::all();
        $devices = Device::all();

        if ($users->isEmpty() || $rooms->isEmpty() || $devices->isEmpty()) {
            throw new \Exception('Users, rooms, or devices not found. Please run other seeders first.');
        }

        $accessMethods = ['FINGERPRINT', 'RFID'];

        // Generate 1000 access logs spread over the last 90 days
        for ($i = 0; $i < 1000; $i++) {
            $randomUser = $users->random();
            $randomRoom = $rooms->random();
            $randomDevice = $devices->random();

            // Random date within last 90 days, during typical working hours (6 AM - 10 PM)
            $randomDate = Carbon::now()
                ->subDays(rand(0, 90))
                ->setHour(rand(6, 22))
                ->setMinute(rand(0, 59))
                ->setSecond(rand(0, 59));

            UserAccessLog::create([
                'user_id' => $randomUser->id,
                'room_id' => $randomRoom->id,
                'device_id' => $randomDevice->id,
                'access_used' => fake()->randomElement($accessMethods),
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);
        }

        // Add some admin access logs (50 logs using ADMIN method)
        $admins = User::where('role', 'ADMIN')->get();
        if ($admins->isNotEmpty()) {
            for ($i = 0; $i < 50; $i++) {
                $randomAdmin = $admins->random();
                $randomRoom = $rooms->random();
                $randomDevice = $devices->random();

                $randomDate = Carbon::now()
                    ->subDays(rand(0, 90))
                    ->setHour(rand(6, 22))
                    ->setMinute(rand(0, 59))
                    ->setSecond(rand(0, 59));

                UserAccessLog::create([
                    'user_id' => $randomAdmin->id,
                    'room_id' => $randomRoom->id,
                    'device_id' => $randomDevice->id,
                    'access_used' => 'ADMIN',
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate,
                ]);
            }
        }
    }
}
