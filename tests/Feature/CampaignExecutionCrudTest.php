<?php

namespace Tests\Feature;

use App\Models\AudienceSegment;
use App\Models\CampaignExecution;
use App\Models\MarketingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignExecutionCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.executions.index'))
            ->assertOk()
            ->assertSee('Campaign Execution')
            ->assertSee('Total Executions')
            ->assertSee('Running')
            ->assertSee('Completed')
            ->assertSee('Total Sent');
    }

    public function test_execution_can_be_created(): void
    {
        $campaign = MarketingCampaign::factory()->create();
        $segment = AudienceSegment::factory()->create();

        $response = $this->post(route('admin.marketing.executions.store'), $this->payload([
            'marketing_campaign_id' => $campaign->id,
            'audience_segment_id' => $segment->id,
            'execution_name' => 'Launch Email Execution',
            'channel' => 'email',
            'status' => 'running',
        ]));

        $execution = CampaignExecution::query()->where('execution_name', 'Launch Email Execution')->firstOrFail();

        $response->assertRedirect(route('admin.marketing.executions.show', $execution));
        $this->assertDatabaseHas('campaign_executions', [
            'execution_name' => 'Launch Email Execution',
            'channel' => 'email',
            'status' => 'running',
            'marketing_campaign_id' => $campaign->id,
            'audience_segment_id' => $segment->id,
        ]);
    }

    public function test_show_and_edit_pages_can_be_opened(): void
    {
        $execution = CampaignExecution::factory()->create(['execution_name' => 'Show Edit Execution']);

        $this->get(route('admin.marketing.executions.show', $execution))
            ->assertOk()
            ->assertSee('Show Edit Execution');

        $this->get(route('admin.marketing.executions.edit', $execution))
            ->assertOk()
            ->assertSee('Edit Campaign Execution');
    }

    public function test_execution_can_be_updated(): void
    {
        $execution = CampaignExecution::factory()->create(['execution_name' => 'Before Execution']);

        $response = $this->put(route('admin.marketing.executions.update', $execution), $this->payload([
            'execution_name' => 'After Execution',
            'channel' => 'whatsapp',
            'status' => 'completed',
            'sent_count' => 1500,
        ]));

        $response->assertRedirect(route('admin.marketing.executions.show', $execution));
        $this->assertDatabaseHas('campaign_executions', [
            'id' => $execution->id,
            'execution_name' => 'After Execution',
            'channel' => 'whatsapp',
            'status' => 'completed',
            'sent_count' => 1500,
        ]);
    }

    public function test_execution_can_be_deleted(): void
    {
        $execution = CampaignExecution::factory()->create();

        $response = $this->delete(route('admin.marketing.executions.destroy', $execution));

        $response->assertRedirect(route('admin.marketing.executions.index'));
        $this->assertDatabaseMissing('campaign_executions', ['id' => $execution->id]);
    }

    public function test_search_execution_works(): void
    {
        $campaign = MarketingCampaign::factory()->create(['name' => 'Search Campaign Name']);
        $segment = AudienceSegment::factory()->create(['name' => 'Search Segment Name']);
        $match = CampaignExecution::factory()->create([
            'marketing_campaign_id' => $campaign->id,
            'audience_segment_id' => $segment->id,
            'execution_name' => 'Matched Execution Search',
        ]);
        $other = CampaignExecution::factory()->create([
            'marketing_campaign_id' => null,
            'audience_segment_id' => null,
            'execution_name' => 'Other Execution Search',
        ]);

        $this->get(route('admin.marketing.executions.index', ['q' => 'Search Campaign Name']))
            ->assertOk()
            ->assertSee($match->execution_name)
            ->assertDontSee($other->execution_name);

        $this->get(route('admin.marketing.executions.index', ['q' => 'Search Segment Name']))
            ->assertOk()
            ->assertSee($match->execution_name)
            ->assertDontSee($other->execution_name);
    }

    public function test_filter_channel_works(): void
    {
        $email = CampaignExecution::factory()->create(['execution_name' => 'Email Execution Filter', 'channel' => 'email']);
        $sms = CampaignExecution::factory()->create(['execution_name' => 'Sms Execution Filter', 'channel' => 'sms']);

        $this->get(route('admin.marketing.executions.index', ['channel' => 'email']))
            ->assertOk()
            ->assertSee($email->execution_name)
            ->assertDontSee($sms->execution_name);
    }

    public function test_filter_status_works(): void
    {
        $running = CampaignExecution::factory()->create(['execution_name' => 'Running Execution Filter', 'status' => 'running']);
        $failed = CampaignExecution::factory()->create(['execution_name' => 'Failed Execution Filter', 'status' => 'failed']);

        $this->get(route('admin.marketing.executions.index', ['status' => 'running']))
            ->assertOk()
            ->assertSee($running->execution_name)
            ->assertDontSee($failed->execution_name);
    }

    public function test_performance_rates_are_displayed_correctly(): void
    {
        $execution = CampaignExecution::factory()->create([
            'execution_name' => 'Rates Execution',
            'sent_count' => 1000,
            'delivered_count' => 800,
            'opened_count' => 400,
            'clicked_count' => 100,
            'response_count' => 50,
        ]);

        $this->get(route('admin.marketing.executions.show', $execution))
            ->assertOk()
            ->assertSee('Delivered Rate')
            ->assertSee('80.00%')
            ->assertSee('Open Rate')
            ->assertSee('50.00%')
            ->assertSee('Click Rate')
            ->assertSee('25.00%')
            ->assertSee('Response Rate')
            ->assertSee('5.00%');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'marketing_campaign_id' => null,
            'audience_segment_id' => null,
            'execution_name' => 'Default Campaign Execution',
            'channel' => 'email',
            'status' => 'scheduled',
            'scheduled_at' => '2026-05-10 09:00:00',
            'started_at' => '2026-05-10 09:10:00',
            'completed_at' => '2026-05-10 10:00:00',
            'sent_count' => 1000,
            'delivered_count' => 900,
            'opened_count' => 450,
            'clicked_count' => 120,
            'response_count' => 35,
            'notes' => 'Execution created from feature test.',
        ], $overrides);
    }
}
