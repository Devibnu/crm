<?php

namespace Tests\Feature;

use App\Models\MarketingCampaign;
use App\Models\SocialMediaEngagement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialMediaEngagementCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_can_be_opened(): void
    {
        $this->get(route('admin.marketing.social-engagements.index'))
            ->assertOk()
            ->assertSee('Social Media Engagement')
            ->assertSee('Total Posts')
            ->assertSee('Published Posts')
            ->assertSee('Total Impressions')
            ->assertSee('Average Engagement Rate');
    }

    public function test_social_post_can_be_created(): void
    {
        $campaign = MarketingCampaign::factory()->create();

        $response = $this->post(route('admin.marketing.social-engagements.store'), $this->payload([
            'marketing_campaign_id' => $campaign->id,
            'post_title' => 'Launch Social Post',
            'platform' => 'linkedin',
        ]));

        $post = SocialMediaEngagement::query()->where('post_title', 'Launch Social Post')->firstOrFail();

        $response->assertRedirect(route('admin.marketing.social-engagements.show', $post));
        $this->assertDatabaseHas('social_media_engagements', [
            'post_title' => 'Launch Social Post',
            'platform' => 'linkedin',
            'marketing_campaign_id' => $campaign->id,
        ]);
    }

    public function test_show_and_edit_pages_can_be_opened(): void
    {
        $post = SocialMediaEngagement::factory()->create(['post_title' => 'Show Edit Social']);

        $this->get(route('admin.marketing.social-engagements.show', $post))
            ->assertOk()
            ->assertSee('Show Edit Social');

        $this->get(route('admin.marketing.social-engagements.edit', $post))
            ->assertOk()
            ->assertSee('Edit Social Post');
    }

    public function test_social_post_can_be_updated(): void
    {
        $post = SocialMediaEngagement::factory()->create(['post_title' => 'Before Social']);

        $response = $this->put(route('admin.marketing.social-engagements.update', $post), $this->payload([
            'post_title' => 'After Social',
            'platform' => 'instagram',
            'status' => 'published',
            'likes_count' => 250,
        ]));

        $response->assertRedirect(route('admin.marketing.social-engagements.show', $post));
        $this->assertDatabaseHas('social_media_engagements', [
            'id' => $post->id,
            'post_title' => 'After Social',
            'platform' => 'instagram',
            'status' => 'published',
            'likes_count' => 250,
        ]);
    }

    public function test_social_post_can_be_deleted(): void
    {
        $post = SocialMediaEngagement::factory()->create();

        $response = $this->delete(route('admin.marketing.social-engagements.destroy', $post));

        $response->assertRedirect(route('admin.marketing.social-engagements.index'));
        $this->assertDatabaseMissing('social_media_engagements', ['id' => $post->id]);
    }

    public function test_search_works(): void
    {
        $match = SocialMediaEngagement::factory()->create([
            'post_title' => 'Searchable Social Content',
            'content' => 'Unique campaign story for search.',
        ]);
        $other = SocialMediaEngagement::factory()->create(['post_title' => 'Other Social Content']);

        $this->get(route('admin.marketing.social-engagements.index', ['q' => 'Unique campaign story']))
            ->assertOk()
            ->assertSee($match->post_title)
            ->assertDontSee($other->post_title);
    }

    public function test_filter_platform_works(): void
    {
        $linkedin = SocialMediaEngagement::factory()->create(['post_title' => 'Linkedin Filter Post', 'platform' => 'linkedin']);
        $tiktok = SocialMediaEngagement::factory()->create(['post_title' => 'Tiktok Filter Post', 'platform' => 'tiktok']);

        $this->get(route('admin.marketing.social-engagements.index', ['platform' => 'linkedin']))
            ->assertOk()
            ->assertSee($linkedin->post_title)
            ->assertDontSee($tiktok->post_title);
    }

    public function test_filter_status_works(): void
    {
        $published = SocialMediaEngagement::factory()->create(['post_title' => 'Published Filter Post', 'status' => 'published']);
        $draft = SocialMediaEngagement::factory()->create(['post_title' => 'Draft Filter Post', 'status' => 'draft']);

        $this->get(route('admin.marketing.social-engagements.index', ['status' => 'published']))
            ->assertOk()
            ->assertSee($published->post_title)
            ->assertDontSee($draft->post_title);
    }

    public function test_engagement_rate_is_displayed_correctly(): void
    {
        $post = SocialMediaEngagement::factory()->create([
            'post_title' => 'Engagement Rate Post',
            'engagement_rate' => 7.25,
        ]);

        $this->get(route('admin.marketing.social-engagements.show', $post))
            ->assertOk()
            ->assertSee('Engagement Rate')
            ->assertSee('7.25%');
    }

    public function test_engagement_score_is_displayed_correctly(): void
    {
        $post = SocialMediaEngagement::factory()->create([
            'post_title' => 'Engagement Score Post',
            'likes_count' => 100,
            'comments_count' => 30,
            'shares_count' => 20,
            'impressions_count' => 3000,
        ]);

        $this->get(route('admin.marketing.social-engagements.show', $post))
            ->assertOk()
            ->assertSee('Engagement Score Summary')
            ->assertSee('5.00%');
    }

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'marketing_campaign_id' => null,
            'platform' => 'instagram',
            'post_title' => 'Default Social Post',
            'content' => 'Default social content from feature test.',
            'post_url' => 'https://example.com/social/default-post',
            'status' => 'scheduled',
            'posted_at' => '2026-05-10 09:00:00',
            'likes_count' => 100,
            'comments_count' => 25,
            'shares_count' => 10,
            'impressions_count' => 2500,
            'engagement_rate' => 5.40,
            'created_by' => 'Social Media Team',
        ], $overrides);
    }
}
