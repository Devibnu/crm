<?php

namespace Database\Factories;

use App\Models\ProjectTask;
use App\Models\ProjectTaskChecklist;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTaskChecklist>
 */
class ProjectTaskChecklistFactory extends Factory
{
    protected $model = ProjectTaskChecklist::class;

    public function definition(): array
    {
        $isCompleted = fake()->boolean(35);

        return [
            'project_task_id' => ProjectTask::factory(),
            'title' => fake()->randomElement(['Confirm scope', 'Prepare asset', 'Review output', 'Collect signoff']),
            'is_completed' => $isCompleted,
            'completed_at' => $isCompleted ? now() : null,
            'completed_by' => $isCompleted ? User::factory() : null,
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}
