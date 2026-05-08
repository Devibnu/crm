<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OpportunityCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_opportunity_index_is_accessible(): void
    {
        $this->get(route('admin.sales.opportunities'))
            ->assertOk()
            ->assertSee('Opportunity Management');
    }

    public function test_opportunity_can_be_created(): void
    {
        $lead = Lead::factory()->create();
        $customer = Customer::factory()->create();

        $payload = [
            'lead_id' => $lead->id,
            'customer_id' => $customer->id,
            'title' => 'Opportunity Test Title',
            'company_name' => 'Opportunity Company',
            'contact_name' => 'Opportunity Contact',
            'estimated_value' => 250000000,
            'probability' => 70,
            'status' => 'proposal',
            'expected_close_date' => '2026-12-31',
            'assigned_to' => 'Sales Opportunity',
            'notes' => 'Opportunity created from feature test.',
        ];

        $response = $this->post(route('admin.sales.opportunities.store'), $payload);

        $response->assertRedirect(route('admin.sales.opportunities'));

        $this->assertDatabaseHas('opportunities', [
            'title' => 'Opportunity Test Title',
            'status' => 'proposal',
            'probability' => 70,
        ]);
    }

    public function test_opportunity_show_and_edit_pages_are_accessible(): void
    {
        $opportunity = Opportunity::factory()->create();

        $this->get(route('admin.sales.opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee($opportunity->title);

        $this->get(route('admin.sales.opportunities.edit', $opportunity))
            ->assertOk()
            ->assertSee('Edit Opportunity');
    }

    public function test_opportunity_can_be_updated(): void
    {
        $opportunity = Opportunity::factory()->create([
            'title' => 'Before Opportunity Update',
            'status' => 'open',
            'probability' => 20,
        ]);

        $response = $this->put(route('admin.sales.opportunities.update', $opportunity), [
            'lead_id' => null,
            'customer_id' => null,
            'title' => 'After Opportunity Update',
            'company_name' => 'Updated Opportunity Co',
            'contact_name' => 'Updated Contact',
            'estimated_value' => 98000000,
            'probability' => 85,
            'status' => 'negotiation',
            'expected_close_date' => '2026-11-15',
            'assigned_to' => 'Sales Updated',
            'notes' => 'Opportunity updated from feature test.',
        ]);

        $response->assertRedirect(route('admin.sales.opportunities.show', $opportunity));

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'title' => 'After Opportunity Update',
            'status' => 'negotiation',
            'probability' => 85,
        ]);
    }

    public function test_opportunity_can_be_deleted(): void
    {
        $opportunity = Opportunity::factory()->create();

        $response = $this->delete(route('admin.sales.opportunities.destroy', $opportunity));

        $response->assertRedirect(route('admin.sales.opportunities'));

        $this->assertDatabaseMissing('opportunities', [
            'id' => $opportunity->id,
        ]);
    }

    public function test_opportunity_search_works(): void
    {
        $match = Opportunity::factory()->create([
            'title' => 'Alpha Opportunity Search',
            'company_name' => 'Search Opportunity Corp',
            'contact_name' => 'Opportunity Search Contact',
            'assigned_to' => 'Opportunity Search Owner',
        ]);

        $other = Opportunity::factory()->create([
            'title' => 'Other Opportunity Name',
            'company_name' => 'Other Opportunity Corp',
        ]);

        $this->get(route('admin.sales.opportunities', ['q' => 'Alpha Opportunity Search']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee($other->title);

        $this->get(route('admin.sales.opportunities', ['q' => 'Opportunity Search Owner']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee($other->title);
    }

    public function test_opportunity_status_filter_works(): void
    {
        $won = Opportunity::factory()->create([
            'title' => 'Won Opportunity',
            'status' => 'won',
        ]);

        $lost = Opportunity::factory()->create([
            'title' => 'Lost Opportunity',
            'status' => 'lost',
        ]);

        $this->get(route('admin.sales.opportunities', ['status' => 'won']))
            ->assertOk()
            ->assertSee($won->title)
            ->assertDontSee($lost->title);
    }
}
