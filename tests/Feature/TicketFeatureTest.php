<?php

namespace Tests\Feature;

use App\Models\Pelanggan;
use App\Models\SLA;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_ticket_persists_valid_data_and_returns_expected_json_structure(): void
    {
        $user = $this->actingAsTicketManager();
        $customer = $this->createCustomer();
        $agent = User::factory()->create(['role' => 'service']);
        $sla = $this->createSlaDefinition();

        $response = $this->postJson('/api/tickets', [
            'customerId' => $customer->id,
            'assignedUserId' => $agent->id,
            'slaDefinitionId' => $sla->id,
            'category' => 'general',
            'priority' => 'medium',
            'subject' => 'Customer cannot access portal',
            'description' => 'Customer reported a login problem from the support desk.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.title', 'Customer cannot access portal')
            ->assertJsonPath('data.category', 'general')
            ->assertJsonPath('data.assigned_to', $agent->id)
            ->assertJsonPath('data.sla_id', $sla->id)
            ->assertJsonPath('data.status', 'open')
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'title',
                    'category',
                    'assigned_to',
                    'sla_id',
                    'status',
                    'activities',
                ],
            ]);

        $this->assertDatabaseHas('tickets', [
            'customer_id' => $customer->id,
            'assigned_user_id' => $agent->id,
            'sla_definition_id' => $sla->id,
            'subject' => 'Customer cannot access portal',
            'category' => 'general',
            'priority' => 'medium',
            'status' => 'open',
            'created_by' => $user->id,
        ]);

        $ticket = Ticket::query()->firstOrFail();

        $this->assertNotNull($ticket->code);
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'activity_type' => 'ticket_created',
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'activity_type' => 'assignment_recorded',
            'user_id' => $user->id,
        ]);
    }

    public function test_ticket_categorization_accepts_all_supported_categories(): void
    {
        $this->actingAsTicketManager();
        $customer = $this->createCustomer();
        $sla = $this->createSlaDefinition();

        foreach (['general', 'technical', 'billing', 'priority-follow-up'] as $category) {
            $response = $this->postJson('/api/tickets', [
                'customerId' => $customer->id,
                'assignedUserId' => null,
                'slaDefinitionId' => $sla->id,
                'category' => $category,
                'priority' => 'medium',
                'subject' => sprintf('Ticket for %s', $category),
                'description' => sprintf('Description for %s.', $category),
            ]);

            $response->assertCreated()->assertJsonPath('data.category', $category);
        }

        $this->assertSame(4, Ticket::query()->count());
    }

    public function test_ticket_categorization_rejects_invalid_category_with_422(): void
    {
        $this->actingAsTicketManager();
        $customer = $this->createCustomer();

        $this->postJson('/api/tickets', [
            'customerId' => $customer->id,
            'category' => 'umum',
            'priority' => 'medium',
            'subject' => 'Invalid localized category',
            'description' => 'This should be rejected by the backend validator.',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['category']);
    }

    public function test_ticket_assignment_updates_database_relation_and_logs_activity(): void
    {
        $manager = $this->actingAsTicketManager();
        $agent = User::factory()->create(['role' => 'service']);
        $ticket = $this->createTicket();

        $response = $this->patchJson("/api/tickets/{$ticket->id}/assign", [
            'assignedUserId' => $agent->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.assigned_to', $agent->id)
            ->assertJsonPath('data.assignedUser.id', $agent->id);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_user_id' => $agent->id,
            'updated_by' => $manager->id,
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'activity_type' => 'assignment_changed',
            'user_id' => $manager->id,
        ]);
    }

    public function test_ticket_escalation_updates_priority_and_records_activity_log(): void
    {
        $manager = $this->actingAsTicketManager();
        $ticket = $this->createTicket([
            'priority' => 'medium',
        ]);

        $response = $this->postJson("/api/tickets/{$ticket->id}/escalate", [
            'targetPriority' => 'critical',
            'reason' => 'Priority customer waiting for a callback.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.priority', 'critical');

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'priority' => 'critical',
            'escalation_level' => 1,
            'updated_by' => $manager->id,
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'activity_type' => 'manual_escalation_placeholder',
            'user_id' => $manager->id,
        ]);
    }

    public function test_ticket_activity_log_records_create_status_assign_and_escalate_actions(): void
    {
        $manager = $this->actingAsTicketManager();
        $customer = $this->createCustomer();
        $agent = User::factory()->create(['role' => 'service']);
        $sla = $this->createSlaDefinition();

        $createResponse = $this->postJson('/api/tickets', [
            'customerId' => $customer->id,
            'assignedUserId' => null,
            'slaDefinitionId' => $sla->id,
            'category' => 'technical',
            'priority' => 'high',
            'subject' => 'Agent workflow regression',
            'description' => 'Need support workflow investigation.',
        ])->assertCreated();

        $ticketId = $createResponse->json('data.id');

        $this->patchJson("/api/tickets/{$ticketId}/status", [
            'status' => 'in_progress',
        ])->assertOk();

        $this->patchJson("/api/tickets/{$ticketId}/assign", [
            'assignedUserId' => $agent->id,
        ])->assertOk();

        $this->postJson("/api/tickets/{$ticketId}/escalate", [
            'targetPriority' => 'critical',
            'reason' => 'VIP escalation path.',
        ])->assertOk();

        $ticket = Ticket::query()->findOrFail($ticketId);

        $activityTypes = TicketActivity::query()
            ->where('ticket_id', $ticket->id)
            ->pluck('activity_type')
            ->all();

        $this->assertContains('ticket_created', $activityTypes);
        $this->assertContains('assignment_recorded', $activityTypes);
        $this->assertContains('status_changed', $activityTypes);
        $this->assertContains('assignment_changed', $activityTypes);
        $this->assertContains('manual_escalation_placeholder', $activityTypes);

        $this->assertDatabaseCount('ticket_activities', 5);
        $this->assertSame($manager->id, $ticket->fresh()->updated_by);
    }

    private function actingAsTicketManager(): User
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'module_permissions' => [
                'customers' => 'full',
                'tickets' => 'full',
                'inbox' => 'full',
                'whatsapp' => 'full',
                'invoice' => 'full',
            ],
        ]);

        Sanctum::actingAs($user);

        return $user;
    }

    private function createCustomer(): Pelanggan
    {
        return Pelanggan::query()->create([
            'nama' => 'Service Customer',
            'email' => 'service.customer@example.com',
            'no_hp' => '08123456789',
            'status' => 'active',
            'source' => 'manual',
        ]);
    }

    private function createSlaDefinition(array $overrides = []): SLA
    {
        return SLA::query()->create(array_merge([
            'name' => 'Priority Standard',
            'description' => 'Standard SLA definition for feature test coverage.',
            'category' => 'general',
            'priority' => 'medium',
            'first_response_minutes' => 30,
            'resolution_minutes' => 240,
            'warning_before_minutes' => 60,
            'auto_escalate' => false,
            'escalation_priority' => null,
            'is_active' => true,
        ], $overrides));
    }

    private function createTicket(array $overrides = []): Ticket
    {
        $manager = User::query()->first() ?? $this->actingAsTicketManager();
        $customer = $this->createCustomer();
        $sla = $this->createSlaDefinition();

        return Ticket::query()->create(array_merge([
            'code' => 'TCK-000001',
            'customer_id' => $customer->id,
            'assigned_user_id' => null,
            'sla_definition_id' => $sla->id,
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
            'subject' => 'Existing service ticket',
            'description' => 'Existing service ticket description.',
            'category' => 'general',
            'status' => 'open',
            'priority' => 'medium',
            'escalation_level' => 0,
            'alert_state' => 'on_track',
            'first_response_due_at' => now()->addMinutes(30),
            'resolution_due_at' => now()->addHours(4),
            'last_activity_at' => now(),
        ], $overrides));
    }
}