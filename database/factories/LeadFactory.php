<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['new', 'contacted', 'qualified', 'unqualified', 'converted'];
        $priorities = ['low', 'medium', 'high'];

        $customerId = null;
        if (fake()->boolean(55)) {
            $customerId = Customer::query()->inRandomOrder()->value('id');
        }

        return [
            'customer_id' => $customerId,
            'name' => fake()->name(),
            'company_name' => fake()->optional()->company(),
            'email' => fake()->boolean(75) ? fake()->unique()->safeEmail() : null,
            'phone' => fake()->optional()->numerify('08##########'),
            'source' => fake()->optional()->randomElement(['Website', 'Referral', 'Event', 'Social Media']),
            'status' => fake()->randomElement($statuses),
            'priority' => fake()->randomElement($priorities),
            'assigned_to' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(12),
        ];
    }
}
