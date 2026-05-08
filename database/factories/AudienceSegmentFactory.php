<?php

namespace Database\Factories;

use App\Models\AudienceSegment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AudienceSegment>
 */
class AudienceSegmentFactory extends Factory
{
    protected $model = AudienceSegment::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['demographic', 'behavioral', 'transactional', 'engagement']);

        $criteria = match ($type) {
            'demographic' => [
                'industries' => fake()->randomElements(['manufacturing', 'finance', 'retail', 'logistics'], 2),
                'company_size' => fake()->randomElement(['50-200', '201-1000', '1000+']),
                'region' => fake()->randomElement(['Jabodetabek', 'West Java', 'East Java', 'Bali']),
            ],
            'behavioral' => [
                'visited_pages' => fake()->randomElements(['pricing', 'demo', 'case-study', 'product'], 2),
                'last_activity_days' => fake()->numberBetween(1, 45),
                'engagement_score' => ['>=' => fake()->numberBetween(50, 90)],
            ],
            'transactional' => [
                'purchase_count' => ['>=' => fake()->numberBetween(1, 5)],
                'lifetime_value' => ['>=' => fake()->numberBetween(10000000, 150000000)],
                'last_purchase_days' => ['<=' => fake()->numberBetween(30, 180)],
            ],
            default => [
                'email_open_rate' => ['>=' => fake()->numberBetween(20, 80)],
                'clicked_campaigns' => ['>=' => fake()->numberBetween(1, 8)],
                'channels' => fake()->randomElements(['email', 'whatsapp', 'webinar'], 2),
            ],
        };

        return [
            'name' => fake()->randomElement([
                'High Intent Prospects',
                'Enterprise Decision Makers',
                'Recent Engaged Leads',
                'Dormant Customers',
                'Repeat Buyers',
                'Webinar Attendees',
            ]) . ' ' . fake()->randomNumber(3),
            'type' => $type,
            'description' => fake()->sentence(14),
            'criteria' => $criteria,
            'estimated_audience' => fake()->numberBetween(250, 50000),
            'status' => fake()->randomElement(['active', 'inactive']),
            'created_by' => fake()->randomElement(['Marketing Ops', 'Growth Team', 'CRM Marketing', 'Demand Generation']),
        ];
    }
}
