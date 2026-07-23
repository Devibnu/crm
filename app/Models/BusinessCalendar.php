<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class BusinessCalendar extends Model
{
    use HasFactory;

    public const ISO_DAYS = [
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
        7 => 'Sunday',
    ];

    protected $fillable = [
        'name',
        'description',
        'timezone',
        'is_default',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(BusinessCalendarWorkingHour::class)->orderBy('day_of_week');
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(BusinessCalendarHoliday::class)->orderBy('holiday_date');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeDefaultCalendar(Builder $query): Builder
    {
        return $query->where('is_default', true)->where('is_active', true);
    }

    public function workingHourForDay(int $isoDay): ?BusinessCalendarWorkingHour
    {
        $hours = $this->relationLoaded('workingHours')
            ? $this->workingHours
            : $this->workingHours()->get();

        return $hours->firstWhere('day_of_week', $isoDay);
    }

    public function isWorkingDay(int $isoDay): bool
    {
        return (bool) $this->workingHourForDay($isoDay)?->is_working_day;
    }

    public function isHoliday(Carbon|string $date): bool
    {
        $holidayDate = $date instanceof Carbon ? $date->toDateString() : Carbon::parse($date)->toDateString();

        $holidays = $this->relationLoaded('holidays')
            ? $this->holidays
            : $this->holidays()->get();

        return $holidays->contains(function (BusinessCalendarHoliday $holiday) use ($holidayDate): bool {
            if ($holiday->holiday_date->toDateString() === $holidayDate) {
                return true;
            }

            return $holiday->is_recurring
                && $holiday->holiday_date->format('m-d') === Carbon::parse($holidayDate)->format('m-d');
        });
    }
}
