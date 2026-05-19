<?php

namespace Database\Factories;

use App\Models\CaseResolution;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CaseResolution>
 */
class CaseResolutionFactory extends Factory
{
    protected $model = CaseResolution::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ticketId = Ticket::query()->inRandomOrder()->value('id') ?: Ticket::factory();

        return [
            'ticket_id' => $ticketId,
            'resolution_summary' => fake()->sentence(6),
            'resolution_notes' => fake()->optional()->paragraph(),
            'root_cause' => fake()->optional()->sentence(10),
            'resolution_type' => fake()->randomElement(['workaround', 'fixed', 'duplicate', 'invalid', 'escalated']),
            'resolved_by' => fake()->optional()->name(),
            'resolved_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'customer_notified' => fake()->boolean(70),
        ];
    }
}
