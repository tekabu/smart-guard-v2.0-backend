<?php

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\SchedulePeriod;
use Illuminate\Database\Seeder;

class SchedulePeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schedules = Schedule::with(['room'])->get();

        if ($schedules->isEmpty()) {
            throw new \Exception('Schedules not found. Please run ScheduleSeeder first.');
        }

        // Common class time slots (format: start_time => end_time)
        $timeSlots = [
            '07:00:00' => '08:30:00', // 7:00 AM - 8:30 AM
            '08:30:00' => '10:00:00', // 8:30 AM - 10:00 AM
            '10:00:00' => '11:30:00', // 10:00 AM - 11:30 AM
            '11:30:00' => '13:00:00', // 11:30 AM - 1:00 PM
            '13:00:00' => '14:30:00', // 1:00 PM - 2:30 PM
            '14:30:00' => '16:00:00', // 2:30 PM - 4:00 PM
            '16:00:00' => '17:30:00', // 4:00 PM - 5:30 PM
            '17:30:00' => '19:00:00', // 5:30 PM - 7:00 PM
        ];

        // Track used time slots per room per day to prevent overlaps
        $usedSlots = [];

        foreach ($schedules as $schedule) {
            // Create a key for tracking room/day combinations
            $slotKey = "{$schedule->room_id}_{$schedule->day_of_week}";

            if (!isset($usedSlots[$slotKey])) {
                $usedSlots[$slotKey] = [];
            }

            // Find an available time slot that doesn't overlap
            $availableSlots = $timeSlots;
            $assigned = false;

            // Shuffle to get random time slots
            $shuffledSlots = [];
            foreach ($availableSlots as $start => $end) {
                $shuffledSlots[] = ['start' => $start, 'end' => $end];
            }
            shuffle($shuffledSlots);

            foreach ($shuffledSlots as $slot) {
                $startTime = $slot['start'];
                $endTime = $slot['end'];

                // Check if this slot overlaps with any existing period in the same room/day
                $overlaps = false;

                foreach ($usedSlots[$slotKey] as $usedSlot) {
                    // Check for overlap: new start < existing end AND new end > existing start
                    if ($startTime < $usedSlot['end'] && $endTime > $usedSlot['start']) {
                        $overlaps = true;
                        break;
                    }
                }

                if (!$overlaps) {
                    // Create the schedule period
                    SchedulePeriod::create([
                        'schedule_id' => $schedule->id,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'active' => true,
                    ]);

                    // Mark this slot as used
                    $usedSlots[$slotKey][] = [
                        'start' => $startTime,
                        'end' => $endTime,
                    ];

                    $assigned = true;
                    break;
                }
            }

            // If no slot was assigned (all overlapped), skip this schedule
            // This can happen if the room is fully booked for that day
        }
    }
}
