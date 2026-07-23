<?php

namespace Database\Factories;

use App\Models\SlaPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlaPolicy>
 */
class SlaPolicyFactory extends Factory
{
    protected $model = SlaPolicy::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $priority = fake()->randomElement(SlaPolicy::priorityOptions());

        $targets = [
            'low' => [240, 4320],
            'medium' => [120, 1440],
            'high' => [60, 480],
            'urgent' => [15, 120],
        ];

        return [
            'name' => ucfirst($priority).' Support SLA '.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->optional()->sentence(14),
            'priority' => $priority,
            'response_time_minutes' => $targets[$priority][0],
            'resolution_time_minutes' => $targets[$priority][1],
            'is_active' => fake()->boolean(80),
        ];
    }
}
