<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['new', 'active', 'inactive', 'blacklist'];

        return [
            'name' => fake()->name(),
            'company_name' => fake()->optional()->company(),
            'email' => fake()->boolean(80) ? fake()->unique()->safeEmail() : null,
            'phone' => fake()->optional()->numerify('08##########'),
            'whatsapp' => fake()->optional()->numerify('628##########'),
            'source' => fake()->optional()->randomElement(['Website', 'Referral', 'Event', 'Social Media']),
            'status' => fake()->randomElement($statuses),
            'owner_name' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(12),
        ];
    }
}
