<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_lead_persists_valid_data_and_returns_expected_json_schema(): void
    {
        $actor = $this->actingAsSalesUser();
        $assignee = User::factory()->create(['role' => 'sales']);

        $response = $this->postJson('/api/leads', [
            'fullName' => 'Raka Wijaya',
            'email' => 'raka@example.com',
            'phone' => '081234567890',
            'company' => 'PT Nusantara Jaya',
            'source' => 'manual',
            'status' => 'new',
            'assignedUserId' => $assignee->id,
            'qualificationNotes' => 'Initial outreach from trade expo.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.company', 'PT Nusantara Jaya')
            ->assertJsonPath('data.full_name', 'Raka Wijaya')
            ->assertJsonPath('data.status', 'new')
            ->assertJsonPath('data.assigned_user_id', $assignee->id)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'company',
                    'full_name',
                    'status',
                    'assigned_user_id',
                ],
            ]);

        $this->assertDatabaseHas('leads', [
            'full_name' => 'Raka Wijaya',
            'company' => 'PT Nusantara Jaya',
            'status' => 'new',
            'assigned_user_id' => $assignee->id,
            'captured_by' => $actor->id,
        ]);

        $this->assertNotNull(Lead::query()->firstOrFail()->code);
    }

    public function test_lead_qualification_accepts_supported_status_values(): void
    {
        $this->actingAsSalesUser();

        foreach (['new', 'qualified', 'disqualified'] as $status) {
            $response = $this->postJson('/api/leads', [
                'fullName' => "Lead {$status}",
                'company' => "Company {$status}",
                'status' => $status,
            ]);

            $response->assertCreated()->assertJsonPath('data.status', $status);
        }

        $this->assertSame(3, Lead::query()->count());
    }

    public function test_lead_qualification_rejects_invalid_status_with_422(): void
    {
        $this->actingAsSalesUser();

        $this->postJson('/api/leads', [
            'fullName' => 'Bad Lead',
            'company' => 'Invalid Status Corp',
            'status' => 'pending',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_lead_assignment_updates_assigned_user_id_and_returns_expected_schema(): void
    {
        $this->actingAsSalesUser();
        $lead = Lead::query()->create([
            'code' => 'LED-000101',
            'full_name' => 'Assignment Lead',
            'company' => 'Assign Me Inc',
            'status' => 'new',
        ]);
        $assignee = User::factory()->create(['role' => 'sales']);

        $response = $this->patchJson("/api/leads/{$lead->id}", [
            'status' => 'qualified',
            'assignedUserId' => $assignee->id,
            'qualificationNotes' => 'Budget approved and ready for follow-up.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $lead->id)
            ->assertJsonPath('data.status', 'qualified')
            ->assertJsonPath('data.assigned_user_id', $assignee->id)
            ->assertJsonPath('data.full_name', 'Assignment Lead');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'qualified',
            'assigned_user_id' => $assignee->id,
            'qualification_notes' => 'Budget approved and ready for follow-up.',
        ]);
    }

    public function test_lead_endpoints_require_sanctum_authentication(): void
    {
        $this->postJson('/api/leads', [
            'fullName' => 'Unauthorized Lead',
            'company' => 'No Auth Corp',
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
}