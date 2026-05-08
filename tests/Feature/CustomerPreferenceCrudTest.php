<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPreference;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPreferenceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_preference_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.preferences'));

        $response
            ->assertOk()
            ->assertSee('Preferences');
    }

    public function test_preference_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'preferred_channel' => 'whatsapp',
            'product_interest' => 'CRM Enterprise',
            'communication_consent' => '1',
            'segment' => 'Enterprise',
            'notes' => 'Prefers WhatsApp updates.',
        ];

        $response = $this->post(route('admin.customers.preferences.store', $customer), $payload);

        $response->assertRedirect(route('admin.customers.preferences'));

        $this->assertDatabaseHas('customer_preferences', [
            'customer_id' => $customer->id,
            'preferred_channel' => 'whatsapp',
            'product_interest' => 'CRM Enterprise',
            'communication_consent' => true,
        ]);
    }

    public function test_preference_edit_page_is_accessible(): void
    {
        $preference = CustomerPreference::factory()->create();

        $this->get(route('admin.customers.preferences.edit', $preference))
            ->assertOk()
            ->assertSee('Edit Preference');
    }

    public function test_preference_can_be_updated(): void
    {
        $preference = CustomerPreference::factory()->create([
            'preferred_channel' => 'none',
            'communication_consent' => false,
        ]);

        $newCustomer = Customer::factory()->create();

        $response = $this->put(route('admin.customers.preferences.update', $preference), [
            'customer_id' => $newCustomer->id,
            'preferred_channel' => 'email',
            'product_interest' => 'Service Desk',
            'communication_consent' => '1',
            'segment' => 'SMB',
            'notes' => 'Wants email newsletter',
        ]);

        $response->assertRedirect(route('admin.customers.preferences'));

        $this->assertDatabaseHas('customer_preferences', [
            'id' => $preference->id,
            'customer_id' => $newCustomer->id,
            'preferred_channel' => 'email',
            'communication_consent' => true,
        ]);
    }

    public function test_preference_can_be_deleted(): void
    {
        $preference = CustomerPreference::factory()->create();

        $response = $this->delete(route('admin.customers.preferences.destroy', $preference));

        $response->assertRedirect(route('admin.customers.preferences'));

        $this->assertDatabaseMissing('customer_preferences', [
            'id' => $preference->id,
        ]);
    }

    public function test_preference_search_works(): void
    {
        $customer = Customer::factory()->create(['name' => 'Preference Search Customer']);

        CustomerPreference::factory()->create([
            'customer_id' => $customer->id,
            'product_interest' => 'Unique Product Interest',
            'segment' => 'Unique Segment',
        ]);

        CustomerPreference::factory()->create([
            'product_interest' => 'Other Product',
            'segment' => 'Other Segment',
        ]);

        $this->get(route('admin.customers.preferences', ['q' => 'Unique Product Interest']))
            ->assertOk()
            ->assertSee('Unique Product Interest')
            ->assertDontSee('Other Product');

        $this->get(route('admin.customers.preferences', ['q' => 'Preference Search Customer']))
            ->assertOk()
            ->assertSee('Unique Product Interest');
    }

    public function test_preferred_channel_filter_works(): void
    {
        CustomerPreference::factory()->create([
            'preferred_channel' => 'whatsapp',
            'product_interest' => 'WhatsApp Product',
        ]);

        CustomerPreference::factory()->create([
            'preferred_channel' => 'email',
            'product_interest' => 'Email Product',
        ]);

        $this->get(route('admin.customers.preferences', ['preferred_channel' => 'whatsapp']))
            ->assertOk()
            ->assertSee('WhatsApp Product')
            ->assertDontSee('Email Product');
    }

    public function test_communication_consent_filter_works(): void
    {
        CustomerPreference::factory()->create([
            'communication_consent' => true,
            'product_interest' => 'Consent Yes Product',
        ]);

        CustomerPreference::factory()->create([
            'communication_consent' => false,
            'product_interest' => 'Consent No Product',
        ]);

        $this->get(route('admin.customers.preferences', ['communication_consent' => '1']))
            ->assertOk()
            ->assertSee('Consent Yes Product')
            ->assertDontSee('Consent No Product');
    }

    public function test_customer_show_page_displays_preferences(): void
    {
        $customer = Customer::factory()->create();

        CustomerPreference::factory()->create([
            'customer_id' => $customer->id,
            'preferred_channel' => 'meeting',
            'product_interest' => 'Show Preference Product',
            'communication_consent' => true,
            'segment' => 'Government',
            'notes' => 'Wants quarterly meeting',
        ]);

        $response = $this->get(route('admin.customers.show', $customer));

        $response
            ->assertOk()
            ->assertSee('Preferences')
            ->assertSee('Show Preference Product')
            ->assertSee('Government');
    }
}
