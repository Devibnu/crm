<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerInteraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerInteraction>
 */
class CustomerInteractionFactory extends Factory
{
    protected $model = CustomerInteraction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['call', 'whatsapp', 'email', 'meeting', 'note', 'follow_up'];

        return [
            'customer_id' => Customer::query()->inRandomOrder()->value('id') ?? Customer::factory(),
            'type' => fake()->randomElement($types),
            'subject' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'interaction_at' => fake()->optional(80)->dateTimeBetween('-6 months', 'now'),
            'handled_by' => fake()->optional()->name(),
            'outcome' => fake()->optional()->randomElement(['Resolved', 'Pending follow-up', 'No response', 'Qualified', 'Not interested']),
        ];
    }
}
