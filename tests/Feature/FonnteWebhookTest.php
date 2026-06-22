<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FonnteWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'webhook_secret' => 'secret-token',
            'status' => 'inactive',
            'is_default' => false,
        ]);
        $this->withHeader('X-Webhook-Secret', 'secret-token');
    }

    public function test_webhook_inbound_creates_broadcast_reply_and_omnichannel_message(): void
    {
        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'webhook_secret' => 'secret-token',
        ]);

        $customer = Customer::factory()->create([
            'phone' => '081234567890',
            'whatsapp' => null,
        ]);

        $broadcast = WhatsAppBroadcast::factory()->create();
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_type' => 'customer',
            'recipient_id' => $customer->id,
            'recipient_name' => 'Customer Test',
            'phone_number' => '+62 812-3456-7890',
            'status' => 'sent',
        ]);

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081234567890',
            'name' => 'Customer Test',
            'message' => 'Saya mau follow up',
            'timestamp' => '2026-05-21 10:00:00',
            'webhook_secret' => 'secret-token',
        ])->assertOk();

        $this->assertDatabaseHas('whatsapp_broadcast_replies', [
            'whatsapp_broadcast_id' => $broadcast->id,
            'whatsapp_broadcast_recipient_id' => $recipient->id,
            'sender_name' => 'Customer Test',
            'phone_number' => '6281234567890',
            'message' => 'Saya mau follow up',
            'status' => 'unread',
        ]);

        $this->assertDatabaseHas('omnichannel_messages', [
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
            'direction' => 'inbound',
            'sender_name' => 'Customer Test',
            'sender_contact' => '6281234567890',
            'message' => 'Saya mau follow up',
            'status' => 'unread',
        ]);

        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'id' => $recipient->id,
            'status' => 'replied',
        ]);
    }

    public function test_webhook_reply_appears_in_whatsapp_reply_inbox(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create(['name' => 'Webhook Campaign']);
        WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'phone_number' => '628111222333',
        ]);

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '+628111222333',
            'name' => 'Inbox Sender',
            'message' => 'Balasan masuk inbox',
        ])->assertOk();

        $this->get(route('admin.marketing.whatsapp-replies.index'))
            ->assertOk()
            ->assertSee('WhatsApp Reply Inbox')
            ->assertSee('Inbox Sender')
            ->assertSee('Balasan masuk inbox')
            ->assertSee('Webhook Campaign');
    }

    public function test_webhook_normalizes_indonesian_phone_number_formats(): void
    {
        foreach (['+62 812 0000 0001', '6281200000001', '081200000001'] as $phone) {
            $this->postJson(route('webhooks.whatsapp.fonnte'), [
                'sender' => $phone,
                'message' => 'Normalize me',
            ])->assertOk();
        }

        $this->assertDatabaseCount('omnichannel_messages', 3);
        $this->assertDatabaseHas('omnichannel_messages', ['sender_contact' => '6281200000001']);
    }

    public function test_webhook_auto_creates_lead_for_unknown_number_without_customer(): void
    {
        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081299988877',
            'name' => 'Customer Baru',
            'message' => 'Nomor baru',
        ])->assertOk();

        $lead = Lead::query()->where('whatsapp', '6281299988877')->firstOrFail();

        $this->assertDatabaseMissing('customers', [
            'whatsapp' => '6281299988877',
        ]);

        $this->assertDatabaseHas('omnichannel_messages', [
            'customer_id' => null,
            'lead_id' => $lead->id,
            'sender_contact' => '6281299988877',
            'message' => 'Nomor baru',
        ]);
    }

    public function test_webhook_invalid_payload_returns_422(): void
    {
        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081299988877',
        ])->assertStatus(422);
    }

    public function test_webhook_rejects_invalid_secret_when_secret_is_sent(): void
    {
        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'webhook_secret' => 'expected-secret',
        ]);

        $this->withHeader('X-Webhook-Secret', 'wrong-secret')->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081299988877',
            'message' => 'Secret salah',
            'webhook_secret' => 'wrong-secret',
        ])->assertForbidden();
    }

    public function test_webhook_rejects_request_without_secret(): void
    {
        $this->withHeaders(['X-Webhook-Secret' => ''])->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081299988877',
            'message' => 'Secret kosong',
        ])->assertForbidden();
    }
}
