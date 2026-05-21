<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppAutoLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_new_number_creates_lead_automatically(): void
    {
        $sales = User::factory()->create(['name' => 'Default Sales']);
        $sales->assignRole('sales');

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '+62 812 3456 0001',
            'sender_name' => 'Inbound WhatsApp Sender',
            'message' => 'Halo, saya tertarik',
            'timestamp' => '2026-05-21 09:30:00',
        ])->assertOk();

        $this->assertDatabaseHas('leads', [
            'name' => 'Inbound WhatsApp Sender',
            'phone' => '6281234560001',
            'whatsapp' => '6281234560001',
            'source' => 'whatsapp',
            'lead_source' => 'whatsapp',
            'status' => 'new',
            'priority' => 'medium',
            'assigned_to' => 'Default Sales',
            'last_whatsapp_message' => 'Halo, saya tertarik',
            'notes' => 'Auto generated from WhatsApp inbound webhook.',
        ]);

        $lead = Lead::query()->where('phone', '6281234560001')->firstOrFail();

        $customer = Customer::query()->where('whatsapp', '6281234560001')->firstOrFail();

        $this->assertDatabaseHas('omnichannel_messages', [
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'sender_contact' => '6281234560001',
            'message' => 'Halo, saya tertarik',
        ]);
    }

    public function test_inbound_existing_lead_does_not_duplicate_and_updates_latest_whatsapp_fields(): void
    {
        $lead = Lead::factory()->create([
            'phone' => '081277788899',
            'whatsapp' => null,
            'source' => 'whatsapp',
            'lead_source' => 'whatsapp',
            'last_whatsapp_message' => 'Old message',
            'last_whatsapp_at' => now()->subDay(),
        ]);

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '6281277788899',
            'name' => 'Existing Lead',
            'message' => 'Latest reply',
        ])->assertOk();

        $this->assertDatabaseCount('leads', 1);

        $lead->refresh();

        $this->assertSame('Latest reply', $lead->last_whatsapp_message);
        $this->assertNotNull($lead->last_whatsapp_at);
        $this->assertDatabaseHas('omnichannel_messages', [
            'lead_id' => $lead->id,
            'sender_contact' => '6281277788899',
            'message' => 'Latest reply',
        ]);
    }

    public function test_inbound_existing_customer_does_not_create_lead(): void
    {
        $customer = Customer::factory()->create([
            'phone' => '081299900011',
            'whatsapp' => null,
        ]);

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '+6281299900011',
            'name' => 'Existing Customer',
            'message' => 'Saya customer lama',
        ])->assertOk();

        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseHas('omnichannel_messages', [
            'customer_id' => $customer->id,
            'lead_id' => null,
            'sender_contact' => '6281299900011',
        ]);
    }

    public function test_phone_normalization_is_consistent_for_auto_leads(): void
    {
        foreach (['+62 811 0000 0001', '6281100000001', '081100000001'] as $phone) {
            $this->postJson(route('webhooks.whatsapp.fonnte'), [
                'sender' => $phone,
                'message' => 'Normalize lead',
            ])->assertOk();
        }

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', [
            'phone' => '6281100000001',
            'lead_source' => 'whatsapp',
            'last_whatsapp_message' => 'Normalize lead',
        ]);
    }

    public function test_sender_name_is_used_when_available_and_falls_back_to_phone(): void
    {
        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081222233344',
            'name' => 'Named Sender',
            'message' => 'Dengan nama',
        ])->assertOk();

        $this->postJson(route('webhooks.whatsapp.fonnte'), [
            'sender' => '081222233345',
            'message' => 'Tanpa nama',
        ])->assertOk();

        $this->assertDatabaseHas('leads', [
            'phone' => '6281222233344',
            'name' => 'Named Sender',
        ]);

        $this->assertDatabaseHas('leads', [
            'phone' => '6281222233345',
            'name' => 'WhatsApp Lead 6281222233345',
        ]);
    }

    public function test_lead_management_displays_whatsapp_source_badge(): void
    {
        Lead::factory()->create([
            'name' => 'WhatsApp Badge Lead',
            'source' => 'whatsapp',
            'lead_source' => 'whatsapp',
        ]);

        $this->get(route('admin.sales.leads'))
            ->assertOk()
            ->assertSee('WhatsApp Badge Lead')
            ->assertSee('WhatsApp');
    }
}
