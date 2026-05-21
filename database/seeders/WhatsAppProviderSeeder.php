<?php

namespace Database\Seeders;

use App\Models\WhatsAppProvider;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WhatsAppProviderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WhatsAppProvider::query()->update(['is_default' => false]);

        WhatsAppProvider::factory()->create([
            'name' => 'Fonnte Primary',
            'provider' => 'fonnte',
            'api_url' => 'https://api.fonnte.test',
            'api_token' => 'dummy-fonnte-token',
            'device_id' => 'fonnte-device-001',
            'webhook_secret' => 'fonnte-webhook-secret',
            'status' => 'active',
            'is_default' => true,
            'notes' => 'Default provider untuk fondasi integrasi WhatsApp CRM.',
            'last_connected_at' => now()->subHours(2),
        ]);

        WhatsAppProvider::factory()->create([
            'name' => 'Wablas Backup',
            'provider' => 'wablas',
            'api_url' => 'https://solo.wablas.com',
            'api_token' => 'demo-wablas-token',
            'device_id' => 'wablas-device-001',
            'webhook_secret' => 'wablas-webhook-secret',
            'status' => 'inactive',
            'is_default' => false,
            'notes' => 'Backup provider untuk switching manual.',
            'last_connected_at' => null,
        ]);
    }
}
