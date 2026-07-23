<?php

namespace Database\Seeders;

use App\Models\BusinessCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessCalendarSeeder extends Seeder
{
    public function run(): void
    {
        if (BusinessCalendar::query()->where('is_default', true)->where('is_active', true)->exists()) {
            return;
        }

        DB::transaction(function (): void {
            $calendar = BusinessCalendar::query()->firstOrCreate(
                ['name' => 'Indonesia Standard Support'],
                [
                    'description' => 'Standard support calendar for Indonesia business hours.',
                    'timezone' => 'Asia/Jakarta',
                    'is_default' => true,
                    'is_active' => true,
                ],
            );

            $calendar->forceFill([
                'is_default' => true,
                'is_active' => true,
            ])->save();

            foreach (BusinessCalendar::ISO_DAYS as $day => $label) {
                $isWorkingDay = $day <= 5;

                $calendar->workingHours()->updateOrCreate(
                    ['day_of_week' => $day],
                    [
                        'start_time' => $isWorkingDay ? '08:00' : null,
                        'end_time' => $isWorkingDay ? '17:00' : null,
                        'is_working_day' => $isWorkingDay,
                    ],
                );
            }
        });
    }
}
