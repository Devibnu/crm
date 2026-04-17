<?php

namespace Tests\Feature;

use App\Jobs\RefreshSlaTimersJob;
use App\Models\Pelanggan;
use App\Models\SLA;
use App\Models\Ticket;
use App\Models\TicketActivity;
use App\Models\User;
use App\Services\SlaTimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SLAFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_sla_definition_stores_time_parameters(): void
    {
        $this->actingAsTicketManager();

        $response = $this->postJson('/api/sla', [
            'name' => 'Escalation Fast Lane',
            'description' => 'Handles urgent technical requests.',
            'category' => 'technical',
            'priority' => 'high',
            'firstResponseMinutes' => 15,
            'resolutionMinutes' => 90,
            'warningBeforeMinutes' => 20,
            'autoEscalate' => true,
            'escalationPriority' => 'critical',
            'isActive' => true,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Escalation Fast Lane')
            ->assertJsonPath('data.priority', 'high')
            ->assertJsonPath('data.firstResponseMinutes', 15)
            ->assertJsonPath('data.resolutionMinutes', 90)
            ->assertJsonPath('data.warningBeforeMinutes', 20)
            ->assertJsonPath('data.autoEscalate', true)
            ->assertJsonPath('data.escalationPriority', 'critical');

        $this->assertDatabaseHas('sla_definitions', [
            'name' => 'Escalation Fast Lane',
            'category' => 'technical',
            'priority' => 'high',
            'first_response_minutes' => 15,
            'resolution_minutes' => 90,
            'warning_before_minutes' => 20,
            'auto_escalate' => true,
            'escalation_priority' => 'critical',
            'is_active' => true,
        ]);
    }

    public function test_create_sla_definition_rejects_invalid_payload_with_422(): void
    {
        $this->actingAsTicketManager();

        $this->postJson('/api/sla', [
            'name' => '',
            'category' => 'umum',
            'priority' => 'critical',
            'firstResponseMinutes' => 0,
            'resolutionMinutes' => 0,
            'warningBeforeMinutes' => 0,
            'autoEscalate' => true,
            'isActive' => true,
        ])->assertStatus(422)
            ->assertJsonValidationErrors([
                'name',
                'category',
                'firstResponseMinutes',
                'resolutionMinutes',
                'warningBeforeMinutes',
            ]);
    }

    public function test_sla_timer_job_marks_ticket_due_soon_and_preserves_countdown_fields(): void
    {
        $this->actingAsTicketManager();

        $sla = $this->createSlaDefinition([
            'warning_before_minutes' => 60,
            'auto_escalate' => false,
        ]);

        $ticket = $this->createTicket([
            'sla_definition_id' => $sla->id,
            'resolution_due_at' => now()->addMinutes(30),
            'first_response_due_at' => now()->addMinutes(10),
            'status' => 'open',
            'alert_state' => 'on_track',
        ]);

        app(SlaTimerService::class)->syncTicket($ticket->fresh(['slaDefinition']));
        RefreshSlaTimersJob::dispatchSync();

        $ticket->refresh();

        $this->assertSame('due_soon', $ticket->alert_state);
        $this->assertNotNull($ticket->first_response_due_at);
        $this->assertNotNull($ticket->resolution_due_at);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'activity_type' => 'sla_alert_placeholder',
        ]);
    }

    public function test_sla_alert_marks_overdue_ticket_and_exposes_alert_payload_in_api(): void
    {
        $this->actingAsTicketManager();

        $sla = $this->createSlaDefinition([
            'category' => 'technical',
            'priority' => 'high',
            'warning_before_minutes' => 15,
            'auto_escalate' => true,
            'escalation_priority' => 'critical',
        ]);

        $ticket = $this->createTicket([
            'sla_definition_id' => $sla->id,
            'category' => 'technical',
            'priority' => 'high',
            'resolution_due_at' => now()->subMinutes(5),
            'first_response_due_at' => now()->subMinutes(10),
            'status' => 'open',
            'alert_state' => 'on_track',
        ]);

        RefreshSlaTimersJob::dispatchSync();

        $ticket->refresh();

        $this->assertSame('overdue', $ticket->alert_state);
        $this->assertSame('critical', $ticket->priority);
        $this->assertSame(1, $ticket->escalation_level);
        $this->assertNotNull($ticket->alert_sent_at);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'activity_type' => 'sla_alert_placeholder',
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'activity_type' => 'auto_escalation_placeholder',
        ]);

        $this->getJson('/api/sla')
            ->assertOk()
            ->assertJsonFragment([
                'ticketId' => $ticket->id,
                'ticketCode' => $ticket->code,
                'alertState' => 'overdue',
            ])
            ->assertJsonFragment(['sla_overdue'])
            ->assertJsonFragment(['escalation_placeholder']);
    }

    public function test_sla_index_returns_definition_and_alert_summary(): void
    {
        $this->actingAsTicketManager();

        $sla = $this->createSlaDefinition();
        $ticket = $this->createTicket([
            'sla_definition_id' => $sla->id,
            'resolution_due_at' => now()->addMinutes(20),
        ]);

        app(SlaTimerService::class)->syncTicket($ticket->fresh(['slaDefinition']));

        $this->getJson('/api/sla')
            ->assertOk()
            ->assertJsonPath('summary.activeDefinitions', SLA::query()->where('is_active', true)->count())
            ->assertJsonFragment([
                'id' => $sla->id,
                'name' => $sla->name,
            ]);
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
            'nama' => 'SLA Customer',
            'email' => 'sla.customer@example.com',
            'no_hp' => '081999999999',
            'status' => 'active',
            'source' => 'manual',
        ]);
    }

    private function createSlaDefinition(array $overrides = []): SLA
    {
        return SLA::query()->create(array_merge([
            'name' => 'Feature Test SLA',
            'description' => 'Feature test SLA definition.',
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
            'code' => 'TCK-000777',
            'customer_id' => $customer->id,
            'assigned_user_id' => null,
            'sla_definition_id' => $sla->id,
            'created_by' => $manager->id,
            'updated_by' => $manager->id,
            'subject' => 'SLA feature coverage ticket',
            'description' => 'Ticket used to verify SLA timer and alert behavior.',
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