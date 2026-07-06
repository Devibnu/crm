<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Opportunity;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'project_number' => sprintf('PRJ-%s-%04d', now()->format('Y'), fake()->unique()->numberBetween(1, 9999)),
            'customer_id' => Customer::query()->inRandomOrder()->value('id'),
            'opportunity_id' => Opportunity::query()->inRandomOrder()->value('id'),
            'quotation_id' => Quotation::query()->inRandomOrder()->value('id'),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'status' => 'planning',
            'budget' => fake()->randomFloat(2, 1000000, 500000000),
            'start_date' => fake()->optional()->dateTimeBetween('now', '+1 month')?->format('Y-m-d'),
            'due_date' => fake()->optional()->dateTimeBetween('+1 month', '+8 months')?->format('Y-m-d'),
            'progress' => fake()->numberBetween(0, 30),
            'project_manager_id' => null,
        ];
    }
}
