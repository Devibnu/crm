<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerSatisfaction;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerSatisfaction>
 */
class CustomerSatisfactionFactory extends Factory
{
    protected $model = CustomerSatisfaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $rating = fake()->numberBetween(1, 5);

        return [
            'ticket_id' => fake()->boolean(75) ? Ticket::query()->inRandomOrder()->value('id') : null,
            'customer_id' => fake()->boolean(75) ? Customer::query()->inRandomOrder()->value('id') : null,
            'rating' => $rating,
            'feedback' => fake()->optional()->paragraph(),
            'survey_channel' => fake()->randomElement(['email', 'whatsapp', 'phone', 'web']),
            'sentiment' => $rating >= 4 ? 'positive' : ($rating === 3 ? 'neutral' : 'negative'),
            'submitted_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'follow_up_required' => fake()->boolean(25),
            'follow_up_notes' => fake()->optional()->sentence(12),
        ];
    }
}
