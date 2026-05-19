<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Opportunity>
 */
class OpportunityFactory extends Factory
{
    protected $model = Opportunity::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['open', 'qualified', 'proposal', 'negotiation', 'won', 'lost'];

        $leadId = null;
        if (fake()->boolean(55)) {
            $leadId = Lead::query()->inRandomOrder()->value('id');
        }

        $customerId = null;
        if (fake()->boolean(60)) {
            $customerId = Customer::query()->inRandomOrder()->value('id');
        }

        return [
            'lead_id' => $leadId,
            'customer_id' => $customerId,
            'title' => fake()->sentence(4),
            'company_name' => fake()->optional()->company(),
            'contact_name' => fake()->optional()->name(),
            'estimated_value' => fake()->boolean(15) ? 0 : fake()->randomFloat(2, 1000000, 500000000),
            'probability' => fake()->numberBetween(0, 100),
            'status' => fake()->randomElement($statuses),
            'expected_close_date' => fake()->boolean(75) ? fake()->dateTimeBetween('now', '+6 months')->format('Y-m-d') : null,
            'assigned_to' => fake()->optional()->name(),
            'notes' => fake()->optional()->sentence(12),
        ];
    }
}
