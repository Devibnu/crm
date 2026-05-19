<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Quotation>
 */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = ['draft', 'sent', 'accepted', 'rejected', 'expired'];

        $opportunityId = null;
        if (fake()->boolean(60)) {
            $opportunityId = Opportunity::query()->inRandomOrder()->value('id');
        }

        $customerId = null;
        if (fake()->boolean(65)) {
            $customerId = Customer::query()->inRandomOrder()->value('id');
        }

        $status = fake()->randomElement($statuses);

        return [
            'opportunity_id' => $opportunityId,
            'customer_id' => $customerId,
            'quote_number' => sprintf('QTN-%s-%04d', now()->format('Y'), fake()->unique()->numberBetween(1, 9999)),
            'title' => fake()->sentence(4),
            'amount' => fake()->boolean(15) ? 0 : fake()->randomFloat(2, 1000000, 350000000),
            'status' => $status,
            'valid_until' => fake()->boolean(70) ? fake()->dateTimeBetween('now', '+4 months')->format('Y-m-d') : null,
            'issued_at' => fake()->boolean(80) ? fake()->dateTimeBetween('-3 months', 'now')->format('Y-m-d') : null,
            'notes' => fake()->optional()->sentence(14),
        ];
    }
}
