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
            'status' => 'pending',
            'due_date' => fake()->optional()->dateTimeBetween('now', '+3 months')?->format('Y-m-d'),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
