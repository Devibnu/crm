<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\Sla\TicketSlaService;
use App\Services\Tickets\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TicketSlaEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_sla_is_applied_when_ticket_is_created(): void
    {
        Carbon::setTestNow('2026-07-23 09:00:00');
        $customer = Customer::factory()->create();
        $policy = $this->activePolicy('high', 30, 240);

        $response = $this->post(route('admin.service.tickets.store'), [
            'customer_id' => $customer->id,
            'subject' => 'High priority SLA ticket',
            'description' => 'Customer needs urgent help.',
            'priority' => 'high',
            'status' => 'open',
            'channel' => 'email',
        ]);

        $ticket = Ticket::query()->where('subject', 'High priority SLA ticket')->firstOrFail();

        $response->assertRedirect(route('admin.service.tickets.show', $ticket));
        $this->assertSame($policy->id, $ticket->sla_policy_id);
        $this->assertSame(30, $ticket->sla_response_time_minutes);
        $this->assertSame(240, $ticket->sla_resolution_time_minutes);
        $this->assertSame('2026-07-23 09:30:00', $ticket->response_due_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-23 13:00:00', $ticket->resolution_due_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_ticket_can_be_created_without_sla_policy(): void
    {
        $customer = Customer::factory()->create();

        $this->post(route('admin.service.tickets.store'), [
            'customer_id' => $customer->id,
            'subject' => 'No SLA policy ticket',
            'priority' => 'low',
            'status' => 'open',
            'channel' => 'web',
        ])->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'No SLA policy ticket')->firstOrFail();

        $this->assertNull($ticket->sla_policy_id);
        $this->assertNull($ticket->response_due_at);
        $this->assertSame('no_policy', $ticket->responseSlaStatus());
    }

    public function test_inactive_sla_policy_is_not_applied(): void
    {
        $customer = Customer::factory()->create();
        SlaPolicy::factory()->create([
            'priority' => 'urgent',
            'response_time_minutes' => 15,
            'resolution_time_minutes' => 120,
            'is_active' => false,
        ]);

        $this->post(route('admin.service.tickets.store'), [
            'customer_id' => $customer->id,
            'subject' => 'Inactive SLA policy ticket',
            'priority' => 'urgent',
            'status' => 'open',
            'channel' => 'phone',
        ])->assertRedirect();

        $ticket = Ticket::query()->where('subject', 'Inactive SLA policy ticket')->firstOrFail();

        $this->assertNull($ticket->sla_policy_id);
        $this->assertNull($ticket->sla_response_time_minutes);
        $this->assertNull($ticket->sla_resolution_time_minutes);
    }

    public function test_ticket_sla_snapshot_does_not_change_when_policy_is_edited(): void
    {
        Carbon::setTestNow('2026-07-23 08:00:00');
        $policy = $this->activePolicy('medium', 60, 480);
        $ticket = Ticket::factory()->create(['priority' => 'medium', 'status' => 'open']);

        app(TicketSlaService::class)->apply($ticket);
        $policy->update([
            'response_time_minutes' => 90,
            'resolution_time_minutes' => 720,
        ]);

        $ticket->refresh();

        $this->assertSame(60, $ticket->sla_response_time_minutes);
        $this->assertSame(480, $ticket->sla_resolution_time_minutes);
        $this->assertSame('2026-07-23 09:00:00', $ticket->response_due_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-23 16:00:00', $ticket->resolution_due_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_priority_change_refreshes_sla_snapshot(): void
    {
        Carbon::setTestNow('2026-07-23 10:00:00');
        $this->activePolicy('medium', 120, 1440);
        $highPolicy = $this->activePolicy('high', 30, 240);
        $ticket = Ticket::factory()->create(['priority' => 'medium', 'status' => 'open']);

        app(TicketSlaService::class)->apply($ticket);
        app(TicketWorkflowService::class)->update($ticket->fresh(), ['priority' => 'high', 'status' => 'open']);

        $ticket->refresh();

        $this->assertSame($highPolicy->id, $ticket->sla_policy_id);
        $this->assertSame(30, $ticket->sla_response_time_minutes);
        $this->assertSame(240, $ticket->sla_resolution_time_minutes);
        $this->assertSame('2026-07-23 10:30:00', $ticket->response_due_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_same_priority_update_does_not_recalculate_sla(): void
    {
        Carbon::setTestNow('2026-07-23 10:00:00');
        $this->activePolicy('medium', 120, 1440);
        $ticket = Ticket::factory()->create(['priority' => 'medium', 'status' => 'open']);

        app(TicketSlaService::class)->apply($ticket);
        $originalResponseDueAt = $ticket->fresh()->response_due_at->copy();

        Carbon::setTestNow('2026-07-23 12:00:00');
        app(TicketWorkflowService::class)->update($ticket->fresh(), [
            'subject' => 'Updated without priority change',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->assertTrue($originalResponseDueAt->equalTo($ticket->fresh()->response_due_at));

        Carbon::setTestNow();
    }

    public function test_first_response_on_time_is_recorded_once(): void
    {
        Carbon::setTestNow('2026-07-23 09:00:00');
        $this->activePolicy('medium', 60, 240);
        $ticket = Ticket::factory()->create(['priority' => 'medium', 'status' => 'open']);
        app(TicketSlaService::class)->apply($ticket);

        app(TicketSlaService::class)->markFirstResponse($ticket->fresh(), Carbon::parse('2026-07-23 09:30:00'));
        app(TicketSlaService::class)->markFirstResponse($ticket->fresh(), Carbon::parse('2026-07-23 09:45:00'));

        $ticket->refresh();

        $this->assertSame('2026-07-23 09:30:00', $ticket->first_responded_at->format('Y-m-d H:i:s'));
        $this->assertNull($ticket->sla_response_breached_at);
        $this->assertSame('on_time', $ticket->responseSlaStatus());

        Carbon::setTestNow();
    }

    public function test_first_response_breach_is_recorded(): void
    {
        Carbon::setTestNow('2026-07-23 09:00:00');
        $this->activePolicy('high', 30, 240);
        $ticket = Ticket::factory()->create(['priority' => 'high', 'status' => 'open']);
        app(TicketSlaService::class)->apply($ticket);

        app(TicketSlaService::class)->markFirstResponse($ticket->fresh(), Carbon::parse('2026-07-23 09:45:00'));

        $ticket->refresh();

        $this->assertSame('2026-07-23 09:45:00', $ticket->sla_response_breached_at->format('Y-m-d H:i:s'));
        $this->assertSame('breached', $ticket->responseSlaStatus());

        Carbon::setTestNow();
    }

    public function test_resolution_on_time_marks_sla_completed(): void
    {
        Carbon::setTestNow('2026-07-23 09:00:00');
        $this->activePolicy('medium', 60, 180);
        $ticket = Ticket::factory()->create([
            'priority' => 'medium',
            'status' => 'waiting_customer',
            'resolved_at' => null,
        ]);
        app(TicketSlaService::class)->apply($ticket);
        app(TicketSlaService::class)->markFirstResponse($ticket->fresh(), Carbon::parse('2026-07-23 09:30:00'));

        Carbon::setTestNow('2026-07-23 11:00:00');
        app(TicketWorkflowService::class)->resolve($ticket->fresh());

        $ticket->refresh();

        $this->assertSame('resolved', $ticket->status);
        $this->assertNull($ticket->sla_resolution_breached_at);
        $this->assertSame('completed', $ticket->resolutionSlaStatus());
        $this->assertSame('completed', $ticket->overallSlaStatus());

        Carbon::setTestNow();
    }

    public function test_resolution_breach_is_recorded(): void
    {
        Carbon::setTestNow('2026-07-23 09:00:00');
        $this->activePolicy('high', 30, 60);
        $ticket = Ticket::factory()->create([
            'priority' => 'high',
            'status' => 'waiting_customer',
            'resolved_at' => null,
        ]);
        app(TicketSlaService::class)->apply($ticket);

        Carbon::setTestNow('2026-07-23 10:30:00');
        app(TicketWorkflowService::class)->resolve($ticket->fresh());

        $ticket->refresh();

        $this->assertSame('2026-07-23 10:30:00', $ticket->sla_resolution_breached_at->format('Y-m-d H:i:s'));
        $this->assertSame('breached', $ticket->resolutionSlaStatus());
        $this->assertSame('breached', $ticket->overallSlaStatus());

        Carbon::setTestNow();
    }

    public function test_reopen_preserves_sla_history(): void
    {
        $breachedAt = Carbon::parse('2026-07-23 10:30:00');
        $ticket = Ticket::factory()->create([
            'status' => 'closed',
            'sla_resolution_breached_at' => $breachedAt,
            'resolved_at' => $breachedAt,
            'closed_at' => Carbon::parse('2026-07-23 11:00:00'),
        ]);

        app(TicketWorkflowService::class)->reopen($ticket);

        $ticket->refresh();

        $this->assertSame('reopened', $ticket->status);
        $this->assertSame('2026-07-23 10:30:00', $ticket->sla_resolution_breached_at->format('Y-m-d H:i:s'));
    }

    public function test_whatsapp_ticket_uses_sla_engine(): void
    {
        Carbon::setTestNow('2026-07-23 09:00:00');
        $policy = $this->activePolicy('medium', 45, 360);
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Support Omnichannel',
            'phone_number' => '6281211100000',
            'channel' => 'whatsapp',
            'last_message' => 'Ada masalah invoice',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6281211100000',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Ada masalah invoice',
            'status' => 'delivered',
            'provider' => 'meta',
            'received_at' => now(),
        ]);

        $this->post(route('admin.marketing.whatsapp-replies.messages.create-ticket', $message), [
            '_token' => 'test-token',
        ])->assertRedirect(route('admin.marketing.whatsapp-replies.index'));

        $ticket = Ticket::query()->where('whatsapp_message_id', $message->id)->firstOrFail();

        $this->assertSame($policy->id, $ticket->sla_policy_id);
        $this->assertSame(45, $ticket->sla_response_time_minutes);
        $this->assertSame(360, $ticket->sla_resolution_time_minutes);
        $this->assertSame('2026-07-23 09:45:00', $ticket->response_due_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
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
}
