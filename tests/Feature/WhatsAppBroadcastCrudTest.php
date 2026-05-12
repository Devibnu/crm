<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\WhatsAppBroadcast;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppBroadcastCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.whatsapp-broadcasts.index'))
            ->assertOk()
            ->assertSee('WhatsApp Broadcast')
            ->assertSee('Total Broadcasts')
            ->assertSee('Scheduled')
            ->assertSee('Completed')
            ->assertSee('Total Replies');
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
