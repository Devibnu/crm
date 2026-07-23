<?php

namespace Tests\Feature;

use App\Models\BusinessCalendar;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlaPolicyCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_sla_policy_index_is_accessible(): void
    {
        $this->get(route('admin.service.sla.index'))
            ->assertOk()
            ->assertSee('SLA Management')
            ->assertSee('Kelola policy response dan resolution target untuk ticket layanan pelanggan.');
    }

    public function test_sla_policy_create_is_accessible(): void
    {
        $this->get(route('admin.service.sla.create'))
            ->assertOk()
            ->assertSee('Add SLA Policy');
    }

    public function test_sla_policy_can_be_created(): void
    {
        $response = $this->post(route('admin.service.sla.store'), [
            'name' => 'Urgent Response SLA',
            'description' => 'Urgent tickets must receive a fast response.',
            'business_calendar_id' => $this->activeCalendarId(),
            'priority' => 'urgent',
            'response_time_minutes' => 15,
            'resolution_time_minutes' => 120,
            'is_active' => 1,
        ]);

        $policy = SlaPolicy::query()->where('name', 'Urgent Response SLA')->firstOrFail();

        $response->assertRedirect(route('admin.service.sla.show', $policy));

        $this->assertDatabaseHas('sla_policies', [
            'id' => $policy->id,
            'name' => 'Urgent Response SLA',
            'priority' => 'urgent',
            'response_time_minutes' => 15,
            'resolution_time_minutes' => 120,
            'is_active' => true,
        ]);
    }

    public function test_sla_policy_show_is_accessible(): void
    {
        $policy = SlaPolicy::factory()->create([
            'name' => 'Show SLA Policy',
        ]);

        $this->get(route('admin.service.sla.show', $policy))
            ->assertOk()
            ->assertSee('SLA Policy 360')
            ->assertSee('Show SLA Policy');
    }

    public function test_sla_policy_edit_is_accessible(): void
    {
        $policy = SlaPolicy::factory()->create();

        $this->get(route('admin.service.sla.edit', $policy))
            ->assertOk()
            ->assertSee('Edit SLA Policy')
            ->assertSee($policy->name);
    }

    public function test_sla_policy_can_be_updated(): void
    {
        $policy = SlaPolicy::factory()->create([
            'name' => 'Before SLA Update',
            'priority' => 'medium',
            'is_active' => true,
        ]);

        $response = $this->put(route('admin.service.sla.update', $policy), [
            'name' => 'After SLA Update',
            'description' => 'Updated SLA description.',
            'business_calendar_id' => $this->activeCalendarId(),
            'priority' => 'high',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 480,
            'is_active' => 0,
        ]);

        $response->assertRedirect(route('admin.service.sla.show', $policy));

        $this->assertDatabaseHas('sla_policies', [
            'id' => $policy->id,
            'name' => 'After SLA Update',
            'priority' => 'high',
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 480,
            'is_active' => false,
        ]);
    }

    public function test_sla_policy_can_be_activated(): void
    {
        $policy = SlaPolicy::factory()->create([
            'priority' => 'high',
            'is_active' => false,
            'response_time_minutes' => 30,
            'resolution_time_minutes' => 240,
        ]);

        app(SlaPolicyService::class)->activate($policy);

        $this->assertDatabaseHas('sla_policies', [
            'id' => $policy->id,
            'priority' => 'high',
            'is_active' => true,
        ]);
    }

    public function test_sla_policy_can_be_deactivated(): void
    {
        $policy = SlaPolicy::factory()->create([
            'priority' => 'medium',
            'is_active' => true,
        ]);

        app(SlaPolicyService::class)->deactivate($policy);

        $this->assertDatabaseHas('sla_policies', [
            'id' => $policy->id,
            'is_active' => false,
        ]);
    }

    public function test_duplicate_active_priority_is_rejected_on_create(): void
    {
        SlaPolicy::factory()->create([
            'priority' => 'urgent',
            'is_active' => true,
        ]);

        $response = $this->from(route('admin.service.sla.create'))
            ->post(route('admin.service.sla.store'), [
                'name' => 'Duplicate Urgent SLA',
                'description' => 'Should fail because urgent already has an active policy.',
                'business_calendar_id' => $this->activeCalendarId(),
                'priority' => 'urgent',
                'response_time_minutes' => 15,
                'resolution_time_minutes' => 120,
                'is_active' => 1,
            ]);

        $response
            ->assertRedirect(route('admin.service.sla.create'))
            ->assertSessionHasErrors('priority');

        $this->assertDatabaseMissing('sla_policies', [
            'name' => 'Duplicate Urgent SLA',
            'priority' => 'urgent',
            'is_active' => true,
        ]);
    }

    public function test_duplicate_active_priority_is_rejected_on_update(): void
    {
        SlaPolicy::factory()->create([
            'priority' => 'high',
            'is_active' => true,
        ]);
        $policy = SlaPolicy::factory()->create([
            'name' => 'Inactive High Candidate',
            'priority' => 'high',
            'is_active' => false,
        ]);

        $response = $this->from(route('admin.service.sla.edit', $policy))
            ->put(route('admin.service.sla.update', $policy), [
                'name' => 'Inactive High Candidate',
                'description' => 'Attempt activation.',
                'business_calendar_id' => $this->activeCalendarId(),
                'priority' => 'high',
                'response_time_minutes' => 45,
                'resolution_time_minutes' => 300,
                'is_active' => 1,
            ]);

        $response
            ->assertRedirect(route('admin.service.sla.edit', $policy))
            ->assertSessionHasErrors('priority');

        $this->assertFalse($policy->fresh()->is_active);
    }

    public function test_invalid_response_time_is_rejected(): void
    {
        $response = $this->from(route('admin.service.sla.create'))
            ->post(route('admin.service.sla.store'), [
                'name' => 'Invalid Response SLA',
                'description' => 'Response target must be positive.',
                'business_calendar_id' => $this->activeCalendarId(),
                'priority' => 'low',
                'response_time_minutes' => 0,
                'resolution_time_minutes' => 120,
                'is_active' => 1,
            ]);

        $response
            ->assertRedirect(route('admin.service.sla.create'))
            ->assertSessionHasErrors('response_time_minutes');
    }

    public function test_invalid_resolution_time_is_rejected(): void
    {
        $response = $this->from(route('admin.service.sla.create'))
            ->post(route('admin.service.sla.store'), [
                'name' => 'Invalid Resolution SLA',
                'description' => 'Resolution target must exceed response target.',
                'business_calendar_id' => $this->activeCalendarId(),
                'priority' => 'medium',
                'response_time_minutes' => 120,
                'resolution_time_minutes' => 120,
                'is_active' => 1,
            ]);

        $response
            ->assertRedirect(route('admin.service.sla.create'))
            ->assertSessionHasErrors('resolution_time_minutes');
    }

    public function test_sla_policy_can_be_deleted(): void
    {
        $policy = SlaPolicy::factory()->create();

        $response = $this->delete(route('admin.service.sla.destroy', $policy));

        $response->assertRedirect(route('admin.service.sla.index'));

        $this->assertDatabaseMissing('sla_policies', [
            'id' => $policy->id,
        ]);
    }

    public function test_sla_policy_used_by_active_ticket_cannot_be_deleted(): void
    {
        $policy = SlaPolicy::factory()->create([
            'priority' => 'urgent',
            'is_active' => true,
        ]);
        Ticket::factory()->create([
            'priority' => 'urgent',
            'status' => 'open',
        ]);

        $response = $this->from(route('admin.service.sla.show', $policy))
            ->delete(route('admin.service.sla.destroy', $policy));

        $response
            ->assertRedirect(route('admin.service.sla.show', $policy))
            ->assertSessionHasErrors('policy');

        $this->assertDatabaseHas('sla_policies', [
            'id' => $policy->id,
        ]);
    }

    public function test_sla_policy_search_works(): void
    {
        $match = SlaPolicy::factory()->create([
            'name' => 'Searchable SLA Policy',
            'description' => 'Searchable response terms.',
        ]);
        $other = SlaPolicy::factory()->create([
            'name' => 'Other SLA Policy',
            'description' => 'Other response terms.',
        ]);

        $this->get(route('admin.service.sla.index', ['q' => 'Searchable']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);
    }

    public function test_sla_policy_priority_filter_works(): void
    {
        $urgent = SlaPolicy::factory()->create(['name' => 'Urgent SLA Filter', 'priority' => 'urgent']);
        $low = SlaPolicy::factory()->create(['name' => 'Low SLA Filter', 'priority' => 'low']);

        $this->get(route('admin.service.sla.index', ['priority' => 'urgent']))
            ->assertOk()
            ->assertSee($urgent->name)
            ->assertDontSee($low->name);
    }

    public function test_sla_policy_active_status_filter_works(): void
    {
        $active = SlaPolicy::factory()->create(['name' => 'Active SLA Filter', 'is_active' => true]);
        $inactive = SlaPolicy::factory()->create(['name' => 'Inactive SLA Filter', 'is_active' => false]);

        $this->get(route('admin.service.sla.index', ['is_active' => 'active']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);

        $this->get(route('admin.service.sla.index', ['is_active' => 'inactive']))
            ->assertOk()
            ->assertSee($inactive->name)
            ->assertDontSee($active->name);
    }

    public function test_sla_policy_routes_reject_unauthorized_users(): void
    {
        $policy = SlaPolicy::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.service.sla.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.service.sla.create'))
            ->assertForbidden();

        $this->actingAs($user)
            ->post(route('admin.service.sla.store'), [
                'name' => 'Unauthorized SLA',
                'business_calendar_id' => $this->activeCalendarId(),
                'priority' => 'low',
                'response_time_minutes' => 60,
                'resolution_time_minutes' => 120,
                'is_active' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.service.sla.edit', $policy))
            ->assertForbidden();

        $this->actingAs($user)
            ->put(route('admin.service.sla.update', $policy), [
                'name' => 'Unauthorized Update',
                'business_calendar_id' => $this->activeCalendarId(),
                'priority' => 'low',
                'response_time_minutes' => 60,
                'resolution_time_minutes' => 120,
                'is_active' => 1,
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->delete(route('admin.service.sla.destroy', $policy))
            ->assertForbidden();
    }

    protected function activeCalendarId(): int
    {
        return BusinessCalendar::factory()->create([
            'is_active' => true,
            'is_default' => false,
        ])->id;
    }

}
