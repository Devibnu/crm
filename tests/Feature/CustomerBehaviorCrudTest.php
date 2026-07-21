<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerBehavior;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CustomerBehaviorCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_behavior_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.behavior'));

        $response
            ->assertOk()
            ->assertSee('Behavior')
            ->assertSee('CUSTOMER PROFILE 360')
            ->assertSee('Total Behavior')
            ->assertSee('Behavior Records')
            ->assertSee('Active')
            ->assertSee('Loyal')
            ->assertSee('Visible Avg Score')
            ->assertSee('Search behavior...')
            ->assertSee('All Lifecycle')
            ->assertDontSee('Data perilaku customer seperti lifecycle stage, engagement, dan aktivitas terakhir.');
    }

    public function test_behavior_index_uses_reusable_customer_selector_modal_for_create_flow(): void
    {
        $customerA = Customer::factory()->create([
            'name' => 'Customer Selector A',
            'company_name' => 'Alpha Company',
            'email' => 'alpha@example.test',
        ]);
        $customerB = Customer::factory()->create([
            'name' => 'Customer Selector B',
            'company_name' => 'Beta Company',
            'phone' => '08123456789',
        ]);

        $response = $this->get(route('admin.customers.behavior'));

        $response
            ->assertOk()
            ->assertSee('data-customer-selector-trigger="newBehavior"', false)
            ->assertSee('data-customer-selector-modal="newBehavior"', false)
            ->assertSee('Select a customer before creating a behavior record.')
            ->assertSee('Search Customer')
            ->assertSee('data-customer-selector-continue disabled', false)
            ->assertSee('Customer Selector A')
            ->assertSee('Alpha Company')
            ->assertSee('alpha@example.test')
            ->assertSee('Customer Selector B')
            ->assertSee('Beta Company')
            ->assertSee('08123456789')
            ->assertSee('data-url="'.route('admin.customers.behavior.create', ['customer' => $customerA]).'"', false)
            ->assertSee('data-url="'.route('admin.customers.behavior.create', ['customer' => $customerB]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.behavior.create', ['customer' => $customerA]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.behavior.create', ['customer' => $customerB]).'"', false);
    }

    public function test_customer_scoped_behavior_create_page_shows_locked_customer_context(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'PT Behavior Locked',
            'company_name' => 'Krakatau Signals',
            'email' => 'behavior@krakatau.test',
        ]);

        $response = $this->get(route('admin.customers.behavior.create', $customer));

        $response
            ->assertOk()
            ->assertSee('CUSTOMER BEHAVIOR')
            ->assertSee('New Behavior')
            ->assertSee('PT Behavior Locked')
            ->assertSee('Krakatau Signals')
            ->assertSee('behavior@krakatau.test')
            ->assertSee('Customer Context')
            ->assertSee('Lifecycle Information')
            ->assertSee('Engagement Information')
            ->assertSee('Product Interest')
            ->assertSee('Notes')
            ->assertSee('name="customer_id"', false)
            ->assertSee('value="'.$customer->id.'"', false)
            ->assertDontSee('<select name="customer_id"', false)
            ->assertDontSee('Tambahkan data behavior customer untuk insight lifecycle dan engagement.');
    }

    public function test_behavior_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'lifecycle_stage' => 'prospect',
            'engagement_score' => 68,
            'last_activity_at' => '2026-05-03 10:30:00',
            'product_interest' => 'Support Desk',
            'behavior_notes' => 'Shows interest after webinar follow-up.',
        ];

        $response = $this->post(route('admin.customers.behavior.store', $customer), $payload);

        $response->assertRedirect(route('admin.customers.behavior'));

        $this->assertDatabaseHas('customer_behaviors', [
            'customer_id' => $customer->id,
            'lifecycle_stage' => 'prospect',
            'engagement_score' => 68,
        ]);
    }

    public function test_customer_scoped_behavior_ignores_submitted_customer_mismatch(): void
    {
        $routeCustomer = Customer::factory()->create();
        $submittedCustomer = Customer::factory()->create();

        $response = $this->post(route('admin.customers.behavior.store', $routeCustomer), [
            'customer_id' => $submittedCustomer->id,
            'lifecycle_stage' => 'active',
            'engagement_score' => 74,
            'last_activity_at' => '2026-05-03 10:30:00',
            'product_interest' => 'Mismatched Behavior',
            'behavior_notes' => 'Submitted customer should be ignored.',
        ]);

        $response->assertRedirect(route('admin.customers.behavior'));

        $this->assertDatabaseHas('customer_behaviors', [
            'customer_id' => $routeCustomer->id,
            'lifecycle_stage' => 'active',
            'engagement_score' => 74,
            'product_interest' => 'Mismatched Behavior',
        ]);

        $this->assertDatabaseMissing('customer_behaviors', [
            'customer_id' => $submittedCustomer->id,
            'product_interest' => 'Mismatched Behavior',
        ]);
    }

    public function test_behavior_create_validation_errors_preserve_old_input(): void
    {
        $customer = Customer::factory()->create();

        $response = $this->from(route('admin.customers.behavior.create', $customer))
            ->post(route('admin.customers.behavior.store', $customer), [
                'customer_id' => $customer->id,
                'lifecycle_stage' => 'invalid-stage',
                'engagement_score' => 55,
                'product_interest' => 'Preserved Behavior Interest',
                'behavior_notes' => 'Preserved behavior note',
            ]);

        $response
            ->assertRedirect(route('admin.customers.behavior.create', $customer))
            ->assertSessionHasErrors('lifecycle_stage')
            ->assertSessionHasInput('engagement_score', 55)
            ->assertSessionHasInput('product_interest', 'Preserved Behavior Interest')
            ->assertSessionHasInput('behavior_notes', 'Preserved behavior note');
    }

    public function test_behavior_edit_page_is_accessible(): void
    {
        $customer = Customer::factory()->create(['name' => 'Editable Behavior Customer']);
        $behavior = CustomerBehavior::factory()->create([
            'customer_id' => $customer->id,
            'lifecycle_stage' => 'loyal',
        ]);

        $this->get(route('admin.customers.behavior.edit', $behavior))
            ->assertOk()
            ->assertSee('CUSTOMER BEHAVIOR')
            ->assertSee('Edit Behavior')
            ->assertSee('Editable Behavior Customer')
            ->assertSee('Loyal')
            ->assertSee('Customer Context')
            ->assertSee('Lifecycle Information')
            ->assertSee('Engagement Information')
            ->assertSee('Product Interest')
            ->assertSee('Notes')
            ->assertSee('<select name="customer_id" required>', false)
            ->assertDontSee('Perbarui data behavior agar profil customer lebih akurat.');
    }

    public function test_behavior_can_be_updated(): void
    {
        $behavior = CustomerBehavior::factory()->create([
            'lifecycle_stage' => 'lead',
            'engagement_score' => 20,
        ]);

        $newCustomer = Customer::factory()->create();

        $response = $this->put(route('admin.customers.behavior.update', $behavior), [
            'customer_id' => $newCustomer->id,
            'lifecycle_stage' => 'loyal',
            'engagement_score' => 92,
            'last_activity_at' => '2026-05-04 09:00:00',
            'product_interest' => 'CRM',
            'behavior_notes' => 'Consistently active monthly.',
        ]);

        $response->assertRedirect(route('admin.customers.behavior'));

        $this->assertDatabaseHas('customer_behaviors', [
            'id' => $behavior->id,
            'customer_id' => $newCustomer->id,
            'lifecycle_stage' => 'loyal',
            'engagement_score' => 92,
        ]);
    }

    public function test_behavior_update_validation_errors_preserve_old_input(): void
    {
        $behavior = CustomerBehavior::factory()->create();

        $response = $this->from(route('admin.customers.behavior.edit', $behavior))
            ->put(route('admin.customers.behavior.update', $behavior), [
                'customer_id' => $behavior->customer_id,
                'lifecycle_stage' => 'invalid-stage',
                'engagement_score' => 81,
                'product_interest' => 'Preserved Edit Behavior',
                'behavior_notes' => 'Preserved edit behavior note',
            ]);

        $response
            ->assertRedirect(route('admin.customers.behavior.edit', $behavior))
            ->assertSessionHasErrors('lifecycle_stage')
            ->assertSessionHasInput('engagement_score', 81)
            ->assertSessionHasInput('product_interest', 'Preserved Edit Behavior')
            ->assertSessionHasInput('behavior_notes', 'Preserved edit behavior note');
    }

    public function test_behavior_can_be_deleted(): void
    {
        $behavior = CustomerBehavior::factory()->create();

        $response = $this->delete(route('admin.customers.behavior.destroy', $behavior));

        $response->assertRedirect(route('admin.customers.behavior'));

        $this->assertDatabaseMissing('customer_behaviors', [
            'id' => $behavior->id,
        ]);
    }

    public function test_behavior_search_works(): void
    {
        $customer = Customer::factory()->create(['name' => 'Behavior Search Customer']);

        CustomerBehavior::factory()->create([
            'customer_id' => $customer->id,
            'product_interest' => 'Unique Behavior Interest',
        ]);

        CustomerBehavior::factory()->create([
            'product_interest' => 'Other Interest',
        ]);

        $this->get(route('admin.customers.behavior', ['q' => 'Unique Behavior Interest']))
            ->assertOk()
            ->assertSee('Unique Behavior Interest')
            ->assertDontSee('Other Interest');

        $this->get(route('admin.customers.behavior', ['q' => 'Behavior Search Customer']))
            ->assertOk()
            ->assertSee('Unique Behavior Interest');
    }

    public function test_lifecycle_stage_filter_works(): void
    {
        CustomerBehavior::factory()->create([
            'lifecycle_stage' => 'active',
            'product_interest' => 'Active Product',
        ]);

        CustomerBehavior::factory()->create([
            'lifecycle_stage' => 'lead',
            'product_interest' => 'Lead Product',
        ]);

        $this->get(route('admin.customers.behavior', ['lifecycle_stage' => 'active']))
            ->assertOk()
            ->assertSee('Active Product')
            ->assertDontSee('Lead Product');
    }

    public function test_behavior_index_pagination_preserves_search_and_filter_query(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            CustomerBehavior::factory()->create([
                'lifecycle_stage' => 'active',
                'product_interest' => "Filtered Behavior {$i}",
            ]);
        }

        $this->get(route('admin.customers.behavior', [
            'q' => 'Filtered Behavior',
            'lifecycle_stage' => 'active',
        ]))
            ->assertOk()
            ->assertSee('Filtered Behavior')
            ->assertSee('q=Filtered%20Behavior&amp;lifecycle_stage=active&amp;page=2', false);
    }

    public function test_behavior_index_actions_respect_customer_permissions(): void
    {
        $behavior = CustomerBehavior::factory()->create([
            'product_interest' => 'Permission Behavior',
        ]);
        $role = Role::create(['name' => 'behavior_viewer_only', 'guard_name' => 'web']);
        $role->syncPermissions(['customers.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.customers.behavior'))
            ->assertOk()
            ->assertSee('Permission Behavior')
            ->assertDontSee('New Behavior')
            ->assertDontSee('data-customer-selector-trigger="newBehavior"', false)
            ->assertDontSee('data-customer-selector-modal="newBehavior"', false)
            ->assertDontSee(route('admin.customers.behavior.edit', $behavior), false)
            ->assertDontSee(route('admin.customers.behavior.destroy', $behavior), false)
            ->assertDontSee('Delete');
    }

    public function test_behavior_routes_reject_unauthorized_users(): void
    {
        $customer = Customer::factory()->create();
        $behavior = CustomerBehavior::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.customers.behavior'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.customers.behavior.create', $customer))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.customers.behavior.store', $customer), [
                'customer_id' => $customer->id,
                'lifecycle_stage' => 'active',
                'engagement_score' => 75,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.customers.behavior.edit', $behavior))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.customers.behavior.update', $behavior), [
                'customer_id' => $behavior->customer_id,
                'lifecycle_stage' => 'active',
                'engagement_score' => 75,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.customers.behavior.destroy', $behavior))
            ->assertForbidden();
    }

    public function test_behavior_empty_state_respects_create_permission(): void
    {
        $customer = Customer::factory()->create();

        $this->get(route('admin.customers.behavior', ['q' => 'missing behavior']))
            ->assertOk()
            ->assertSee('No Behavior Yet')
            ->assertSee('Customer lifecycle, engagement, and activity signals will appear here.')
            ->assertSee('data-customer-selector-trigger="newBehavior"', false)
            ->assertSee('data-url="'.route('admin.customers.behavior.create', ['customer' => $customer]).'"', false)
            ->assertDontSee('href="'.route('admin.customers.behavior.create', ['customer' => $customer]).'"', false);
    }

    public function test_behavior_create_and_edit_routes_highlight_behavior_sidebar(): void
    {
        $customer = Customer::factory()->create();
        $behavior = CustomerBehavior::factory()->create();
        $activeBehaviorNavigation = 'href="'.route('admin.customers.behavior').'" class="nav-link parent compact active"';
        $inactiveCustomerListNavigation = 'href="'.route('admin.customers.index').'" class="nav-link parent compact active"';

        $this->get(route('admin.customers.behavior.create', $customer))
            ->assertOk()
            ->assertSee($activeBehaviorNavigation, false)
            ->assertDontSee($inactiveCustomerListNavigation, false);

        $this->get(route('admin.customers.behavior.edit', $behavior))
            ->assertOk()
            ->assertSee($activeBehaviorNavigation, false)
            ->assertDontSee($inactiveCustomerListNavigation, false);
    }

    public function test_customer_show_page_links_to_behavior_module(): void
    {
        $customer = Customer::factory()->create();

        CustomerBehavior::factory()->create([
            'customer_id' => $customer->id,
            'lifecycle_stage' => 'churned',
            'engagement_score' => 12,
            'product_interest' => 'Behavior Show Product',
            'behavior_notes' => 'No activity in the last quarter.',
        ]);

        $response = $this->get(route('admin.customers.show', $customer));

        $response
            ->assertOk()
            ->assertSee('Behavior')
            ->assertSee(route('admin.customers.behavior', ['q' => $customer->name]), false)
            ->assertDontSee('No activity in the last quarter.');
    }
}
