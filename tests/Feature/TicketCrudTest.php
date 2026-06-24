<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                'status' => 'resolved',
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
            'status' => 'resolved',
            'channel' => 'whatsapp',
            'assigned_to' => 'Updated Support Agent',
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
