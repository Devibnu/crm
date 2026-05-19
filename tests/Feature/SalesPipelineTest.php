<?php

namespace Tests\Feature;

use App\Models\Opportunity;
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
            ->assertSee('Total Pipeline Value')
            ->assertSee('Weighted Forecast')
            ->assertSee('Won Value')
            ->assertSee('Open Opportunities');
    }

    public function test_pipeline_page_displays_all_stages(): void
    {
        $response = $this->get(route('admin.sales.pipeline'));

        $response->assertOk()
            ->assertSee('Open')
            ->assertSee('Qualified')
            ->assertSee('Proposal')
            ->assertSee('Negotiation')
            ->assertSee('Won')
            ->assertSee('Lost');
    }

    public function test_pipeline_displays_opportunity_title(): void
    {
        Opportunity::factory()->create([
            'title' => 'Pipeline Display Opportunity',
            'status' => 'proposal',
        ]);

        $this->get(route('admin.sales.pipeline'))
            ->assertOk()
            ->assertSee('Pipeline Display Opportunity');
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
