<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class MetaWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_webhook_inbound_message_from_new_number_creates_conversation_and_message_without_lead(): void
    {
        $this->postMetaWebhook($this->metaInboundPayload(
            phone: '6289679349884',
            name: 'New Billing Sender',
            body: 'Bililing',
            messageId: 'wamid.new-billing-1',
        ))->assertOk();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone_number' => '6289679349884',
            'contact_name' => 'New Billing Sender',
            'channel' => 'whatsapp',
            'status' => 'open',
            'last_message' => 'Bililing',
            'lead_id' => null,
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '6289679349884',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Bililing',
            'provider_message_id' => 'wamid.new-billing-1',
            'provider' => 'meta',
            'status' => 'delivered',
            'lead_id' => null,
        ]);
        $this->assertDatabaseMissing('leads', [
            'whatsapp' => '6289679349884',
        ]);
    }

    public function test_meta_webhook_same_number_updates_existing_conversation(): void
    {
        $this->postMetaWebhook($this->metaInboundPayload(
            phone: '6289679349884',
            body: 'Billing',
            messageId: 'wamid.same-number-1',
        ))->assertOk();

        $this->postMetaWebhook($this->metaInboundPayload(
            phone: '6289679349884',
            body: 'Ok',
            messageId: 'wamid.same-number-2',
        ))->assertOk();

        $this->assertSame(1, WhatsAppConversation::query()->where('phone_number', '6289679349884')->count());
        $this->assertSame(2, WhatsAppMessage::query()->where('phone', '6289679349884')->count());
        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone_number' => '6289679349884',
            'last_message' => 'Ok',
            'unread_count' => 2,
        ]);
    }

    public function test_meta_webhook_new_conversation_appears_in_omnichannel_index(): void
    {
        $this->postMetaWebhook($this->metaInboundPayload(
            phone: '6289679349884',
            name: 'New Billing Sender',
            body: 'Billing',
            messageId: 'wamid.omni-billing-1',
        ))->assertOk();

        $this->get('/admin/service/omnichannel?q=&channel=&status=&filter=semua#contact')
            ->assertOk()
            ->assertSee('New Billing Sender')
            ->assertSee('Billing');
    }

    public function test_meta_webhook_links_existing_lead_but_does_not_auto_create_one(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'Existing WhatsApp Lead',
            'whatsapp' => '6289679349884',
            'last_whatsapp_message' => null,
        ]);

        $this->postMetaWebhook($this->metaInboundPayload(
            phone: '6289679349884',
            body: 'Billing',
            messageId: 'wamid.existing-lead-1',
        ))->assertOk();

        $this->assertSame(1, Lead::query()->count());
        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone_number' => '6289679349884',
            'lead_id' => $lead->id,
            'last_message' => 'Billing',
        ]);
        $this->assertSame('Billing', $lead->fresh()->last_whatsapp_message);
    }

    public function test_meta_webhook_accepts_additional_configured_app_secret_for_rotation(): void
    {
        config([
            'services.whatsapp.meta_app_secret' => 'old-app-secret',
            'services.whatsapp.meta_app_secrets' => 'new-app-secret, another-secret ',
        ]);

        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'webhook_secret' => 'verify-token',
            'status' => 'active',
            'is_default' => false,
        ]);

        $payload = $this->metaInboundPayload(
            phone: '6289679349884',
            body: 'Rotation test',
            messageId: 'wamid.rotation-secret-1',
        );

        $this
            ->withHeader('X-Hub-Signature-256', 'sha256='.hash_hmac('sha256', json_encode($payload), 'new-app-secret'))
            ->postJson(route('webhooks.whatsapp.meta'), $payload)
            ->assertOk();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone_number' => '6289679349884',
            'last_message' => 'Rotation test',
        ]);
    }

    public function test_whatsapp_meta_error_131047_is_stored_as_user_friendly_message(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Reengagement Customer',
            'phone_number' => '6289679349885',
            'channel' => 'whatsapp',
            'last_message' => 'Template follow up',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6289679349885',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Free form follow up',
            'provider_message_id' => 'wamid.reengagement-1',
            'provider' => 'meta',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->postMetaWebhook($this->metaStatusPayload(
            messageId: 'wamid.reengagement-1',
            status: 'failed',
            errorCode: 131047,
            errorMessage: 'Re-engagement message',
        ))->assertOk()
            ->assertJsonPath('updated_statuses.0.status', 'failed');

        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'wamid.reengagement-1',
            'provider' => 'meta',
            'status' => 'failed',
            'error_message' => 'Sesi WhatsApp 24 jam sudah berakhir. Gunakan template message.',
        ]);
    }

    public function test_inbound_after_template_reopens_twenty_four_hour_session(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Template Reply Customer',
            'phone_number' => '6289679349886',
            'channel' => 'whatsapp',
            'last_message' => 'Template sent',
            'last_message_at' => now()->subMinutes(5),
            'status' => 'pending',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6289679349886',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Old inbound',
            'provider' => 'meta',
            'status' => 'delivered',
            'received_at' => now()->subHours(30),
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6289679349886',
            'direction' => 'outbound',
            'message_type' => 'template',
            'message' => 'Template sent',
            'provider' => 'meta',
            'provider_message_id' => 'wamid.template-before-reply',
            'status' => 'sent',
            'sent_at' => now()->subMinutes(5),
            'raw_payload' => ['template_name' => 'reopen_session'],
        ]);

        $payload = $this->metaInboundPayload(
            phone: '6289679349886',
            name: 'Template Reply Customer',
            body: 'Saya balas template',
            messageId: 'wamid.template-reply-1',
        );
        data_set($payload, 'entry.0.changes.0.value.messages.0.timestamp', (string) now()->timestamp);

        $this->postMetaWebhook($payload)->assertOk();

        $conversation->refresh();

        $this->assertSame('open', $conversation->status);
        $this->assertSame('Saya balas template', $conversation->last_message);
        $this->assertTrue($conversation->isWhatsAppSessionOpen());
        $this->assertFalse($conversation->isWaitingForCustomerReply());
        $this->assertTrue(now()->addHours(23)->lt($conversation->whatsappSessionExpiresAt()));
    }

    /**
     * @return array<string, mixed>
     */
    private function metaInboundPayload(
        string $phone,
        string $name = 'Meta Sender',
        string $body = 'Billing',
        string $messageId = 'wamid.inbound-test',
    ): array {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba-1',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'contacts' => [
                                    [
                                        'profile' => ['name' => $name],
                                        'wa_id' => $phone,
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => $phone,
                                        'id' => $messageId,
                                        'timestamp' => '1780732800',
                                        'type' => 'text',
                                        'text' => ['body' => $body],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metaStatusPayload(
        string $messageId,
        string $status,
        ?int $errorCode = null,
        ?string $errorMessage = null,
    ): array {
        $statusPayload = [
            'id' => $messageId,
            'status' => $status,
            'timestamp' => '1780732800',
            'recipient_id' => '6289679349885',
        ];

        if ($errorCode !== null) {
            $statusPayload['errors'] = [
                [
                    'code' => $errorCode,
                    'title' => $errorMessage,
                    'message' => $errorMessage,
                ],
            ];
        }

        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => 'waba-1',
                    'changes' => [
                        [
                            'field' => 'messages',
                            'value' => [
                                'messaging_product' => 'whatsapp',
                                'statuses' => [
                                    $statusPayload,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function postMetaWebhook(array $payload, string $secret = 'meta-app-secret'): TestResponse
    {
        config(['services.whatsapp.meta_app_secret' => $secret]);

        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'webhook_secret' => 'verify-token',
            'status' => 'active',
            'is_default' => false,
        ]);

        return $this
            ->withHeader('X-Hub-Signature-256', 'sha256='.hash_hmac('sha256', json_encode($payload), $secret))
            ->postJson(route('webhooks.whatsapp.meta'), $payload);
    }
}
