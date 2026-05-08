<?php

namespace Tests\Feature;

use App\Models\SlaPolicy;
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
            ->assertSee('Kelola aturan waktu respons dan penyelesaian tiket layanan pelanggan.');
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
            ->assertSee('SLA Policy Detail')
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

    public function test_sla_policy_can_be_deleted(): void
    {
        $policy = SlaPolicy::factory()->create();

        $response = $this->delete(route('admin.service.sla.destroy', $policy));

        $response->assertRedirect(route('admin.service.sla.index'));

        $this->assertDatabaseMissing('sla_policies', [
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
}
