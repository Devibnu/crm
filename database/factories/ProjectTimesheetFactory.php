<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\ProjectTimesheet;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTimesheet>
 */
class ProjectTimesheetFactory extends Factory
{
    protected $model = ProjectTimesheet::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('08:00', '14:00');
        $end = (clone $start)->modify('+'.fake()->numberBetween(1, 6).' hours');

        return [
            'project_id' => Project::factory(),
            'milestone_id' => null,
            'task_id' => null,
            'user_id' => User::factory(),
            'work_date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'start_time' => $start->format('H:i'),
            'end_time' => $end->format('H:i'),
            'duration_minutes' => (int) (((int) $end->format('U') - (int) $start->format('U')) / 60),
            'billable' => true,
            'description' => fake()->sentence(8),
            'status' => ProjectTimesheet::STATUS_DRAFT,
        ];
    }
}
