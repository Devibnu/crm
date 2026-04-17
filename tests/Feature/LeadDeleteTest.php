<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_delete_lead_valid_removes_record_from_database(): void
    {
        $this->actingAsSalesUser();
        $lead = Lead::factory()->create([
            'company' => 'PT Delete Me',
            'full_name' => 'Delete Candidate',
        ]);

        $response = $this->deleteJson("/api/leads/{$lead->id}");

        $response
            ->assertOk()
            ->assertJsonStructure([
                'message',
            ]);

        $this->assertDatabaseMissing('leads', [
            'id' => $lead->id,
        ]);
    }

    public function test_delete_lead_invalid_returns_404_and_json_error_message(): void
    {
        $this->actingAsSalesUser();

        $response = $this->deleteJson('/api/leads/999999');

        $response
            ->assertNotFound()
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_delete_lead_requires_sanctum_authentication(): void
    {
        $lead = Lead::factory()->create();

        $this->deleteJson("/api/leads/{$lead->id}")
            ->assertUnauthorized()
            ->assertJsonStructure([
                'message',
            ]);
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