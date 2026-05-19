<?php

namespace Tests\Feature;

use App\Models\MarketingCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCampaignCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.campaigns.index'))
            ->assertOk()
            ->assertSee('Campaign Management')
            ->assertSee('Total Campaigns')
            ->assertSee('Running Campaigns')
            ->assertSee('Completed Campaigns')
            ->assertSee('Total Leads Generated');
    }

    public function test_campaign_can_be_created(): void
    {
        $payload = $this->payload([
            'name' => 'Enterprise Launch Campaign',
            'type' => 'email',
            'status' => 'running',
        ]);

        $response = $this->post(route('admin.marketing.campaigns.store'), $payload);

        $campaign = MarketingCampaign::query()->where('name', 'Enterprise Launch Campaign')->firstOrFail();

        $response->assertRedirect(route('admin.marketing.campaigns.show', $campaign));
        $this->assertDatabaseHas('marketing_campaigns', [
            'name' => 'Enterprise Launch Campaign',
            'type' => 'email',
            'status' => 'running',
        ]);
    }

    public function test_show_and_edit_pages_can_be_opened(): void
    {
        $campaign = MarketingCampaign::factory()->create(['name' => 'Show Edit Campaign']);

        $this->get(route('admin.marketing.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Show Edit Campaign');

        $this->get(route('admin.marketing.campaigns.edit', $campaign))
            ->assertOk()
            ->assertSee('Edit Campaign');
    }

    public function test_campaign_can_be_updated(): void
    {
        $campaign = MarketingCampaign::factory()->create([
            'name' => 'Before Campaign',
            'status' => 'draft',
        ]);

        $response = $this->put(route('admin.marketing.campaigns.update', $campaign), $this->payload([
            'name' => 'After Campaign',
            'type' => 'webinar',
            'status' => 'completed',
            'actual_leads' => 240,
        ]));

        $response->assertRedirect(route('admin.marketing.campaigns.show', $campaign));
        $this->assertDatabaseHas('marketing_campaigns', [
            'id' => $campaign->id,
            'name' => 'After Campaign',
            'type' => 'webinar',
            'status' => 'completed',
            'actual_leads' => 240,
        ]);
    }

    public function test_campaign_can_be_deleted(): void
    {
        $campaign = MarketingCampaign::factory()->create();

        $response = $this->delete(route('admin.marketing.campaigns.destroy', $campaign));

        $response->assertRedirect(route('admin.marketing.campaigns.index'));
        $this->assertDatabaseMissing('marketing_campaigns', ['id' => $campaign->id]);
    }

    public function test_search_campaign_works(): void
    {
        $match = MarketingCampaign::factory()->create([
            'name' => 'Searchable Demand Campaign',
            'description' => 'Targeted retention motion',
            'target_audience' => 'Enterprise audience',
        ]);
        $other = MarketingCampaign::factory()->create(['name' => 'Other Campaign']);

        $this->get(route('admin.marketing.campaigns.index', ['q' => 'retention motion']))
            ->assertOk()
            ->assertSee($match->name)
            ->assertDontSee($other->name);
    }

    public function test_filter_type_works(): void
    {
        $email = MarketingCampaign::factory()->create(['name' => 'Email Campaign Filter', 'type' => 'email']);
        $ads = MarketingCampaign::factory()->create(['name' => 'Ads Campaign Filter', 'type' => 'ads']);

        $this->get(route('admin.marketing.campaigns.index', ['type' => 'email']))
            ->assertOk()
            ->assertSee($email->name)
            ->assertDontSee($ads->name);
    }

    public function test_filter_status_works(): void
    {
        $running = MarketingCampaign::factory()->create(['name' => 'Running Campaign Filter', 'status' => 'running']);
        $draft = MarketingCampaign::factory()->create(['name' => 'Draft Campaign Filter', 'status' => 'draft']);

        $this->get(route('admin.marketing.campaigns.index', ['status' => 'running']))
            ->assertOk()
            ->assertSee($running->name)
            ->assertDontSee($draft->name);
    }

    public function test_lead_progress_is_displayed_correctly(): void
    {
        $campaign = MarketingCampaign::factory()->create([
            'name' => 'Progress Campaign',
            'expected_leads' => 200,
            'actual_leads' => 75,
        ]);

        $this->get(route('admin.marketing.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('37.50%')
            ->assertSee('75 actual leads from 200 expected leads');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Default Marketing Campaign',
            'type' => 'email',
            'status' => 'scheduled',
            'target_audience' => 'Enterprise prospects',
            'budget' => 15000000,
            'expected_leads' => 300,
            'actual_leads' => 25,
            'start_date' => '2026-05-10',
            'end_date' => '2026-06-10',
            'description' => 'Campaign created from feature test.',
            'created_by' => 'Marketing Ops',
        ], $overrides);
    }
}
