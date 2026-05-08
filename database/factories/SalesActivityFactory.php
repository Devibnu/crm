<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\SalesActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesActivity>
 */
class SalesActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['call', 'whatsapp', 'email', 'meeting', 'note', 'follow_up'];
        $relatedType = fake()->randomElement(['lead', 'opportunity', 'customer']);

        return [
            'related_type' => $relatedType,
            'related_id' => $this->relatedId($relatedType),
            'type' => fake()->randomElement($types),
            'subject' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'activity_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'assigned_to' => fake()->optional()->name(),
            'outcome' => fake()->optional()->sentence(),
        ];
    }

    protected function relatedId(string $relatedType): int
    {
        $id = match ($relatedType) {
            'lead' => Lead::query()->inRandomOrder()->value('id'),
            'opportunity' => Opportunity::query()->inRandomOrder()->value('id'),
            'customer' => Customer::query()->inRandomOrder()->value('id'),
            default => null,
        };

        return $id ?? fake()->numberBetween(1, 10);
    }
}
