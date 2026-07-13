<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectMilestone;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectMilestone>
 */
class ProjectMilestoneFactory extends Factory
{
    protected $model = ProjectMilestone::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => fake()->randomElement(['Requirement', 'Design', 'Development', 'Testing', 'Go Live', 'Closed']),
            'description' => fake()->optional()->paragraph(),
            'color' => fake()->randomElement(['blue', 'green', 'amber', 'violet', 'rose']),
            'icon' => fake()->randomElement(['calendar', 'kanban', 'activity', 'deal', 'case']),
            'status' => 'pending',
            'start_date' => fake()->optional()->dateTimeBetween('-1 month', 'now')?->format('Y-m-d'),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
