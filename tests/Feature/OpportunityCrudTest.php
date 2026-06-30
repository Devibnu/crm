<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\WhatsAppConversation;
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

    public function test_opportunity_create_prefills_from_lead_query(): void
    {
        $customer = Customer::factory()->create(['name' => 'Prefill Opportunity Customer']);
        $lead = Lead::factory()->create([
            'customer_id' => $customer->id,
            'name' => 'Query Prefill Lead',
            'company_name' => 'Query Prefill Co',
            'assigned_to' => 'Query Owner',
            'source' => 'referral',
            'notes' => 'Query prefill notes.',
        ]);

        $this->get(route('admin.sales.opportunities.create', ['lead_id' => $lead->id]))
            ->assertOk()
            ->assertSee('<option value="'.$lead->id.'" selected>'.$lead->name.'</option>', false)
            ->assertSee('<option value="'.$customer->id.'" selected>'.$customer->name.'</option>', false)
            ->assertSee('value="Query Prefill Lead Opportunity"', false)
            ->assertSee('value="Query Prefill Co"', false)
            ->assertSee('value="Query Prefill Lead"', false)
            ->assertSee('value="25"', false)
            ->assertSee('value="Query Owner"', false)
            ->assertSee('Created from Lead #'.$lead->id.'. Source: referral');
    }

    public function test_opportunity_created_from_lead_keeps_lead_id(): void
    {
        $lead = Lead::factory()->create(['name' => 'Saved Source Lead']);

        $this->post(route('admin.sales.opportunities.store'), [
            'lead_id' => $lead->id,
            'customer_id' => null,
            'title' => 'Saved Lead Opportunity',
            'company_name' => 'Saved Lead Co',
            'contact_name' => 'Saved Source Lead',
            'estimated_value' => 0,
            'probability' => 25,
            'status' => 'open',
            'expected_close_date' => null,
            'assigned_to' => 'Saved Owner',
            'notes' => 'Saved from lead.',
        ])->assertRedirect(route('admin.sales.opportunities'));

        $this->assertDatabaseHas('opportunities', [
            'lead_id' => $lead->id,
            'title' => 'Saved Lead Opportunity',
            'probability' => 25,
        ]);
    }

    public function test_opportunity_created_from_lead_keeps_conversation_id_if_available(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Saved Opportunity Conversation',
            'phone_number' => '628120004444',
            'channel' => 'whatsapp',
            'last_message' => 'Need follow up',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'name' => 'Conversation Source Lead',
            'conversation_id' => $conversation->id,
        ]);

        $this->post(route('admin.sales.opportunities.store'), [
            'lead_id' => $lead->id,
            'customer_id' => null,
            'title' => 'Conversation Source Opportunity',
            'company_name' => null,
            'contact_name' => 'Conversation Source Lead',
            'estimated_value' => 0,
            'probability' => 25,
            'status' => 'open',
            'expected_close_date' => null,
            'assigned_to' => null,
            'notes' => 'Created from lead.',
        ])->assertRedirect(route('admin.sales.opportunities'));

        $this->assertDatabaseHas('opportunities', [
            'lead_id' => $lead->id,
            'conversation_id' => $conversation->id,
            'title' => 'Conversation Source Opportunity',
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

    public function test_opportunity_detail_shows_source_lead_and_conversation_links(): void
    {
        $conversation = WhatsAppConversation::create([
            'contact_name' => 'Opportunity Source Conversation',
            'phone_number' => '628120003333',
            'channel' => 'whatsapp',
            'last_message' => 'Opportunity source message',
            'last_message_at' => now(),
            'status' => 'open',
        ]);
        $lead = Lead::factory()->create([
            'name' => 'Opportunity Source Lead',
            'conversation_id' => $conversation->id,
        ]);
        $opportunity = Opportunity::factory()->create([
            'lead_id' => $lead->id,
            'title' => 'Opportunity With Source Lead',
        ]);

        $this->get(route('admin.sales.opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('Source Lead')
            ->assertSee('Open Lead')
            ->assertSee(route('admin.sales.leads.show', $lead), false)
            ->assertSee('Source Conversation')
            ->assertSee('Open Conversation')
            ->assertSee(route('admin.service.omnichannel.index', ['conversation' => $conversation->id]).'#contact', false);
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
