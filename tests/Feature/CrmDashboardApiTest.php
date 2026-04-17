<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pesan;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CrmDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_trimmed_metrics_when_user_lacks_ticket_and_inbox_access(): void
    {
        $user = User::factory()->create([
            'role' => 'subscriber',
            'module_permissions' => [
                'customers' => 'view',
                'tickets' => null,
                'inbox' => null,
                'whatsapp' => null,
            ],
        ]);

        $customer = Pelanggan::query()->create([
            'nama' => 'Dashboard Customer',
            'email' => 'dashboard@example.com',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'kategori' => 'general',
            'subjek' => 'Hidden ticket',
            'status' => 'baru',
            'prioritas' => 'sedang',
        ]);

        Pesan::query()->create([
            'tiket_id' => $ticket->id,
            'channel' => 'whatsapp',
            'isi_pesan' => 'Hidden conversation',
            'pengirim' => 'Dashboard Customer',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/crm/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.pelanggan', 1)
            ->assertJsonPath('stats.tiket', 0)
            ->assertJsonPath('stats.tiketAktif', 0)
            ->assertJsonPath('stats.percakapan', 0)
            ->assertJsonPath('stats.pengguna', 0)
            ->assertJsonCount(0, 'recentTickets');
    }
}