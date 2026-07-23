<?php

namespace App\Services\BusinessCalendar;

use App\Models\BusinessCalendar;
use App\Models\BusinessCalendarHoliday;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BusinessCalendarService
{
    /**
     * @param  array<string, mixed>  $calendarData
     * @param  array<int, array<string, mixed>>  $workingHours
     */
    public function create(array $calendarData, array $workingHours): BusinessCalendar
    {
        $this->assertValidDefaultState($calendarData);
        $workingHours = $this->normalizedWorkingHours($workingHours);

        return DB::transaction(function () use ($calendarData, $workingHours): BusinessCalendar {
            BusinessCalendar::query()->lockForUpdate()->get(['id']);

            $isDefault = (bool) ($calendarData['is_default'] ?? false);
            $calendarData['is_active'] = $isDefault ? true : (bool) ($calendarData['is_active'] ?? true);
            $calendarData['is_default'] = $isDefault;

            if ($isDefault) {
                BusinessCalendar::query()->where('is_default', true)->update(['is_default' => false]);
            }

            $calendar = BusinessCalendar::create($calendarData);
            $this->syncWorkingHours($calendar, $workingHours);

            return $calendar->refresh()->load(['workingHours', 'holidays']);
        });
    }

    /**
     * @param  array<string, mixed>  $calendarData
     * @param  array<int, array<string, mixed>>  $workingHours
     */
    public function update(BusinessCalendar $calendar, array $calendarData, array $workingHours): BusinessCalendar
    {
        $this->assertValidDefaultState($calendarData);
        $workingHours = $this->normalizedWorkingHours($workingHours);

        return DB::transaction(function () use ($calendar, $calendarData, $workingHours): BusinessCalendar {
            BusinessCalendar::query()->lockForUpdate()->get(['id']);

            $isDefault = (bool) ($calendarData['is_default'] ?? false);

            if ($calendar->is_default && ! (bool) ($calendarData['is_active'] ?? true)) {
                throw ValidationException::withMessages([
                    'is_active' => 'The default business calendar cannot be inactive.',
                ]);
            }

            $calendarData['is_active'] = $isDefault ? true : (bool) ($calendarData['is_active'] ?? true);
            $calendarData['is_default'] = $isDefault;

            if ($isDefault) {
                BusinessCalendar::query()
                    ->whereKeyNot($calendar->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $calendar->update($calendarData);
            $this->syncWorkingHours($calendar, $workingHours);

            return $calendar->refresh()->load(['workingHours', 'holidays']);
        });
    }

    public function setDefault(BusinessCalendar $calendar): BusinessCalendar
    {
        return DB::transaction(function () use ($calendar): BusinessCalendar {
            BusinessCalendar::query()->lockForUpdate()->get(['id']);
            BusinessCalendar::query()->where('is_default', true)->update(['is_default' => false]);

            $calendar->forceFill([
                'is_active' => true,
                'is_default' => true,
            ])->save();

            return $calendar->refresh();
        });
    }

    public function delete(BusinessCalendar $calendar): void
    {
        DB::transaction(function () use ($calendar): void {
            BusinessCalendar::query()->lockForUpdate()->get(['id']);

            if ($calendar->is_default && $calendar->is_active) {
                throw ValidationException::withMessages([
                    'calendar' => 'The active default business calendar cannot be deleted.',
                ]);
            }

            $calendar->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function addHoliday(BusinessCalendar $calendar, array $data): BusinessCalendarHoliday
    {
        return DB::transaction(function () use ($calendar, $data): BusinessCalendarHoliday {
            $this->assertHolidayDateIsUnique($calendar, $data['holiday_date']);

            return $calendar->holidays()->create($data);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateHoliday(BusinessCalendar $calendar, BusinessCalendarHoliday $holiday, array $data): BusinessCalendarHoliday
    {
        $this->assertHolidayBelongsToCalendar($calendar, $holiday);

        return DB::transaction(function () use ($calendar, $holiday, $data): BusinessCalendarHoliday {
            $this->assertHolidayDateIsUnique($calendar, $data['holiday_date'], $holiday);

            $holiday->update($data);

            return $holiday->refresh();
        });
    }

    public function deleteHoliday(BusinessCalendar $calendar, BusinessCalendarHoliday $holiday): void
    {
        $this->assertHolidayBelongsToCalendar($calendar, $holiday);

        DB::transaction(fn () => $holiday->delete());
    }

    /**
     * @param  array<string, mixed>  $calendarData
     */
    protected function assertValidDefaultState(array $calendarData): void
    {
        if ((bool) ($calendarData['is_default'] ?? false) && ! (bool) ($calendarData['is_active'] ?? true)) {
            throw ValidationException::withMessages([
                'is_active' => 'A default business calendar must be active.',
            ]);
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $workingHours
     * @return array<int, array<string, mixed>>
     */
    protected function normalizedWorkingHours(array $workingHours): array
    {
        $normalized = collect($workingHours)
            ->mapWithKeys(function (array $day): array {
                $isWorkingDay = (bool) ($day['is_working_day'] ?? false);

                return [
                    (int) $day['day_of_week'] => [
                        'day_of_week' => (int) $day['day_of_week'],
                        'is_working_day' => $isWorkingDay,
                        'start_time' => $isWorkingDay ? $day['start_time'] : null,
                        'end_time' => $isWorkingDay ? $day['end_time'] : null,
                    ],
                ];
            })
            ->sortKeys();

        if ($normalized->keys()->values()->all() !== array_keys(BusinessCalendar::ISO_DAYS)) {
            throw ValidationException::withMessages([
                'working_hours' => 'Every business calendar must define exactly seven weekdays.',
            ]);
        }

        $normalized->each(function (array $day): void {
            if (! $day['is_working_day']) {
                return;
            }

            if (! $day['start_time'] || ! $day['end_time'] || $day['end_time'] <= $day['start_time']) {
                throw ValidationException::withMessages([
                    'working_hours' => 'Working days must have valid start and end times.',
                ]);
            }
        });

        return $normalized->values()->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $workingHours
     */
    protected function syncWorkingHours(BusinessCalendar $calendar, array $workingHours): void
    {
        foreach ($workingHours as $day) {
            $calendar->workingHours()->updateOrCreate(
                ['day_of_week' => $day['day_of_week']],
                [
                    'start_time' => $day['start_time'],
                    'end_time' => $day['end_time'],
                    'is_working_day' => $day['is_working_day'],
                ],
            );
        }
    }

    protected function assertHolidayDateIsUnique(BusinessCalendar $calendar, string $date, ?BusinessCalendarHoliday $except = null): void
    {
        $exists = $calendar->holidays()
            ->whereDate('holiday_date', $date)
            ->when($except, fn ($query) => $query->whereKeyNot($except->id))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'holiday_date' => 'A holiday already exists for this date.',
            ]);
        }
    }

    protected function assertHolidayBelongsToCalendar(BusinessCalendar $calendar, BusinessCalendarHoliday $holiday): void
    {
        if ((int) $holiday->business_calendar_id === (int) $calendar->id) {
            return;
        }

        throw ValidationException::withMessages([
            'holiday' => 'The selected holiday does not belong to this business calendar.',
        ]);
    }
}
