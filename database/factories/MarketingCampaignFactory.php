<?php

namespace Database\Factories;

use App\Models\MarketingCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketingCampaign>
 */
class MarketingCampaignFactory extends Factory
{
    protected $model = MarketingCampaign::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['email', 'whatsapp', 'social_media', 'webinar', 'event', 'ads']);
        $status = fake()->randomElement(['draft', 'scheduled', 'running', 'completed', 'cancelled']);
        $expectedLeads = fake()->numberBetween(50, 2500);
        $actualLeads = match ($status) {
            'draft', 'scheduled' => fake()->numberBetween(0, 100),
            'running' => fake()->numberBetween(20, $expectedLeads),
            'completed' => fake()->numberBetween((int) floor($expectedLeads * 0.4), (int) ceil($expectedLeads * 1.25)),
            default => fake()->numberBetween(0, (int) floor($expectedLeads * 0.4)),
        };

        $startDate = fake()->optional(0.85)->dateTimeBetween('-6 months', '+2 months');

        return [
            'name' => fake()->randomElement([
                'Enterprise Lead Nurturing',
                'Product Launch Awareness',
                'Customer Reactivation',
                'Regional Webinar Series',
                'Industry Event Follow-up',
                'Paid Acquisition Sprint',
            ]) . ' ' . fake()->monthName(),
            'type' => $type,
            'status' => $status,
            'target_audience' => fake()->randomElement([
                'Enterprise prospects',
                'SMB decision makers',
                'Inactive customers',
                'Manufacturing accounts',
                'Finance industry leads',
                'High-intent website visitors',
            ]),
            'budget' => fake()->randomFloat(2, 5000000, 250000000),
            'expected_leads' => $expectedLeads,
            'actual_leads' => $actualLeads,
            'start_date' => $startDate?->format('Y-m-d'),
            'end_date' => $startDate ? fake()->dateTimeBetween($startDate, '+4 months')->format('Y-m-d') : null,
            'description' => fake()->sentence(16),
            'created_by' => fake()->randomElement(['Marketing Team', 'Growth Team', 'Demand Generation', 'CRM Marketing']),
        ];
    }
}
