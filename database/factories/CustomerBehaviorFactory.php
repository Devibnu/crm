<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerBehavior;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerBehavior>
 */
class CustomerBehaviorFactory extends Factory
{
    protected $model = CustomerBehavior::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::query()->inRandomOrder()->value('id') ?? Customer::factory(),
            'lifecycle_stage' => fake()->randomElement(['lead', 'prospect', 'active', 'loyal', 'churned']),
            'engagement_score' => fake()->numberBetween(0, 100),
            'last_activity_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-6 months', 'now') : null,
            'product_interest' => fake()->optional()->randomElement(['CRM', 'Support Desk', 'Sales Toolkit', 'Automation']),
            'behavior_notes' => fake()->optional()->sentence(12),
        ];
    }
}
