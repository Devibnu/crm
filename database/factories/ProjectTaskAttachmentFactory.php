<?php

namespace Database\Factories;

use App\Models\ProjectTask;
use App\Models\ProjectTaskAttachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectTaskAttachment>
 */
class ProjectTaskAttachmentFactory extends Factory
{
    protected $model = ProjectTaskAttachment::class;

    public function definition(): array
    {
        $extension = fake()->randomElement(['pdf', 'docx', 'xlsx', 'png', 'zip']);
        $storedName = fake()->uuid().'.'.$extension;

        return [
            'project_task_id' => ProjectTask::factory(),
            'user_id' => User::factory(),
            'original_name' => fake()->words(3, true).'.'.$extension,
            'stored_name' => $storedName,
            'mime_type' => 'application/octet-stream',
            'file_size' => fake()->numberBetween(12000, 2500000),
            'disk' => 'public',
            'path' => 'project-task-attachments/'.$storedName,
        ];
    }
}
