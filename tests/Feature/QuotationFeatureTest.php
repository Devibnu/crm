<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuotationFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_quotation_persists_valid_data_and_returns_expected_json_schema(): void
    {
        $this->actingAsSalesUser();
        $opportunity = $this->createOpportunity();

        $response = $this->postJson('/api/quotations', [
            'opportunityId' => $opportunity->id,
            'title' => 'Software Subscription Quote',
            'amount' => 125000000,
            'currency' => 'IDR',
            'validUntil' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'approvalNotes' => 'Initial commercial draft.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.opportunity_id', $opportunity->id)
            ->assertJsonPath('data.amount', 125000000)
            ->assertJsonPath('data.status', 'draft')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'opportunity_id',
                    'amount',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('quotations', [
            'opportunity_id' => $opportunity->id,
            'title' => 'Software Subscription Quote',
            'amount' => 125000000,
            'status' => 'draft',
        ]);
    }

    public function test_quotation_approval_workflow_accepts_supported_status_values(): void
    {
        $this->actingAsSalesUser();
        $opportunity = $this->createOpportunity();

        foreach (['draft', 'submitted', 'approved', 'rejected'] as $status) {
            $response = $this->postJson('/api/quotations', [
                'opportunityId' => $opportunity->id,
                'title' => "Quotation {$status}",
                'amount' => 2000000,
                'status' => $status,
            ]);

            $response->assertCreated()->assertJsonPath('data.status', $status);
        }

        $this->assertSame(4, Quotation::query()->count());
    }

    public function test_quotation_approval_workflow_rejects_invalid_status_with_422(): void
    {
        $this->actingAsSalesUser();
        $opportunity = $this->createOpportunity();

        $this->postJson('/api/quotations', [
            'opportunityId' => $opportunity->id,
            'title' => 'Invalid Quote',
            'amount' => 1000000,
            'status' => 'queued',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_quotation_update_changes_workflow_status_and_keeps_opportunity_relation(): void
    {
        $this->actingAsSalesUser();
        $opportunity = $this->createOpportunity();
        $quotation = Quotation::query()->create([
            'quote_number' => 'QTN-000101',
            'opportunity_id' => $opportunity->id,
            'title' => 'Cloud Hosting Quote',
            'amount' => 150000000,
            'currency' => 'IDR',
            'status' => 'draft',
        ]);

        $response = $this->patchJson("/api/quotations/{$quotation->id}", [
            'status' => 'approved',
            'approvalNotes' => 'Approved by sales manager.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $quotation->id)
            ->assertJsonPath('data.opportunity_id', $opportunity->id)
            ->assertJsonPath('data.status', 'approved')
            ->assertJsonPath('data.amount', 150000000);

        $this->assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'opportunity_id' => $opportunity->id,
            'status' => 'approved',
            'approval_notes' => 'Approved by sales manager.',
        ]);
    }

    public function test_quotation_endpoints_require_sanctum_authentication(): void
    {
        $opportunity = $this->createOpportunity();

        $this->postJson('/api/quotations', [
            'opportunityId' => $opportunity->id,
            'title' => 'Unauthorized Quote',
            'amount' => 1000,
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

    private function createOpportunity(): Opportunity
    {
        $lead = Lead::query()->create([
            'code' => 'LED-000701',
            'full_name' => 'Quotation Lead',
            'company' => 'Quotation Corp',
            'status' => 'qualified',
        ]);

        return Opportunity::query()->create([
            'code' => 'OPP-000701',
            'lead_id' => $lead->id,
            'name' => 'Cloud Hosting Contract',
            'stage' => 'negotiation',
            'amount' => 150000000,
            'currency' => 'IDR',
            'probability' => 70,
        ]);
    }
}