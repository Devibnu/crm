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
            'root_cause' => fake()->randomElement(CaseResolution::rootCauseOptions()),
            'workaround' => fake()->optional()->paragraph(),
            'permanent_fix' => fake()->optional()->paragraph(),
            'internal_notes' => fake()->optional()->paragraph(),
            'resolution_type' => fake()->randomElement(CaseResolution::resolutionTypeOptions()),
            'resolution_outcome' => fake()->randomElement(CaseResolution::resolutionOutcomeOptions()),
            'reopened_count' => fake()->numberBetween(0, 3),
            'knowledge_candidate' => fake()->boolean(35),
            'knowledge_article_id' => null,
            'resolved_by' => fake()->optional()->name(),
            'resolved_at' => fake()->dateTimeBetween('-2 months', 'now'),
            'customer_notified' => fake()->boolean(70),
            'customer_notified_at' => fake()->optional()->dateTimeBetween('-2 months', 'now'),
            'customer_confirmation_at' => fake()->optional()->dateTimeBetween('-1 month', 'now'),
            'resolution_duration_minutes' => fake()->optional()->numberBetween(15, 1440),
        ];
    }
}
