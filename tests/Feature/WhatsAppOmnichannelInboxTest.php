<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppOmnichannelInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_inbound_creates_conversation_customer_and_message(): void
    {
        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081234560001',
            'name' => 'Customer WhatsApp',
            'message' => 'Halo admin',
            'id' => 'fonnte-in-1',
        ])->assertOk();

        $customer = Customer::query()->where('whatsapp', '6281234560001')->firstOrFail();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'customer_id' => $customer->id,
            'phone_number' => '6281234560001',
            'channel' => 'whatsapp',
            'status' => 'open',
            'last_message' => 'Halo admin',
            'unread_count' => 1,
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'customer_id' => $customer->id,
            'phone' => '6281234560001',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Halo admin',
            'provider_message_id' => 'fonnte-in-1',
            'provider' => 'fonnte',
            'status' => 'delivered',
        ]);
    }

    public function test_webhook_inbound_appends_existing_conversation_without_duplicate(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Existing Customer',
            'phone' => '628111222333',
            'whatsapp' => '628111222333',
        ]);
        WhatsAppConversation::create([
            'customer_id' => $customer->id,
            'contact_name' => $customer->name,
            'phone_number' => '628111222333',
            'channel' => 'whatsapp',
            'last_message' => 'Old',
            'last_message_at' => now()->subHour(),
            'unread_count' => 2,
            'status' => 'open',
        ]);

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '+628111222333',
            'name' => 'Existing Customer',
            'message' => 'Pesan baru',
        ])->assertOk();

        $this->assertDatabaseCount('whatsapp_conversations', 1);
        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone_number' => '628111222333',
            'last_message' => 'Pesan baru',
            'unread_count' => 3,
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '628111222333',
            'direction' => 'inbound',
            'message' => 'Pesan baru',
        ]);
    }

    public function test_admin_outbound_reply_is_sent_and_saved(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.fonnte.test/send' => Http::response([
                'status' => true,
                'id' => 'out-1',
            ]),
        ]);
        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://api.fonnte.test',
            'api_token' => 'dummy-token',
        ]);
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Reply Customer',
            'phone_number' => '628111222333',
            'channel' => 'whatsapp',
            'last_message' => 'Inbound',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        $this->post(route('admin.service.omnichannel.reply', $conversation), [
            'message' => 'Baik, kami bantu cek.',
        ])->assertRedirect(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]));

        $this->assertDatabaseHas('whatsapp_messages', [
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '628111222333',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Baik, kami bantu cek.',
            'provider_message_id' => 'out-1',
            'provider' => 'fonnte',
            'status' => 'sent',
        ]);
        $this->assertSame('Baik, kami bantu cek.', $conversation->fresh()->last_message);
    }

    public function test_broadcast_reply_is_linked_to_conversation_message(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '628555111222',
            'whatsapp' => '628555111222',
        ]);
        $broadcast = WhatsAppBroadcast::factory()->create(['status' => 'completed']);
        WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_type' => 'customer',
            'recipient_id' => $customer->id,
            'phone_number' => '+62 855-5111-222',
            'status' => 'sent',
        ]);

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '08555111222',
            'name' => $customer->name,
            'message' => 'Saya balas broadcast',
        ])->assertOk();

        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '628555111222',
            'broadcast_id' => $broadcast->id,
            'direction' => 'inbound',
            'message' => 'Saya balas broadcast',
        ]);
        $this->assertSame('completed', $broadcast->fresh()->status);
    }
}
