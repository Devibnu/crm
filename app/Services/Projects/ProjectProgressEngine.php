<?php

namespace App\Services\Projects;

use App\Models\Project;
use App\Models\ProjectMilestone;

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

    public function refreshMilestone(ProjectMilestone $milestone): string
    {
        $totalTasks = $milestone->tasks()->count();
        $oldStatus = $milestone->status;

        if ($totalTasks === 0) {
            $status = $milestone->isOverdue() ? ProjectMilestone::STATUS_DELAYED : $oldStatus;
        } else {
            $completedTasks = $milestone->tasks()->where('status', 'done')->count();
            $openTasks = $totalTasks - $completedTasks;
            $overdueTasks = $milestone->tasks()
                ->whereDate('due_date', '<', now()->toDateString())
                ->where('status', '!=', 'done')
                ->count();

            $status = match (true) {
                $completedTasks === $totalTasks => ProjectMilestone::STATUS_COMPLETED,
                $overdueTasks > 0 || ($milestone->due_date && $milestone->due_date->lt(now()->startOfDay())) => ProjectMilestone::STATUS_DELAYED,
                $openTasks > 0 && $completedTasks > 0 => ProjectMilestone::STATUS_IN_PROGRESS,
                $openTasks > 0 => ProjectMilestone::STATUS_IN_PROGRESS,
                default => $oldStatus,
            };
        }

        $milestone->forceFill([
            'status' => $status,
            'completed_at' => $status === ProjectMilestone::STATUS_COMPLETED
                ? ($milestone->completed_at ?: now())
                : null,
        ])->save();

        return $status;
    }
}
