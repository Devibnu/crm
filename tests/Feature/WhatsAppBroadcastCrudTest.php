<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppBroadcastCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.whatsapp-broadcasts.index'))
            ->assertOk()
            ->assertSee('WA Blast - Kampanye')
            ->assertSee('Total Kampanye')
            ->assertSee('Sedang Berjalan')
            ->assertSee('Terjadwal')
            ->assertSee('Selesai')
            ->assertSee('Draft')
            ->assertSee('Gagal');
    }

    public function test_create_page_can_be_opened(): void
    {
        $this->get(route('admin.marketing.whatsapp-broadcasts.create'))
            ->assertOk()
            ->assertSee('Buat Kampanye WA Blast')
            ->assertSee('Detail Kampanye')
            ->assertSee('Target Penerima')
            ->assertSee('Estimasi Biaya');
    }

    public function test_broadcast_can_be_created_with_customer_recipients(): void
    {
        MarketingCampaign::factory()->create(['name' => 'WA Campaign One']);
        Customer::factory()->count(2)->create([
            'phone' => '081234567890',
        ]);

        $response = $this->post(route('admin.marketing.whatsapp-broadcasts.store'), $this->payload([
            'name' => 'WA Blast Customer Segment',
            'recipient_type' => 'customer',
        ]));

        $broadcast = WhatsAppBroadcast::query()->where('name', 'WA Blast Customer Segment')->firstOrFail();

        $response->assertRedirect(route('admin.marketing.whatsapp-broadcasts.show', $broadcast));
        $this->assertDatabaseHas('whatsapp_broadcasts', [
            'id' => $broadcast->id,
            'name' => 'WA Blast Customer Segment',
            'status' => 'scheduled',
        ]);
        $this->assertGreaterThan(0, $broadcast->recipients()->count());
    }

    public function test_broadcast_can_be_updated_and_generates_lead_recipients(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create([
            'name' => 'WA Broadcast Before',
        ]);

        Lead::factory()->count(3)->create([
            'phone' => '089876543210',
        ]);

        $response = $this->put(route('admin.marketing.whatsapp-broadcasts.update', $broadcast), $this->payload([
            'name' => 'WA Broadcast After',
            'status' => 'sending',
            'recipient_type' => 'lead',
        ]));

        $response->assertRedirect(route('admin.marketing.whatsapp-broadcasts.show', $broadcast));
        $this->assertDatabaseHas('whatsapp_broadcasts', [
            'id' => $broadcast->id,
            'name' => 'WA Broadcast After',
            'status' => 'sending',
        ]);
        $this->assertDatabaseHas('whatsapp_broadcast_recipients', [
            'whatsapp_broadcast_id' => $broadcast->id,
            'recipient_type' => 'lead',
        ]);
    }

    public function test_show_page_displays_recipients_and_status_tracking(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create([
            'name' => 'Tracking Broadcast',
            'total_recipients' => 100,
            'sent_count' => 90,
            'delivered_count' => 80,
            'read_count' => 50,
            'replied_count' => 20,
            'failed_count' => 10,
        ]);

        $broadcast->recipients()->create([
            'recipient_type' => 'customer',
            'recipient_name' => 'Tracking Recipient',
            'phone_number' => '081111111111',
            'status' => 'replied',
        ]);

        $this->get(route('admin.marketing.whatsapp-broadcasts.show', $broadcast))
            ->assertOk()
            ->assertSee('WhatsApp Broadcast Detail')
            ->assertSee('Tracking Broadcast')
            ->assertSee('Broadcast Recipients')
            ->assertSee('Tracking Recipient')
            ->assertSee('Total Recipients')
            ->assertSee('Delivery Rate')
            ->assertSee('Reply Rate');
    }

    public function test_broadcast_can_be_deleted(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create();

        $response = $this->delete(route('admin.marketing.whatsapp-broadcasts.destroy', $broadcast));

        $response->assertRedirect(route('admin.marketing.whatsapp-broadcasts.index'));
        $this->assertDatabaseMissing('whatsapp_broadcasts', ['id' => $broadcast->id]);
    }

    public function test_index_handles_many_recipients_with_pagination(): void
    {
        for ($broadcastNumber = 1; $broadcastNumber <= 12; $broadcastNumber++) {
            $broadcast = WhatsAppBroadcast::factory()->create([
                'name' => sprintf('Heavy Broadcast %02d', $broadcastNumber),
                'status' => 'scheduled',
                'total_recipients' => 100,
                'sent_count' => 25,
                'total_sent' => 25,
                'delivered_count' => 20,
                'read_count' => 10,
                'replied_count' => 5,
                'failed_count' => 2,
                'total_failed' => 2,
                'delivery_rate' => 80,
                'reply_rate' => 5,
            ]);

            $rows = [];
            for ($recipientNumber = 1; $recipientNumber <= 100; $recipientNumber++) {
                $rows[] = [
                    'whatsapp_broadcast_id' => $broadcast->id,
                    'recipient_type' => 'customer',
                    'recipient_id' => null,
                    'recipient_name' => "Recipient {$recipientNumber}",
                    'phone_number' => '62812' . str_pad((string) (($broadcastNumber * 1000) + $recipientNumber), 8, '0', STR_PAD_LEFT),
                    'status' => 'queued',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            WhatsAppBroadcastRecipient::query()->insert($rows);
        }

        $this->get(route('admin.marketing.whatsapp-broadcasts.index'))
            ->assertOk()
            ->assertSee('WA Blast - Kampanye')
            ->assertSee('Heavy Broadcast')
            ->assertSee('Showing')
            ->assertSee('of')
            ->assertSee('results');
    }

    public function test_show_page_paginates_recipients(): void
    {
        $broadcast = WhatsAppBroadcast::factory()->create([
            'name' => 'Paginated Recipient Broadcast',
            'total_recipients' => 30,
        ]);

        WhatsAppBroadcastRecipient::factory()->count(30)->create([
            'whatsapp_broadcast_id' => $broadcast->id,
            'status' => 'queued',
        ]);

        $this->get(route('admin.marketing.whatsapp-broadcasts.show', $broadcast))
            ->assertOk()
            ->assertSee('Paginated Recipient Broadcast')
            ->assertSee('Menampilkan 1-15 dari 30 recipients');
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'marketing_campaign_id' => MarketingCampaign::factory()->create()->id,
            'name' => 'Default WhatsApp Broadcast',
            'message_template' => 'Halo {{name}}, ini update promo terbaru.',
            'target_type' => 'customer',
            'status' => 'scheduled',
            'scheduled_at' => '2026-05-12 10:00:00',
            'sent_at' => '2026-05-12 10:30:00',
            'created_by' => 'Marketing Ops',
            'notes' => 'Broadcast from feature test.',
            'recipient_type' => 'customer',
        ], $overrides);
    }
}
