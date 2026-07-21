<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerPreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerPreferenceCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_preference_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.preferences'));

        $response
            ->assertOk()
            ->assertSee('Preferences')
            ->assertSee('CUSTOMER PROFILE 360')
            ->assertSee('Total Preferences')
            ->assertSee('Consent Yes')
            ->assertSee('WhatsApp')
            ->assertSee('Email')
            ->assertSee('Search preferences...')
            ->assertSee('All Channels')
            ->assertSee('All Consent')
            ->assertDontSee('Preferensi customer seperti channel komunikasi, minat produk, consent, dan segmentasi.');
    }

    public function test_customer_scoped_preference_create_page_shows_locked_customer_context(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'PT Preference Locked',
            'company_name' => 'Krakatau Relationship',
            'email' => 'preference@krakatau.test',
        ]);

        $response = $this->get(route('admin.customers.preferences.create', $customer));

        $response
            ->assertOk()
            ->assertSee('CUSTOMER PREFERENCES')
            ->assertSee('New Preference')
            ->assertSee('PT Preference Locked')
            ->assertSee('Krakatau Relationship')
            ->assertSee('preference@krakatau.test')
            ->assertSee('Customer Context')
            ->assertSee('Communication Preferences')
            ->assertSee('Product / Segment Preferences')
            ->assertSee('Notes')
            ->assertSee('name="customer_id"', false)
            ->assertSee('value="'.$customer->id.'"', false)
            ->assertDontSee('<select name="customer_id"', false)
            ->assertDontSee('Tambahkan preferensi customer agar komunikasi dan segmentasi lebih tepat.');
    }

    public function test_preference_index_uses_customer_selector_modal_for_create_flow(): void
    {
        $customerA = Customer::factory()->create([
            'name' => 'Preference Selector A',
            'company_name' => 'Alpha Preference',
            'email' => 'preference-a@example.test',
        ]);
        $customerB = Customer::factory()->create([
            'name' => 'Preference Selector B',
            'company_name' => 'Beta Preference',
            'phone' => '0833333333',
        ]);

        $this->get(route('admin.customers.preferences'))
            ->assertOk()
            ->assertSee('data-customer-selector-trigger="newPreference"', false)
            ->assertSee('data-customer-selector-modal="newPreference"', false)
            ->assertSee('Select a customer before creating a preference record.')
            ->assertSee('data-customer-selector-continue disabled', false)
            ->assertSee('Preference Selector A')
            ->assertSee('Preference Selector B')
            ->assertSee('data-url="'.route('admin.customers.preferences.create', ['customer' => $customerA]).'"', false)
            ->assertSee('data-url="'.route('admin.customers.preferences.create', ['customer' => $customerB]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.preferences.create', ['customer' => $customerA]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.preferences.create', ['customer' => $customerB]).'"', false);
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

    public function test_customer_scoped_preference_ignores_submitted_customer_mismatch(): void
    {
        $routeCustomer = Customer::factory()->create();
        $submittedCustomer = Customer::factory()->create();

        $response = $this->post(route('admin.customers.preferences.store', $routeCustomer), [
            'customer_id' => $submittedCustomer->id,
            'preferred_channel' => 'email',
            'product_interest' => 'Mismatched Preference',
            'communication_consent' => '1',
            'segment' => 'Enterprise',
            'notes' => 'Submitted customer should be ignored.',
        ]);

        $response->assertRedirect(route('admin.customers.preferences'));

        $this->assertDatabaseHas('customer_preferences', [
            'customer_id' => $routeCustomer->id,
            'preferred_channel' => 'email',
            'product_interest' => 'Mismatched Preference',
        ]);

        $this->assertDatabaseMissing('customer_preferences', [
            'customer_id' => $submittedCustomer->id,
            'product_interest' => 'Mismatched Preference',
        ]);
    }

    public function test_preference_create_validation_errors_preserve_old_input(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->from(route('admin.customers.preferences.create', $customer))
            ->post(route('admin.customers.preferences.store', $customer), [
                'customer_id' => $customer->id,
                'preferred_channel' => 'invalid-channel',
                'product_interest' => 'Preserved Product Interest',
                'segment' => 'Preserved Segment',
                'notes' => 'Preserved preference note',
            ]);

        $response
            ->assertRedirect(route('admin.customers.preferences.create', $customer))
            ->assertSessionHasErrors('preferred_channel')
            ->assertSessionHasInput('product_interest', 'Preserved Product Interest')
            ->assertSessionHasInput('segment', 'Preserved Segment')
            ->assertSessionHasInput('notes', 'Preserved preference note');
    }

    public function test_preference_edit_page_is_accessible(): void
    {
        $customer = Customer::factory()->create(['name' => 'Editable Preference Customer']);
        $preference = CustomerPreference::factory()->create([
            'customer_id' => $customer->id,
            'preferred_channel' => 'meeting',
        ]);

        $this->get(route('admin.customers.preferences.edit', $preference))
            ->assertOk()
            ->assertSee('CUSTOMER PREFERENCES')
            ->assertSee('Edit Preference')
            ->assertSee('Editable Preference Customer')
            ->assertSee('Meeting')
            ->assertSee('Customer Context')
            ->assertSee('Communication Preferences')
            ->assertSee('Product / Segment Preferences')
            ->assertSee('Notes')
            ->assertSee('<select name="customer_id" required>', false)
            ->assertDontSee('Perbarui preferensi customer untuk menjaga akurasi komunikasi.');
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

    public function test_preference_update_validation_errors_preserve_old_input(): void
    {
        $preference = CustomerPreference::factory()->create();

        $response = $this->from(route('admin.customers.preferences.edit', $preference))
            ->put(route('admin.customers.preferences.update', $preference), [
                'customer_id' => $preference->customer_id,
                'preferred_channel' => 'invalid-channel',
                'product_interest' => 'Preserved Edit Product',
                'segment' => 'Preserved Edit Segment',
                'notes' => 'Preserved edit preference note',
            ]);

        $response
            ->assertRedirect(route('admin.customers.preferences.edit', $preference))
            ->assertSessionHasErrors('preferred_channel')
            ->assertSessionHasInput('product_interest', 'Preserved Edit Product')
            ->assertSessionHasInput('segment', 'Preserved Edit Segment')
            ->assertSessionHasInput('notes', 'Preserved edit preference note');
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

    public function test_preference_index_pagination_preserves_search_and_filter_query(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            CustomerPreference::factory()->create([
                'preferred_channel' => 'whatsapp',
                'product_interest' => "Filtered Preference {$i}",
            ]);
        }

        $this->get(route('admin.customers.preferences', [
            'q' => 'Filtered Preference',
            'preferred_channel' => 'whatsapp',
        ]))
            ->assertOk()
            ->assertSee('Filtered Preference')
            ->assertSee('q=Filtered%20Preference&amp;preferred_channel=whatsapp&amp;page=2', false);
    }

    public function test_preference_index_actions_respect_customer_permissions(): void
    {
        $preference = CustomerPreference::factory()->create([
            'product_interest' => 'Permission Preference',
        ]);
        $role = Role::create(['name' => 'preference_viewer_only', 'guard_name' => 'web']);
        $role->syncPermissions(['customers.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.customers.preferences'))
            ->assertOk()
            ->assertSee('Permission Preference')
            ->assertDontSee('New Preference')
            ->assertDontSee('data-customer-selector-trigger="newPreference"', false)
            ->assertDontSee('data-customer-selector-modal="newPreference"', false)
            ->assertDontSee(route('admin.customers.preferences.edit', $preference), false)
            ->assertDontSee(route('admin.customers.preferences.destroy', $preference), false)
            ->assertDontSee('Delete');
    }

    public function test_preference_routes_reject_unauthorized_users(): void
    {
        $customer = Customer::factory()->create();
        $preference = CustomerPreference::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.customers.preferences'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.customers.preferences.create', $customer))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.customers.preferences.store', $customer), [
                'customer_id' => $customer->id,
                'preferred_channel' => 'whatsapp',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.customers.preferences.edit', $preference))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.customers.preferences.update', $preference), [
                'customer_id' => $preference->customer_id,
                'preferred_channel' => 'email',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.customers.preferences.destroy', $preference))
            ->assertForbidden();
    }

    public function test_preference_empty_state_respects_create_permission(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('admin.customers.preferences', ['q' => 'missing preference']))
            ->assertOk()
            ->assertSee('No Preferences Yet')
            ->assertSee('Customer communication preferences and segmentation details will appear here.')
            ->assertSee('data-customer-selector-trigger="newPreference"', false)
            ->assertSee('data-url="'.route('admin.customers.preferences.create', ['customer' => $customer]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.preferences.create', ['customer' => $customer]).'"', false);
    }

    public function test_customer_show_page_links_to_preferences_module(): void
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
            ->assertSee(route('admin.customers.preferences', ['q' => $customer->name]), false)
            ->assertDontSee('Show Preference Product')
            ->assertDontSee('Government');
    }
}
