<?php

namespace Tests\Feature;

use App\Models\WhatsAppMessageTemplate;
use App\Models\WhatsAppProvider;
use App\Services\WhatsApp\MetaTemplateSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppTemplateSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_template_baru_dari_meta_masuk_crm(): void
    {
        $provider = $this->metaProvider();
        $this->fakeMetaTemplates([
            $this->metaTemplatePayload('template-1', 'crm_test', 'Halo dari Meta'),
        ]);

        $result = app(MetaTemplateSyncService::class)->sync($provider);

        $this->assertSame(1, $result['total_meta_templates']);
        $this->assertSame(1, $result['upserted']);
        $this->assertDatabaseHas('whatsapp_message_templates', [
            'provider_id' => $provider->id,
            'template_id' => 'template-1',
            'name' => 'crm_test',
            'language' => 'id',
            'status' => 'APPROVED',
            'body' => 'Halo dari Meta',
            'source' => 'meta_sync',
        ]);
    }

    public function test_template_existing_di_update_dari_meta(): void
    {
        $provider = $this->metaProvider();
        WhatsAppMessageTemplate::query()->create([
            'provider_id' => $provider->id,
            'template_id' => 'template-old',
            'name' => 'crm_test',
            'language' => 'id',
            'category' => 'MARKETING',
            'status' => 'PENDING',
            'body' => 'Body lama',
            'source' => 'manual',
        ]);
        $this->fakeMetaTemplates([
            $this->metaTemplatePayload('template-new', 'crm_test', 'Body baru dari Meta', 'UTILITY', 'APPROVED'),
        ]);

        app(MetaTemplateSyncService::class)->sync($provider);

        $this->assertSame(1, WhatsAppMessageTemplate::query()->where('name', 'crm_test')->count());
        $this->assertDatabaseHas('whatsapp_message_templates', [
            'provider_id' => $provider->id,
            'template_id' => 'template-new',
            'name' => 'crm_test',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'body' => 'Body baru dari Meta',
            'source' => 'meta_sync',
        ]);
    }

    public function test_template_stale_berubah_menjadi_not_found_on_meta_dan_tidak_di_hard_delete(): void
    {
        $provider = $this->metaProvider([
            'meta_template_name' => 'promo',
            'meta_template_language' => 'id',
        ]);
        $staleTemplate = WhatsAppMessageTemplate::query()->create([
            'provider_id' => $provider->id,
            'template_id' => 'template-stale',
            'name' => 'promo',
            'language' => 'id',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'body' => 'Promo lama',
            'source' => 'meta_sync',
            'is_default' => true,
        ]);
        WhatsAppMessageTemplate::query()->create([
            'provider_id' => $provider->id,
            'template_id' => 'template-survay-old',
            'name' => 'survay',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'body' => 'Survey lama',
            'source' => 'meta_sync',
        ]);
        $this->fakeMetaTemplates([
            $this->metaTemplatePayload('template-survay-new', 'survay', 'Survey terbaru', 'UTILITY', 'APPROVED'),
        ]);

        $result = app(MetaTemplateSyncService::class)->sync($provider);
        $staleTemplate->refresh();
        $provider->refresh();

        $this->assertSame(1, $result['marked_missing']);
        $this->assertDatabaseHas('whatsapp_message_templates', [
            'id' => $staleTemplate->id,
            'name' => 'promo',
            'status' => WhatsAppMessageTemplate::STATUS_NOT_FOUND_ON_META,
            'is_default' => false,
        ]);
        $this->assertDatabaseHas('whatsapp_message_templates', [
            'provider_id' => $provider->id,
            'name' => 'survay',
            'status' => 'APPROVED',
            'is_default' => true,
        ]);
        $this->assertSame('survay', $provider->meta_template_name);
        $this->assertSame('id', $provider->meta_template_language);
        $this->assertNotNull(WhatsAppMessageTemplate::query()->find($staleTemplate->id));
    }

    public function test_template_missing_tidak_muncul_di_form_broadcast(): void
    {
        $provider = $this->metaProvider();
        WhatsAppMessageTemplate::query()->create([
            'provider_id' => $provider->id,
            'name' => 'promo',
            'language' => 'id',
            'category' => 'MARKETING',
            'status' => WhatsAppMessageTemplate::STATUS_NOT_FOUND_ON_META,
            'body' => 'Promo stale',
            'source' => 'meta_sync',
        ]);
        WhatsAppMessageTemplate::query()->create([
            'provider_id' => $provider->id,
            'name' => 'crm_test',
            'language' => 'id',
            'category' => 'UTILITY',
            'status' => 'APPROVED',
            'body' => 'Template valid',
            'source' => 'meta_sync',
        ]);

        $this->get(route('admin.marketing.whatsapp-broadcasts.create'))
            ->assertOk()
            ->assertSee('crm_test')
            ->assertDontSee('promo - MARKETING / id');
    }

    public function test_default_template_dikosongkan_jika_tidak_ada_template_approved_valid(): void
    {
        $provider = $this->metaProvider([
            'meta_template_name' => 'promo',
            'meta_template_language' => 'id',
        ]);
        WhatsAppMessageTemplate::query()->create([
            'provider_id' => $provider->id,
            'name' => 'promo',
            'language' => 'id',
            'category' => 'MARKETING',
            'status' => 'APPROVED',
            'body' => 'Promo lama',
            'source' => 'meta_sync',
            'is_default' => true,
        ]);
        $this->fakeMetaTemplates([
            $this->metaTemplatePayload('template-pending', 'crm_pending', 'Pending review', 'UTILITY', 'PENDING'),
        ]);

        app(MetaTemplateSyncService::class)->sync($provider);
        $provider->refresh();

        $this->assertNull($provider->meta_template_name);
        $this->assertNull($provider->meta_template_language);
        $this->assertDatabaseMissing('whatsapp_message_templates', [
            'provider_id' => $provider->id,
            'is_default' => true,
        ]);
    }

    /**
     * @param array<string, mixed> $overrides
     */
    private function metaProvider(array $overrides = []): WhatsAppProvider
    {
        return WhatsAppProvider::factory()->create(array_merge([
            'name' => 'Meta Primary',
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
        ], $overrides));
    }

    /**
     * @param array<int, array<string, mixed>> $templates
     */
    private function fakeMetaTemplates(array $templates): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://graph.facebook.com/v23.0/1234567890*' => Http::response([
                'display_phone_number' => '+62 812-3456-0001',
                'verified_name' => 'Krakatau CRM',
            ], 200),
            'https://graph.facebook.com/v23.0/9876543210/message_templates*' => Http::response([
                'data' => $templates,
            ], 200),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function metaTemplatePayload(
        string $id,
        string $name,
        string $body,
        string $category = 'MARKETING',
        string $status = 'APPROVED',
        string $language = 'id',
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'category' => $category,
            'language' => $language,
            'status' => $status,
            'components' => [
                ['type' => 'BODY', 'text' => $body],
            ],
        ];
    }
}
