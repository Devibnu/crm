<?php

namespace Tests\Feature;

use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_pipeline_page_is_accessible(): void
    {
        $this->get(route('admin.sales.pipeline'))
            ->assertOk()
            ->assertSee('Pipeline & Forecasting')
            ->assertSee('Total Pipeline')
            ->assertSee('Weighted Forecast')
            ->assertSee('Won Value')
            ->assertSee('Open Opportunities')
            ->assertSee('Opportunity Stages');
    }

    public function test_pipeline_page_displays_all_stages(): void
    {
        $response = $this->get(route('admin.sales.pipeline'));

        $response->assertOk()
            ->assertSee('Prospecting')
            ->assertSee('Qualified')
            ->assertSee('Proposal')
            ->assertSee('Negotiation')
            ->assertSee('Won')
            ->assertSee('Lost');
    }

    public function test_pipeline_has_drag_drop_confirmation_and_active_navigation(): void
    {
        $opportunity = Opportunity::factory()->create(['status' => 'open']);

        $this->get(route('admin.sales.pipeline'))
            ->assertOk()
            ->assertSee('draggable="true"', false)
            ->assertSee(route('admin.sales.opportunities.update-stage', $opportunity), false)
            ->assertSee('data-edit-url="'.route('admin.sales.opportunities.edit', $opportunity).'"', false)
            ->assertSee('Pindahkan opportunity ke stage', false)
            ->assertSee('Ya, Proses')
            ->assertSee('type="button" class="btn btn-primary" data-stage-process', false)
            ->assertSee("method: 'PATCH'", false)
            ->assertSee("'X-CSRF-TOKEN': csrfToken", false)
            ->assertSee('Opportunity berhasil dipindahkan.', false)
            ->assertSee('stage_updated=', false)
            ->assertSee('href="'.route('admin.sales.pipeline').'" class="nav-link parent compact active"', false);
    }

    public function test_opportunity_stage_can_be_updated_from_pipeline_endpoint(): void
    {
        $opportunity = Opportunity::factory()->create(['status' => 'open']);

        $this->patchJson(route('admin.sales.opportunities.update-stage', $opportunity), [
            'status' => 'qualified',
        ])->assertOk()
            ->assertJson([
                'message' => 'Opportunity berhasil dipindahkan ke Qualified.',
                'status' => 'qualified',
                'stage' => 'Qualified',
            ]);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'status' => 'qualified',
        ]);
    }

    public function test_opportunity_edit_page_displays_stage_updated_success_message(): void
    {
        $opportunity = Opportunity::factory()->create(['status' => 'qualified']);

        $this->get(route('admin.sales.opportunities.edit', [
            'opportunity' => $opportunity,
            'stage_updated' => 'qualified',
        ]))->assertOk()
            ->assertSee('Opportunity berhasil dipindahkan ke stage Qualified. Silakan lengkapi data opportunity.');
    }

    public function test_pipeline_displays_opportunity_title(): void
    {
        Opportunity::factory()->create([
            'title' => 'Pipeline Display Opportunity',
            'status' => 'proposal',
        ]);

        $this->get(route('admin.sales.pipeline'))
            ->assertOk()
            ->assertSee('Pipeline Display Opportunity')
            ->assertSee(route('admin.sales.opportunities.show', Opportunity::query()->where('title', 'Pipeline Display Opportunity')->firstOrFail()), false);
    }

    public function test_pipeline_summary_forecast_calculation_is_correct_minimally(): void
    {
        Opportunity::factory()->create([
            'title' => 'Open Opp',
            'status' => 'open',
            'estimated_value' => 100000,
            'probability' => 50,
        ]);

        Opportunity::factory()->create([
            'title' => 'Won Opp',
            'status' => 'won',
            'estimated_value' => 200000,
            'probability' => 100,
        ]);

        Opportunity::factory()->create([
            'title' => 'Lost Opp',
            'status' => 'lost',
            'estimated_value' => 300000,
            'probability' => 80,
        ]);

        $this->get(route('admin.sales.pipeline'))
            ->assertOk()
            ->assertSee('Rp 300.000,00')
            ->assertSee('Rp 250.000,00')
            ->assertSee('Rp 200.000,00');
    }

    public function test_pipeline_open_count_includes_all_nonterminal_stages(): void
    {
        Opportunity::factory()->create(['status' => 'qualified']);
        Opportunity::factory()->create(['status' => 'proposal']);
        Opportunity::factory()->create(['status' => 'won']);
        Opportunity::factory()->create(['status' => 'lost']);

        $this->get(route('admin.sales.pipeline'))
            ->assertOk()
            ->assertViewHas('summary', fn ($summary) => $summary['open_opportunities_count'] === 2);
    }

    public function test_pipeline_won_value_updates_after_accepted_quotation(): void
    {
        $opportunity = Opportunity::factory()->create([
            'title' => 'Accepted Pipeline Opportunity',
            'status' => 'proposal',
            'estimated_value' => 1000000,
            'probability' => 40,
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-PIPELINE-WON-001',
            'status' => 'sent',
            'amount' => 98765432,
        ]);

        $this->put(route('admin.sales.deals.update', $quotation), [
            'opportunity_id' => $opportunity->id,
            'customer_id' => null,
            'quote_number' => 'QT-PIPELINE-WON-001',
            'title' => $quotation->title,
            'amount' => 98765432,
            'status' => 'accepted',
            'issued_at' => '2026-05-10',
            'valid_until' => '2026-06-10',
            'notes' => $quotation->notes,
        ]);

        $this->get(route('admin.sales.pipeline'))
            ->assertOk()
            ->assertSee('Accepted Pipeline Opportunity')
            ->assertSee('Rp 98.765.432,00');
    }

    public function test_pipeline_lost_stage_updates_after_mark_lost_quotation(): void
    {
        $opportunity = Opportunity::factory()->create([
            'title' => 'Lost Pipeline Opportunity',
            'status' => 'negotiation',
            'estimated_value' => 1000000,
            'probability' => 60,
        ]);

        $quotation = Quotation::factory()->create([
            'opportunity_id' => $opportunity->id,
            'quote_number' => 'QT-PIPELINE-LOST-001',
            'status' => 'sent',
            'amount' => 22200000,
        ]);

        $this->post(route('admin.sales.deals.mark-lost', $quotation), [
            'lost_reason' => 'Budget',
        ]);

        $this->get(route('admin.sales.pipeline'))
            ->assertOk()
            ->assertSee('Lost Pipeline Opportunity')
            ->assertSee('Rp 22.200.000,00');
    }

    public function test_pipeline_filter_assigned_to_works(): void
    {
        Opportunity::factory()->create([
            'title' => 'Assigned Alice Opportunity',
            'assigned_to' => 'Alice',
            'status' => 'open',
        ]);

        Opportunity::factory()->create([
            'title' => 'Assigned Bob Opportunity',
            'assigned_to' => 'Bob',
            'status' => 'open',
        ]);

        $this->get(route('admin.sales.pipeline', ['assigned_to' => 'Alice']))
            ->assertOk()
            ->assertSee('Assigned Alice Opportunity')
            ->assertDontSee('Assigned Bob Opportunity');
    }

    public function test_pipeline_filter_date_range_works(): void
    {
        Opportunity::factory()->create([
            'title' => 'Date In Range Opportunity',
            'expected_close_date' => '2026-06-15',
            'status' => 'qualified',
        ]);

        Opportunity::factory()->create([
            'title' => 'Date Out Range Opportunity',
            'expected_close_date' => '2027-01-10',
            'status' => 'qualified',
        ]);

        $this->get(route('admin.sales.pipeline', [
            'expected_close_date_from' => '2026-06-01',
            'expected_close_date_to' => '2026-06-30',
        ]))
            ->assertOk()
            ->assertSee('Date In Range Opportunity')
            ->assertDontSee('Date Out Range Opportunity');
    }
}
