<?php

namespace Tests\Feature;

use App\Models\AudienceSegment;
use App\Models\MarketingAutomation;
use App\Models\MarketingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingAutomationCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.automations.index'))
            ->assertOk()
            ->assertSee('Automation &amp; Nurturing', false)
            ->assertSee('Total Automations')
            ->assertSee('Active Automations')
            ->assertSee('Paused Automations')
            ->assertSee('Total Executed');
    }

    public function test_automation_can_be_created(): void
    {
        $campaign = MarketingCampaign::factory()->create();
        $segment = AudienceSegment::factory()->create();

        $response = $this->post(route('admin.marketing.automations.store'), $this->payload([
            'marketing_campaign_id' => $campaign->id,
            'audience_segment_id' => $segment->id,
            'name' => 'Welcome Automation Rule',
        ]));

        $automation = MarketingAutomation::query()->where('name', 'Welcome Automation Rule')->firstOrFail();

        $response->assertRedirect(route('admin.marketing.automations.show', $automation));
        $this->assertDatabaseHas('marketing_automations', [
            'name' => 'Welcome Automation Rule',
            'marketing_campaign_id' => $campaign->id,
            'audience_segment_id' => $segment->id,
            'trigger_type' => 'form_submit',
            'action_type' => 'send_email',
            'status' => 'active',
        ]);
        $this->assertSame('landing_page', $automation->conditions['source']);
        $this->assertSame('welcome_email', $automation->action_payload['template']);
    }

    public function test_show_and_edit_pages_can_be_opened(): void
    {
        $automation = MarketingAutomation::factory()->create(['name' => 'Show Edit Automation']);

        $this->get(route('admin.marketing.automations.show', $automation))
            ->assertOk()
            ->assertSee('Show Edit Automation');

        $this->get(route('admin.marketing.automations.edit', $automation))
            ->assertOk()
            ->assertSee('Edit Automation');
    }

    public function test_automation_can_be_updated(): void
    {
        $automation = MarketingAutomation::factory()->create(['name' => 'Before Automation']);

        $response = $this->put(route('admin.marketing.automations.update', $automation), $this->payload([
            'name' => 'After Automation',
            'trigger_type' => 'link_clicked',
            'action_type' => 'assign_sales',
            'status' => 'paused',
            'delay_minutes' => 120,
            'conditions' => '{"link_type":"pricing"}',
            'action_payload' => '{"team":"Enterprise Sales"}',
        ]));

        $response->assertRedirect(route('admin.marketing.automations.show', $automation));
        $this->assertDatabaseHas('marketing_automations', [
            'id' => $automation->id,
            'name' => 'After Automation',
            'trigger_type' => 'link_clicked',
            'action_type' => 'assign_sales',
            'status' => 'paused',
            'delay_minutes' => 120,
        ]);
    }

    public function test_automation_can_be_deleted(): void
    {
        $automation = MarketingAutomation::factory()->create();

        $response = $this->delete(route('admin.marketing.automations.destroy', $automation));

        $response->assertRedirect(route('admin.marketing.automations.index'));
        $this->assertDatabaseMissing('marketing_automations', ['id' => $automation->id]);
    }

    public function test_search_works(): void
    {
        $match = MarketingAutomation::factory()->create([
            'name' => 'Searchable Automation',
            'notes' => 'Unique nurture rule for search.',
        ]);
        $other = MarketingAutomation::factory()->create(['name' => 'Other Automation']);

        $this->get(route('admin.marketing.automations.index', ['q' => 'Unique nurture rule']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);
    }

    public function test_filter_trigger_type_works(): void
    {
        $opened = MarketingAutomation::factory()->create(['name' => 'Opened Trigger Automation', 'trigger_type' => 'campaign_opened']);
        $manual = MarketingAutomation::factory()->create(['name' => 'Manual Trigger Automation', 'trigger_type' => 'manual']);

        $this->get(route('admin.marketing.automations.index', ['trigger_type' => 'campaign_opened']))
            ->assertOk()
            ->assertSee($opened->name)
            ->assertDontSee($manual->name);
    }

    public function test_filter_action_type_works(): void
    {
        $email = MarketingAutomation::factory()->create(['name' => 'Email Action Automation', 'action_type' => 'send_email']);
        $task = MarketingAutomation::factory()->create(['name' => 'Task Action Automation', 'action_type' => 'create_task']);

        $this->get(route('admin.marketing.automations.index', ['action_type' => 'send_email']))
            ->assertOk()
            ->assertSee($email->name)
            ->assertDontSee($task->name);
    }

    public function test_filter_status_works(): void
    {
        $active = MarketingAutomation::factory()->create(['name' => 'Active Automation Filter', 'status' => 'active']);
        $draft = MarketingAutomation::factory()->create(['name' => 'Draft Automation Filter', 'status' => 'draft']);

        $this->get(route('admin.marketing.automations.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee($active->name)
            ->assertDontSee($draft->name);
    }

    public function test_json_conditions_and_action_payload_are_displayed(): void
    {
        $automation = MarketingAutomation::factory()->create([
            'name' => 'JSON Display Automation',
            'conditions' => ['source' => 'landing_page', 'score' => ['>=' => 70]],
            'action_payload' => ['template' => 'welcome_email', 'subject' => 'Welcome'],
        ]);

        $this->get(route('admin.marketing.automations.show', $automation))
            ->assertOk()
            ->assertSee('Conditions JSON')
            ->assertSee('Action Payload JSON')
            ->assertSee('source')
            ->assertSee('landing_page')
            ->assertSee('template')
            ->assertSee('welcome_email')
            ->assertSee('70');
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'marketing_campaign_id' => null,
            'audience_segment_id' => null,
            'name' => 'Default Automation Rule',
            'trigger_type' => 'form_submit',
            'action_type' => 'send_email',
            'status' => 'active',
            'delay_minutes' => 30,
            'conditions' => '{"source":"landing_page","score":{">=":70}}',
            'action_payload' => '{"template":"welcome_email","subject":"Welcome"}',
            'executed_count' => 5,
            'last_executed_at' => '2026-05-10 09:00:00',
            'created_by' => 'Marketing Ops',
            'notes' => 'Automation created from feature test.',
        ], $overrides);
    }
}
