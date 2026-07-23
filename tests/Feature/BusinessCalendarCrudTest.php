<?php

namespace Tests\Feature;

use App\Models\BusinessCalendar;
use App\Models\BusinessCalendarHoliday;
use App\Models\User;
use App\Services\BusinessCalendar\BusinessCalendarService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BusinessCalendarCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_list_create_show_and_edit_calendars(): void
    {
        $calendar = $this->createCalendar(['name' => 'Support Jakarta Calendar']);

        $this->get(route('admin.service.business-calendars.index'))
            ->assertOk()
            ->assertSee('Business Calendar')
            ->assertSee('Support Jakarta Calendar');

        $this->get(route('admin.service.business-calendars.create'))
            ->assertOk()
            ->assertSee('Add Business Calendar');

        $this->get(route('admin.service.business-calendars.show', $calendar))
            ->assertOk()
            ->assertSee('Business Calendar 360')
            ->assertSee('Support Jakarta Calendar');

        $this->get(route('admin.service.business-calendars.edit', $calendar))
            ->assertOk()
            ->assertSee('Edit Business Calendar');
    }

    public function test_business_calendar_can_be_created_with_exactly_seven_weekday_records(): void
    {
        $response = $this->post(route('admin.service.business-calendars.store'), $this->calendarPayload([
            'name' => 'Indonesia Support Hours',
            'timezone' => 'Asia/Jakarta',
            'is_default' => 1,
        ]));

        $calendar = BusinessCalendar::query()->where('name', 'Indonesia Support Hours')->firstOrFail();

        $response->assertRedirect(route('admin.service.business-calendars.show', $calendar));
        $this->assertSame(7, $calendar->workingHours()->count());
        $this->assertDatabaseHas('business_calendars', [
            'id' => $calendar->id,
            'timezone' => 'Asia/Jakarta',
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    public function test_business_calendar_update_preserves_exactly_seven_weekday_records(): void
    {
        $calendar = $this->createCalendar(['name' => 'Before Calendar']);

        $this->put(route('admin.service.business-calendars.update', $calendar), $this->calendarPayload([
            'name' => 'After Calendar',
            'timezone' => 'Asia/Makassar',
        ], saturdayWorking: true))->assertRedirect(route('admin.service.business-calendars.show', $calendar));

        $calendar->refresh();

        $this->assertSame('After Calendar', $calendar->name);
        $this->assertSame('Asia/Makassar', $calendar->timezone);
        $this->assertSame(7, $calendar->workingHours()->count());
        $this->assertTrue($calendar->workingHourForDay(6)->is_working_day);
    }

    public function test_default_calendar_cannot_be_inactive(): void
    {
        $response = $this->from(route('admin.service.business-calendars.create'))
            ->post(route('admin.service.business-calendars.store'), $this->calendarPayload([
                'name' => 'Invalid Default Calendar',
                'is_active' => 0,
                'is_default' => 1,
            ]));

        $response
            ->assertRedirect(route('admin.service.business-calendars.create'))
            ->assertSessionHasErrors('is_active');

        $this->assertDatabaseMissing('business_calendars', [
            'name' => 'Invalid Default Calendar',
        ]);
    }

    public function test_setting_new_default_unsets_previous_default(): void
    {
        $first = $this->createCalendar(['name' => 'First Default', 'is_default' => true]);
        $second = $this->createCalendar(['name' => 'Second Calendar']);

        $this->post(route('admin.service.business-calendars.set-default', $second))
            ->assertRedirect(route('admin.service.business-calendars.show', $second));

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_active);
    }

    public function test_active_default_calendar_cannot_be_deleted(): void
    {
        $calendar = $this->createCalendar(['name' => 'Default Calendar', 'is_default' => true]);

        $this->from(route('admin.service.business-calendars.show', $calendar))
            ->delete(route('admin.service.business-calendars.destroy', $calendar))
            ->assertRedirect(route('admin.service.business-calendars.show', $calendar))
            ->assertSessionHasErrors('calendar');

        $this->assertDatabaseHas('business_calendars', [
            'id' => $calendar->id,
        ]);
    }

    public function test_non_default_calendar_can_be_deleted(): void
    {
        $calendar = $this->createCalendar(['name' => 'Disposable Calendar']);

        $this->delete(route('admin.service.business-calendars.destroy', $calendar))
            ->assertRedirect(route('admin.service.business-calendars.index'));

        $this->assertDatabaseMissing('business_calendars', [
            'id' => $calendar->id,
        ]);
    }

    public function test_duplicate_holiday_date_is_rejected(): void
    {
        $calendar = $this->createCalendar();
        BusinessCalendarHoliday::factory()->create([
            'business_calendar_id' => $calendar->id,
            'holiday_date' => '2026-08-17',
            'name' => 'Independence Day',
        ]);

        $this->from(route('admin.service.business-calendars.show', $calendar))
            ->post(route('admin.service.business-calendars.holidays.store', $calendar), [
                'holiday_date' => '2026-08-17',
                'name' => 'Duplicate Holiday',
                'is_recurring' => 1,
            ])
            ->assertRedirect(route('admin.service.business-calendars.show', $calendar))
            ->assertSessionHasErrors('holiday_date');
    }

    public function test_holiday_crud_works(): void
    {
        $calendar = $this->createCalendar();

        $this->post(route('admin.service.business-calendars.holidays.store', $calendar), [
            'holiday_date' => '2026-12-25',
            'name' => 'Christmas',
            'description' => 'Public holiday',
            'is_recurring' => 1,
        ])->assertRedirect(route('admin.service.business-calendars.show', $calendar));

        $holiday = $calendar->holidays()->where('name', 'Christmas')->firstOrFail();
        $this->assertTrue($holiday->is_recurring);

        $this->put(route('admin.service.business-calendars.holidays.update', [$calendar, $holiday]), [
            'holiday_date' => '2026-12-24',
            'name' => 'Christmas Eve',
            'description' => 'Adjusted holiday',
            'is_recurring' => 0,
        ])->assertRedirect(route('admin.service.business-calendars.show', $calendar));

        $holiday->refresh();

        $this->assertSame('2026-12-24', $holiday->holiday_date->toDateString());
        $this->assertSame('Christmas Eve', $holiday->name);
        $this->assertFalse($holiday->is_recurring);

        $this->delete(route('admin.service.business-calendars.holidays.destroy', [$calendar, $holiday]))
            ->assertRedirect(route('admin.service.business-calendars.show', $calendar));

        $this->assertDatabaseMissing('business_calendar_holidays', [
            'id' => $holiday->id,
        ]);
    }

    public function test_sidebar_is_active_on_business_calendar_routes_only(): void
    {
        $calendar = $this->createCalendar(['name' => 'Sidebar Calendar']);
        $activeCalendarNavigation = 'href="'.route('admin.service.business-calendars.index').'" class="nav-link parent compact active"';
        $activeTicketNavigation = 'href="'.route('admin.service.tickets.index').'" class="nav-link parent compact active"';

        foreach ([
            route('admin.service.business-calendars.index'),
            route('admin.service.business-calendars.create'),
            route('admin.service.business-calendars.show', $calendar),
            route('admin.service.business-calendars.edit', $calendar),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($activeCalendarNavigation, false)
                ->assertDontSee($activeTicketNavigation, false);
        }
    }

    public function test_unauthorized_user_is_blocked_and_permission_visibility_is_preserved(): void
    {
        $calendar = $this->createCalendar(['name' => 'Permission Calendar']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.service.business-calendars.index'))
            ->assertForbidden();

        $role = Role::create(['name' => 'business_calendar_viewer', 'guard_name' => 'web']);
        $role->syncPermissions(['business-calendar.view']);
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.service.business-calendars.index'))
            ->assertOk()
            ->assertSee('Permission Calendar')
            ->assertDontSee(route('admin.service.business-calendars.create'), false)
            ->assertDontSee(route('admin.service.business-calendars.edit', $calendar), false)
            ->assertDontSee('method="POST" action="'.route('admin.service.business-calendars.destroy', $calendar).'"', false);

        $this->actingAs($user)
            ->get(route('admin.service.business-calendars.create'))
            ->assertForbidden();
    }

    protected function createCalendar(array $overrides = []): BusinessCalendar
    {
        return app(BusinessCalendarService::class)->create(
            array_merge([
                'name' => 'Business Calendar',
                'description' => 'Standard support hours.',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'is_default' => false,
            ], $overrides),
            $this->workingHoursPayload(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function calendarPayload(array $overrides = [], bool $saturdayWorking = false): array
    {
        return array_merge([
            'name' => 'Business Calendar',
            'description' => 'Standard support hours.',
            'timezone' => 'Asia/Jakarta',
            'is_active' => 1,
            'is_default' => 0,
            'working_hours' => $this->workingHoursPayload($saturdayWorking),
        ], $overrides);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function workingHoursPayload(bool $saturdayWorking = false): array
    {
        return collect(range(1, 7))
            ->mapWithKeys(function (int $day) use ($saturdayWorking): array {
                $isWorkingDay = $day <= 5 || ($saturdayWorking && $day === 6);

                return [
                    $day => [
                        'day_of_week' => $day,
                        'is_working_day' => $isWorkingDay ? 1 : 0,
                        'start_time' => $isWorkingDay ? '08:00' : null,
                        'end_time' => $isWorkingDay ? '17:00' : null,
                    ],
                ];
            })
            ->all();
    }
}
