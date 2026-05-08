<?php

namespace Database\Factories;

use App\Models\MarketingCampaign;
use App\Models\SocialMediaEngagement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SocialMediaEngagement>
 */
class SocialMediaEngagementFactory extends Factory
{
    protected $model = SocialMediaEngagement::class;

    public function definition(): array
    {
        $impressions = fake()->numberBetween(500, 500000);
        $likes = fake()->numberBetween(0, (int) floor($impressions * 0.12));
        $comments = fake()->numberBetween(0, (int) floor($impressions * 0.03));
        $shares = fake()->numberBetween(0, (int) floor($impressions * 0.02));
        $engagementRate = round((($likes + $comments + $shares) / $impressions) * 100, 2);
        $status = fake()->randomElement(['draft', 'scheduled', 'published', 'archived']);

        return [
            'marketing_campaign_id' => fake()->boolean(70) ? MarketingCampaign::query()->inRandomOrder()->value('id') : null,
            'platform' => fake()->randomElement(['instagram', 'facebook', 'linkedin', 'twitter', 'tiktok', 'youtube']),
            'post_title' => fake()->randomElement([
                'Product Launch Announcement',
                'Customer Story Highlight',
                'Webinar Promotion',
                'Feature Education Post',
                'Campaign Reminder',
                'Industry Insight Thread',
            ]) . ' ' . fake()->bothify('##??'),
            'content' => fake()->sentence(18),
            'post_url' => fake()->optional(0.75)->url(),
            'status' => $status,
            'posted_at' => in_array($status, ['published', 'archived'], true) ? fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d H:i:s') : null,
            'likes_count' => $likes,
            'comments_count' => $comments,
            'shares_count' => $shares,
            'impressions_count' => $impressions,
            'engagement_rate' => $engagementRate,
            'created_by' => fake()->randomElement(['Social Media Team', 'Content Marketing', 'Growth Team', 'Brand Team']),
        ];
    }
}
