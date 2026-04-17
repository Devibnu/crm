<?php

namespace Tests\Feature;

use App\Models\Forecast;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ForecastFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_forecast_persists_snapshot_and_returns_expected_json_schema(): void
    {
        $this->actingAsSalesUser();
        $this->createOpportunity('closed_won', 150000000, 100);
        $this->createOpportunity('negotiation', 50000000, 70);

        $response = $this->postJson('/api/forecast', [
            'periodLabel' => 'April 2026',
            'snapshotDate' => '2026-04-01',
            'status' => 'published',
            'notes' => 'Monthly QA snapshot.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.period_label', 'April 2026')
            ->assertJsonPath('data.forecast_amount', 150000000)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'period_label',
                    'forecast_amount',
                ],
            ]);

        $this->assertDatabaseHas('forecasts', [
            'period_label' => 'April 2026',
            'snapshot_date' => '2026-04-01 00:00:00',
            'forecast_amount' => 150000000,
            'status' => 'published',
        ]);
    }

    public function test_forecast_calculation_uses_closed_won_opportunities_only_for_revenue_total(): void
    {
        $this->actingAsSalesUser();
        $this->createOpportunity('closed_won', 100000000, 100);
        $this->createOpportunity('closed_won', 25000000, 100);
        $this->createOpportunity('proposal', 50000000, 50);

        $response = $this->postJson('/api/forecast', [
            'periodLabel' => 'May 2026',
            'snapshotDate' => '2026-05-01',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.forecast_amount', 125000000);

        $this->assertDatabaseHas('forecasts', [
            'period_label' => 'May 2026',
            'forecast_amount' => 125000000,
        ]);
    }

    public function test_forecast_supports_multiple_month_snapshots_and_index_returns_them(): void
    {
        $this->actingAsSalesUser();
        $this->createOpportunity('closed_won', 175000000, 100);

        $this->postJson('/api/forecast', [
            'periodLabel' => 'April 2026',
            'snapshotDate' => '2026-04-01',
        ])->assertCreated();

        $this->postJson('/api/forecast', [
            'periodLabel' => 'May 2026',
            'snapshotDate' => '2026-05-01',
        ])->assertCreated();

        $this->assertDatabaseHas('forecasts', [
            'period_label' => 'April 2026',
            'forecast_amount' => 175000000,
        ]);

        $this->assertDatabaseHas('forecasts', [
            'period_label' => 'May 2026',
            'forecast_amount' => 175000000,
        ]);

        $this->getJson('/api/forecast')
            ->assertOk()
            ->assertJsonFragment([
                'period_label' => 'April 2026',
                'forecast_amount' => 175000000.0,
            ])
            ->assertJsonFragment([
                'period_label' => 'May 2026',
                'forecast_amount' => 175000000.0,
            ]);

        $this->assertSame(2, Forecast::query()->count());
    }

    public function test_forecast_create_rejects_invalid_payload_with_422(): void
    {
        $this->actingAsSalesUser();

        $this->postJson('/api/forecast', [
            'periodLabel' => '',
            'snapshotDate' => 'invalid-date',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['periodLabel', 'snapshotDate']);
    }

    public function test_forecast_endpoints_require_sanctum_authentication(): void
    {
        $this->postJson('/api/forecast', [
            'periodLabel' => 'April 2026',
        ])->assertUnauthorized();
    }

    private function actingAsSalesUser(): User
    {
        $user = User::factory()->create([
            'role' => 'sales',
            'module_permissions' => User::defaultModulePermissionsForRole('sales'),
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function createOpportunity(string $stage, float $amount, int $probability): Opportunity
    {
        $lead = Lead::query()->create([
            'full_name' => "Forecast Lead {$stage} {$amount}",
            'company' => 'Forecast Company',
            'status' => 'qualified',
        ]);

        return Opportunity::query()->create([
            'lead_id' => $lead->id,
            'name' => "Opportunity {$stage} {$amount}",
            'stage' => $stage,
            'amount' => $amount,
            'currency' => 'IDR',
            'probability' => $probability,
        ]);
    }
}