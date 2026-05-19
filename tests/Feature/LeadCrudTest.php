<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_index_is_accessible(): void
    {
        $this->get(route('admin.sales.leads'))
            ->assertOk()
            ->assertSee('Lead Management');
    }

    public function test_lead_can_be_created(): void
    {
        $customer = Customer::factory()->create();

        $payload = [
            'customer_id' => $customer->id,
            'name' => 'Lead Test Name',
            'company_name' => 'Lead Test Company',
            'email' => 'lead-create@example.com',
            'phone' => '081111111111',
            'source' => 'Website',
            'status' => 'new',
            'priority' => 'high',
            'assigned_to' => 'Sales One',
            'notes' => 'Lead created from feature test.',
        ];

        $response = $this->post(route('admin.sales.leads.store'), $payload);

        $response->assertRedirect(route('admin.sales.leads'));

        $this->assertDatabaseHas('leads', [
            'name' => 'Lead Test Name',
            'status' => 'new',
            'priority' => 'high',
        ]);
    }

    public function test_lead_show_and_edit_pages_are_accessible(): void
    {
        $lead = Lead::factory()->create();

        $this->get(route('admin.sales.leads.show', $lead))
            ->assertOk()
            ->assertSee($lead->name);

        $this->get(route('admin.sales.leads.edit', $lead))
            ->assertOk()
            ->assertSee('Edit Lead');
    }

    public function test_lead_can_be_updated(): void
    {
        $lead = Lead::factory()->create([
            'name' => 'Before Lead Update',
            'email' => 'before-lead-update@example.com',
            'status' => 'new',
            'priority' => 'medium',
        ]);

        $response = $this->put(route('admin.sales.leads.update', $lead), [
            'customer_id' => null,
            'name' => 'After Lead Update',
            'company_name' => 'Updated Lead Co',
            'email' => 'after-lead-update@example.com',
            'phone' => '082222222222',
            'source' => 'Referral',
            'status' => 'qualified',
            'priority' => 'low',
            'assigned_to' => 'Sales Two',
            'notes' => 'Lead updated from feature test.',
        ]);

        $response->assertRedirect(route('admin.sales.leads.show', $lead));

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'After Lead Update',
            'status' => 'qualified',
            'priority' => 'low',
        ]);
    }

    public function test_lead_can_be_deleted(): void
    {
        $lead = Lead::factory()->create();

        $response = $this->delete(route('admin.sales.leads.destroy', $lead));

        $response->assertRedirect(route('admin.sales.leads'));

        $this->assertDatabaseMissing('leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_lead_search_works(): void
    {
        $match = Lead::factory()->create([
            'name' => 'Alpha Lead Search',
            'company_name' => 'Search Corp',
            'email' => 'search-lead@example.com',
            'phone' => '083333333333',
            'assigned_to' => 'Search Person',
        ]);

        $other = Lead::factory()->create([
            'name' => 'Other Lead Name',
            'company_name' => 'Other Corp',
            'email' => 'other-lead@example.com',
        ]);

        $this->get(route('admin.sales.leads', ['q' => 'Alpha Lead Search']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);

        $this->get(route('admin.sales.leads', ['q' => 'Search Person']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);
    }

    public function test_lead_status_filter_works(): void
    {
        $qualified = Lead::factory()->create([
            'name' => 'Qualified Lead',
            'status' => 'qualified',
        ]);

        $unqualified = Lead::factory()->create([
            'name' => 'Unqualified Lead',
            'status' => 'unqualified',
        ]);

        $this->get(route('admin.sales.leads', ['status' => 'qualified']))
            ->assertOk()
            ->assertSee($qualified->name)
            ->assertDontSee($unqualified->name);
    }

    public function test_lead_priority_filter_works(): void
    {
        $high = Lead::factory()->create([
            'name' => 'High Priority Lead',
            'priority' => 'high',
        ]);

        $low = Lead::factory()->create([
            'name' => 'Low Priority Lead',
            'priority' => 'low',
        ]);

        $this->get(route('admin.sales.leads', ['priority' => 'high']))
            ->assertOk()
            ->assertSee($high->name)
            ->assertDontSee($low->name);
    }
}
