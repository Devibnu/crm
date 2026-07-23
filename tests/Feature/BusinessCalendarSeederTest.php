<?php

namespace Tests\Feature;

use App\Models\BusinessCalendar;
use App\Models\BusinessCalendarHoliday;
use App\Services\BusinessCalendar\BusinessTimeCalculator;
use Carbon\CarbonImmutable;
use Database\Seeders\BusinessCalendarSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessCalendarSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var array<int, string>
     */
    protected array $templateNames = [
        'Indonesia Standard Support',
        '24x7 Support',
        'Weekend Support',
    ];

    public function test_seeder_creates_exactly_three_builtin_templates(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        $this->assertSame(3, BusinessCalendar::query()->whereIn('name', $this->templateNames)->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(BusinessCalendarSeeder::class);
        $this->seed(BusinessCalendarSeeder::class);

        $this->assertSame(3, BusinessCalendar::query()->whereIn('name', $this->templateNames)->count());
        $this->assertSame(21, BusinessCalendar::query()
            ->whereIn('name', $this->templateNames)
            ->withCount('workingHours')
            ->get()
            ->sum('working_hours_count'));
    }

    public function test_each_template_has_exactly_seven_weekday_records(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        BusinessCalendar::query()
            ->whereIn('name', $this->templateNames)
            ->get()
            ->each(fn (BusinessCalendar $calendar) => $this->assertSame(7, $calendar->workingHours()->count()));
    }

    public function test_indonesia_standard_support_has_weekday_hours_and_weekend_off(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        $calendar = BusinessCalendar::query()->where('name', 'Indonesia Standard Support')->firstOrFail();

        foreach (range(1, 5) as $day) {
            $hours = $calendar->workingHourForDay($day);

            $this->assertTrue($hours->is_working_day);
            $this->assertSame('08:00', (string) $hours->start_time);
            $this->assertSame('17:00', (string) $hours->end_time);
        }

        foreach ([6, 7] as $day) {
            $hours = $calendar->workingHourForDay($day);

            $this->assertFalse($hours->is_working_day);
            $this->assertNull($hours->start_time);
            $this->assertNull($hours->end_time);
        }
    }

    public function test_24x7_support_has_all_seven_days_working(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        $calendar = BusinessCalendar::query()->where('name', '24x7 Support')->firstOrFail();

        foreach (range(1, 7) as $day) {
            $hours = $calendar->workingHourForDay($day);

            $this->assertTrue($hours->is_working_day);
            $this->assertSame('00:00', (string) $hours->start_time);
            $this->assertSame('23:59', (string) $hours->end_time);
        }
    }

    public function test_seeded_24x7_calendar_is_recognized_as_working_every_day(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        $calendar = BusinessCalendar::query()
            ->where('name', '24x7 Support')
            ->with(['workingHours', 'holidays'])
            ->firstOrFail();
        $calculator = app(BusinessTimeCalculator::class);

        foreach ([
            '2026-07-27 10:00:00',
            '2026-07-28 10:00:00',
            '2026-07-29 10:00:00',
            '2026-07-30 10:00:00',
            '2026-07-31 10:00:00',
            '2026-08-01 10:00:00',
            '2026-08-02 10:00:00',
        ] as $dateTime) {
            $this->assertTrue($calculator->isBusinessMinute(CarbonImmutable::parse($dateTime, 'Asia/Jakarta'), $calendar));
        }
    }

    public function test_weekend_support_has_weekdays_off_and_weekend_working(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        $calendar = BusinessCalendar::query()->where('name', 'Weekend Support')->firstOrFail();

        foreach (range(1, 5) as $day) {
            $hours = $calendar->workingHourForDay($day);

            $this->assertFalse($hours->is_working_day);
            $this->assertNull($hours->start_time);
            $this->assertNull($hours->end_time);
        }

        foreach ([6, 7] as $day) {
            $hours = $calendar->workingHourForDay($day);

            $this->assertTrue($hours->is_working_day);
            $this->assertSame('08:00', (string) $hours->start_time);
            $this->assertSame('17:00', (string) $hours->end_time);
        }
    }

    public function test_only_one_active_default_calendar_exists(): void
    {
        $this->seed(BusinessCalendarSeeder::class);
        $this->seed(BusinessCalendarSeeder::class);

        $this->assertSame(1, BusinessCalendar::query()->defaultCalendar()->count());
        $this->assertTrue(BusinessCalendar::query()->where('name', 'Indonesia Standard Support')->firstOrFail()->is_default);
    }

    public function test_user_selected_default_calendar_remains_untouched(): void
    {
        $custom = BusinessCalendar::factory()->create([
            'name' => 'Custom Default Calendar',
            'description' => 'User selected default.',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'is_default' => true,
        ]);

        $this->seed(BusinessCalendarSeeder::class);

        $this->assertTrue($custom->fresh()->is_default);
        $this->assertFalse(BusinessCalendar::query()->where('name', 'Indonesia Standard Support')->firstOrFail()->is_default);
        $this->assertSame(1, BusinessCalendar::query()->defaultCalendar()->count());
    }

    public function test_user_created_calendar_remains_untouched(): void
    {
        $custom = BusinessCalendar::factory()->create([
            'name' => 'Custom Ops Calendar',
            'description' => 'User-created calendar.',
            'timezone' => 'Asia/Makassar',
            'is_active' => true,
            'is_default' => false,
        ]);

        $this->seed(BusinessCalendarSeeder::class);

        $custom->refresh();

        $this->assertSame('Custom Ops Calendar', $custom->name);
        $this->assertSame('User-created calendar.', $custom->description);
        $this->assertSame('Asia/Makassar', $custom->timezone);
        $this->assertFalse($custom->is_default);
    }

    public function test_user_created_holidays_remain_untouched(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        $calendar = BusinessCalendar::query()->where('name', 'Indonesia Standard Support')->firstOrFail();
        $holiday = BusinessCalendarHoliday::factory()->create([
            'business_calendar_id' => $calendar->id,
            'holiday_date' => '2026-08-17',
            'name' => 'User Holiday',
            'is_recurring' => true,
        ]);

        $this->seed(BusinessCalendarSeeder::class);

        $this->assertDatabaseHas('business_calendar_holidays', [
            'id' => $holiday->id,
            'business_calendar_id' => $calendar->id,
            'name' => 'User Holiday',
            'is_recurring' => true,
        ]);
    }

    public function test_seeder_updates_existing_template_working_hours_to_match_definition(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        $calendar = BusinessCalendar::query()->where('name', '24x7 Support')->firstOrFail();
        $calendar->workingHourForDay(1)->update([
            'is_working_day' => false,
            'start_time' => null,
            'end_time' => null,
        ]);

        $this->seed(BusinessCalendarSeeder::class);

        $monday = $calendar->fresh()->workingHourForDay(1);

        $this->assertTrue($monday->is_working_day);
        $this->assertSame('00:00', (string) $monday->start_time);
        $this->assertSame('23:59', (string) $monday->end_time);
    }

    public function test_sla_policy_create_dropdown_contains_all_active_seeded_calendars(): void
    {
        $this->seed(BusinessCalendarSeeder::class);

        $this->get(route('admin.service.sla.create'))
            ->assertOk()
            ->assertSee('Indonesia Standard Support')
            ->assertSee('24x7 Support')
            ->assertSee('Weekend Support')
            ->assertSee('Asia/Jakarta')
            ->assertSee('Default');
    }
}
