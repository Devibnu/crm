<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\Tickets\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_ticket_index_is_accessible(): void
    {
        $this->get(route('admin.service.tickets.index'))
            ->assertOk()
            ->assertSee('Ticket Management')
            ->assertSee('Kelola tiket layanan pelanggan dari berbagai channel.');
    }

    public function test_ticket_create_is_accessible(): void
    {
        $this->get(route('admin.service.tickets.create'))
            ->assertOk()
            ->assertSee('Add Ticket');
    }

    public function test_ticket_create_prefills_from_whatsapp_conversation(): void
    {
        $customer = Customer::factory()->create(['name' => 'Jasaibnu']);
        $conversation = WhatsAppConversation::create([
            'customer_id' => $customer->id,
            'contact_name' => 'Jasaibnu',
            'phone_number' => '6285156638712',
            'channel' => 'whatsapp',
            'last_message' => 'Saya butuh bantuan order.',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $response = $this->get(route('admin.service.tickets.create', [
            'conversation_id' => $conversation->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('Source Conversation')
            ->assertSee('value="'.$conversation->id.'"', false)
            ->assertSee('value="WhatsApp - Jasaibnu"', false)
            ->assertSee('Saya butuh bantuan order.')
            ->assertSee('value="'.$customer->id.'" selected', false)
            ->assertSee('value="medium" selected', false)
            ->assertSee('value="open" selected', false)
            ->assertSee('value="whatsapp" selected', false)
            ->assertSee('name="assigned_to"', false)
            ->assertSee(auth()->user()->name);
    }

    public function test_ticket_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->withSession(['_token' => 'test-token'])
            ->post(route('admin.service.tickets.store'), [
                '_token' => 'test-token',
                'customer_id' => $customer->id,
                'subject' => 'Cannot access customer portal',
                'description' => 'Customer reports login failure.',
                'priority' => 'high',
                'status' => 'open',
                'channel' => 'email',
                'assigned_to' => 'Support Agent',
                'due_at' => '2026-05-08T10:00',
                'resolved_at' => null,
                'closed_at' => null,
            ]);

        $ticket = Ticket::query()->where('subject', 'Cannot access customer portal')->firstOrFail();

        $response->assertRedirect(route('admin.service.tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'customer_id' => $customer->id,
            'subject' => 'Cannot access customer portal',
            'priority' => 'high',
            'status' => 'open',
            'channel' => 'email',
            'assigned_to' => 'Support Agent',
        ]);

        $this->assertStringStartsWith('TCK-', $ticket->ticket_number);
    }

    public function test_ticket_created_from_whatsapp_conversation_keeps_source_reference(): void
    {
        $customer = Customer::factory()->create(['name' => 'Source Customer']);
        $conversation = WhatsAppConversation::create([
            'customer_id' => $customer->id,
            'contact_name' => 'Source Customer',
            'phone_number' => '6285156638712',
            'channel' => 'whatsapp',
            'last_message' => 'Tolong dibuatkan tiket.',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $response = $this->withSession(['_token' => 'test-token'])
            ->post(route('admin.service.tickets.store'), [
                '_token' => 'test-token',
                'conversation_id' => $conversation->id,
                'customer_id' => $customer->id,
                'subject' => 'WhatsApp - Source Customer',
                'description' => 'Tolong dibuatkan tiket.',
                'priority' => 'medium',
                'status' => 'open',
                'channel' => 'whatsapp',
                'assigned_to' => auth()->user()->name,
            ]);

        $ticket = Ticket::query()->where('subject', 'WhatsApp - Source Customer')->firstOrFail();

        $response->assertRedirect(route('admin.service.tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'conversation_id' => $conversation->id,
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'priority' => 'medium',
            'status' => 'open',
        ]);

        $this->get(route('admin.service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Source Conversation')
            ->assertSee('Open Conversation')
            ->assertSee(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]).'#contact', false);
    }

    public function test_ticket_create_rejects_conversation_customer_mismatch(): void
    {
        $conversationCustomer = Customer::factory()->create();
        $submittedCustomer = Customer::factory()->create();
        $conversation = WhatsAppConversation::create([
            'customer_id' => $conversationCustomer->id,
            'contact_name' => 'Mismatch Customer',
            'phone_number' => '6285156638700',
            'channel' => 'whatsapp',
            'last_message' => 'Conversation belongs to another customer.',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $response = $this->withSession(['_token' => 'test-token'])
            ->from(route('admin.service.tickets.create', ['conversation_id' => $conversation->id]))
            ->post(route('admin.service.tickets.store'), [
                '_token' => 'test-token',
                'conversation_id' => $conversation->id,
                'customer_id' => $submittedCustomer->id,
                'subject' => 'Mismatched WhatsApp Ticket',
                'description' => 'This should not be persisted.',
                'priority' => 'medium',
                'status' => 'open',
                'channel' => 'whatsapp',
            ]);

        $response
            ->assertRedirect(route('admin.service.tickets.create', ['conversation_id' => $conversation->id]))
            ->assertSessionHasErrors('conversation_id');

        $this->assertDatabaseMissing('tickets', [
            'customer_id' => $submittedCustomer->id,
            'conversation_id' => $conversation->id,
            'subject' => 'Mismatched WhatsApp Ticket',
        ]);
    }

    public function test_ticket_show_is_accessible(): void
    {
        $ticket = Ticket::factory()->create([
            'subject' => 'Show Ticket Subject',
        ]);

        $this->get(route('admin.service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Ticket Detail')
            ->assertSee('Show Ticket Subject');
    }

    public function test_ticket_edit_is_accessible(): void
    {
        $ticket = Ticket::factory()->create();

        $this->get(route('admin.service.tickets.edit', $ticket))
            ->assertOk()
            ->assertSee('Edit Ticket')
            ->assertSee($ticket->ticket_number);
    }

    public function test_ticket_can_be_updated(): void
    {
        $ticket = Ticket::factory()->create([
            'subject' => 'Before Ticket Update',
            'status' => 'open',
        ]);

        $response = $this->withSession(['_token' => 'test-token'])
            ->put(route('admin.service.tickets.update', $ticket), [
                '_token' => 'test-token',
                'customer_id' => null,
                'subject' => 'After Ticket Update',
                'description' => 'Updated ticket description.',
                'priority' => 'urgent',
                'status' => 'in_progress',
                'channel' => 'whatsapp',
                'assigned_to' => 'Updated Support Agent',
                'due_at' => '2026-05-09T12:00',
                'resolved_at' => '2026-05-09T13:00',
                'closed_at' => null,
            ]);

        $response->assertRedirect(route('admin.service.tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'subject' => 'After Ticket Update',
            'priority' => 'urgent',
            'status' => 'in_progress',
            'channel' => 'whatsapp',
            'assigned_to' => 'Updated Support Agent',
        ]);

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
            'resolved_at' => '2026-05-09 13:00:00',
        ]);
    }

    public function test_ticket_valid_workflow_transitions_are_allowed(): void
    {
        $ticket = Ticket::factory()->create(['status' => 'open']);
        $service = app(TicketWorkflowService::class);

        $service->startProgress($ticket);
        $this->assertSame('in_progress', $ticket->fresh()->status);

        $service->waitingCustomer($ticket->fresh());
        $this->assertSame('waiting_customer', $ticket->fresh()->status);

        $service->resolve($ticket->fresh());
        $this->assertSame('resolved', $ticket->fresh()->status);

        $service->close($ticket->fresh());
        $this->assertSame('closed', $ticket->fresh()->status);

        $service->reopen($ticket->fresh());
        $this->assertSame('reopened', $ticket->fresh()->status);

        $service->startProgress($ticket->fresh());
        $this->assertSame('in_progress', $ticket->fresh()->status);
    }

    public function test_ticket_invalid_workflow_transition_is_rejected(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => 'open',
            'subject' => 'Invalid Jump Ticket',
        ]);

        $response = $this->withSession(['_token' => 'test-token'])
            ->from(route('admin.service.tickets.edit', $ticket))
            ->put(route('admin.service.tickets.update', $ticket), [
                '_token' => 'test-token',
                'customer_id' => $ticket->customer_id,
                'subject' => 'Invalid Jump Ticket Updated',
                'description' => 'Invalid transition should fail.',
                'priority' => $ticket->priority,
                'status' => 'closed',
                'channel' => $ticket->channel,
                'assigned_to' => $ticket->assigned_to,
            ]);

        $response
            ->assertRedirect(route('admin.service.tickets.edit', $ticket))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'open',
            'subject' => 'Invalid Jump Ticket',
        ]);
    }

    public function test_ticket_disallowed_workflow_jumps_are_rejected_by_service(): void
    {
        $service = app(TicketWorkflowService::class);
        $disallowedTransitions = [
            ['open', 'closed'],
            ['open', 'resolved'],
            ['resolved', 'open'],
            ['closed', 'in_progress'],
        ];

        foreach ($disallowedTransitions as [$from, $to]) {
            $ticket = Ticket::factory()->create(['status' => $from]);

            try {
                $service->transition($ticket, $to);
                $this->fail("Transition {$from} to {$to} should be rejected.");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('status', $exception->errors());
                $this->assertSame($from, $ticket->fresh()->status);
            }
        }
    }

    public function test_ticket_resolve_sets_resolved_at_automatically(): void
    {
        Carbon::setTestNow('2026-07-22 09:15:00');
        $ticket = Ticket::factory()->create([
            'status' => 'waiting_customer',
            'resolved_at' => null,
        ]);

        app(TicketWorkflowService::class)->resolve($ticket);

        $ticket->refresh();

        $this->assertSame('resolved', $ticket->status);
        $this->assertSame('2026-07-22 09:15:00', $ticket->resolved_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_ticket_close_sets_closed_at_and_preserves_resolved_at(): void
    {
        Carbon::setTestNow('2026-07-22 11:30:00');
        $resolvedAt = Carbon::parse('2026-07-22 10:00:00');
        $ticket = Ticket::factory()->create([
            'status' => 'resolved',
            'resolved_at' => $resolvedAt,
            'closed_at' => null,
        ]);

        app(TicketWorkflowService::class)->close($ticket);

        $ticket->refresh();

        $this->assertSame('closed', $ticket->status);
        $this->assertSame('2026-07-22 10:00:00', $ticket->resolved_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-22 11:30:00', $ticket->closed_at->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_ticket_reopen_preserves_resolution_and_close_timestamps(): void
    {
        $resolvedAt = Carbon::parse('2026-07-22 10:00:00');
        $closedAt = Carbon::parse('2026-07-22 11:00:00');
        $ticket = Ticket::factory()->create([
            'status' => 'closed',
            'resolved_at' => $resolvedAt,
            'closed_at' => $closedAt,
        ]);

        app(TicketWorkflowService::class)->reopen($ticket);

        $ticket->refresh();

        $this->assertSame('reopened', $ticket->status);
        $this->assertSame('2026-07-22 10:00:00', $ticket->resolved_at->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-22 11:00:00', $ticket->closed_at->format('Y-m-d H:i:s'));
    }

    public function test_ticket_assignment_action_preserves_status(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => 'in_progress',
            'assigned_to' => null,
        ]);

        app(TicketWorkflowService::class)->assign($ticket, 'Support Lead');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
            'assigned_to' => 'Support Lead',
        ]);
    }

    public function test_ticket_can_be_deleted(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->withSession(['_token' => 'test-token'])
            ->delete(route('admin.service.tickets.destroy', $ticket), [
                '_token' => 'test-token',
            ]);

        $response->assertRedirect(route('admin.service.tickets.index'));

        $this->assertDatabaseMissing('tickets', [
            'id' => $ticket->id,
        ]);
    }

    public function test_ticket_actions_are_hidden_without_create_update_delete_permissions(): void
    {
        $ticket = Ticket::factory()->create([
            'subject' => 'Permission Hidden Ticket',
        ]);
        $role = Role::create(['name' => 'ticket_viewer_only', 'guard_name' => 'web']);
        $role->syncPermissions(['tickets.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.service.tickets.index'))
            ->assertOk()
            ->assertSee('Permission Hidden Ticket')
            ->assertDontSee('Add Ticket')
            ->assertDontSee(route('admin.service.tickets.create'), false)
            ->assertDontSee(route('admin.service.tickets.edit', $ticket), false)
            ->assertDontSee('method="POST" action="'.route('admin.service.tickets.destroy', $ticket).'"', false)
            ->assertDontSee('name="_method" value="DELETE"', false)
            ->assertDontSee('Delete');

        $this->actingAs($user)
            ->get(route('admin.service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Permission Hidden Ticket')
            ->assertDontSee(route('admin.service.tickets.edit', $ticket), false)
            ->assertDontSee('method="POST" action="'.route('admin.service.tickets.destroy', $ticket).'"', false)
            ->assertDontSee('name="_method" value="DELETE"', false)
            ->assertDontSee('Delete');
    }

    public function test_unauthorized_user_cannot_create_ticket(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.service.tickets.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.service.tickets.store'), [
                'subject' => 'Unauthorized Ticket',
                'priority' => 'medium',
                'status' => 'open',
                'channel' => 'web',
            ])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_edit_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.service.tickets.edit', $ticket))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.service.tickets.update', $ticket), [
                'subject' => 'Unauthorized Update',
                'priority' => 'medium',
                'status' => 'open',
                'channel' => 'web',
            ])
            ->assertForbidden();
    }

    public function test_unauthorized_user_cannot_delete_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('admin.service.tickets.destroy', $ticket))
            ->assertForbidden();
    }

    public function test_ticket_search_works(): void
    {
        $customer = Customer::factory()->create(['name' => 'Searchable Customer']);
        $match = Ticket::factory()->create([
            'customer_id' => $customer->id,
            'ticket_number' => 'TCK-SEARCH-001',
            'subject' => 'Searchable Ticket Subject',
            'assigned_to' => 'Searchable Agent',
        ]);
        $other = Ticket::factory()->create([
            'customer_id' => null,
            'ticket_number' => 'TCK-OTHER-001',
            'subject' => 'Other Ticket Subject',
            'assigned_to' => 'Other Agent',
        ]);

        $this->get(route('admin.service.tickets.index', ['q' => 'TCK-SEARCH-001']))
            ->assertOk()
            ->assertSee($match->ticket_number)
            ->assertDontSee($other->ticket_number);

        $this->get(route('admin.service.tickets.index', ['q' => 'Searchable Customer']))
            ->assertOk()
            ->assertSee($match->ticket_number)
            ->assertDontSee($other->ticket_number);
    }

    public function test_ticket_status_filter_works(): void
    {
        $open = Ticket::factory()->create(['ticket_number' => 'TCK-OPEN-001', 'status' => 'open']);
        $closed = Ticket::factory()->create(['ticket_number' => 'TCK-CLOSED-001', 'status' => 'closed']);

        $this->get(route('admin.service.tickets.index', ['status' => 'open']))
            ->assertOk()
            ->assertSee($open->ticket_number)
            ->assertDontSee($closed->ticket_number);
    }

    public function test_ticket_priority_filter_works(): void
    {
        $urgent = Ticket::factory()->create(['ticket_number' => 'TCK-URGENT-001', 'priority' => 'urgent']);
        $low = Ticket::factory()->create(['ticket_number' => 'TCK-LOW-001', 'priority' => 'low']);

        $this->get(route('admin.service.tickets.index', ['priority' => 'urgent']))
            ->assertOk()
            ->assertSee($urgent->ticket_number)
            ->assertDontSee($low->ticket_number);
    }

    public function test_ticket_channel_filter_works(): void
    {
        $email = Ticket::factory()->create(['ticket_number' => 'TCK-EMAIL-001', 'channel' => 'email']);
        $web = Ticket::factory()->create(['ticket_number' => 'TCK-WEB-001', 'channel' => 'web']);

        $this->get(route('admin.service.tickets.index', ['channel' => 'email']))
            ->assertOk()
            ->assertSee($email->ticket_number)
            ->assertDontSee($web->ticket_number);
    }
}
