<?php

namespace Database\Factories;

use App\Models\AudienceSegment;
use App\Models\CampaignExecution;
use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CampaignExecution>
 */
class CampaignExecutionFactory extends Factory
{
    protected $model = CampaignExecution::class;

    public function definition(): array
    {
        $sent = fake()->numberBetween(250, 50000);
        $delivered = fake()->numberBetween((int) floor($sent * 0.65), $sent);
        $opened = fake()->numberBetween(0, $delivered);
        $clicked = fake()->numberBetween(0, $opened);
        $responses = fake()->numberBetween(0, max(1, (int) floor($sent * 0.12)));
        $scheduledAt = fake()->dateTimeBetween('-4 months', '+1 month');
        $startedLimit = (clone $scheduledAt)->modify('+5 days');
        $startedAt = fake()->boolean(75) ? fake()->dateTimeBetween($scheduledAt, $startedLimit) : null;
        $completedAt = $startedAt && fake()->boolean(65) ? fake()->dateTimeBetween($startedAt, (clone $startedAt)->modify('+3 days')) : null;

        return [
            'marketing_campaign_id' => fake()->boolean(75) ? MarketingCampaign::query()->inRandomOrder()->value('id') : null,
            'audience_segment_id' => fake()->boolean(70) ? AudienceSegment::query()->inRandomOrder()->value('id') : null,
            'channel' => fake()->randomElement(['email', 'whatsapp', 'sms', 'social_media', 'ads']),
            'status' => fake()->randomElement(['scheduled', 'running', 'completed', 'failed', 'cancelled']),
            'execution_name' => fake()->randomElement([
                'Initial Blast',
                'Follow-up Batch',
                'Retargeting Push',
                'Reminder Send',
                'Final Conversion Sprint',
            ]) . ' ' . fake()->bothify('##??'),
            'scheduled_at' => $scheduledAt->format('Y-m-d H:i:s'),
            'started_at' => $startedAt?->format('Y-m-d H:i:s'),
            'completed_at' => $completedAt?->format('Y-m-d H:i:s'),
            'sent_count' => $sent,
            'delivered_count' => $delivered,
            'opened_count' => $opened,
            'clicked_count' => $clicked,
            'response_count' => $responses,
            'notes' => fake()->optional()->sentence(12),
        ];
    }
}
