<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppProvider;
use App\Jobs\SendWhatsAppBroadcastJob;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaWhatsAppProviderTest extends TestCase
{
    use RefreshDatabase;

    public function test_meta_provider_can_be_saved(): void
    {
        $response = $this->post(route('admin.system.whatsapp-providers.store'), [
            'name' => 'Meta Cloud API',
            'provider' => 'meta',
            'api_url' => '',
            'graph_api_version' => '',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
            'meta_template_name' => 'crm_notification',
            'meta_template_language' => 'id',
            'webhook_secret' => 'verify-token',
            'status' => 'active',
            'is_default' => '1',
            'notes' => 'Official WhatsApp Cloud API provider.',
        ]);

        $provider = WhatsAppProvider::query()->where('provider', 'meta')->firstOrFail();

        $response->assertRedirect(route('admin.system.whatsapp-providers.show', $provider));
        $this->assertDatabaseHas('whatsapp_providers', [
            'id' => $provider->id,
            'name' => 'Meta Cloud API',
            'provider' => 'meta',
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
            'meta_template_name' => 'crm_notification',
            'meta_template_language' => 'id',
            'webhook_secret' => 'verify-token',
            'status' => 'active',
            'is_default' => true,
        ]);
        $this->assertSame('permanent-token', $provider->api_token);
    }

    public function test_meta_service_send_message_uses_http_fake(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.meta-1'],
                ],
            ], 200),
        ]);

        $provider = WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
        ]);

        $result = (new MetaWhatsAppService($provider))->sendMessage('081234560001', 'Halo dari Meta');

        $this->assertTrue($result['success']);
        $this->assertSame('meta', $result['provider']);
        $this->assertSame('wamid.meta-1', $result['message_id']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer permanent-token')
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '6281234560001'
                && $request['type'] === 'text'
                && $request['text']['preview_url'] === false
                && $request['text']['body'] === 'Halo dari Meta';
        });
    }

    public function test_manager_resolves_meta_provider(): void
    {
        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
        ]);

        $this->assertInstanceOf(MetaWhatsAppService::class, app(WhatsAppManager::class)->driver());
    }

    public function test_meta_service_send_configured_template(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.template-1'],
                ],
            ], 200),
        ]);

        $provider = WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
            'meta_template_name' => 'crm_notification',
            'meta_template_language' => 'id',
        ]);

        $result = (new MetaWhatsAppService($provider))->sendTemplateMessage('081234560001');

        $this->assertTrue($result['success']);
        $this->assertSame('wamid.template-1', $result['message_id']);
        $this->assertSame('accepted', $result['delivery_status']);
        $this->assertSame('template', $result['message_type']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
                && $request['messaging_product'] === 'whatsapp'
                && $request['to'] === '6281234560001'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'crm_notification'
                && $request['template']['language']['code'] === 'id';
        });
    }

    public function test_meta_template_send_requires_configured_template_name(): void
    {
        $provider = WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'meta_template_name' => null,
            'meta_template_language' => 'id',
        ]);

        $result = (new MetaWhatsAppService($provider))->sendTemplateMessage('081234560001');

        $this->assertFalse($result['success']);
        $this->assertSame('failed', $result['delivery_status']);
        $this->assertStringContainsString('template name is not configured', $result['reason']);
    }

    public function test_meta_webhook_verification_success(): void
    {
        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'webhook_secret' => 'verify-token',
        ]);

        $this->get('/webhooks/whatsapp/meta?hub.mode=subscribe&hub.verify_token=verify-token&hub.challenge=challenge-123')
            ->assertOk()
            ->assertSee('challenge-123');
    }

    public function test_meta_webhook_inbound_creates_conversation(): void
    {
        $this->postJson(route('webhooks.whatsapp.meta'), $this->metaInboundPayload())
            ->assertOk();

        $customer = Customer::query()->where('whatsapp', '6281234560001')->firstOrFail();

        $this->assertDatabaseHas('whatsapp_conversations', [
            'customer_id' => $customer->id,
            'phone_number' => '6281234560001',
            'channel' => 'whatsapp',
            'status' => 'open',
            'last_message' => 'Halo admin dari Meta',
            'unread_count' => 1,
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'customer_id' => $customer->id,
            'phone' => '6281234560001',
            'direction' => 'inbound',
            'message_type' => 'inbound',
            'message' => 'Halo admin dari Meta',
            'provider_message_id' => 'wamid.inbound-1',
            'provider' => 'meta',
            'status' => 'delivered',
        ]);
    }

    public function test_meta_inbound_message_appears_in_omnichannel(): void
    {
        $this->postJson(route('webhooks.whatsapp.meta'), $this->metaInboundPayload())
            ->assertOk();

        $conversation = WhatsAppConversation::query()->where('phone_number', '6281234560001')->firstOrFail();

        $this->get(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]))
            ->assertOk()
            ->assertSee('Meta Customer')
            ->assertSee('Halo admin dari Meta');
    }

    public function test_meta_webhook_status_updates_message_and_broadcast_recipient(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'total_recipients' => 1,
            'sent_count' => 1,
        ]);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'sent',
            'provider_message_id' => 'wamid.status-1',
        ]);
        $conversation = WhatsAppConversation::create([
            'phone_number' => '6281234560001',
            'channel' => 'whatsapp',
            'status' => 'open',
        ]);
        WhatsAppMessage::create([
            'whatsapp_conversation_id' => $conversation->id,
            'phone' => '6281234560001',
            'direction' => 'outbound',
            'message_type' => 'outbound',
            'message' => 'Template: crm_notification',
            'provider_message_id' => 'wamid.status-1',
            'provider' => 'meta',
            'broadcast_id' => $broadcast->id,
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->postJson(route('webhooks.whatsapp.meta'), $this->metaStatusPayload('wamid.status-1', 'delivered'))
            ->assertOk()
            ->assertJsonPath('updated_statuses.0.status', 'delivered');

        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'id' => $recipient->id,
            'status' => 'delivered',
            'provider_message_id' => 'wamid.status-1',
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'wamid.status-1',
            'provider' => 'meta',
            'status' => 'delivered',
        ]);
        $this->assertSame(1, $broadcast->fresh()->delivered_count);
    }

    public function test_meta_broadcast_without_open_session_uses_template_message(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.broadcast-template'],
                ],
            ], 200),
        ]);
        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
            'meta_template_name' => 'crm_notification',
            'meta_template_language' => 'id',
        ]);

        $customer = Customer::factory()->create([
            'name' => 'Template Customer',
            'phone' => '6281234560001',
        ]);
        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'message_template' => 'Halo {{name}}',
        ]);
        $recipient = WhatsAppBroadcastRecipient::factory()->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_type' => 'customer',
            'recipient_id' => $customer->id,
            'recipient_name' => $customer->name,
            'phone_number' => $customer->phone,
            'status' => 'queued',
        ]);

        (new SendWhatsAppBroadcastJob($broadcast->id, $recipient->id))->handle(app(WhatsAppManager::class));

        Http::assertSent(fn ($request) => $request['type'] === 'template'
            && $request['template']['name'] === 'crm_notification'
            && $request['template']['language']['code'] === 'id');
        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'id' => $recipient->id,
            'status' => 'sent',
            'provider_message_id' => 'wamid.broadcast-template',
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'provider_message_id' => 'wamid.broadcast-template',
            'provider' => 'meta',
            'broadcast_id' => $broadcast->id,
            'status' => 'sent',
            'message' => 'Template: crm_notification',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function metaInboundPayload(): array
    {
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
                                        'profile' => ['name' => 'Meta Customer'],
                                        'wa_id' => '6281234560001',
                                    ],
                                ],
                                'messages' => [
                                    [
                                        'from' => '6281234560001',
                                        'id' => 'wamid.inbound-1',
                                        'timestamp' => '1780732800',
                                        'type' => 'text',
                                        'text' => [
                                            'body' => 'Halo admin dari Meta',
                                        ],
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
    private function metaStatusPayload(string $messageId, string $status): array
    {
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
                                    [
                                        'id' => $messageId,
                                        'status' => $status,
                                        'timestamp' => '1780732800',
                                        'recipient_id' => '6281234560001',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
