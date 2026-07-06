<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\WhatsAppConversation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class WhatsAppCustomerBackfillCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_conversation_without_customer_id_gets_customer(): void
    {
        $conversation = WhatsAppConversation::create([
            'customer_id' => null,
            'lead_id' => null,
            'contact_name' => 'Vendy',
            'phone_number' => '08179560856',
            'channel' => 'whatsapp',
            'last_message' => 'Halo',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        Artisan::call('whatsapp:backfill-customers', [
            '--conversation_id' => $conversation->id,
        ]);

        $customer = Customer::query()->where('whatsapp', '628179560856')->firstOrFail();
        $output = Artisan::output();

        $this->assertSame('Vendy', $customer->name);
        $this->assertSame($customer->id, $conversation->fresh()->customer_id);
        $this->assertStringContainsString('customers_created', $output);
        $this->assertStringContainsString('conversations_linked', $output);
    }

    public function test_old_lead_with_same_number_is_linked_to_backfilled_customer(): void
    {
        $lead = Lead::factory()->create([
            'customer_id' => null,
            'name' => 'Vendy Lead',
            'phone' => '+62 817 9560 856',
            'whatsapp' => null,
            'source' => 'whatsapp',
            'lead_source' => 'whatsapp',
        ]);
        $conversation = WhatsAppConversation::create([
            'customer_id' => null,
            'lead_id' => null,
            'contact_name' => 'Vendy',
            'phone_number' => '628179560856',
            'channel' => 'whatsapp',
            'last_message' => 'Saya mau follow up',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        Artisan::call('whatsapp:backfill-customers', [
            '--conversation_id' => $conversation->id,
        ]);

        $customer = Customer::query()->where('whatsapp', '628179560856')->firstOrFail();

        $this->assertSame($customer->id, $lead->fresh()->customer_id);
        $this->assertSame($customer->id, $conversation->fresh()->customer_id);
        $this->assertSame($lead->id, $conversation->fresh()->lead_id);
    }

    public function test_same_number_does_not_create_duplicate_customers(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Existing Vendy',
            'phone' => '08179560856',
            'whatsapp' => null,
        ]);
        $firstConversation = WhatsAppConversation::create([
            'customer_id' => null,
            'lead_id' => null,
            'contact_name' => 'Vendy',
            'phone_number' => '+62 817 9560 856',
            'channel' => 'whatsapp',
            'last_message' => 'First',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $secondConversation = WhatsAppConversation::create([
            'customer_id' => null,
            'lead_id' => null,
            'contact_name' => 'Vendy',
            'phone_number' => '628179560856',
            'channel' => 'whatsapp',
            'last_message' => 'Second',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        Artisan::call('whatsapp:backfill-customers');

        $this->assertDatabaseCount('customers', 1);
        $this->assertSame($customer->id, $firstConversation->fresh()->customer_id);
        $this->assertSame($customer->id, $secondConversation->fresh()->customer_id);
    }

    public function test_old_conversation_lead_opportunity_and_quotation_chain_get_same_customer(): void
    {
        $lead = Lead::factory()->create([
            'customer_id' => null,
            'name' => 'Vendy Chain Lead',
            'phone' => '08179560856',
            'whatsapp' => null,
            'source' => 'whatsapp',
            'lead_source' => 'whatsapp',
        ]);
        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'customer_id' => null,
            'title' => 'Vendy Chain Opportunity',
        ]);
        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'lead_id' => $lead->id,
            'customer_id' => null,
            'quote_number' => 'QTN-VENDY-CHAIN-001',
        ]);
        $conversation = WhatsAppConversation::create([
            'customer_id' => null,
            'lead_id' => null,
            'contact_name' => 'Vendy',
            'phone_number' => '+62 817 9560 856',
            'channel' => 'whatsapp',
            'last_message' => 'Saya mau lanjut',
            'last_message_at' => now(),
            'status' => 'open',
        ]);

        Artisan::call('whatsapp:backfill-customers', [
            '--conversation_id' => $conversation->id,
        ]);

        $customer = Customer::query()->where('whatsapp', '628179560856')->firstOrFail();
        $output = Artisan::output();

        $this->assertSame($customer->id, $conversation->fresh()->customer_id);
        $this->assertSame($lead->id, $conversation->fresh()->lead_id);
        $this->assertSame($customer->id, $lead->fresh()->customer_id);
        $this->assertSame($customer->id, $opportunity->fresh()->customer_id);
        $this->assertSame($customer->id, $quotation->fresh()->customer_id);
        $this->assertStringContainsString('opportunities_linked', $output);
        $this->assertStringContainsString('quotations_linked', $output);
    }
}
