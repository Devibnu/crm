<?php

namespace Tests\Feature;

use App\Models\CaseResolution;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CaseResolutionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_case_resolution_index_is_accessible(): void
    {
        $this->get(route('admin.service.case-resolutions.index'))
            ->assertOk()
            ->assertSee('Case Resolution')
            ->assertSee('Catat dan kelola penyelesaian kasus layanan pelanggan.');
    }

    public function test_case_resolution_create_is_accessible(): void
    {
        $this->get(route('admin.service.case-resolutions.create'))
            ->assertOk()
            ->assertSee('Add Case Resolution');
    }

    public function test_case_resolution_can_be_created(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->post(route('admin.service.case-resolutions.store'), [
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'Password reset issue fixed',
            'resolution_notes' => 'Reset link regenerated and verified.',
            'root_cause' => 'Expired password reset token.',
            'resolution_type' => 'fixed',
            'resolved_by' => 'Support Agent',
            'resolved_at' => '2026-05-10T11:00',
            'customer_notified' => 1,
        ]);

        $resolution = CaseResolution::query()->where('resolution_summary', 'Password reset issue fixed')->firstOrFail();

        $response->assertRedirect(route('admin.service.case-resolutions.show', $resolution));

        $this->assertDatabaseHas('case_resolutions', [
            'id' => $resolution->id,
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'Password reset issue fixed',
            'resolution_type' => 'fixed',
            'resolved_by' => 'Support Agent',
            'customer_notified' => true,
        ]);
    }

    public function test_case_resolution_show_is_accessible(): void
    {
        $resolution = CaseResolution::factory()->create([
            'resolution_summary' => 'Show Case Resolution',
        ]);

        $this->get(route('admin.service.case-resolutions.show', $resolution))
            ->assertOk()
            ->assertSee('Case Resolution Detail')
            ->assertSee('Show Case Resolution');
    }

    public function test_case_resolution_edit_is_accessible(): void
    {
        $resolution = CaseResolution::factory()->create();

        $this->get(route('admin.service.case-resolutions.edit', $resolution))
            ->assertOk()
            ->assertSee('Edit Case Resolution')
            ->assertSee($resolution->resolution_summary);
    }

    public function test_case_resolution_can_be_updated(): void
    {
        $ticket = Ticket::factory()->create();
        $resolution = CaseResolution::factory()->create([
            'resolution_summary' => 'Before Case Update',
            'resolution_type' => 'workaround',
        ]);

        $response = $this->put(route('admin.service.case-resolutions.update', $resolution), [
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'After Case Update',
            'resolution_notes' => 'Updated resolution notes.',
            'root_cause' => 'Updated root cause.',
            'resolution_type' => 'escalated',
            'resolved_by' => 'Updated Resolver',
            'resolved_at' => '2026-05-11T12:00',
            'customer_notified' => 0,
        ]);

        $response->assertRedirect(route('admin.service.case-resolutions.show', $resolution));

        $this->assertDatabaseHas('case_resolutions', [
            'id' => $resolution->id,
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'After Case Update',
            'resolution_type' => 'escalated',
            'resolved_by' => 'Updated Resolver',
            'customer_notified' => false,
        ]);
    }

    public function test_case_resolution_can_be_deleted(): void
    {
        $resolution = CaseResolution::factory()->create();

        $response = $this->delete(route('admin.service.case-resolutions.destroy', $resolution));

        $response->assertRedirect(route('admin.service.case-resolutions.index'));

        $this->assertDatabaseMissing('case_resolutions', [
            'id' => $resolution->id,
        ]);
    }

    public function test_case_resolution_search_works(): void
    {
        $ticket = Ticket::factory()->create([
            'ticket_number' => 'TCK-CASE-SEARCH',
            'subject' => 'Searchable Ticket Subject',
        ]);
        $match = CaseResolution::factory()->create([
            'ticket_id' => $ticket->id,
            'resolution_summary' => 'Searchable Resolution Summary',
            'resolved_by' => 'Searchable Resolver',
        ]);
        $otherTicket = Ticket::factory()->create([
            'ticket_number' => 'TCK-CASE-OTHER',
            'subject' => 'Other Ticket Subject',
        ]);
        $other = CaseResolution::factory()->create([
            'ticket_id' => $otherTicket->id,
            'resolution_summary' => 'Other Resolution Summary',
            'resolved_by' => 'Other Resolver',
        ]);

        $this->get(route('admin.service.case-resolutions.index', ['q' => 'TCK-CASE-SEARCH']))
            ->assertOk()
            ->assertSee($match->resolution_summary)
            ->assertDontSee($other->resolution_summary);

        $this->get(route('admin.service.case-resolutions.index', ['q' => 'Searchable Resolver']))
            ->assertOk()
            ->assertSee($match->resolution_summary)
            ->assertDontSee($other->resolution_summary);
    }

    public function test_case_resolution_type_filter_works(): void
    {
        $fixed = CaseResolution::factory()->create([
            'resolution_summary' => 'Fixed Resolution Filter',
            'resolution_type' => 'fixed',
        ]);
        $escalated = CaseResolution::factory()->create([
            'resolution_summary' => 'Escalated Resolution Filter',
            'resolution_type' => 'escalated',
        ]);

        $this->get(route('admin.service.case-resolutions.index', ['resolution_type' => 'fixed']))
            ->assertOk()
            ->assertSee($fixed->resolution_summary)
            ->assertDontSee($escalated->resolution_summary);
    }

    public function test_case_resolution_customer_notified_filter_works(): void
    {
        $notified = CaseResolution::factory()->create([
            'resolution_summary' => 'Customer Alert Sent Filter',
            'customer_notified' => true,
        ]);
        $notNotified = CaseResolution::factory()->create([
            'resolution_summary' => 'Pending Customer Alert Filter',
            'customer_notified' => false,
        ]);

        $this->get(route('admin.service.case-resolutions.index', ['customer_notified' => 'yes']))
            ->assertOk()
            ->assertSee($notified->resolution_summary)
            ->assertDontSee($notNotified->resolution_summary);

        $this->get(route('admin.service.case-resolutions.index', ['customer_notified' => 'no']))
            ->assertOk()
            ->assertSee($notNotified->resolution_summary)
            ->assertDontSee($notified->resolution_summary);
    }
}
