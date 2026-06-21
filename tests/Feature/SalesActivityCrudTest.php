<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Customer;
use App\Models\SalesActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesActivityCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_index_is_accessible(): void
    {
        $this->get(route('admin.sales.activities.index'))
            ->assertOk()
            ->assertSee('Sales Activity Tracking')
            ->assertSee('Total Activities');
    }

    public function test_activity_can_be_created(): void
    {
        $payload = [
            'related_type' => 'lead',
            'related_id' => 1,
            'type' => 'call',
            'subject' => 'Test Activity Subject',
            'description' => 'Test activity description.',
            'activity_at' => '2026-05-04T10:00',
            'assigned_to' => 'Test User',
            'outcome' => 'Successful call',
        ];

        $response = $this->post(route('admin.sales.activities.store'), $payload);

        $activity = SalesActivity::query()->where('subject', 'Test Activity Subject')->firstOrFail();

        $response->assertRedirect(route('admin.sales.activities.show', $activity));

        $this->assertDatabaseHas('sales_activities', [
            'subject' => 'Test Activity Subject',
            'type' => 'call',
        ]);
    }

    public function test_activity_create_uses_related_sales_workspace_navigation(): void
    {
        $lead = Lead::factory()->create();
        $opportunity = Opportunity::factory()->create();
        $activeLeadNavigation = 'href="'.route('admin.sales.leads').'" class="nav-link parent compact active"';
        $activeOpportunityNavigation = 'href="'.route('admin.sales.opportunities').'" class="nav-link parent compact active"';

        $this->get(route('admin.sales.activities.create', [
            'related_type' => 'lead',
            'related_id' => $lead->id,
        ]))
            ->assertOk()
            ->assertSee('Add Sales Activity')
            ->assertSee('Related Record')
            ->assertSee('Activity Workflow')
            ->assertSee('Simpan Sales Activity?')
            ->assertSee('Ya, Simpan Activity')
            ->assertSee($activeLeadNavigation, false);

        $this->get(route('admin.sales.activities.create', [
            'related_type' => 'opportunity',
            'related_id' => $opportunity->id,
        ]))
            ->assertOk()
            ->assertSee('Add Sales Activity')
            ->assertSee('Schedule & Assignment', false)
            ->assertSee('Best Practices')
            ->assertSee($activeOpportunityNavigation, false);
    }

    public function test_activity_show_and_edit_pages_are_accessible(): void
    {
        $activity = SalesActivity::factory()->create();

        $this->get(route('admin.sales.activities.show', $activity))
            ->assertOk()
            ->assertSee($activity->subject)
            ->assertSee('Sales Workspace')
            ->assertSee('Activity Details')
            ->assertSee('Hapus Sales Activity?');

        $this->get(route('admin.sales.activities.edit', $activity))
            ->assertOk()
            ->assertSee('Edit Sales Activity')
            ->assertSee('Simpan Perubahan Activity?');
    }

    public function test_activity_detail_preserves_related_workspace_context(): void
    {
        $lead = Lead::factory()->create();
        $leadActivity = SalesActivity::factory()->create([
            'related_type' => 'lead',
            'related_id' => $lead->id,
        ]);
        $opportunity = Opportunity::factory()->create();
        $opportunityActivity = SalesActivity::factory()->create([
            'related_type' => 'opportunity',
            'related_id' => $opportunity->id,
        ]);
        $activeLeadNavigation = 'href="'.route('admin.sales.leads').'" class="nav-link parent compact active"';
        $activeOpportunityNavigation = 'href="'.route('admin.sales.opportunities').'" class="nav-link parent compact active"';

        $this->get(route('admin.sales.activities.show', $leadActivity))
            ->assertOk()
            ->assertSee('Back to Lead')
            ->assertSee(route('admin.sales.leads.show', $lead), false)
            ->assertSee($activeLeadNavigation, false)
            ->assertDontSee("confirm('Delete activity ini?')", false);

        $this->get(route('admin.sales.activities.edit', $leadActivity))
            ->assertOk()
            ->assertSee('Ya, Simpan Perubahan')
            ->assertSee($activeLeadNavigation, false);

        $this->get(route('admin.sales.activities.show', $opportunityActivity))
            ->assertOk()
            ->assertSee('Back to Opportunity')
            ->assertSee(route('admin.sales.opportunities.show', $opportunity), false)
            ->assertSee($activeOpportunityNavigation, false);
    }

    public function test_activity_can_be_updated(): void
    {
        $activity = SalesActivity::factory()->create([
            'subject' => 'Before Update',
            'type' => 'email',
        ]);

        $response = $this->put(route('admin.sales.activities.update', $activity), [
            'related_type' => 'opportunity',
            'related_id' => 2,
            'type' => 'meeting',
            'subject' => 'After Update',
            'description' => 'Updated description.',
            'activity_at' => '2026-05-05T11:00',
            'assigned_to' => 'Updated User',
            'outcome' => 'Meeting scheduled',
        ]);

        $response->assertRedirect(route('admin.sales.activities.show', $activity));

        $this->assertDatabaseHas('sales_activities', [
            'id' => $activity->id,
            'subject' => 'After Update',
            'type' => 'meeting',
        ]);
    }

    public function test_activity_can_be_deleted(): void
    {
        $activity = SalesActivity::factory()->create();

        $response = $this->delete(route('admin.sales.activities.destroy', $activity));

        $response->assertRedirect(route('admin.sales.activities.index'));

        $this->assertDatabaseMissing('sales_activities', [
            'id' => $activity->id,
        ]);
    }

    public function test_activity_search_works(): void
    {
        $match = SalesActivity::factory()->create([
            'subject' => 'Searchable Subject',
            'description' => 'Searchable description',
            'assigned_to' => 'Searchable User',
        ]);

        $other = SalesActivity::factory()->create([
            'subject' => 'Other Subject',
            'description' => 'Other description',
            'assigned_to' => 'Other User',
        ]);

        $this->get(route('admin.sales.activities.index', ['q' => 'Searchable']))
            ->assertOk()
            ->assertSee($match->subject)
            ->assertDontSee($other->subject);
    }

    public function test_activity_filter_type_works(): void
    {
        $call = SalesActivity::factory()->create(['type' => 'call']);
        $email = SalesActivity::factory()->create(['type' => 'email']);

        $this->get(route('admin.sales.activities.index', ['type' => 'call']))
            ->assertOk()
            ->assertSee($call->subject)
            ->assertDontSee($email->subject);
    }

    public function test_activity_filter_related_type_works(): void
    {
        $leadActivity = SalesActivity::factory()->create(['related_type' => 'lead']);
        $oppActivity = SalesActivity::factory()->create(['related_type' => 'opportunity']);

        $this->get(route('admin.sales.activities.index', ['related_type' => 'lead']))
            ->assertOk()
            ->assertSee($leadActivity->subject)
            ->assertDontSee($oppActivity->subject);
    }

    public function test_lead_show_displays_activities(): void
    {
        $lead = Lead::factory()->create();
        $activity = SalesActivity::factory()->create([
            'related_type' => 'lead',
            'related_id' => $lead->id,
            'subject' => 'Lead Activity Test',
        ]);

        $this->get(route('admin.sales.leads.show', $lead))
            ->assertOk()
            ->assertSee('Recent Activities')
            ->assertSee($activity->subject);
    }

    public function test_opportunity_show_displays_activities(): void
    {
        $opportunity = Opportunity::factory()->create();
        $activity = SalesActivity::factory()->create([
            'related_type' => 'opportunity',
            'related_id' => $opportunity->id,
            'subject' => 'Opportunity Activity Test',
        ]);

        $this->get(route('admin.sales.opportunities.show', $opportunity))
            ->assertOk()
            ->assertSee('Recent Activities')
            ->assertSee($activity->subject);
    }

    public function test_customer_show_displays_sales_activities(): void
    {
        $customer = Customer::factory()->create();
        $activity = SalesActivity::factory()->create([
            'related_type' => 'customer',
            'related_id' => $customer->id,
            'subject' => 'Customer Sales Activity Test',
        ]);

        $this->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertSee('Sales Activities')
            ->assertSee($activity->subject);
    }
}
