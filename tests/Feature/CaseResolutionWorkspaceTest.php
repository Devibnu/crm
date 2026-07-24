<?php

namespace Tests\Feature;

use App\Models\CaseResolution;
use App\Models\Customer;
use App\Models\KnowledgeBase;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Tickets\TicketWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CaseResolutionWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_workspace_create_persists_enterprise_resolution_fields(): void
    {
        $ticket = Ticket::factory()->create([
            'created_at' => '2026-07-20 09:00:00',
        ]);
        $article = KnowledgeBase::factory()->create(['title' => 'Router Reset Playbook']);

        $response = $this->post(route('admin.service.case-resolutions.store'), [
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'Router configuration restored',
            'resolution_notes' => 'Validated customer connectivity after configuration rollback.',
            'root_cause' => 'configuration',
            'workaround' => 'Moved customer to backup route.',
            'permanent_fix' => 'Restored correct router policy.',
            'internal_notes' => 'Monitor this account for 24 hours.',
            'resolution_type' => 'fixed',
            'resolution_outcome' => 'resolved',
            'resolved_by' => 'Service Agent',
            'resolved_at' => '2026-07-20T10:30',
            'customer_notified' => 1,
            'customer_notified_at' => '2026-07-20T10:35',
            'customer_confirmation_at' => '2026-07-20T11:00',
            'knowledge_candidate' => 1,
            'knowledge_article_id' => $article->id,
        ]);

        $resolution = CaseResolution::query()->where('resolution_summary', 'Router configuration restored')->firstOrFail();

        $response->assertRedirect(route('admin.service.case-resolutions.show', $resolution));

        $this->assertDatabaseHas('case_resolutions', [
            'id' => $resolution->id,
            'ticket_id' => $ticket->id,
            'root_cause' => 'configuration',
            'workaround' => 'Moved customer to backup route.',
            'permanent_fix' => 'Restored correct router policy.',
            'internal_notes' => 'Monitor this account for 24 hours.',
            'resolution_outcome' => 'resolved',
            'knowledge_candidate' => true,
            'knowledge_article_id' => $article->id,
            'resolution_duration_minutes' => 90,
        ]);
    }

    public function test_workspace_update_keeps_resolution_history_fields_editable(): void
    {
        $article = KnowledgeBase::factory()->create(['title' => 'VPN Troubleshooting']);
        $resolution = CaseResolution::factory()->create([
            'resolution_summary' => 'Before update',
            'resolution_outcome' => 'workaround',
            'knowledge_candidate' => false,
        ]);

        $response = $this->put(route('admin.service.case-resolutions.update', $resolution), [
            'ticket_id' => $resolution->ticket_id,
            'resolution_summary' => 'After update',
            'resolution_notes' => 'Updated detail.',
            'root_cause' => 'network',
            'workaround' => 'Temporary DNS override.',
            'permanent_fix' => 'Network route corrected.',
            'internal_notes' => 'Escalation reviewed.',
            'resolution_type' => 'workaround',
            'resolution_outcome' => 'workaround',
            'resolved_by' => 'Updated Agent',
            'resolved_at' => '2026-07-21T12:00',
            'customer_notified' => 1,
            'customer_notified_at' => '2026-07-21T12:10',
            'customer_confirmation_at' => '2026-07-21T13:00',
            'resolution_duration_minutes' => 240,
            'knowledge_candidate' => 1,
            'knowledge_article_id' => $article->id,
        ]);

        $response->assertRedirect(route('admin.service.case-resolutions.show', $resolution));

        $this->assertDatabaseHas('case_resolutions', [
            'id' => $resolution->id,
            'resolution_summary' => 'After update',
            'root_cause' => 'network',
            'resolution_outcome' => 'workaround',
            'knowledge_candidate' => true,
            'knowledge_article_id' => $article->id,
            'resolution_duration_minutes' => 240,
        ]);
    }

    public function test_workspace_search_covers_ticket_customer_resolver_summary_root_cause_and_outcome(): void
    {
        $ticket = Ticket::factory()->create([
            'ticket_number' => 'TCK-RCA-001',
            'subject' => 'Payment gateway outage',
        ]);
        $ticket->customer()->associate(Customer::factory()->create(['name' => 'Searchable Enterprise']));
        $ticket->save();

        $match = CaseResolution::factory()->create([
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'Gateway certificate fixed',
            'root_cause' => 'third_party',
            'resolution_outcome' => 'resolved',
            'resolved_by' => 'Resolver Search Name',
        ]);
        $other = CaseResolution::factory()->create([
            'ticket_id' => Ticket::factory()->create([
                'customer_id' => Customer::factory()->create(['name' => 'Unrelated Customer'])->id,
                'ticket_number' => 'TCK-RCA-OTHER',
                'subject' => 'Other ticket subject',
            ])->id,
            'resolution_summary' => 'Unrelated resolution',
            'root_cause' => 'hardware',
            'resolution_outcome' => 'cancelled',
            'resolved_by' => 'Other Resolver',
        ]);

        foreach (['TCK-RCA-001', 'Searchable Enterprise', 'Resolver Search Name', 'Gateway certificate', 'third_party', 'resolved'] as $term) {
            $this->get(route('admin.service.case-resolutions.index', ['q' => $term]))
                ->assertOk()
                ->assertSee($match->resolution_summary)
                ->assertDontSee($other->resolution_summary);
        }
    }

    public function test_workspace_filters_and_pagination_preserve_query(): void
    {
        $match = CaseResolution::factory()->create([
            'resolution_summary' => 'Filtered RCA',
            'resolution_type' => 'workaround',
            'resolution_outcome' => 'workaround',
            'root_cause' => 'network',
            'knowledge_candidate' => true,
            'customer_notified' => true,
            'resolved_at' => '2026-07-20 10:00:00',
        ]);
        CaseResolution::factory(12)->create([
            'resolution_type' => 'fixed',
            'resolution_outcome' => 'resolved',
            'root_cause' => 'software_bug',
            'knowledge_candidate' => false,
            'customer_notified' => false,
        ]);

        $response = $this->get(route('admin.service.case-resolutions.index', [
            'resolution_type' => 'workaround',
            'resolution_outcome' => 'workaround',
            'root_cause' => 'network',
            'knowledge_candidate' => 'yes',
            'customer_notified' => 'yes',
            'date_from' => '2026-07-01',
            'date_to' => '2026-07-31',
        ]));

        $response
            ->assertOk()
            ->assertSee($match->resolution_summary)
            ->assertSee('resolution_type=workaround', false)
            ->assertSee('knowledge_candidate=yes', false);
    }

    public function test_resolution_show_displays_timeline_and_knowledge_context(): void
    {
        $article = KnowledgeBase::factory()->create(['title' => 'Known Router Fix']);
        $resolution = CaseResolution::factory()->create([
            'resolution_summary' => 'Known issue resolved',
            'resolution_notes' => 'Resolution timeline body.',
            'root_cause' => 'hardware',
            'resolution_outcome' => 'resolved',
            'workaround' => 'Temporary spare device.',
            'permanent_fix' => 'Replaced defective device.',
            'knowledge_candidate' => true,
            'knowledge_article_id' => $article->id,
            'customer_notified_at' => '2026-07-20 10:00:00',
            'customer_confirmation_at' => '2026-07-20 11:00:00',
        ]);

        $this->get(route('admin.service.case-resolutions.show', $resolution))
            ->assertOk()
            ->assertSee('Resolution Timeline')
            ->assertSee('Known Router Fix')
            ->assertSee('Temporary spare device.')
            ->assertSee('Replaced defective device.');
    }

    public function test_reopened_ticket_increments_latest_resolution_without_overwriting_history(): void
    {
        $ticket = Ticket::factory()->create([
            'status' => 'closed',
            'resolved_at' => '2026-07-20 10:00:00',
            'closed_at' => '2026-07-20 11:00:00',
        ]);
        $resolution = CaseResolution::factory()->create([
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'Original resolution',
            'resolution_notes' => 'Preserve this narrative.',
            'reopened_count' => 0,
            'resolved_at' => '2026-07-20 10:30:00',
        ]);

        app(TicketWorkflowService::class)->reopen($ticket);

        $this->assertDatabaseHas('case_resolutions', [
            'id' => $resolution->id,
            'resolution_summary' => 'Original resolution',
            'resolution_notes' => 'Preserve this narrative.',
            'reopened_count' => 1,
        ]);
    }

    public function test_ticket_360_displays_latest_resolution_and_resolution_timeline(): void
    {
        $ticket = Ticket::factory()->create(['subject' => 'Ticket 360 Resolution']);
        CaseResolution::factory()->create([
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'Earlier resolution',
            'resolved_at' => '2026-07-19 10:00:00',
        ]);
        CaseResolution::factory()->create([
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'Latest resolution',
            'root_cause' => 'software_bug',
            'resolution_outcome' => 'resolved',
            'workaround' => 'Short-term patch.',
            'permanent_fix' => 'Release fix deployed.',
            'resolved_at' => '2026-07-20 10:00:00',
        ]);

        $this->get(route('admin.service.tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Case Resolution')
            ->assertSee('Latest resolution')
            ->assertSee('Resolution fix details')
            ->assertSee('Short-term patch.')
            ->assertSee('Release fix deployed.');
    }

    public function test_dashboard_metrics_and_analytics_are_visible(): void
    {
        CaseResolution::factory()->create([
            'resolution_summary' => 'Metric one',
            'resolution_outcome' => 'resolved',
            'resolution_type' => 'fixed',
            'root_cause' => 'software_bug',
            'knowledge_candidate' => true,
            'resolution_duration_minutes' => 60,
            'reopened_count' => 2,
        ]);
        CaseResolution::factory()->create([
            'resolution_summary' => 'Metric two',
            'resolution_outcome' => 'escalated',
            'resolution_type' => 'escalated',
            'root_cause' => 'network',
            'resolution_duration_minutes' => 120,
        ]);

        $this->get(route('admin.service.case-resolutions.index'))
            ->assertOk()
            ->assertSee('Total Resolution')
            ->assertSee('Knowledge Candidate')
            ->assertSee('Avg Resolution Time')
            ->assertSee('Top Root Causes')
            ->assertSee('Most Reopened Categories');
    }

    public function test_csv_export_uses_current_filtered_data(): void
    {
        $match = CaseResolution::factory()->create([
            'resolution_summary' => 'Exported workaround case',
            'resolution_outcome' => 'workaround',
        ]);
        CaseResolution::factory()->create([
            'resolution_summary' => 'Non exported case',
            'resolution_outcome' => 'resolved',
        ]);

        $response = $this->get(route('admin.service.case-resolutions.index', [
            'resolution_outcome' => 'workaround',
            'export' => 'csv',
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Ticket Number', $content);
        $this->assertStringContainsString($match->resolution_summary, $content);
        $this->assertStringNotContainsString('Non exported case', $content);
    }

    public function test_permission_aware_buttons_are_hidden_without_create_update_delete_permissions(): void
    {
        $resolution = CaseResolution::factory()->create([
            'resolution_summary' => 'Permission hidden resolution',
        ]);
        $role = Role::create(['name' => 'case_viewer_only', 'guard_name' => 'web']);
        $role->syncPermissions(['cases.view']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get(route('admin.service.case-resolutions.index'))
            ->assertOk()
            ->assertSee('Permission hidden resolution')
            ->assertDontSee(route('admin.service.case-resolutions.create'), false)
            ->assertDontSee(route('admin.service.case-resolutions.edit', $resolution), false)
            ->assertDontSee('method="POST" action="'.route('admin.service.case-resolutions.destroy', $resolution).'"', false);

        $this->actingAs($user)
            ->get(route('admin.service.case-resolutions.show', $resolution))
            ->assertOk()
            ->assertDontSee(route('admin.service.case-resolutions.edit', $resolution), false)
            ->assertDontSee('method="POST" action="'.route('admin.service.case-resolutions.destroy', $resolution).'"', false);
    }
}
