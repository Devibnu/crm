<?php

namespace Database\Factories;

use App\Models\MarketingCampaign;
use App\Models\WhatsAppBroadcast;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WhatsAppBroadcast>
 */
class WhatsAppBroadcastFactory extends Factory
{
    protected $model = WhatsAppBroadcast::class;

    public function definition(): array
    {
        $status = fake()->randomElement(['draft', 'scheduled', 'sending', 'paused', 'completed', 'failed', 'cancelled']);
        $totalRecipients = fake()->numberBetween(20, 500);
        $sentCount = in_array($status, ['sending', 'completed', 'failed'], true) ? fake()->numberBetween(0, $totalRecipients) : 0;
        $deliveredCount = $sentCount > 0 ? fake()->numberBetween(0, $sentCount) : 0;
        $readCount = $deliveredCount > 0 ? fake()->numberBetween(0, $deliveredCount) : 0;
        $repliedCount = $readCount > 0 ? fake()->numberBetween(0, $readCount) : 0;
        $failedCount = $sentCount > 0 ? fake()->numberBetween(0, max(1, $sentCount - $deliveredCount + 1)) : 0;

        $scheduledAt = fake()->optional(0.8)->dateTimeBetween('-3 months', '+1 month');
        $sentAt = in_array($status, ['sending', 'completed', 'failed'], true) && $scheduledAt
            ? fake()->dateTimeBetween($scheduledAt, (clone $scheduledAt)->modify('+7 days'))
            : null;

        return [
            'marketing_campaign_id' => fake()->boolean(75) ? MarketingCampaign::query()->inRandomOrder()->value('id') : null,
            'name' => fake()->randomElement(['Promo Blast', 'Retention Push', 'Upsell Notice', 'New Product Intro']) . ' ' . fake()->monthName(),
            'message_template' => fake()->sentence(14),
            'target_type' => fake()->randomElement(['segment', 'customer', 'lead']),
            'status' => $status,
            'scheduled_at' => $scheduledAt,
            'sent_at' => $sentAt,
            'total_recipients' => $totalRecipients,
            'sent_count' => $sentCount,
            'total_sent' => $sentCount,
            'delivered_count' => $deliveredCount,
            'read_count' => $readCount,
            'replied_count' => $repliedCount,
            'failed_count' => $failedCount,
            'total_failed' => $failedCount,
            'delivery_rate' => $sentCount > 0 ? round(($deliveredCount / $sentCount) * 100, 2) : 0,
            'reply_rate' => $totalRecipients > 0 ? round(($repliedCount / $totalRecipients) * 100, 2) : 0,
            'created_by' => fake()->randomElement(['Marketing Ops', 'CRM Team', 'Growth Team']),
            'notes' => fake()->optional(0.6)->sentence(10),
        ];
    }
}
