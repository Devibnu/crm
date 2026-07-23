<?php

namespace Tests\Feature;

use App\Models\BusinessCalendar;
use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\BusinessCalendar\BusinessCalendarService;
use App\Services\Tickets\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketBusinessCalendarIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_ticket_created_monday_morning_uses_business_minutes(): void
    {
        $this->defaultCalendar();
        $policy = $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-07-27 09:00:00', 'medium', 'Monday SLA ticket');

        $this->assertSame($policy->id, $ticket->sla_policy_id);
        $this->assertResponseDueAt('2026-07-27 10:00:00', $ticket);
        $this->assertResolutionDueAt('2026-07-27 11:00:00', $ticket);
    }

    public function test_ticket_created_friday_afternoon_rolls_into_next_business_day(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-07-31 16:30:00', 'medium', 'Friday SLA ticket');

        $this->assertResponseDueAt('2026-08-03 08:30:00', $ticket);
        $this->assertResolutionDueAt('2026-08-03 09:30:00', $ticket);
    }

    public function test_ticket_created_after_office_hours_starts_next_business_day(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-07-27 17:30:00', 'medium', 'After hours SLA ticket');

        $this->assertResponseDueAt('2026-07-28 09:00:00', $ticket);
        $this->assertResolutionDueAt('2026-07-28 10:00:00', $ticket);
    }

    public function test_ticket_created_before_office_opens_starts_at_opening_time(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-07-27 07:30:00', 'medium', 'Before open SLA ticket');

        $this->assertResponseDueAt('2026-07-27 09:00:00', $ticket);
        $this->assertResolutionDueAt('2026-07-27 10:00:00', $ticket);
    }

    public function test_ticket_created_on_weekend_starts_next_working_day(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-08-01 10:00:00', 'medium', 'Weekend SLA ticket');

        $this->assertResponseDueAt('2026-08-03 09:00:00', $ticket);
        $this->assertResolutionDueAt('2026-08-03 10:00:00', $ticket);
    }

    public function test_ticket_created_on_holiday_starts_next_working_day(): void
    {
        $this->defaultCalendar(holidays: [
            ['holiday_date' => '2026-08-03', 'name' => 'Company Holiday', 'is_recurring' => false],
        ]);
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-08-03 10:00:00', 'medium', 'Holiday SLA ticket');

        $this->assertResponseDueAt('2026-08-04 09:00:00', $ticket);
        $this->assertResolutionDueAt('2026-08-04 10:00:00', $ticket);
    }

    public function test_recurring_holiday_is_respected_across_years(): void
    {
        $this->defaultCalendar(holidays: [
            ['holiday_date' => '2026-08-17', 'name' => 'Independence Day', 'is_recurring' => true],
        ]);
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2027-08-17 10:00:00', 'medium', 'Recurring holiday SLA ticket');

        $this->assertResponseDueAt('2027-08-18 09:00:00', $ticket);
        $this->assertResolutionDueAt('2027-08-18 10:00:00', $ticket);
    }

    public function test_calendar_timezone_is_used_for_due_date_calculation(): void
    {
        $this->defaultCalendar(['timezone' => 'Asia/Jakarta']);
        $this->activePolicy('medium', 60, 120);

        Carbon::setTestNow(Carbon::parse('2026-07-27 01:00:00', 'UTC'));
        $ticket = $this->createTicket('medium', 'Timezone SLA ticket');

        $this->assertResponseDueAt('2026-07-27 09:00:00', $ticket);
        $this->assertResolutionDueAt('2026-07-27 10:00:00', $ticket);
    }

    public function test_priority_recalculation_uses_business_calendar(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 60, 120);
        $highPolicy = $this->activePolicy('high', 120, 240);
        $ticket = $this->createTicketAt('2026-07-27 16:00:00', 'medium', 'Priority refresh SLA ticket');

        app(TicketWorkflowService::class)->update($ticket->fresh(), [
            'priority' => 'high',
            'status' => 'open',
        ]);

        $ticket->refresh();

        $this->assertSame($highPolicy->id, $ticket->sla_policy_id);
        $this->assertResponseDueAt('2026-07-28 09:00:00', $ticket);
        $this->assertResolutionDueAt('2026-07-28 11:00:00', $ticket);
    }

    public function test_same_priority_update_does_not_recalculate_business_sla(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 60, 120);
        $ticket = $this->createTicketAt('2026-07-27 16:00:00', 'medium', 'No refresh SLA ticket');
        $originalResponseDueAt = $ticket->response_due_at->copy();

        Carbon::setTestNow('2026-07-28 10:00:00');
        app(TicketWorkflowService::class)->update($ticket->fresh(), [
            'subject' => 'No refresh SLA ticket updated',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->assertTrue($originalResponseDueAt->equalTo($ticket->fresh()->response_due_at));
    }

    public function test_missing_default_calendar_falls_back_to_calendar_minutes(): void
    {
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-07-27 17:30:00', 'medium', 'Fallback SLA ticket');

        $this->assertResponseDueAt('2026-07-27 11:30:00', $ticket);
        $this->assertResolutionDueAt('2026-07-27 12:30:00', $ticket);
    }

    public function test_multi_day_sla_crosses_working_days(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 600, 720);

        $ticket = $this->createTicketAt('2026-07-27 09:00:00', 'medium', 'Multi day SLA ticket');

        $this->assertResponseDueAt('2026-07-28 10:00:00', $ticket);
        $this->assertResolutionDueAt('2026-07-28 12:00:00', $ticket);
    }

    public function test_month_boundary_is_handled(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 120, 180);

        $ticket = $this->createTicketAt('2026-07-31 16:30:00', 'medium', 'Month boundary SLA ticket');

        $this->assertResponseDueAt('2026-08-03 09:30:00', $ticket);
        $this->assertResolutionDueAt('2026-08-03 10:30:00', $ticket);
    }

    public function test_year_boundary_and_cross_year_holiday_are_handled(): void
    {
        $this->defaultCalendar(holidays: [
            ['holiday_date' => '2027-01-01', 'name' => 'New Year', 'is_recurring' => false],
        ]);
        $this->activePolicy('medium', 120, 180);

        $ticket = $this->createTicketAt('2026-12-31 16:30:00', 'medium', 'Year boundary SLA ticket');

        $this->assertResponseDueAt('2027-01-04 09:30:00', $ticket);
        $this->assertResolutionDueAt('2027-01-04 10:30:00', $ticket);
    }

    public function test_working_saturday_calendar_is_supported(): void
    {
        $this->defaultCalendar(saturdayWorking: true);
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-08-01 10:00:00', 'medium', 'Saturday SLA ticket');

        $this->assertResponseDueAt('2026-08-01 11:00:00', $ticket);
        $this->assertResolutionDueAt('2026-08-01 12:00:00', $ticket);
    }

    public function test_twenty_four_hour_calendar_is_supported(): void
    {
        $this->defaultCalendar(open: '00:00', close: '23:59', saturdayWorking: true, sundayWorking: true);
        $this->activePolicy('medium', 60, 120);

        $ticket = $this->createTicketAt('2026-07-27 23:30:00', 'medium', 'Twenty four hour SLA ticket');

        $this->assertResponseDueAt('2026-07-28 00:30:00', $ticket);
        $this->assertResolutionDueAt('2026-07-28 01:30:00', $ticket);
    }

    public function test_first_response_and_resolution_sla_use_business_due_dates(): void
    {
        $this->defaultCalendar();
        $this->activePolicy('medium', 60, 120);
        $ticket = $this->createTicketAt('2026-07-31 16:30:00', 'medium', 'Response resolution SLA ticket');

        $ticket = app(\App\Services\Sla\TicketSlaService::class)
            ->markFirstResponse($ticket->fresh(), Carbon::parse('2026-08-03 08:15:00'));

        $this->assertNull($ticket->sla_response_breached_at);
        $this->assertSame('on_time', $ticket->responseSlaStatus());

        Carbon::setTestNow('2026-08-03 09:45:00');
        app(TicketWorkflowService::class)->update($ticket->fresh(), ['status' => 'in_progress']);
        app(TicketWorkflowService::class)->update($ticket->fresh(), ['status' => 'waiting_customer']);
        app(TicketWorkflowService::class)->resolve($ticket->fresh());

        $ticket->refresh();

        $this->assertSame('2026-08-03 09:45:00', $ticket->sla_resolution_breached_at->format('Y-m-d H:i:s'));
        $this->assertSame('breached', $ticket->resolutionSlaStatus());
    }

    public function test_whatsapp_ticket_uses_business_calendar_sla_engine(): void
    {
        $this->defaultCalendar();
        $policy = $this->activePolicy('medium', 60, 120);

        Carbon::setTestNow(Carbon::parse('2026-07-31 16:30:00', 'Asia/Jakarta')->setTimezone(config('app.timezone')));
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Support Customer',
            'phone_number' => '6281211100000',
            'channel' => 'whatsapp',
            'last_message' => 'Need help',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6281211100000',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Need help',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);

        $this->post(route('admin.marketing.whatsapp-replies.messages.create-ticket', $message), [
            '_token' => 'test-token',
        ])->assertRedirect(route('admin.marketing.whatsapp-replies.index'));

        $ticket = Ticket::query()->where('whatsapp_message_id', $message->id)->firstOrFail();

        $this->assertSame($policy->id, $ticket->sla_policy_id);
        $this->assertResponseDueAt('2026-08-03 08:30:00', $ticket);
        $this->assertResolutionDueAt('2026-08-03 09:30:00', $ticket);
    }

    protected function createTicketAt(string $now, string $priority, string $subject): Ticket
    {
        Carbon::setTestNow(Carbon::parse($now, 'Asia/Jakarta')->setTimezone(config('app.timezone')));

        return $this->createTicket($priority, $subject);
    }

    protected function createTicket(string $priority, string $subject): Ticket
    {
        $customer = Customer::factory()->create();

        $this->post(route('admin.service.tickets.store'), [
            'customer_id' => $customer->id,
            'subject' => $subject,
            'description' => 'Business calendar SLA integration test.',
            'priority' => $priority,
            'status' => 'open',
            'channel' => 'email',
        ])->assertRedirect();

        return Ticket::query()->where('subject', $subject)->firstOrFail();
    }

    protected function activePolicy(string $priority, int $responseMinutes, int $resolutionMinutes): SlaPolicy
    {
        return SlaPolicy::factory()->create([
            'priority' => $priority,
            'response_time_minutes' => $responseMinutes,
            'resolution_time_minutes' => $resolutionMinutes,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @param  array<int, array<string, mixed>>  $holidays
     */
    protected function defaultCalendar(
        array $overrides = [],
        array $holidays = [],
        bool $saturdayWorking = false,
        bool $sundayWorking = false,
        string $open = '08:00',
        string $close = '17:00',
    ): BusinessCalendar {
        $calendar = app(BusinessCalendarService::class)->create(
            array_merge([
                'name' => 'Default SLA Calendar',
                'description' => 'Default calendar for SLA integration tests.',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'is_default' => true,
            ], $overrides),
            $this->workingHoursPayload($saturdayWorking, $sundayWorking, $open, $close),
        );

        foreach ($holidays as $holiday) {
            $calendar->holidays()->create($holiday);
        }

        return $calendar->refresh()->load(['workingHours', 'holidays']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function workingHoursPayload(
        bool $saturdayWorking,
        bool $sundayWorking,
        string $open,
        string $close,
    ): array {
        return collect(range(1, 7))
            ->mapWithKeys(function (int $day) use ($saturdayWorking, $sundayWorking, $open, $close): array {
                $isWorkingDay = $day <= 5 || ($saturdayWorking && $day === 6) || ($sundayWorking && $day === 7);

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

    protected function assertResponseDueAt(string $expected, Ticket $ticket): void
    {
        $this->assertSame($expected, $ticket->fresh()->response_due_at->format('Y-m-d H:i:s'));
    }

    protected function assertResolutionDueAt(string $expected, Ticket $ticket): void
    {
        $this->assertSame($expected, $ticket->fresh()->resolution_due_at->format('Y-m-d H:i:s'));
    }
}
