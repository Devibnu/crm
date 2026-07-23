<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(Ticket::statusOptions());
        $hasResolutionHistory = in_array($status, ['resolved', 'closed', 'reopened'], true);
        $resolvedAt = $hasResolutionHistory ? fake()->dateTimeBetween('-2 months', 'now') : null;

        return [
            'ticket_number' => 'TCK-'.fake()->unique()->bothify('2026####-#####'),
            'customer_id' => fake()->boolean(70) ? Customer::query()->inRandomOrder()->value('id') : null,
            'subject' => fake()->sentence(5),
            'description' => fake()->optional()->paragraph(),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'status' => $status,
            'channel' => fake()->randomElement(['email', 'phone', 'whatsapp', 'web', 'social', 'walk_in']),
            'assigned_to' => fake()->optional()->name(),
            'due_at' => fake()->boolean(75) ? fake()->dateTimeBetween('now', '+30 days') : null,
            'resolved_at' => $resolvedAt,
            'closed_at' => in_array($status, ['closed', 'reopened'], true) ? ($resolvedAt ?: fake()->dateTimeBetween('-2 months', 'now')) : null,
        ];
    }
}
