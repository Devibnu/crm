<?php

namespace Tests\Feature;

use App\Models\LeadScoringRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadScoringRuleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.lead-scoring.index'))
            ->assertOk()
            ->assertSee('Lead Scoring &amp; Routing', false)
            ->assertSee('Total Rules')
            ->assertSee('Active Rules')
            ->assertSee('Auto Assign Rules')
            ->assertSee('Total Executions');
    }

    public function test_rule_can_be_created(): void
    {
        $response = $this->post(route('admin.marketing.lead-scoring.store'), $this->payload([
            'name' => 'High Intent Demo Rule',
            'auto_assign' => '1',
        ]));

        $rule = LeadScoringRule::query()->where('name', 'High Intent Demo Rule')->firstOrFail();

        $response->assertRedirect(route('admin.marketing.lead-scoring.show', $rule));
        $this->assertDatabaseHas('lead_scoring_rules', [
            'name' => 'High Intent Demo Rule',
            'trigger_source' => 'form_submit',
            'score_value' => 85,
            'routing_team' => 'Enterprise Sales',
            'priority' => 'high',
            'status' => 'active',
            'auto_assign' => true,
        ]);
        $this->assertSame('demo_request', $rule->conditions['form']);
    }

    public function test_show_and_edit_pages_can_be_opened(): void
    {
        $rule = LeadScoringRule::factory()->create(['name' => 'Show Edit Scoring Rule']);

        $this->get(route('admin.marketing.lead-scoring.show', $rule))
            ->assertOk()
            ->assertSee('Show Edit Scoring Rule');

        $this->get(route('admin.marketing.lead-scoring.edit', $rule))
            ->assertOk()
            ->assertSee('Edit Rule');
    }

    public function test_rule_can_be_updated(): void
    {
        $rule = LeadScoringRule::factory()->create(['name' => 'Before Scoring Rule']);

        $response = $this->put(route('admin.marketing.lead-scoring.update', $rule), $this->payload([
            'name' => 'After Scoring Rule',
            'trigger_source' => 'social_engagement',
            'score_value' => 65,
            'priority' => 'medium',
            'status' => 'inactive',
            'conditions' => '{"platform":"linkedin","engagement_rate":{">=":8}}',
            'auto_assign' => '0',
        ]));

        $response->assertRedirect(route('admin.marketing.lead-scoring.show', $rule));
        $this->assertDatabaseHas('lead_scoring_rules', [
            'id' => $rule->id,
            'name' => 'After Scoring Rule',
            'trigger_source' => 'social_engagement',
            'score_value' => 65,
            'priority' => 'medium',
            'status' => 'inactive',
            'auto_assign' => false,
        ]);
    }

    public function test_rule_can_be_deleted(): void
    {
        $rule = LeadScoringRule::factory()->create();

        $response = $this->delete(route('admin.marketing.lead-scoring.destroy', $rule));

        $response->assertRedirect(route('admin.marketing.lead-scoring.index'));
        $this->assertDatabaseMissing('lead_scoring_rules', ['id' => $rule->id]);
    }

    public function test_search_works(): void
    {
        $match = LeadScoringRule::factory()->create([
            'name' => 'Searchable Lead Rule',
            'notes' => 'Unique routing note for search.',
        ]);
        $other = LeadScoringRule::factory()->create(['name' => 'Other Lead Rule']);

        $this->get(route('admin.marketing.lead-scoring.index', ['q' => 'Unique routing note']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);
    }

    public function test_filter_trigger_source_works(): void
    {
        $campaign = LeadScoringRule::factory()->create(['name' => 'Campaign Trigger Rule', 'trigger_source' => 'campaign_engagement']);
        $manual = LeadScoringRule::factory()->create(['name' => 'Manual Trigger Rule', 'trigger_source' => 'manual']);

        $this->get(route('admin.marketing.lead-scoring.index', ['trigger_source' => 'campaign_engagement']))
            ->assertOk()
            ->assertSee($campaign->name)
            ->assertDontSee($manual->name);
    }

    public function test_filter_priority_works(): void
    {
        $high = LeadScoringRule::factory()->create(['name' => 'High Priority Rule', 'priority' => 'high']);
        $low = LeadScoringRule::factory()->create(['name' => 'Low Priority Rule', 'priority' => 'low']);

        $this->get(route('admin.marketing.lead-scoring.index', ['priority' => 'high']))
            ->assertOk()
            ->assertSee($high->name)
            ->assertDontSee($low->name);
    }

    public function test_filter_status_works(): void
    {
        $active = LeadScoringRule::factory()->create(['name' => 'Active Scoring Filter', 'status' => 'active']);
        $inactive = LeadScoringRule::factory()->create(['name' => 'Inactive Scoring Filter', 'status' => 'inactive']);

        $this->get(route('admin.marketing.lead-scoring.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);
    }

    public function test_json_conditions_are_displayed(): void
    {
        $rule = LeadScoringRule::factory()->create([
            'name' => 'JSON Conditions Rule',
            'conditions' => ['form' => 'demo_request', 'lead_score' => ['>=' => 75]],
        ]);

        $this->get(route('admin.marketing.lead-scoring.show', $rule))
            ->assertOk()
            ->assertSee('Conditions JSON')
            ->assertSee('form')
            ->assertSee('demo_request')
            ->assertSee('lead_score')
            ->assertSee('75');
    }

    public function test_score_progress_is_displayed_correctly(): void
    {
        $rule = LeadScoringRule::factory()->create([
            'name' => 'Score Progress Rule',
            'score_value' => 72,
        ]);

        $this->get(route('admin.marketing.lead-scoring.show', $rule))
            ->assertOk()
            ->assertSee('Score Visualization')
            ->assertSee('72%')
            ->assertSee('width: 72%', false);
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Default Lead Scoring Rule',
            'trigger_source' => 'form_submit',
            'score_value' => 85,
            'routing_team' => 'Enterprise Sales',
            'routing_user' => 'Ari Sales',
            'conditions' => '{"form":"demo_request","required_fields":["email","company"]}',
            'priority' => 'high',
            'status' => 'active',
            'auto_assign' => '0',
            'execution_count' => 12,
            'last_executed_at' => '2026-05-10 09:00:00',
            'notes' => 'Lead scoring rule created from feature test.',
            'created_by' => 'Sales Ops',
        ], $overrides);
    }
}
