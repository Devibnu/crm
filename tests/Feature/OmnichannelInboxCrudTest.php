<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OmnichannelMessage;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OmnichannelInboxCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_omnichannel_inbox_index_is_accessible(): void
    {
        $this->get(route('admin.service.omnichannel.index'))
            ->assertOk()
            ->assertSee('Omnichannel Inbox')
            ->assertSee('Inbox percakapan WhatsApp real dari webhook Meta Cloud API.')
            ->assertSee('data-omni-profile-tab="contact"', false)
            ->assertSee('data-omni-profile-tab="crm"', false)
            ->assertSee("profileTabStorageKey = 'krakatau.omnichannel.profileTab'", false)
            ->assertSee('window.location.hash.slice(1)', false)
            ->assertSee('data-poll-url=', false)
            ->assertSee('pollOmnichannel', false)
            ->assertDontSee('window.location.reload', false);
    }

    public function test_omnichannel_message_can_be_created(): void
    {
        $customer = Customer::factory()->create(['name' => 'Omni Customer']);

        $response = $this->post(route('admin.service.omnichannel.store'), [
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'sender_name' => 'Omni Sender',
            'sender_contact' => '+628123456789',
            'subject' => 'Need delivery update',
            'message' => 'Can you check my latest delivery status?',
            'status' => 'unread',
            'assigned_to' => 'Support Agent',
            'received_at' => '2026-05-20T09:00',
            'resolved_at' => null,
        ]);

        $message = OmnichannelMessage::query()->where('subject', 'Need delivery update')->firstOrFail();

        $response->assertRedirect(route('admin.service.omnichannel.show', $message));

        $this->assertDatabaseHas('omnichannel_messages', [
            'id' => $message->id,
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'status' => 'unread',
            'assigned_to' => 'Support Agent',
        ]);
    }

    public function test_omnichannel_show_and_edit_pages_are_accessible(): void
    {
        $message = OmnichannelMessage::factory()->create([
            'subject' => 'Accessible Omni Message',
        ]);

        $this->get(route('admin.service.omnichannel.show', $message))
            ->assertOk()
            ->assertSee('Accessible Omni Message');

        $this->get(route('admin.service.omnichannel.edit', $message))
            ->assertOk()
            ->assertSee('Edit Omnichannel Message');
    }

    public function test_omnichannel_message_can_be_updated(): void
    {
        $message = OmnichannelMessage::factory()->create([
            'channel' => 'email',
            'direction' => 'inbound',
            'status' => 'pending',
        ]);

        $response = $this->put(route('admin.service.omnichannel.update', $message), [
            'customer_id' => null,
            'channel' => 'instagram',
            'direction' => 'outbound',
            'sender_name' => 'Updated Omni Sender',
            'sender_contact' => '@updatedsender',
            'subject' => 'Updated omnichannel subject',
            'message' => 'Updated message body for omnichannel inbox.',
            'status' => 'resolved',
            'assigned_to' => 'Updated Agent',
            'received_at' => '2026-05-20T10:00',
            'resolved_at' => '2026-05-20T11:00',
        ]);

        $response->assertRedirect(route('admin.service.omnichannel.show', $message));

        $this->assertDatabaseHas('omnichannel_messages', [
            'id' => $message->id,
            'channel' => 'instagram',
            'direction' => 'outbound',
            'status' => 'resolved',
            'assigned_to' => 'Updated Agent',
        ]);
    }

    public function test_omnichannel_message_can_be_deleted(): void
    {
        $message = OmnichannelMessage::factory()->create();

        $response = $this->delete(route('admin.service.omnichannel.destroy', $message));

        $response->assertRedirect(route('admin.service.omnichannel.index'));

        $this->assertDatabaseMissing('omnichannel_messages', [
            'id' => $message->id,
        ]);
    }

    public function test_omnichannel_search_works(): void
    {
        $match = WhatsAppConversation::query()->create([
            'contact_name' => 'Unique Omni Search Sender',
            'phone_number' => '+628111111111',
            'channel' => 'whatsapp',
            'last_message' => 'Searchable WhatsApp conversation',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $match->id,
            'phone' => '+628111111111',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Searchable WhatsApp conversation',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $other = WhatsAppConversation::query()->create([
            'contact_name' => 'Different Sender',
            'phone_number' => '+628222222222',
            'channel' => 'whatsapp',
            'last_message' => 'Different WhatsApp conversation',
            'last_message_at' => now()->subMinute(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $other->id,
            'phone' => '+628222222222',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Different WhatsApp conversation',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->get(route('admin.service.omnichannel.index', ['q' => 'Unique Omni Search']))
            ->assertOk()
            ->assertSee($match->contact_name)
            ->assertDontSee($other->contact_name);
    }

    public function test_omnichannel_index_uses_whatsapp_conversations_not_legacy_messages(): void
    {
        $conversation = WhatsAppConversation::query()->create([
            'contact_name' => 'Real WhatsApp Sender',
            'phone_number' => '+628333333333',
            'channel' => 'whatsapp',
            'last_message' => 'Real database conversation',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '+628333333333',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Real database conversation',
            'provider' => 'fonnte',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $legacy = OmnichannelMessage::factory()->create([
            'sender_name' => 'Legacy Channel Sender',
            'channel' => 'email',
        ]);

        $this->get(route('admin.service.omnichannel.index'))
            ->assertOk()
            ->assertSee($conversation->contact_name)
            ->assertDontSee($legacy->sender_name);
    }

    public function test_omnichannel_status_filter_works(): void
    {
        $open = WhatsAppConversation::query()->create([
            'contact_name' => 'Open Status Sender',
            'phone_number' => '+628444444444',
            'channel' => 'whatsapp',
            'last_message' => 'Open conversation',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $open->id,
            'phone' => '+628444444444',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Open conversation',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);
        $pending = WhatsAppConversation::query()->create([
            'contact_name' => 'Pending Status Sender',
            'phone_number' => '+628555555555',
            'channel' => 'whatsapp',
            'last_message' => 'Pending conversation',
            'last_message_at' => now()->subMinute(),
            'status' => 'pending',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $pending->id,
            'phone' => '+628555555555',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Pending conversation',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->get(route('admin.service.omnichannel.index', ['status' => 'open']))
            ->assertOk()
            ->assertSee($open->contact_name)
            ->assertDontSee($pending->contact_name);
    }

    public function test_omnichannel_poll_endpoint_returns_conversations_messages_and_workspace(): void
    {
        $conversation = WhatsAppConversation::query()->create([
            'contact_name' => 'Polling Contact',
            'phone_number' => '+628777777777',
            'channel' => 'whatsapp',
            'last_message' => 'Latest polling message',
            'last_message_at' => now(),
            'status' => 'open',
            'unread_count' => 2,
        ]);

        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '+628777777777',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Latest polling message',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now(),
        ]);

        $this->getJson(route('admin.service.omnichannel.poll', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertJsonPath('data.selected_conversation_id', $conversation->id)
            ->assertJsonPath('data.selected_conversation.name', 'Polling Contact')
            ->assertJsonPath('data.conversations.0.name', 'Polling Contact')
            ->assertJsonPath('data.messages.0.message', 'Latest polling message')
            ->assertJsonPath('data.workspace.contact.name', 'Polling Contact')
            ->assertJsonPath(
                'data.workspace.contact.lead_create_url',
                route('admin.sales.leads.create', ['conversation_id' => $conversation->id]),
            )
            ->assertJsonPath(
                'data.workspace.contact.ticket_create_url',
                route('admin.service.tickets.create', ['conversation_id' => $conversation->id]),
            );

        $this->assertDatabaseHas('whatsapp_conversations', [
            'id' => $conversation->id,
            'unread_count' => 0,
        ]);
    }
}
