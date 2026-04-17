<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\Pesan;
use App\Models\Tiket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WhatsAppInboxApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_it_lists_whatsapp_threads_from_backend_ticket_data(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'WA Customer',
            'email' => 'wa@example.com',
            'no_hp' => '6281234567890',
            'status' => 'active',
            'source' => 'inbox',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'assigned_user_id' => $this->user->id,
            'kategori' => 'general',
            'subjek' => 'Pertanyaan WhatsApp',
            'status' => 'diproses',
            'prioritas' => 'tinggi',
        ]);

        Pesan::query()->create([
            'tiket_id' => $ticket->id,
            'channel' => 'whatsapp',
            'isi_pesan' => 'Halo admin via WA',
            'pengirim' => 'WA Customer',
        ]);

        $response = $this->getJson('/api/crm/inbox/whatsapp');

        $response
            ->assertOk()
            ->assertJsonPath('threads.0.ticketId', $ticket->id)
            ->assertJsonPath('threads.0.name', 'WA Customer')
            ->assertJsonPath('threads.0.phone', '6281234567890')
            ->assertJsonPath('threads.0.assignedTo', $this->user->email)
            ->assertJsonPath('threads.0.messages.0.sender', 'customer')
            ->assertJsonPath('threads.0.status', 'butuh_respons');

        $this->assertContains('inbound', $response->json('threads.0.labels'));
    }

    public function test_whatsapp_reply_flow_updates_backend_thread_messages(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Reply Customer',
            'email' => 'reply@example.com',
            'no_hp' => '6281987654321',
            'status' => 'active',
            'source' => 'inbox',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'kategori' => 'general',
            'subjek' => 'Butuh bantuan',
            'status' => 'baru',
            'prioritas' => 'sedang',
        ]);

        Pesan::query()->create([
            'tiket_id' => $ticket->id,
            'channel' => 'whatsapp',
            'isi_pesan' => 'Tolong dibantu ya',
            'pengirim' => 'Reply Customer',
        ]);

        $this->postJson("/api/crm/inbox/conversations/{$ticket->id}/reply", [
            'isiPesan' => 'Siap, kami bantu sekarang.',
            'mode' => 'customer',
            'status' => 'diproses',
        ])->assertOk();

        $response = $this->getJson('/api/crm/inbox/whatsapp');

        $response->assertOk();

        $thread = collect($response->json('threads'))->firstWhere('ticketId', $ticket->id);

        $this->assertNotNull($thread);
        $this->assertSame('Siap, kami bantu sekarang.', $thread['messages'][1]['text']);
        $this->assertSame('agent', $thread['messages'][1]['sender']);
        $this->assertSame('menunggu_pelanggan', $thread['status']);
        $this->assertContains('inbound', $thread['labels']);
    }

    public function test_it_marks_agent_started_threads_as_outbound_waiting_customer(): void
    {
        $customer = Pelanggan::query()->create([
            'nama' => 'Outbound Customer',
            'email' => 'outbound@example.com',
            'no_hp' => '6287770001234',
            'status' => 'active',
            'source' => 'manual',
        ]);

        $ticket = Tiket::query()->create([
            'pelanggan_id' => $customer->id,
            'assigned_user_id' => $this->user->id,
            'kategori' => 'general',
            'subjek' => 'Follow up outbound',
            'status' => 'baru',
            'prioritas' => 'sedang',
        ]);

        Pesan::query()->create([
            'tiket_id' => $ticket->id,
            'channel' => 'balasan-internal',
            'isi_pesan' => 'Halo, kami follow up dari KitCRM.',
            'pengirim' => $this->user->full_name,
        ]);

        $response = $this->getJson('/api/crm/inbox/whatsapp');

        $response->assertOk();

        $thread = collect($response->json('threads'))->firstWhere('ticketId', $ticket->id);

        $this->assertNotNull($thread);
        $this->assertSame('menunggu_pelanggan', $thread['status']);
        $this->assertContains('outbound', $thread['labels']);
        $this->assertFalse($thread['unread']);
    }
}