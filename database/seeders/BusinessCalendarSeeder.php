<?php

namespace Database\Seeders;

use App\Models\BusinessCalendar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BusinessCalendarSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $activeDefault = BusinessCalendar::query()->defaultCalendar()->first();
            $shouldAssignIndonesiaDefault = ! $activeDefault;

            if ($shouldAssignIndonesiaDefault) {
                BusinessCalendar::query()->where('is_default', true)->update(['is_default' => false]);
            }

            foreach ($this->templates($shouldAssignIndonesiaDefault, $activeDefault?->name === 'Indonesia Standard Support') as $template) {
                $calendar = BusinessCalendar::query()->updateOrCreate(
                    ['name' => $template['name']],
                    [
                        'description' => $template['description'],
                        'timezone' => $template['timezone'],
                        'is_default' => $template['is_default'],
                        'is_active' => true,
                    ],
                );

                foreach (BusinessCalendar::ISO_DAYS as $day => $label) {
                    $hours = $template['working_hours'][$day];

                    $calendar->workingHours()->updateOrCreate(
                        ['day_of_week' => $day],
                        [
                            'start_time' => $hours['is_working_day'] ? $hours['start_time'] : null,
                            'end_time' => $hours['is_working_day'] ? $hours['end_time'] : null,
                            'is_working_day' => $hours['is_working_day'],
                        ],
                    );
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function templates(bool $assignIndonesiaDefault, bool $indonesiaAlreadyDefault): array
    {
        return [
            [
                'name' => 'Indonesia Standard Support',
                'description' => 'Standard weekday support calendar for Indonesia operations.',
                'timezone' => 'Asia/Jakarta',
                'is_default' => $assignIndonesiaDefault || $indonesiaAlreadyDefault,
                'working_hours' => $this->workingHours(function (int $day): array {
                    $isWorkingDay = $day <= 5;

                    return [
                        'is_working_day' => $isWorkingDay,
                        'start_time' => $isWorkingDay ? '08:00' : null,
                        'end_time' => $isWorkingDay ? '17:00' : null,
                    ];
                }),
            ],
            [
                'name' => '24x7 Support',
                'description' => 'Continuous support calendar for critical or always-on services.',
                'timezone' => 'Asia/Jakarta',
                'is_default' => false,
                'working_hours' => $this->workingHours(fn (): array => [
                    'is_working_day' => true,
                    'start_time' => '00:00',
                    'end_time' => '23:59',
                ]),
            ],
            [
                'name' => 'Weekend Support',
                'description' => 'Weekend-only support calendar.',
                'timezone' => 'Asia/Jakarta',
                'is_default' => false,
                'working_hours' => $this->workingHours(function (int $day): array {
                    $isWorkingDay = $day >= 6;

                    return [
                        'is_working_day' => $isWorkingDay,
                        'start_time' => $isWorkingDay ? '08:00' : null,
                        'end_time' => $isWorkingDay ? '17:00' : null,
                    ];
                }),
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function workingHours(callable $resolver): array
    {
        return collect(array_keys(BusinessCalendar::ISO_DAYS))
            ->mapWithKeys(fn (int $day): array => [$day => $resolver($day)])
            ->all();
    }
}
