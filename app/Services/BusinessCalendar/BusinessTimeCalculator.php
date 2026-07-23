<?php

namespace App\Services\BusinessCalendar;

use App\Models\BusinessCalendar;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use RuntimeException;

class BusinessTimeCalculator
{
    protected const MAX_SEARCH_MINUTES = 1_052_640;

    public function isBusinessMinute(CarbonInterface $dateTime, BusinessCalendar $calendar): bool
    {
        $local = $this->toCalendarTime($dateTime, $calendar);

        if (! $this->isWorkingDay($local, $calendar)) {
            return false;
        }

        $workingHour = $calendar->workingHourForDay((int) $local->isoWeekday());

        if (! $workingHour?->start_time || ! $workingHour?->end_time) {
            return false;
        }

        $time = $local->format('H:i:s');
        $start = strlen($workingHour->start_time) === 5 ? $workingHour->start_time.':00' : $workingHour->start_time;
        $end = strlen($workingHour->end_time) === 5 ? $workingHour->end_time.':00' : $workingHour->end_time;

        return $time >= $start && $time < $end;
    }

    public function isWorkingDay(CarbonInterface $dateTime, BusinessCalendar $calendar): bool
    {
        $local = $this->toCalendarTime($dateTime, $calendar);

        if ($this->isHoliday($local, $calendar)) {
            return false;
        }

        return (bool) $calendar->workingHourForDay((int) $local->isoWeekday())?->is_working_day;
    }

    public function isHoliday(CarbonInterface $dateTime, BusinessCalendar $calendar): bool
    {
        $local = $this->toCalendarTime($dateTime, $calendar);
        $date = $local->toDateString();
        $monthDay = $local->format('m-d');

        $holidays = $calendar->relationLoaded('holidays')
            ? $calendar->holidays
            : $calendar->holidays()->get();

        return $holidays->contains(function ($holiday) use ($date, $monthDay): bool {
            return $holiday->holiday_date->toDateString() === $date
                || ($holiday->is_recurring && $holiday->holiday_date->format('m-d') === $monthDay);
        });
    }

    public function nextBusinessMinute(CarbonInterface $dateTime, BusinessCalendar $calendar): CarbonImmutable
    {
        $candidate = $this->toCalendarTime($dateTime, $calendar)->toImmutable();

        for ($i = 0; $i < self::MAX_SEARCH_MINUTES; $i++) {
            if ($this->isBusinessMinute($candidate, $calendar)) {
                return $candidate;
            }

            $candidate = $candidate->addMinute()->startOfMinute();
        }

        throw new RuntimeException('Unable to find the next business minute for this calendar.');
    }

    public function previousBusinessMinute(CarbonInterface $dateTime, BusinessCalendar $calendar): CarbonImmutable
    {
        $candidate = $this->toCalendarTime($dateTime, $calendar)->toImmutable();

        for ($i = 0; $i < self::MAX_SEARCH_MINUTES; $i++) {
            if ($this->isBusinessMinute($candidate, $calendar)) {
                return $candidate;
            }

            $candidate = $candidate->subMinute()->startOfMinute();
        }

        throw new RuntimeException('Unable to find the previous business minute for this calendar.');
    }

    protected function toCalendarTime(CarbonInterface $dateTime, BusinessCalendar $calendar): CarbonImmutable
    {
        return CarbonImmutable::instance($dateTime)->setTimezone($calendar->timezone);
    }
}
