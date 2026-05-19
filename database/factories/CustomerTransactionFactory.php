<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerTransaction>
 */
class CustomerTransactionFactory extends Factory
{
    protected $model = CustomerTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::query()->inRandomOrder()->value('id') ?? Customer::factory(),
            'title' => fake()->sentence(4),
            'amount' => fake()->randomFloat(2, 100000, 50000000),
            'status' => fake()->randomElement(['pending', 'won', 'lost', 'cancelled']),
            'closing_date' => fake()->boolean(80)
                ? fake()->dateTimeBetween('-6 months', '+2 months')->format('Y-m-d')
                : null,
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
