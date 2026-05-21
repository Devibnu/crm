<?php

namespace Tests\Feature;

use App\Models\WhatsAppProvider;
use App\Services\WhatsApp\FonnteService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class FonnteServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_resolves_default_active_provider(): void
    {
        $provider = WhatsAppProvider::factory()->create([
            'name' => 'Default Fonnte',
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://api.fonnte.test',
            'api_token' => 'dummy-token',
        ]);
        WhatsAppProvider::factory()->create([
            'provider' => 'wablas',
            'status' => 'active',
            'is_default' => false,
        ]);

        $manager = app(WhatsAppManager::class);

        $this->assertTrue($provider->is($manager->provider()));
        $this->assertInstanceOf(FonnteService::class, $manager->driver());
    }

    public function test_send_message_success_uses_http_fake(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.fonnte.test/send' => Http::response([
                'status' => true,
                'id' => 'abc123',
            ], 200),
        ]);

        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://api.fonnte.test',
            'api_token' => 'dummy-token',
        ]);

        $result = app(WhatsAppManager::class)->sendMessage('6281234567890', 'Halo dari CRM');

        $this->assertTrue($result['success']);
        $this->assertSame('fonnte', $result['provider']);
        $this->assertSame('abc123', $result['message_id']);
        $this->assertSame(['status' => true, 'id' => 'abc123'], $result['raw']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.fonnte.test/send'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'dummy-token')
                && $request['target'] === '6281234567890'
                && $request['message'] === 'Halo dari CRM';
        });
    }

    public function test_send_message_failed_uses_http_fake(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://api.fonnte.test/send' => Http::response([
                'status' => false,
                'reason' => 'Invalid token',
            ], 200),
        ]);

        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'status' => 'active',
            'is_default' => true,
            'api_url' => 'https://api.fonnte.test',
            'api_token' => 'bad-token',
        ]);

        $result = app(WhatsAppManager::class)->sendMessage('6281234567890', 'Halo dari CRM');

        $this->assertFalse($result['success']);
        $this->assertSame('fonnte', $result['provider']);
        $this->assertNull($result['message_id']);
        $this->assertSame([
            'status' => false,
            'reason' => 'Invalid token',
        ], $result['raw']);

        Http::assertSentCount(1);
    }

    public function test_no_active_default_provider_throws_clear_exception(): void
    {
        WhatsAppProvider::factory()->create([
            'provider' => 'fonnte',
            'status' => 'inactive',
            'is_default' => true,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No active WhatsApp provider configured.');

        app(WhatsAppManager::class)->sendMessage('6281234567890', 'Halo dari CRM');
    }
}
