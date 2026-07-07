<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTask>
 */
class ProjectTaskFactory extends Factory
{
    protected $model = ProjectTask::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'milestone_id' => null,
            'assignee_id' => User::factory(),
            'title' => fake()->randomElement(['Prepare scope', 'Design delivery flow', 'Build module', 'QA signoff']),
            'description' => fake()->optional()->paragraph(),
            'status' => 'todo',
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'critical']),
            'start_date' => fake()->optional()->dateTimeBetween('-1 week', '+1 week')?->format('Y-m-d'),
            'due_date' => fake()->optional()->dateTimeBetween('now', '+1 month')?->format('Y-m-d'),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
