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
            ->assertSee('Opportunity Management')
            ->assertSee('Open Opportunities')
            ->assertSee('Show 10');
    }

    public function test_opportunity_index_uses_custom_delete_confirmation_modal(): void
    {
        $opportunity = Opportunity::factory()->create(['title' => 'Opportunity Modal Confirmation']);

        $this->get(route('admin.sales.opportunities'))
            ->assertOk()
            ->assertSee('Hapus Opportunity?')
            ->assertSee('Ya, Hapus Opportunity')
            ->assertSee('data-delete-action="'.route('admin.sales.opportunities.destroy', $opportunity).'"', false)
            ->assertSee('data-opportunity-name="Opportunity Modal Confirmation"', false)
            ->assertDontSee("confirm('Delete opportunity ini?')", false);
    }

    public function test_opportunity_index_supports_whitelisted_per_page_values(): void
    {
        Opportunity::factory()->count(120)->create([
            'title' => 'Pagination Opportunity',
            'status' => 'proposal',
        ]);

        foreach ([10, 20, 50, 100] as $perPage) {
            $this->get(route('admin.sales.opportunities', ['per_page' => $perPage]))
                ->assertOk()
                ->assertViewHas('opportunities', fn ($opportunities) => $opportunities->perPage() === $perPage && $opportunities->count() === $perPage)
                ->assertSee('<option value="'.$perPage.'" selected>Show '.$perPage.'</option>', false);
        }

        $this->get(route('admin.sales.opportunities', ['per_page' => 999]))
            ->assertOk()
            ->assertViewHas('opportunities', fn ($opportunities) => $opportunities->perPage() === 10 && $opportunities->count() === 10)
            ->assertSee('<option value="10" selected>Show 10</option>', false);

        $this->get(route('admin.sales.opportunities', [
            'q' => 'Pagination Opportunity',
            'status' => 'proposal',
            'per_page' => 20,
        ]))
            ->assertOk()
            ->assertViewHas('opportunities', function ($opportunities) {
                parse_str((string) parse_url($opportunities->nextPageUrl(), PHP_URL_QUERY), $query);

                return $opportunities->perPage() === 20
                    && $opportunities->count() === 20
                    && ($query['q'] ?? null) === 'Pagination Opportunity'
                    && ($query['status'] ?? null) === 'proposal'
                    && ($query['per_page'] ?? null) === '20';
            });
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
            ->assertSee($opportunity->title)
            ->assertSee('Sales Workspace')
            ->assertSee('Back to Opportunity Management');

        $this->get(route('admin.sales.opportunities.edit', $opportunity))
            ->assertOk()
            ->assertSee('Edit Opportunity')
            ->assertSee('Sales Workspace');

        $this->get(route('admin.sales.opportunities.create'))
            ->assertOk()
            ->assertSee('Add Opportunity')
            ->assertSee('Sales Workspace');
    }

    public function test_opportunity_navigation_remains_active_across_workspace_pages(): void
    {
        $opportunity = Opportunity::factory()->create();
        $activeOpportunityNavigation = 'href="'.route('admin.sales.opportunities').'" class="nav-link parent compact active"';

        foreach ([
            route('admin.sales.opportunities'),
            route('admin.sales.opportunities.create'),
            route('admin.sales.opportunities.show', $opportunity),
            route('admin.sales.opportunities.edit', $opportunity),
        ] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertSee($activeOpportunityNavigation, false);
        }
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
