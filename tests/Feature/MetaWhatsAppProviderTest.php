<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppProvider;
use App\Jobs\SendWhatsAppBroadcastJob;
use App\Services\WhatsApp\MetaWhatsAppService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
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

    public function test_whatsapp_cloud_api_page_can_be_opened(): void
    {
        WhatsAppProvider::factory()->create([
            'name' => 'Meta Primary',
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
            'verified_name' => 'Meta Primary',
            'meta_template_name' => 'crm_notification',
            'meta_template_language' => 'id',
        ]);

        $this->get(route('admin.marketing.whatsapp-cloud-api.index'))
            ->assertOk()
            ->assertSee('WhatsApp Cloud API')
            ->assertSee('Meta Primary')
            ->assertSee('Terhubung');
    }

    public function test_sync_template_saves_dummy_meta_data(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890*' => Http::response([
                'display_phone_number' => '+62 812-3456-0001',
                'verified_name' => 'Krakatau CRM',
            ], 200),
            'https://graph.facebook.com/v23.0/9876543210/message_templates*' => Http::response([
                'data' => [
                    [
                        'id' => 'template-1',
                        'name' => 'crm_welcome',
                        'category' => 'MARKETING',
                        'language' => 'id',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'HEADER', 'format' => 'TEXT', 'text' => 'Halo'],
                            ['type' => 'BODY', 'text' => 'Halo {{1}}, selamat datang.'],
                            ['type' => 'FOOTER', 'text' => 'Krakatau CRM'],
                            ['type' => 'BUTTONS', 'buttons' => [['type' => 'QUICK_REPLY', 'text' => 'Mulai']]],
                        ],
                    ],
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
            'business_account_id' => '9876543210',
        ]);

        $this->post(route('admin.marketing.whatsapp-cloud-api.sync'))
            ->assertRedirect();

        $this->assertDatabaseHas('whatsapp_message_templates', [
            'provider_id' => $provider->id,
            'template_id' => 'template-1',
            'name' => 'crm_welcome',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Halo {{1}}, selamat datang.',
            'header' => 'Halo',
            'footer' => 'Krakatau CRM',
            'is_default' => true,
        ]);
        $this->assertDatabaseHas('whatsapp_providers', [
            'id' => $provider->id,
            'display_phone_number' => '+62 812-3456-0001',
            'verified_name' => 'Krakatau CRM',
            'meta_connection_status' => 'connected',
            'meta_connection_error' => null,
            'meta_template_name' => 'crm_welcome',
            'meta_template_language' => 'id',
        ]);
        $this->assertNotNull($provider->fresh()->last_connected_at);
    }

    public function test_sync_template_shows_clear_error_when_meta_returns_no_templates(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890*' => Http::response([
                'display_phone_number' => '+62 812-3456-0001',
                'verified_name' => 'Krakatau CRM',
            ], 200),
            'https://graph.facebook.com/v23.0/9876543210/message_templates*' => Http::response([
                'data' => [],
            ], 200),
        ]);

        WhatsAppProvider::factory()->create([
            'name' => 'Meta Primary',
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
        ]);

        $this->from(route('admin.marketing.whatsapp-cloud-api.index'))
            ->post(route('admin.marketing.whatsapp-cloud-api.sync'))
            ->assertRedirect(route('admin.marketing.whatsapp-cloud-api.index'))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Meta mengembalikan 0 template')
                && str_contains($message, 'WABA ID: 9876543210'));
    }

    public function test_sync_uses_meta_primary_when_no_meta_default_exists(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890*' => Http::response([
                'display_phone_number' => '+62 812-3456-0001',
                'verified_name' => 'Krakatau CRM',
            ], 200),
            'https://graph.facebook.com/v23.0/correct-waba/message_templates*' => Http::response([
                'data' => [
                    [
                        'id' => 'template-primary',
                        'name' => 'promo',
                        'category' => 'MARKETING',
                        'language' => 'id',
                        'status' => 'APPROVED',
                        'components' => [
                            ['type' => 'BODY', 'text' => 'Promo aktif'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        WhatsAppProvider::factory()->create([
            'name' => 'Old Meta',
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => false,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'old-token',
            'device_id' => 'old-phone',
            'business_account_id' => 'wrong-waba',
        ]);
        $provider = WhatsAppProvider::factory()->create([
            'name' => 'Meta Primary',
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => false,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
            'business_account_id' => 'correct-waba',
        ]);

        $this->post(route('admin.marketing.whatsapp-cloud-api.sync'))
            ->assertRedirect(route('admin.marketing.whatsapp-cloud-api.index', ['provider_id' => $provider->id]));

        $this->assertDatabaseHas('whatsapp_message_templates', [
            'provider_id' => $provider->id,
            'template_id' => 'template-primary',
            'name' => 'promo',
            'status' => 'APPROVED',
            'is_default' => true,
        ]);
        Http::assertSent(fn ($request) => str_contains($request->url(), '/correct-waba/message_templates'));
    }

    public function test_refresh_connection_updates_business_phone_data(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890*' => Http::response([
                'display_phone_number' => '+62 811-2222-3333',
                'verified_name' => 'Krakatau Official',
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
            'business_account_id' => '9876543210',
            'display_phone_number' => null,
            'verified_name' => null,
            'meta_connection_status' => null,
            'meta_connection_error' => 'old error',
        ]);

        $this->post(route('admin.marketing.whatsapp-cloud-api.refresh-connection'), [
            'provider_id' => $provider->id,
        ])->assertRedirect(route('admin.marketing.whatsapp-cloud-api.index', ['provider_id' => $provider->id]));

        $this->assertDatabaseHas('whatsapp_providers', [
            'id' => $provider->id,
            'display_phone_number' => '+62 811-2222-3333',
            'verified_name' => 'Krakatau Official',
            'meta_connection_status' => 'connected',
            'meta_connection_error' => null,
        ]);
        $this->assertNotNull($provider->fresh()->last_connected_at);
    }

    public function test_refresh_connection_marks_expired_meta_token(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890*' => Http::response([
                'error' => [
                    'message' => 'Error validating access token: Session has expired',
                    'type' => 'OAuthException',
                    'code' => 190,
                    'error_subcode' => 463,
                ],
            ], 400),
        ]);

        $provider = WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'expired-token',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
        ]);

        $this->post(route('admin.marketing.whatsapp-cloud-api.refresh-connection'), [
            'provider_id' => $provider->id,
        ])->assertRedirect(route('admin.marketing.whatsapp-cloud-api.index', ['provider_id' => $provider->id]))
            ->assertSessionHas('error', fn (string $message) => str_contains($message, 'Token Meta telah kedaluwarsa'));

        $this->assertDatabaseHas('whatsapp_providers', [
            'id' => $provider->id,
            'meta_connection_status' => 'token_expired',
        ]);
    }

    public function test_set_default_template_updates_provider(): void
    {
        $provider = WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'meta_template_name' => null,
            'meta_template_language' => null,
        ]);
        $template = WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'template_id' => 'template-1',
            'name' => 'crm_welcome',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Halo {{1}}',
            'last_synced_at' => now(),
        ]);
        $otherTemplate = WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'template_id' => 'template-2',
            'name' => 'crm_other',
            'category' => 'UTILITY',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Template lain',
            'is_default' => true,
            'last_synced_at' => now(),
        ]);

        $this->post(route('admin.marketing.whatsapp-cloud-api.templates.default', $template))
            ->assertRedirect();

        $provider->refresh();
        $this->assertSame('crm_welcome', $provider->meta_template_name);
        $this->assertSame('id', $provider->meta_template_language);
        $this->assertTrue($template->fresh()->is_default);
        $this->assertFalse($otherTemplate->fresh()->is_default);
    }

    public function test_send_template_uses_meta_template_name_not_hello_world(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.template-route'],
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
            'business_account_id' => '9876543210',
            'meta_template_name' => 'crm_notification',
            'meta_template_language' => 'id',
        ]);

        $this->postJson(route('admin.system.whatsapp-providers.test-send'), [
            'phone' => '081234560001',
            'send_mode' => 'template',
        ])->assertOk();

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v23.0/1234567890/messages'
                && $request['type'] === 'template'
                && $request['template']['name'] === 'crm_notification'
                && $request['template']['name'] !== 'hello_world'
                && $request['template']['language']['code'] === 'id';
        });
    }

    public function test_system_provider_template_test_uses_default_synced_template_from_database(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.default-db-template'],
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
            'business_account_id' => '9876543210',
            'meta_template_name' => null,
            'meta_template_language' => null,
        ]);
        WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'template_id' => 'template-1',
            'name' => 'promo',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Promo hari ini',
            'is_default' => true,
            'last_synced_at' => now(),
        ]);

        $this->postJson(route('admin.system.whatsapp-providers.test-send'), [
            'phone' => '081234560001',
            'send_mode' => 'template',
        ])->assertOk();

        Http::assertSent(fn ($request) => $request['template']['name'] === 'promo'
            && $request['template']['language']['code'] === 'id');
        $this->assertSame('promo', $provider->fresh()->meta_template_name);
    }

    public function test_system_provider_template_test_uses_first_approved_when_no_default_exists(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.first-approved-template'],
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
            'business_account_id' => '9876543210',
            'meta_template_name' => null,
            'meta_template_language' => null,
        ]);
        $template = WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'template_id' => 'template-1',
            'name' => 'crm_test',
            'category' => 'UTILITY',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Test CRM',
            'is_default' => false,
            'last_synced_at' => now(),
        ]);

        $this->postJson(route('admin.system.whatsapp-providers.test-send'), [
            'phone' => '081234560001',
            'send_mode' => 'template',
        ])->assertOk();

        Http::assertSent(fn ($request) => $request['template']['name'] === 'crm_test');
        $this->assertTrue($template->fresh()->is_default);
        $this->assertSame('crm_test', $provider->fresh()->meta_template_name);
    }

    public function test_system_provider_test_page_shows_synced_template_dropdown(): void
    {
        $provider = WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'meta_template_name' => null,
            'meta_template_language' => null,
        ]);
        WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'template_id' => 'template-1',
            'name' => 'promo',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Promo',
            'is_default' => true,
            'last_synced_at' => now(),
        ]);

        $this->get(route('admin.system.whatsapp-providers.show', $provider))
            ->assertOk()
            ->assertSee('Template')
            ->assertSee('promo / id - Default')
            ->assertSee('Auto: Default approved template');
    }

    public function test_whatsapp_template_create_page_can_be_opened(): void
    {
        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
        ]);

        $this->get(route('admin.marketing.whatsapp-templates.create'))
            ->assertOk()
            ->assertSee('Tambah Template')
            ->assertSee('Pilih template siap pakai')
            ->assertSee('notifikasi_pelanggan')
            ->assertSee('Direkomendasikan / Approval lebih aman')
            ->assertSee('Template ini mengikuti panduan aman agar peluang approval Meta lebih tinggi.')
            ->assertSee('kami ingin menginformasikan bahwa permintaan Anda telah kami terima')
            ->assertSee('Submit to Meta');
    }

    public function test_whatsapp_template_index_uses_compact_cards_and_missing_meta_state(): void
    {
        $provider = WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'permanent-token',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
        ]);
        WhatsAppMessageTemplate::query()->create([
            'provider_id' => $provider->id,
            'template_id' => 'template-approved',
            'name' => 'follow_up_customer',
            'language' => 'id',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'body' => 'Halo {{1}}, kami ingin menindaklanjuti kebutuhan Anda dengan informasi terbaru dari tim kami.',
            'source' => 'meta_sync',
            'last_synced_at' => now(),
        ]);
        WhatsAppMessageTemplate::query()->create([
            'provider_id' => $provider->id,
            'template_id' => 'template-missing',
            'name' => 'promo_lama',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => WhatsAppMessageTemplate::STATUS_NOT_FOUND_ON_META,
            'body' => 'Promo lama yang sudah tidak ditemukan di Meta.',
            'source' => 'meta_sync',
            'last_synced_at' => now()->subDay(),
        ]);

        $response = $this->get(route('admin.marketing.whatsapp-templates.index'))
            ->assertOk()
            ->assertSee('Template Pesan WhatsApp')
            ->assertSee('Kelola template pesan WhatsApp untuk broadcast dan follow-up customer')
            ->assertSee('+ Tambah Template')
            ->assertSee('Sync Templates')
            ->assertSee('Cara Menggunakan Template untuk WA Blast')
            ->assertSee('Missing on Meta')
            ->assertSee('follow_up_customer')
            ->assertSee('Approved Meta')
            ->assertSee('promo_lama')
            ->assertSee('is-missing', false)
            ->assertSee('disabled', false)
            ->assertSee('wa-template-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr))', false)
            ->assertSee('@media(max-width:1180px){.wa-template-grid{grid-template-columns:repeat(2,minmax(0,1fr))', false)
            ->assertSee('@media(max-width:860px)', false);

        $content = $response->getContent();
        $missingPosition = strpos($content, 'promo_lama');
        $approvedPosition = strpos($content, 'follow_up_customer');
        $missingCardStart = strrpos(substr($content, 0, $missingPosition), '<article');
        $missingCardEnd = strpos($content, '</article>', $missingPosition);
        $approvedCardStart = strrpos(substr($content, 0, $approvedPosition), '<article');
        $approvedCardEnd = strpos($content, '</article>', $approvedPosition);
        $missingCard = substr($content, $missingCardStart, $missingCardEnd - $missingCardStart);
        $approvedCard = substr($content, $approvedCardStart, $approvedCardEnd - $approvedCardStart);

        $this->assertStringContainsString('wa-template-card is-missing', $missingCard);
        $this->assertStringContainsString('Missing on Meta', $missingCard);
        $this->assertStringContainsString('Send Test</button>', $missingCard);
        $this->assertStringContainsString('disabled', $missingCard);
        $this->assertStringNotContainsString('Set Default', $missingCard);
        $this->assertStringContainsString('Delete', $missingCard);
        $this->assertStringContainsString('Approved Meta', $approvedCard);
        $this->assertStringContainsString('Send Test</button>', $approvedCard);
        $this->assertStringNotContainsString('disabled', $approvedCard);
    }

    public function test_create_whatsapp_template_from_crm_submits_meta_payload_and_saves_pending(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/9876543210/message_templates' => Http::response([
                'id' => 'meta-template-1',
                'status' => 'PENDING',
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
            'business_account_id' => '9876543210',
        ]);

        $this->post(route('admin.marketing.whatsapp-templates.store'), [
            'name' => 'Undangan Acara CRM',
            'category' => 'MARKETING',
            'language' => 'id',
            'header' => 'Undangan',
            'body' => 'Halo {{nama}}, jadwal Anda pada {{tanggal}}.',
            'footer' => 'Krakatau CRM',
        ])->assertRedirect(route('admin.marketing.whatsapp-templates.index'));

        $this->assertDatabaseHas('whatsapp_message_templates', [
            'provider_id' => $provider->id,
            'template_id' => 'meta-template-1',
            'name' => 'undangan_acara_crm',
            'safe_name' => 'undangan_acara_crm',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'PENDING',
            'body' => 'Halo {{nama}}, jadwal Anda pada {{tanggal}}.',
            'body_meta' => 'Halo {{1}}, jadwal Anda pada {{2}}.',
            'source' => 'manual',
        ]);

        $template = WhatsAppMessageTemplate::query()->where('name', 'undangan_acara_crm')->firstOrFail();
        $this->assertSame(['1' => 'nama', '2' => 'tanggal'], $template->variable_mapping);
        $this->assertNotNull($template->submitted_at);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://graph.facebook.com/v23.0/9876543210/message_templates'
                && $request['name'] === 'undangan_acara_crm'
                && $request['components'][1]['text'] === 'Halo {{1}}, jadwal Anda pada {{2}}.'
                && $request['components'][1]['example']['body_text'][0] === ['Ibnu', '10 Juni 2026'];
        });
    }

    public function test_create_whatsapp_template_meta_error_redirects_with_clear_alert(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/9876543210/message_templates' => Http::response([
                'error' => [
                    'message' => 'Error validating access token: Session has expired.',
                    'type' => 'OAuthException',
                    'code' => 190,
                ],
            ], 400),
        ]);

        WhatsAppProvider::factory()->create([
            'name' => 'Meta Primary',
            'provider' => 'meta',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://graph.facebook.com',
            'graph_api_version' => 'v23.0',
            'api_token' => 'expired-token',
            'device_id' => '1234567890',
            'business_account_id' => '9876543210',
        ]);

        $this->from(route('admin.marketing.whatsapp-templates.create'))
            ->post(route('admin.marketing.whatsapp-templates.store'), [
                'name' => 'Notifikasi Pelanggan',
                'category' => 'UTILITY',
                'language' => 'id',
                'body' => 'Halo {{nama}}, permintaan Anda sedang diproses oleh tim kami. Terima kasih.',
            ])
            ->assertRedirect(route('admin.marketing.whatsapp-templates.create'))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'Provider: Meta Primary')
                && str_contains($message, 'WABA ID: 9876543210')
                && str_contains($message, 'Endpoint: https://graph.facebook.com/v23.0/9876543210/message_templates')
                && str_contains($message, 'Error code: 190')
                && str_contains($message, 'Error type: OAuthException')
                && str_contains($message, 'Message: Token Meta telah kedaluwarsa.'));

        $this->assertDatabaseMissing('whatsapp_message_templates', [
            'name' => 'notifikasi_pelanggan',
        ]);
    }

    public function test_whatsapp_template_validation_blocks_utility_promotional_words(): void
    {
        WhatsAppProvider::factory()->create(['provider' => 'meta', 'status' => 'active', 'is_default' => true]);

        $this->post(route('admin.marketing.whatsapp-templates.store'), [
            'name' => 'Promo Utility',
            'category' => 'UTILITY',
            'language' => 'id',
            'body' => 'Halo {{nama}}, ada promo diskon gratis untuk Anda.',
        ])->assertSessionHasErrors('body');
    }

    public function test_whatsapp_template_validation_blocks_non_otp_authentication(): void
    {
        WhatsAppProvider::factory()->create(['provider' => 'meta', 'status' => 'active', 'is_default' => true]);

        $this->post(route('admin.marketing.whatsapp-templates.store'), [
            'name' => 'Auth Info',
            'category' => 'AUTHENTICATION',
            'language' => 'id',
            'body' => 'Halo {{nama}}, akun Anda sudah aktif.',
        ])->assertSessionHasErrors('body');
    }

    public function test_whatsapp_template_validation_blocks_risky_links(): void
    {
        WhatsAppProvider::factory()->create(['provider' => 'meta', 'status' => 'active', 'is_default' => true]);

        $this->post(route('admin.marketing.whatsapp-templates.store'), [
            'name' => 'Link Template',
            'category' => 'MARKETING',
            'language' => 'id',
            'body' => 'Halo {{nama}}, buka https://example.com sekarang.',
        ])->assertSessionHasErrors('body');
    }

    public function test_broadcast_can_choose_approved_meta_template(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.broadcast-selected-template'],
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
            'business_account_id' => '9876543210',
        ]);
        $template = WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'template_id' => 'template-1',
            'name' => 'promo',
            'safe_name' => 'promo',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Halo {{nama}}, promo tersedia.',
            'body_meta' => 'Halo {{1}}, promo tersedia.',
            'variable_mapping' => ['1' => 'nama'],
            'source' => 'manual',
            'last_synced_at' => now(),
        ]);
        $customer = Customer::factory()->create([
            'name' => 'Ibnu Customer',
            'phone' => '6281234560001',
        ]);
        $broadcast = WhatsAppBroadcast::factory()->create([
            'status' => 'sending',
            'send_mode' => 'meta_template',
            'whatsapp_message_template_id' => $template->id,
            'message_template' => $template->body,
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

        Http::assertSent(fn ($request) => $request['template']['name'] === 'promo'
            && $request['template']['components'][0]['parameters'][0]['text'] === 'Ibnu Customer'
            && $request['template']['name'] !== 'hello_world');
    }

    public function test_send_test_template_adds_example_parameter_for_variables(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890/messages' => Http::response([
                'messaging_product' => 'whatsapp',
                'messages' => [
                    ['id' => 'wamid.template-variable'],
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
            'business_account_id' => '9876543210',
        ]);
        $template = WhatsAppMessageTemplate::create([
            'provider_id' => $provider->id,
            'template_id' => 'template-1',
            'name' => 'crm_welcome',
            'category' => 'MARKETING',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Halo {{1}}, selamat datang.',
            'last_synced_at' => now(),
        ]);

        $this->postJson(route('admin.marketing.whatsapp-cloud-api.templates.send-test', $template), [
            'phone' => '081234560001',
        ])->assertOk();

        Http::assertSent(function ($request) {
            return $request['template']['name'] === 'crm_welcome'
                && $request['template']['components'][0]['type'] === 'body'
                && $request['template']['components'][0]['parameters'][0]['type'] === 'text'
                && $request['template']['components'][0]['parameters'][0]['text'] === 'Ibnu';
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

    public function test_meta_webhook_rejects_invalid_signature(): void
    {
        config(['services.whatsapp.meta_app_secret' => 'meta-app-secret']);

        WhatsAppProvider::factory()->create([
            'provider' => 'meta',
            'webhook_secret' => 'verify-token',
            'status' => 'active',
        ]);

        $this->withHeader('X-Hub-Signature-256', 'sha256=' . str_repeat('0', 64))
            ->postJson(route('webhooks.whatsapp.meta'), $this->metaInboundPayload())
            ->assertForbidden()
            ->assertJsonPath('message', 'Invalid webhook signature.');
    }

    public function test_meta_webhook_inbound_creates_conversation(): void
    {
        $this->postMetaWebhook($this->metaInboundPayload())
            ->assertOk();

        $lead = Lead::query()->where('whatsapp', '6281234560001')->firstOrFail();

        $this->assertDatabaseMissing('customers', [
            'whatsapp' => '6281234560001',
        ]);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'customer_id' => null,
            'lead_id' => $lead->id,
            'phone_number' => '6281234560001',
            'channel' => 'whatsapp',
            'status' => 'open',
            'last_message' => 'Halo admin dari Meta',
            'unread_count' => 1,
        ]);
        $this->assertDatabaseHas('whatsapp_messages', [
            'customer_id' => null,
            'lead_id' => $lead->id,
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
        $this->postMetaWebhook($this->metaInboundPayload())
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

        $this->postMetaWebhook($this->metaStatusPayload('wamid.status-1', 'delivered'))
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

    private function postMetaWebhook(array $payload, string $secret = 'meta-app-secret'): TestResponse
    {
        config(['services.whatsapp.meta_app_secret' => $secret]);

        if (! WhatsAppProvider::query()->where('provider', 'meta')->exists()) {
            WhatsAppProvider::factory()->create([
                'provider' => 'meta',
                'webhook_secret' => 'verify-token',
                'status' => 'active',
                'is_default' => false,
            ]);
        }

        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), $secret);

        return $this->withHeader('X-Hub-Signature-256', $signature)
            ->postJson(route('webhooks.whatsapp.meta'), $payload);
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
