<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerBehavior;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerBehaviorCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_behavior_index_is_accessible(): void
    {
        $response = $this->get(route('admin.customers.behavior'));

        $response
            ->assertOk()
            ->assertSee('Behavior');
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

    public function test_behavior_edit_page_is_accessible(): void
    {
        $behavior = CustomerBehavior::factory()->create();

        $this->get(route('admin.customers.behavior.edit', $behavior))
            ->assertOk()
            ->assertSee('Edit Behavior');
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

    public function test_customer_show_page_displays_behavior_data(): void
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
            ->assertSee('Behavior Show Product')
            ->assertSee('No activity in the last quarter.');
    }
}
