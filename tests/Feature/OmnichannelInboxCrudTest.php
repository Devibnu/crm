<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\OmnichannelMessage;
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
            ->assertSee('Centralized inbox untuk Email, WhatsApp, Chat, Social, Phone, dan Web.');
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
        $match = OmnichannelMessage::factory()->create([
            'sender_name' => 'Unique Omni Search Sender',
            'subject' => 'Searchable Omnichannel Subject',
        ]);
        $other = OmnichannelMessage::factory()->create([
            'sender_name' => 'Different Sender',
            'subject' => 'Different Omnichannel Subject',
        ]);

        $this->get(route('admin.service.omnichannel.index', ['q' => 'Unique Omni Search']))
            ->assertOk()
            ->assertSee($match->sender_name)
            ->assertDontSee($other->sender_name);
    }

    public function test_omnichannel_channel_filter_works(): void
    {
        $email = OmnichannelMessage::factory()->create(['sender_name' => 'Email Channel Sender', 'channel' => 'email']);
        $telegram = OmnichannelMessage::factory()->create(['sender_name' => 'Telegram Channel Sender', 'channel' => 'telegram']);

        $this->get(route('admin.service.omnichannel.index', ['channel' => 'email']))
            ->assertOk()
            ->assertSee($email->sender_name)
            ->assertDontSee($telegram->sender_name);
    }

    public function test_omnichannel_status_filter_works(): void
    {
        $unread = OmnichannelMessage::factory()->create(['sender_name' => 'Unread Status Sender', 'status' => 'unread']);
        $resolved = OmnichannelMessage::factory()->create(['sender_name' => 'Resolved Status Sender', 'status' => 'resolved']);

        $this->get(route('admin.service.omnichannel.index', ['status' => 'unread']))
            ->assertOk()
            ->assertSee($unread->sender_name)
            ->assertDontSee($resolved->sender_name);
    }
}
