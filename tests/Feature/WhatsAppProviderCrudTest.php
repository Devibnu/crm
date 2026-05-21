<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppProviderCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        WhatsAppProvider::factory()->create([
            'name' => 'Fonnte Main',
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
        ]);

        $this->get(route('admin.system.whatsapp-providers.index'))
            ->assertOk()
            ->assertSee('WhatsApp Providers')
            ->assertSee('Total Providers')
            ->assertSee('Active Providers')
            ->assertSee('Default Provider')
            ->assertSee('Last Connected')
            ->assertSee('Fonnte Main');
    }

    public function test_provider_can_be_created(): void
    {
        $response = $this->post(route('admin.system.whatsapp-providers.store'), $this->payload([
            'name' => 'Fonnte Primary',
            'provider' => 'fonnte',
            'is_default' => '1',
        ]));

        $provider = WhatsAppProvider::query()->where('name', 'Fonnte Primary')->firstOrFail();

        $response->assertRedirect(route('admin.system.whatsapp-providers.show', $provider));
        $this->assertDatabaseHas('whatsapp_providers', [
            'id' => $provider->id,
            'name' => 'Fonnte Primary',
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
        ]);
        $this->assertSame('secret-token', $provider->api_token);
    }

    public function test_provider_can_be_updated(): void
    {
        $provider = WhatsAppProvider::factory()->create([
            'name' => 'Before Provider',
            'provider' => 'fonnte',
            'status' => 'inactive',
        ]);

        $response = $this->put(route('admin.system.whatsapp-providers.update', $provider), $this->payload([
            'name' => 'After Provider',
            'provider' => 'wablas',
            'status' => 'active',
            'device_id' => 'wablas-device-9',
        ]));

        $response->assertRedirect(route('admin.system.whatsapp-providers.show', $provider));
        $this->assertDatabaseHas('whatsapp_providers', [
            'id' => $provider->id,
            'name' => 'After Provider',
            'provider' => 'wablas',
            'status' => 'active',
            'device_id' => 'wablas-device-9',
        ]);
    }

    public function test_default_provider_is_unique(): void
    {
        $currentDefault = WhatsAppProvider::factory()->create([
            'name' => 'Current Default',
            'provider' => 'fonnte',
            'is_default' => true,
        ]);
        $nextDefault = WhatsAppProvider::factory()->create([
            'name' => 'Next Default',
            'provider' => 'wablas',
            'is_default' => false,
        ]);

        $this->put(route('admin.system.whatsapp-providers.update', $nextDefault), $this->payload([
            'name' => 'Next Default',
            'provider' => 'wablas',
            'is_default' => '1',
        ]))->assertRedirect(route('admin.system.whatsapp-providers.show', $nextDefault));

        $this->assertFalse($currentDefault->fresh()->is_default);
        $this->assertTrue($nextDefault->fresh()->is_default);
        $this->assertSame(1, WhatsAppProvider::query()->where('is_default', true)->count());
    }

    public function test_provider_can_be_deleted(): void
    {
        $provider = WhatsAppProvider::factory()->create();

        $response = $this->delete(route('admin.system.whatsapp-providers.destroy', $provider));

        $response->assertRedirect(route('admin.system.whatsapp-providers.index'));
        $this->assertDatabaseMissing('whatsapp_providers', ['id' => $provider->id]);
    }

    public function test_role_protection_blocks_non_admin_roles(): void
    {
        $user = User::factory()->create();
        $user->assignRole('marketing');

        $this->actingAs($user)
            ->get(route('admin.system.whatsapp-providers.index'))
            ->assertForbidden();
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Default Provider',
            'provider' => 'fonnte',
            'api_url' => 'https://api.fonnte.com',
            'api_token' => 'secret-token',
            'device_id' => 'device-001',
            'webhook_secret' => 'webhook-secret',
            'status' => 'active',
            'is_default' => '0',
            'notes' => 'Feature test provider.',
        ], $overrides);
    }
}
