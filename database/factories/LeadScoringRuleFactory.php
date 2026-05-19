<?php

namespace Database\Factories;

use App\Models\LeadScoringRule;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadScoringRule>
 */
class LeadScoringRuleFactory extends Factory
{
    protected $model = LeadScoringRule::class;

    public function definition(): array
    {
        $trigger = fake()->randomElement(['form_submit', 'campaign_engagement', 'social_engagement', 'manual', 'crm_activity']);

        return [
            'name' => fake()->randomElement([
                'High Intent Form Submit',
                'Campaign Engagement Boost',
                'Social Signal Routing',
                'Manual Sales Priority',
                'CRM Activity Score',
            ]) . ' ' . fake()->bothify('##??'),
            'trigger_source' => $trigger,
            'score_value' => fake()->numberBetween(5, 100),
            'routing_team' => fake()->optional(0.8)->randomElement(['Enterprise Sales', 'Inside Sales', 'SMB Sales', 'Retention Team']),
            'routing_user' => fake()->optional(0.6)->name(),
            'conditions' => $this->conditionsFor($trigger),
            'priority' => fake()->randomElement(['low', 'medium', 'high']),
            'status' => fake()->randomElement(['active', 'inactive']),
            'auto_assign' => fake()->boolean(55),
            'execution_count' => fake()->numberBetween(0, 900),
            'last_executed_at' => fake()->optional(0.75)->dateTimeBetween('-4 months', 'now')?->format('Y-m-d H:i:s'),
            'notes' => fake()->optional()->sentence(12),
            'created_by' => fake()->randomElement(['Marketing Ops', 'CRM Admin', 'Sales Ops', 'Growth Team']),
        ];
    }

    protected function conditionsFor(string $trigger): array
    {
        return match ($trigger) {
            'form_submit' => [
                'form' => fake()->randomElement(['demo_request', 'lead_capture', 'webinar_registration']),
                'required_fields' => ['email', 'company'],
            ],
            'campaign_engagement' => [
                'minimum_open_count' => fake()->numberBetween(1, 5),
                'clicked_link' => fake()->randomElement(['pricing', 'case_study', 'contact_sales']),
            ],
            'social_engagement' => [
                'platform' => fake()->randomElement(['linkedin', 'instagram', 'facebook']),
                'engagement_rate' => ['>=' => fake()->randomElement([3, 5, 8])],
            ],
            'crm_activity' => [
                'activity_type' => fake()->randomElement(['call', 'meeting', 'email']),
                'within_days' => fake()->randomElement([7, 14, 30]),
            ],
            default => [
                'reason' => fake()->randomElement(['strategic_account', 'partner_referral', 'sales_request']),
            ],
        };
    }
}
