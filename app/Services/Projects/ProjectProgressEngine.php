<?php

namespace App\Services\Projects;

use App\Models\Project;

class ProjectProgressEngine
{
    public function refresh(Project $project): int
    {
        $totalTasks = $project->tasks()->count();

        if ($totalTasks > 0) {
            $progress = (int) round(($project->tasks()->where('status', 'done')->count() / $totalTasks) * 100);

            $project->forceFill(['progress' => $progress])->save();

            return $progress;
        }

        $totalMilestones = $project->milestones()->count();

        $progress = $totalMilestones === 0
            ? 0
            : (int) round(($project->milestones()->where('status', 'completed')->count() / $totalMilestones) * 100);

        $project->forceFill(['progress' => $progress])->save();

        return $progress;
    }
}
