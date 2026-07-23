<?php

namespace Tests\Unit;

use App\Models\BusinessCalendar;
use App\Services\BusinessCalendar\BusinessCalendarService;
use App\Services\BusinessCalendar\BusinessTimeCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessTimeCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_monday_inside_working_hours_is_business_minute(): void
    {
        $calendar = $this->calendar();

        $this->assertTrue($this->calculator()->isBusinessMinute(CarbonImmutable::parse('2026-07-27 10:00:00', 'Asia/Jakarta'), $calendar));
    }

    public function test_before_opening_is_not_business_minute(): void
    {
        $calendar = $this->calendar();

        $this->assertFalse($this->calculator()->isBusinessMinute(CarbonImmutable::parse('2026-07-27 07:59:00', 'Asia/Jakarta'), $calendar));
    }

    public function test_at_opening_is_business_minute(): void
    {
        $calendar = $this->calendar();

        $this->assertTrue($this->calculator()->isBusinessMinute(CarbonImmutable::parse('2026-07-27 08:00:00', 'Asia/Jakarta'), $calendar));
    }

    public function test_at_or_after_closing_is_not_business_minute(): void
    {
        $calendar = $this->calendar();

        $this->assertFalse($this->calculator()->isBusinessMinute(CarbonImmutable::parse('2026-07-27 17:00:00', 'Asia/Jakarta'), $calendar));
        $this->assertFalse($this->calculator()->isBusinessMinute(CarbonImmutable::parse('2026-07-27 17:01:00', 'Asia/Jakarta'), $calendar));
    }

    public function test_weekends_are_not_working_for_default_schedule(): void
    {
        $calendar = $this->calendar();

        $this->assertFalse($this->calculator()->isWorkingDay(CarbonImmutable::parse('2026-08-01 10:00:00', 'Asia/Jakarta'), $calendar));
        $this->assertFalse($this->calculator()->isWorkingDay(CarbonImmutable::parse('2026-08-02 10:00:00', 'Asia/Jakarta'), $calendar));
    }

    public function test_configured_working_saturday_is_supported(): void
    {
        $calendar = $this->calendar(saturdayWorking: true);

        $this->assertTrue($this->calculator()->isBusinessMinute(CarbonImmutable::parse('2026-08-01 10:00:00', 'Asia/Jakarta'), $calendar));
    }

    public function test_holiday_on_weekday_is_non_working(): void
    {
        $calendar = $this->calendar();
        $calendar->holidays()->create([
            'holiday_date' => '2026-07-27',
            'name' => 'Company Holiday',
            'is_recurring' => false,
        ]);
        $calendar->load('holidays');

        $this->assertTrue($this->calculator()->isHoliday(CarbonImmutable::parse('2026-07-27 10:00:00', 'Asia/Jakarta'), $calendar));
        $this->assertFalse($this->calculator()->isWorkingDay(CarbonImmutable::parse('2026-07-27 10:00:00', 'Asia/Jakarta'), $calendar));
    }

    public function test_timezone_conversion_is_respected(): void
    {
        $calendar = $this->calendar(['timezone' => 'Asia/Jakarta']);

        $this->assertTrue($this->calculator()->isBusinessMinute(CarbonImmutable::parse('2026-07-27 01:00:00', 'UTC'), $calendar));
    }

    public function test_next_business_minute_skips_after_hours(): void
    {
        $calendar = $this->calendar();

        $next = $this->calculator()->nextBusinessMinute(CarbonImmutable::parse('2026-07-27 17:01:00', 'Asia/Jakarta'), $calendar);

        $this->assertSame('2026-07-28 08:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_next_business_minute_skips_weekends(): void
    {
        $calendar = $this->calendar();

        $next = $this->calculator()->nextBusinessMinute(CarbonImmutable::parse('2026-07-31 17:30:00', 'Asia/Jakarta'), $calendar);

        $this->assertSame('2026-08-03 08:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_next_business_minute_skips_holidays(): void
    {
        $calendar = $this->calendar();
        $calendar->holidays()->create([
            'holiday_date' => '2026-08-03',
            'name' => 'Monday Holiday',
            'is_recurring' => false,
        ]);
        $calendar->load('holidays');

        $next = $this->calculator()->nextBusinessMinute(CarbonImmutable::parse('2026-07-31 17:30:00', 'Asia/Jakarta'), $calendar);

        $this->assertSame('2026-08-04 08:00:00', $next->format('Y-m-d H:i:s'));
    }

    public function test_previous_business_minute_works_safely(): void
    {
        $calendar = $this->calendar();

        $previous = $this->calculator()->previousBusinessMinute(CarbonImmutable::parse('2026-08-03 07:30:00', 'Asia/Jakarta'), $calendar);

        $this->assertSame('2026-07-31 16:59:00', $previous->format('Y-m-d H:i:s'));
    }

    public function test_inactive_or_non_working_days_are_handled(): void
    {
        $calendar = $this->calendar(nonWorkingMonday: true);

        $this->assertFalse($this->calculator()->isBusinessMinute(CarbonImmutable::parse('2026-07-27 10:00:00', 'Asia/Jakarta'), $calendar));
    }

    public function test_recurring_holiday_is_respected(): void
    {
        $calendar = $this->calendar();
        $calendar->holidays()->create([
            'holiday_date' => '2026-08-17',
            'name' => 'Independence Day',
            'is_recurring' => true,
        ]);
        $calendar->load('holidays');

        $this->assertTrue($this->calculator()->isHoliday(CarbonImmutable::parse('2027-08-17 10:00:00', 'Asia/Jakarta'), $calendar));
    }

    protected function calculator(): BusinessTimeCalculator
    {
        return app(BusinessTimeCalculator::class);
    }

    protected function calendar(array $overrides = [], bool $saturdayWorking = false, bool $nonWorkingMonday = false): BusinessCalendar
    {
        return app(BusinessCalendarService::class)->create(
            array_merge([
                'name' => 'Unit Test Calendar '.uniqid(),
                'description' => 'Unit test support schedule.',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'is_default' => false,
            ], $overrides),
            $this->workingHoursPayload($saturdayWorking, $nonWorkingMonday),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function workingHoursPayload(bool $saturdayWorking = false, bool $nonWorkingMonday = false): array
    {
        return collect(range(1, 7))
            ->mapWithKeys(function (int $day) use ($saturdayWorking, $nonWorkingMonday): array {
                $isWorkingDay = ($day <= 5 || ($saturdayWorking && $day === 6)) && ! ($nonWorkingMonday && $day === 1);

                return [
                    $day => [
                        'day_of_week' => $day,
                        'is_working_day' => $isWorkingDay,
                        'start_time' => $isWorkingDay ? '08:00' : null,
                        'end_time' => $isWorkingDay ? '17:00' : null,
                    ],
                ];
            })
            ->all();
    }
}
