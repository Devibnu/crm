<?php

namespace Tests\Feature;

use App\Models\BusinessCalendar;
use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketSlaEscalation;
use App\Services\BusinessCalendar\BusinessCalendarService;
use App\Services\Sla\SlaEscalationService;
use App\Services\Sla\TicketSlaService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlaEscalationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_response_warning_fires_at_warning_threshold(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, responseMinutes: 120, resolutionMinutes: 240);

        $this->setNow('2026-07-27 09:36:00');
        $summary = app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());

        $this->assertSame(1, $summary['warnings']);
        $this->assertEscalationExists($ticket, TicketSlaEscalation::TYPE_RESPONSE_WARNING);
    }

    public function test_response_breach_fires_after_due_time(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, responseMinutes: 60);

        $this->setNow('2026-07-27 09:01:00');
        $summary = app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());

        $this->assertSame(1, $summary['breaches']);
        $this->assertEscalationExists($ticket, TicketSlaEscalation::TYPE_RESPONSE_BREACH);
    }

    public function test_escalation_records_are_not_duplicated(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, responseMinutes: 60);

        $this->setNow('2026-07-27 09:01:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());
        $summary = app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());

        $this->assertSame(0, $summary['breaches']);
        $this->assertSame(1, $ticket->slaEscalations()->where('type', TicketSlaEscalation::TYPE_RESPONSE_BREACH)->count());
    }

    public function test_closed_ticket_is_ignored(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, status: 'closed');

        $this->setNow('2026-07-28 10:00:00');
        $summary = app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());

        $this->assertSame(1, $summary['skipped']);
        $this->assertSame(0, $ticket->slaEscalations()->count());
    }

    public function test_resolved_ticket_ignores_resolution_escalation(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, status: 'resolved', resolutionMinutes: 60);
        $ticket->forceFill([
            'first_responded_at' => Carbon::parse('2026-07-27 08:10:00'),
            'resolved_at' => Carbon::parse('2026-07-27 08:30:00'),
        ])->save();

        $this->setNow('2026-07-27 10:00:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());

        $this->assertFalse($ticket->fresh()->hasResolutionBreach());
        $this->assertFalse($ticket->fresh()->hasResolutionWarning());
    }

    public function test_waiting_customer_ticket_keeps_counting_for_resolution(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, status: 'waiting_customer', resolutionMinutes: 60);
        $ticket->forceFill(['first_responded_at' => Carbon::parse('2026-07-27 08:10:00')])->save();

        $this->setNow('2026-07-27 09:01:00');
        $summary = app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());

        $this->assertSame(1, $summary['breaches']);
        $this->assertEscalationExists($ticket, TicketSlaEscalation::TYPE_RESOLUTION_BREACH);
    }

    public function test_weekend_is_skipped_for_warning_calculation(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-31 16:30:00', 'medium', $calendar, responseMinutes: 60);

        $this->setNow('2026-08-02 12:00:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());
        $this->assertFalse($ticket->fresh()->hasResponseWarning());

        $this->setNow('2026-08-03 08:18:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());
        $this->assertTrue($ticket->fresh()->hasResponseWarning());
    }

    public function test_holiday_is_skipped_for_warning_calculation(): void
    {
        $calendar = $this->calendar(holidays: [
            ['holiday_date' => '2026-08-03', 'name' => 'Company Holiday', 'is_recurring' => false],
        ]);
        $ticket = $this->slaTicket('2026-07-31 16:30:00', 'medium', $calendar, responseMinutes: 60);

        $this->setNow('2026-08-03 12:00:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());
        $this->assertFalse($ticket->fresh()->hasResponseWarning());

        $this->setNow('2026-08-04 08:18:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());
        $this->assertTrue($ticket->fresh()->hasResponseWarning());
    }

    public function test_policy_specific_calendar_drives_warning_threshold(): void
    {
        $defaultCalendar = $this->calendar(name: 'Default Calendar', isDefault: true, open: '08:00');
        $policyCalendar = $this->calendar(name: 'Late Calendar', open: '10:00');
        $ticket = $this->slaTicket('2026-07-27 09:00:00', 'medium', $policyCalendar, responseMinutes: 60);

        $this->setNow('2026-07-27 10:00:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());
        $this->assertFalse($ticket->fresh()->hasResponseWarning());
        $this->assertNotSame($defaultCalendar->id, $ticket->sla_business_calendar_id);

        $this->setNow('2026-07-27 10:48:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());
        $this->assertTrue($ticket->fresh()->hasResponseWarning());
    }

    public function test_sla_check_command_creates_escalations_and_outputs_summary(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, responseMinutes: 60);

        $this->setNow('2026-07-27 09:01:00');

        $this->artisan('sla:check')
            ->expectsOutput('SLA escalation check completed.')
            ->assertExitCode(0);

        $this->assertEscalationExists($ticket, TicketSlaEscalation::TYPE_RESPONSE_BREACH);
    }

    public function test_scheduler_registers_sla_check_command(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('sla:check')
            ->assertExitCode(0);
    }

    public function test_sla_dashboard_displays_escalation_metrics(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, responseMinutes: 60);

        $this->setNow('2026-07-27 09:01:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());

        $this->get(route('admin.service.sla.index'))
            ->assertOk()
            ->assertSee('Tickets On Time')
            ->assertSee('Warning')
            ->assertSee('Breached')
            ->assertSee('Average Response')
            ->assertSee('Average Resolution');
    }

    public function test_ticket_360_displays_sla_escalation_timeline(): void
    {
        $calendar = $this->calendar();
        $ticket = $this->slaTicket('2026-07-27 08:00:00', 'medium', $calendar, responseMinutes: 120);

        $this->setNow('2026-07-27 09:36:00');
        app(SlaEscalationService::class)->evaluateTicket($ticket->fresh());

        $this->get(route('admin.service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('SLA Escalation')
            ->assertSee('Warning')
            ->assertSee('Response Warning');
    }

    protected function slaTicket(
        string $createdAt,
        string $priority,
        BusinessCalendar $calendar,
        string $status = 'open',
        int $responseMinutes = 60,
        int $resolutionMinutes = 120,
    ): Ticket {
        $this->setNow($createdAt);
        SlaPolicy::factory()->create([
            'priority' => $priority,
            'business_calendar_id' => $calendar->id,
            'response_time_minutes' => $responseMinutes,
            'response_warning_percentage' => 80,
            'resolution_time_minutes' => $resolutionMinutes,
            'resolution_warning_percentage' => 80,
            'is_active' => true,
        ]);

        $ticket = Ticket::factory()->create([
            'customer_id' => Customer::factory(),
            'priority' => $priority,
            'status' => $status,
            'resolved_at' => null,
            'closed_at' => null,
        ]);

        return app(TicketSlaService::class)->apply($ticket);
    }

    /**
     * @param  array<int, array<string, mixed>>  $holidays
     */
    protected function calendar(
        string $name = 'Escalation Calendar',
        bool $isDefault = false,
        string $open = '08:00',
        string $close = '17:00',
        array $holidays = [],
    ): BusinessCalendar {
        $calendar = app(BusinessCalendarService::class)->create(
            [
                'name' => $name.' '.uniqid(),
                'description' => 'Calendar for SLA escalation tests.',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'is_default' => $isDefault,
            ],
            $this->workingHours($open, $close),
        );

        foreach ($holidays as $holiday) {
            $calendar->holidays()->create($holiday);
        }

        return $calendar->refresh()->load(['workingHours', 'holidays']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function workingHours(string $open, string $close): array
    {
        return collect(range(1, 7))
            ->mapWithKeys(function (int $day) use ($open, $close): array {
                $isWorkingDay = $day <= 5;

                return [
                    $day => [
                        'day_of_week' => $day,
                        'is_working_day' => $isWorkingDay,
                        'start_time' => $isWorkingDay ? $open : null,
                        'end_time' => $isWorkingDay ? $close : null,
                    ],
                ];
            })
            ->all();
    }

    protected function setNow(string $dateTime): void
    {
        Carbon::setTestNow(CarbonImmutable::parse($dateTime, 'Asia/Jakarta')->setTimezone(config('app.timezone')));
    }

    protected function assertEscalationExists(Ticket $ticket, string $type): void
    {
        $this->assertDatabaseHas('ticket_sla_escalations', [
            'ticket_id' => $ticket->id,
            'type' => $type,
        ]);
    }
}
