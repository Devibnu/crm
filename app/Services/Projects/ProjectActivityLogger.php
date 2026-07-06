<?php

namespace App\Services\Projects;

use App\Models\Project;
use Illuminate\Database\Eloquent\Model;

class ProjectActivityLogger
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function log(Project $project, string $event, string $description, ?Model $subject = null, array $metadata = []): void
    {
        $project->activityLogs()->create([
            'actor_id' => auth()->id(),
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'event' => $event,
            'description' => $description,
            'metadata' => $metadata ?: null,
        ]);
    }
}
