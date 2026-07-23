<?php

namespace Tests\Feature;

use App\Models\BusinessCalendar;
use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\BusinessCalendar\BusinessCalendarService;
use App\Services\Sla\TicketSlaService;
use App\Services\Tickets\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlaPolicyBusinessCalendarTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_create_sla_policy_requires_business_calendar(): void
    {
        $this->from(route('admin.service.sla.create'))
            ->post(route('admin.service.sla.store'), $this->policyPayload(['business_calendar_id' => null]))
            ->assertRedirect(route('admin.service.sla.create'))
            ->assertSessionHasErrors('business_calendar_id');
    }

    public function test_inactive_business_calendar_is_rejected(): void
    {
        $calendar = $this->calendar(['is_active' => false], isDefault: false);

        $this->from(route('admin.service.sla.create'))
            ->post(route('admin.service.sla.store'), $this->policyPayload(['business_calendar_id' => $calendar->id]))
            ->assertRedirect(route('admin.service.sla.create'))
            ->assertSessionHasErrors('business_calendar_id');
    }

    public function test_active_business_calendar_can_be_assigned(): void
    {
        $calendar = $this->calendar(name: 'Indonesia Standard Support');

        $this->post(route('admin.service.sla.store'), $this->policyPayload([
            'name' => 'Calendar Assigned SLA',
            'business_calendar_id' => $calendar->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('sla_policies', [
            'name' => 'Calendar Assigned SLA',
            'business_calendar_id' => $calendar->id,
        ]);
    }

    public function test_update_changes_assigned_business_calendar(): void
    {
        $oldCalendar = $this->calendar(name: 'Old Support Calendar');
        $newCalendar = $this->calendar(name: 'New Support Calendar');
        $policy = SlaPolicy::factory()->create([
            'priority' => 'low',
            'is_active' => false,
            'business_calendar_id' => $oldCalendar->id,
        ]);

        $this->put(route('admin.service.sla.update', $policy), $this->policyPayload([
            'name' => $policy->name,
            'priority' => 'low',
            'is_active' => 0,
            'business_calendar_id' => $newCalendar->id,
        ]))->assertRedirect(route('admin.service.sla.show', $policy));

        $this->assertSame($newCalendar->id, $policy->fresh()->business_calendar_id);
    }

    public function test_index_displays_assigned_business_calendar(): void
    {
        $calendar = $this->calendar(name: 'Index Calendar');
        $policy = SlaPolicy::factory()->create([
            'name' => 'Index Calendar SLA',
            'business_calendar_id' => $calendar->id,
        ]);

        $this->get(route('admin.service.sla.index'))
            ->assertOk()
            ->assertSee($policy->name)
            ->assertSee('Index Calendar')
            ->assertSee($calendar->timezone);
    }

    public function test_show_displays_assigned_business_calendar(): void
    {
        $calendar = $this->calendar(name: 'Show Calendar');
        $policy = SlaPolicy::factory()->create([
            'name' => 'Show Calendar SLA',
            'business_calendar_id' => $calendar->id,
        ]);

        $this->get(route('admin.service.sla.show', $policy))
            ->assertOk()
            ->assertSee('Business Calendar')
            ->assertSee('Show Calendar')
            ->assertSee($calendar->timezone);
    }

    public function test_legacy_policy_with_null_calendar_remains_readable(): void
    {
        $policy = SlaPolicy::factory()->create([
            'name' => 'Legacy Null Calendar SLA',
            'business_calendar_id' => null,
        ]);

        $this->get(route('admin.service.sla.show', $policy))
            ->assertOk()
            ->assertSee('Legacy Null Calendar SLA')
            ->assertSee('Belum ditentukan');
    }

    public function test_ticket_uses_policy_specific_calendar_instead_of_global_default(): void
    {
        $defaultCalendar = $this->calendar(name: 'Default Calendar', isDefault: true, open: '08:00', close: '17:00');
        $policyCalendar = $this->calendar(name: 'Policy Calendar', open: '10:00', close: '18:00');
        $policy = $this->activePolicy('medium', 60, 120, $policyCalendar);

        $ticket = $this->createTicketAt('2026-07-27 09:00:00', 'medium', 'Policy calendar ticket');

        $this->assertSame($policy->id, $ticket->sla_policy_id);
        $this->assertNotSame($defaultCalendar->id, $ticket->sla_business_calendar_id);
        $this->assertSame($policyCalendar->id, $ticket->sla_business_calendar_id);
        $this->assertDueAt('2026-07-27 11:00:00', $ticket->response_due_at);
    }

    public function test_two_policies_with_different_calendars_produce_different_due_dates(): void
    {
        $earlyCalendar = $this->calendar(name: 'Early Calendar', open: '08:00', close: '17:00');
        $lateCalendar = $this->calendar(name: 'Late Calendar', open: '10:00', close: '18:00');
        $this->activePolicy('medium', 60, 120, $earlyCalendar);
        $this->activePolicy('high', 60, 120, $lateCalendar);

        $mediumTicket = $this->createTicketAt('2026-07-27 09:00:00', 'medium', 'Early calendar SLA ticket');
        $highTicket = $this->createTicketAt('2026-07-27 09:00:00', 'high', 'Late calendar SLA ticket');

        $this->assertDueAt('2026-07-27 10:00:00', $mediumTicket->response_due_at);
        $this->assertDueAt('2026-07-27 11:00:00', $highTicket->response_due_at);
    }

    public function test_ticket_stores_business_calendar_snapshot(): void
    {
        $calendar = $this->calendar(name: 'Snapshot Calendar');
        $this->activePolicy('medium', 60, 120, $calendar);

        $ticket = $this->createTicketAt('2026-07-27 09:00:00', 'medium', 'Snapshot calendar ticket');

        $this->assertSame($calendar->id, $ticket->sla_business_calendar_id);
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'sla_business_calendar_id' => $calendar->id,
        ]);
    }

    public function test_changing_policy_calendar_does_not_change_existing_ticket_snapshot(): void
    {
        $oldCalendar = $this->calendar(name: 'Old SLA Calendar', open: '08:00', close: '17:00');
        $newCalendar = $this->calendar(name: 'New SLA Calendar', open: '10:00', close: '18:00');
        $policy = $this->activePolicy('medium', 60, 120, $oldCalendar);
        $ticket = $this->createTicketAt('2026-07-27 09:00:00', 'medium', 'Historical calendar ticket');

        $policy->update(['business_calendar_id' => $newCalendar->id]);
        $ticket->refresh();

        $this->assertSame($oldCalendar->id, $ticket->sla_business_calendar_id);
        $this->assertDueAt('2026-07-27 10:00:00', $ticket->response_due_at);
    }

    public function test_priority_refresh_updates_calendar_snapshot(): void
    {
        $mediumCalendar = $this->calendar(name: 'Medium Calendar', open: '08:00', close: '17:00');
        $highCalendar = $this->calendar(name: 'High Calendar', open: '10:00', close: '18:00');
        $this->activePolicy('medium', 60, 120, $mediumCalendar);
        $highPolicy = $this->activePolicy('high', 60, 120, $highCalendar);
        $ticket = $this->createTicketAt('2026-07-27 09:00:00', 'medium', 'Priority calendar refresh ticket');

        app(TicketWorkflowService::class)->update($ticket->fresh(), [
            'priority' => 'high',
            'status' => 'open',
        ]);

        $ticket->refresh();

        $this->assertSame($highPolicy->id, $ticket->sla_policy_id);
        $this->assertSame($highCalendar->id, $ticket->sla_business_calendar_id);
        $this->assertDueAt('2026-07-27 11:00:00', $ticket->response_due_at);
    }

    public function test_legacy_policy_without_calendar_falls_back_to_default_calendar(): void
    {
        $defaultCalendar = $this->calendar(name: 'Default Fallback Calendar', isDefault: true, open: '08:00', close: '17:00');
        $this->activePolicy('medium', 60, 120, null);

        $ticket = $this->createTicketAt('2026-07-27 17:30:00', 'medium', 'Default fallback calendar ticket');

        $this->assertSame($defaultCalendar->id, $ticket->sla_business_calendar_id);
        $this->assertDueAt('2026-07-28 09:00:00', $ticket->response_due_at);
    }

    public function test_legacy_policy_without_any_calendar_falls_back_to_calendar_minutes(): void
    {
        $this->activePolicy('medium', 60, 120, null);

        $ticket = $this->createTicketAt('2026-07-27 17:30:00', 'medium', 'Legacy minute fallback ticket');

        $this->assertNull($ticket->sla_business_calendar_id);
        $this->assertDueAt('2026-07-27 11:30:00', $ticket->response_due_at);
    }

    public function test_manual_ticket_flow_uses_policy_business_calendar(): void
    {
        $calendar = $this->calendar(name: 'Manual Flow Calendar', open: '10:00', close: '18:00');
        $this->activePolicy('medium', 60, 120, $calendar);

        $ticket = $this->createTicketAt('2026-07-27 09:00:00', 'medium', 'Manual calendar flow ticket');

        $this->assertSame($calendar->id, $ticket->sla_business_calendar_id);
        $this->assertDueAt('2026-07-27 11:00:00', $ticket->response_due_at);
    }

    public function test_whatsapp_ticket_flow_uses_policy_business_calendar(): void
    {
        $calendar = $this->calendar(name: 'WhatsApp Flow Calendar', open: '10:00', close: '18:00');
        $policy = $this->activePolicy('medium', 60, 120, $calendar);
        Carbon::setTestNow(Carbon::parse('2026-07-27 09:00:00', 'Asia/Jakarta')->setTimezone(config('app.timezone')));
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Calendar Support',
            'phone_number' => '6281211100000',
            'channel' => 'whatsapp',
            'last_message' => 'Need calendar support',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6281211100000',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Need calendar support',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);

        $this->post(route('admin.marketing.whatsapp-replies.messages.create-ticket', $message), [
            '_token' => 'test-token',
        ])->assertRedirect(route('admin.marketing.whatsapp-replies.index'));

        $ticket = Ticket::query()->where('whatsapp_message_id', $message->id)->firstOrFail();

        $this->assertSame($policy->id, $ticket->sla_policy_id);
        $this->assertSame($calendar->id, $ticket->sla_business_calendar_id);
        $this->assertDueAt('2026-07-27 11:00:00', $ticket->response_due_at);
    }

    public function test_backfill_assigns_default_calendar_when_one_exists(): void
    {
        $calendar = $this->calendar(name: 'Backfill Default Calendar', isDefault: true);
        $policy = SlaPolicy::factory()->create(['business_calendar_id' => null]);

        SlaPolicy::query()
            ->whereNull('business_calendar_id')
            ->update(['business_calendar_id' => $calendar->id]);

        $this->assertSame($calendar->id, $policy->fresh()->business_calendar_id);
    }

    public function test_no_default_calendar_leaves_legacy_record_null_safely(): void
    {
        $policy = SlaPolicy::factory()->create(['business_calendar_id' => null]);

        $this->assertNull(BusinessCalendar::query()->defaultCalendar()->value('id'));
        $this->assertNull($policy->fresh()->business_calendar_id);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function policyPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Business Calendar SLA',
            'description' => 'SLA policy with an explicit business calendar.',
            'business_calendar_id' => $this->calendar()->id,
            'priority' => 'medium',
            'response_time_minutes' => 60,
            'resolution_time_minutes' => 120,
            'is_active' => 1,
        ], $overrides);
    }

    protected function createTicketAt(string $now, string $priority, string $subject): Ticket
    {
        Carbon::setTestNow(Carbon::parse($now, 'Asia/Jakarta')->setTimezone(config('app.timezone')));

        $customer = Customer::factory()->create();

        $this->post(route('admin.service.tickets.store'), [
            'customer_id' => $customer->id,
            'subject' => $subject,
            'description' => 'Ticket for policy calendar integration.',
            'priority' => $priority,
            'status' => 'open',
            'channel' => 'email',
        ])->assertRedirect();

        return Ticket::query()->where('subject', $subject)->firstOrFail();
    }

    protected function activePolicy(string $priority, int $responseMinutes, int $resolutionMinutes, ?BusinessCalendar $calendar): SlaPolicy
    {
        return SlaPolicy::factory()->create([
            'priority' => $priority,
            'response_time_minutes' => $responseMinutes,
            'resolution_time_minutes' => $resolutionMinutes,
            'business_calendar_id' => $calendar?->id,
            'is_active' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function calendar(
        array $overrides = [],
        string $name = 'Policy Calendar',
        bool $isDefault = false,
        string $open = '08:00',
        string $close = '17:00',
    ): BusinessCalendar {
        return app(BusinessCalendarService::class)->create(
            array_merge([
                'name' => $name.' '.uniqid(),
                'description' => 'Calendar for SLA policy tests.',
                'timezone' => 'Asia/Jakarta',
                'is_active' => true,
                'is_default' => $isDefault,
            ], $overrides),
            $this->workingHoursPayload($open, $close),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function workingHoursPayload(string $open, string $close): array
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

    protected function assertDueAt(string $expected, $actual): void
    {
        $this->assertSame($expected, $actual->format('Y-m-d H:i:s'));
    }
}
