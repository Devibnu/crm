<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\CustomerPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CustomerPreference>
 */
class CustomerPreferenceFactory extends Factory
{
    protected $model = CustomerPreference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::query()->inRandomOrder()->value('id') ?? Customer::factory(),
            'preferred_channel' => fake()->randomElement(['whatsapp', 'email', 'phone', 'meeting', 'none']),
            'product_interest' => fake()->optional()->randomElement(['CRM Enterprise', 'Service Desk', 'Sales Pipeline', 'Automation']),
            'communication_consent' => fake()->boolean(70),
            'segment' => fake()->optional()->randomElement(['SMB', 'Enterprise', 'Government', 'Education']),
            'notes' => fake()->optional()->sentence(12),
        ];
    }
}
