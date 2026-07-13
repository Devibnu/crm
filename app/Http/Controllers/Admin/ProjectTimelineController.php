<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectTimelineController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $selectedProject = (string) $request->query('project_id', '');
        $selectedOwner = (string) $request->query('owner_id', '');
        $selectedStatus = (string) $request->query('status', '');
        $dateFrom = (string) $request->query('date_from', '');
        $dateTo = (string) $request->query('date_to', '');
        $statusOptions = $this->statusOptions();

        $projects = Project::query()
            ->with([
                'projectManager:id,name,email',
                'milestones.tasks.assignee:id,name,email',
                'tasks.assignee:id,name,email',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('project_number', 'like', "%{$search}%")
                        ->orWhereHas('milestones', fn ($milestone) => $milestone->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('tasks', fn ($task) => $task->where('title', 'like', "%{$search}%"));
                });
            })
            ->when($selectedProject !== '', fn ($query) => $query->whereKey((int) $selectedProject))
            ->when($selectedOwner !== '', function ($query) use ($selectedOwner): void {
                $query->where(function ($ownerQuery) use ($selectedOwner): void {
                    $ownerQuery
                        ->where('project_manager_id', (int) $selectedOwner)
                        ->orWhereHas('tasks', fn ($task) => $task->where('assignee_id', (int) $selectedOwner));
                });
            })
            ->when(array_key_exists($selectedStatus, $statusOptions), function ($query) use ($selectedStatus): void {
                $query->where(function ($statusQuery) use ($selectedStatus): void {
                    $projectStatuses = $selectedStatus === 'in_progress' ? ['in_progress', 'active'] : [$selectedStatus];

                    $statusQuery
                        ->whereIn('status', $projectStatuses)
                        ->orWhereHas('milestones', fn ($milestone) => $milestone->where('status', $selectedStatus))
                        ->orWhereHas('tasks', fn ($task) => $task->where('status', $selectedStatus));
                });
            })
            ->when($dateFrom !== '' || $dateTo !== '', function ($query) use ($dateFrom, $dateTo): void {
                $from = $dateFrom !== '' ? $dateFrom : '1900-01-01';
                $to = $dateTo !== '' ? $dateTo : '2999-12-31';

                $query->where(function ($dateQuery) use ($from, $to): void {
                    $dateQuery
                        ->whereBetween('start_date', [$from, $to])
                        ->orWhereBetween('due_date', [$from, $to])
                        ->orWhereHas('milestones', fn ($milestone) => $milestone
                            ->whereBetween('start_date', [$from, $to])
                            ->orWhereBetween('due_date', [$from, $to]))
                        ->orWhereHas('tasks', fn ($task) => $task
                            ->whereBetween('start_date', [$from, $to])
                            ->orWhereBetween('due_date', [$from, $to]));
                });
            })
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('admin.projects.timeline.index', [
            'projects' => $projects,
            'search' => $search,
            'selectedProject' => $selectedProject,
            'selectedOwner' => $selectedOwner,
            'selectedStatus' => $selectedStatus,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statusOptions' => $statusOptions,
            'projectOptions' => Project::query()->orderBy('title')->get(['id', 'project_number', 'title']),
            'owners' => User::query()
                ->whereIn('id', Project::query()->select('project_manager_id')->whereNotNull('project_manager_id'))
                ->orWhereIn('id', ProjectTask::query()->select('assignee_id')->whereNotNull('assignee_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'summary' => $this->summary(),
            'timelineStart' => $this->timelineStart($dateFrom),
            'timelineEnd' => $this->timelineEnd($dateTo),
            'today' => CarbonImmutable::today(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function statusOptions(): array
    {
        return [
            'planning' => 'Planning',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'delayed' => 'Delayed',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * @return array<string, int|string>
     */
    protected function summary(): array
    {
        $totalProjects = Project::query()->count();
        $completedProjects = Project::query()->where('status', 'completed')->count();

        return [
            'total_projects' => $totalProjects,
            'active_timelines' => Project::query()->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'upcoming_milestones' => ProjectMilestone::query()
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereBetween('due_date', [now()->toDateString(), now()->addDays(7)->toDateString()])
                ->count(),
            'due_this_week' => ProjectTask::query()
                ->whereNotIn('status', ['done'])
                ->whereBetween('due_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])
                ->count(),
            'overdue_tasks' => ProjectTask::query()
                ->whereNotIn('status', ['done'])
                ->whereDate('due_date', '<', now()->toDateString())
                ->count(),
            'completion_percentage' => $totalProjects === 0
                ? '0%'
                : ((int) round(($completedProjects / $totalProjects) * 100)).'%',
        ];
    }

    protected function timelineStart(string $dateFrom): CarbonImmutable
    {
        if ($dateFrom !== '') {
            return CarbonImmutable::parse($dateFrom)->startOfDay();
        }

        return CarbonImmutable::today()->subDays(14);
    }

    protected function timelineEnd(string $dateTo): CarbonImmutable
    {
        if ($dateTo !== '') {
            return CarbonImmutable::parse($dateTo)->startOfDay();
        }

        return CarbonImmutable::today()->addDays(76);
    }
}
