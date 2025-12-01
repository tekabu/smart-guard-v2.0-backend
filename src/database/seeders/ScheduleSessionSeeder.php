<?php

namespace Database\Seeders;

use App\Models\ScheduleSession;
use App\Models\SectionSubjectSchedule;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ScheduleSessionSeeder extends Seeder
{
    private const DAYS = ['SUNDAY', 'MONDAY', 'TUESDAY', 'WEDNESDAY', 'THURSDAY', 'FRIDAY', 'SATURDAY'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectionSchedules = SectionSubjectSchedule::with(['sectionSubject.faculty'])->get();

        if ($sectionSchedules->isEmpty()) {
            $sectionSchedules = SectionSubjectSchedule::factory()->count(5)->create();
            $sectionSchedules->load(['sectionSubject.faculty']);
        }

        foreach ($sectionSchedules as $schedule) {
            $faculty = $schedule->sectionSubject?->faculty;
            if (!$faculty) {
                continue;
            }

            $sessionsToCreate = rand(1, 2);
            $baseDate = $this->resolveBaseDate($schedule->day_of_week);

            for ($i = 0; $i < $sessionsToCreate; $i++) {
                $startDate = $baseDate->copy()->addWeeks($i)->format('Y-m-d');

                ScheduleSession::firstOrCreate(
                    [
                        'section_subject_schedule_id' => $schedule->id,
                        'start_date' => $startDate,
                    ],
                    [
                        'faculty_id' => $faculty->id,
                        'day_of_week' => $schedule->day_of_week,
                        'room_id' => $schedule->room_id,
                        'start_time' => $schedule->start_time,
                        'end_date' => $startDate,
                        'end_time' => $schedule->end_time,
                    ]
                );
            }
        }
    }

    private function resolveBaseDate(string $dayOfWeek): Carbon
    {
        $index = array_search($dayOfWeek, self::DAYS, true);
        $index = $index === false ? 0 : $index;

        return Carbon::now()
            ->startOfWeek(Carbon::SUNDAY)
            ->addDays($index);
    }
}
