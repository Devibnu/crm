<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OpportunityFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_opportunity_persists_valid_data_and_returns_expected_json_schema(): void
    {
        $this->actingAsSalesUser();
        $lead = $this->createQualifiedLead();
        $assignee = User::factory()->create(['role' => 'sales']);

        $response = $this->postJson('/api/opportunities', [
            'leadId' => $lead->id,
            'assignedUserId' => $assignee->id,
            'name' => 'Software Subscription',
            'stage' => 'new',
            'amount' => 85000000,
            'currency' => 'IDR',
            'probability' => 25,
            'expectedCloseDate' => now()->addDays(10)->toDateString(),
            'statusNotes' => 'Qualified lead is asking for a proposal draft.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.id', 1)
            ->assertJsonPath('data.title', 'Software Subscription')
            ->assertJsonPath('data.stage', 'new')
            ->assertJsonPath('data.lead_id', $lead->id)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'stage',
                    'lead_id',
                ],
            ]);

        $this->assertDatabaseHas('opportunities', [
            'lead_id' => $lead->id,
            'assigned_user_id' => $assignee->id,
            'name' => 'Software Subscription',
            'stage' => 'new',
        ]);
    }

    public function test_opportunity_stage_management_accepts_supported_stage_values(): void
    {
        $this->actingAsSalesUser();
        $lead = $this->createQualifiedLead();

        foreach (['new', 'qualified', 'proposal', 'negotiation', 'closed_won', 'closed_lost'] as $stage) {
            $response = $this->postJson('/api/opportunities', [
                'leadId' => $lead->id,
                'name' => "Opportunity {$stage}",
                'stage' => $stage,
                'amount' => 1000000,
            ]);

            $response->assertCreated()->assertJsonPath('data.stage', $stage);
        }

        $this->assertSame(6, Opportunity::query()->count());
    }

    public function test_opportunity_stage_management_accepts_legacy_prospecting_alias_and_normalizes_it(): void
    {
        $this->actingAsSalesUser();
        $lead = $this->createQualifiedLead();

        $this->postJson('/api/opportunities', [
            'leadId' => $lead->id,
            'name' => 'Legacy Prospecting Opportunity',
            'stage' => 'prospecting',
            'amount' => 1000000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.stage', 'new');

        $this->assertDatabaseHas('opportunities', [
            'name' => 'Legacy Prospecting Opportunity',
            'stage' => 'new',
        ]);
    }

    public function test_opportunity_stage_management_rejects_invalid_stage_with_422(): void
    {
        $this->actingAsSalesUser();
        $lead = $this->createQualifiedLead();

        $this->postJson('/api/opportunities', [
            'leadId' => $lead->id,
            'name' => 'Bad Opportunity',
            'stage' => 'contracting',
            'amount' => 1000000,
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['stage']);
    }

    public function test_opportunity_update_keeps_valid_relation_to_lead_and_updates_stage(): void
    {
        $this->actingAsSalesUser();
        $lead = $this->createQualifiedLead();
        $opportunity = Opportunity::query()->create([
            'code' => 'OPP-000101',
            'lead_id' => $lead->id,
            'name' => 'Deal Laptop Enterprise',
            'stage' => 'new',
            'amount' => 120000000,
            'currency' => 'IDR',
            'probability' => 20,
        ]);

        $response = $this->patchJson("/api/opportunities/{$opportunity->id}", [
            'stage' => 'closed_won',
            'probability' => 100,
            'statusNotes' => 'Deal closed successfully.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $opportunity->id)
            ->assertJsonPath('data.title', 'Deal Laptop Enterprise')
            ->assertJsonPath('data.stage', 'closed_won')
            ->assertJsonPath('data.lead_id', $lead->id);

        $this->assertDatabaseHas('opportunities', [
            'id' => $opportunity->id,
            'lead_id' => $lead->id,
            'stage' => 'closed_won',
            'probability' => 100,
        ]);
    }

    public function test_delete_opportunity_removes_record_from_database(): void
    {
        $this->actingAsSalesUser();
        $lead = $this->createQualifiedLead();
        $opportunity = Opportunity::query()->create([
            'code' => 'OPP-000202',
            'lead_id' => $lead->id,
            'name' => 'Delete Me',
            'stage' => 'qualified',
            'amount' => 45000000,
            'currency' => 'IDR',
            'probability' => 25,
        ]);

        $this->deleteJson("/api/opportunities/{$opportunity->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Opportunity deleted successfully.');

        $this->assertDatabaseMissing('opportunities', [
            'id' => $opportunity->id,
        ]);
    }

    public function test_opportunity_endpoints_require_sanctum_authentication(): void
    {
        $lead = $this->createQualifiedLead();

        $this->postJson('/api/opportunities', [
            'leadId' => $lead->id,
            'name' => 'Unauthorized Opportunity',
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

    private function createQualifiedLead(): Lead
    {
        return Lead::query()->create([
            'code' => 'LED-000501',
            'full_name' => 'Qualified Lead',
            'company' => 'Qualified Company',
            'status' => 'qualified',
        ]);
    }
}