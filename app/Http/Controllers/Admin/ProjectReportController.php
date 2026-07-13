<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectMilestone;
use App\Models\ProjectTimesheet;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ProjectReportController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $projectsQuery = $this->filteredProjects($filters);

        return view('admin.projects.reports.index', [
            'filters' => $filters,
            'kpis' => $this->kpis($projectsQuery),
            'statusDistribution' => $this->statusDistribution($projectsQuery),
            'completionTrend' => $this->completionTrend($projectsQuery),
            'workloadByEmployee' => $this->workloadByEmployee($filters),
            'milestoneHealth' => $this->milestoneHealth($filters),
            'timesheetSummary' => $this->timesheetSummary($filters),
            'recentDelivery' => $this->recentDelivery($projectsQuery),
            'topDelayedProjects' => $this->topDelayedProjects($projectsQuery),
            'projects' => Project::query()->orderBy('title')->get(['id', 'project_number', 'title']),
            'managers' => User::query()
                ->whereIn('id', Project::query()->select('project_manager_id')->whereNotNull('project_manager_id'))
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
            'customers' => Customer::query()->orderBy('name')->get(['id', 'name', 'company_name']),
            'statusOptions' => $this->statusOptions(),
            'priorityOptions' => $this->priorityOptions(),
            'departmentOptions' => $this->departmentOptions(),
        ]);
    }

    public function export(Request $request): Response
    {
        $filters = $this->filters($request);
        $type = strtolower((string) $request->query('type', 'csv'));
        $projectsQuery = $this->filteredProjects($filters);
        $rows = $this->recentDelivery($projectsQuery, 50);

        if ($type === 'print') {
            return response()->view('admin.projects.reports.print', [
                'kpis' => $this->kpis($projectsQuery),
                'recentDelivery' => $rows,
                'generatedAt' => now(),
            ]);
        }

        if ($type === 'pdf') {
            $lines = collect(['Project Reports', 'Generated at '.now()->format('d M Y H:i'), ''])
                ->merge($rows->map(fn (Project $project): string => implode(' | ', [
                    $project->project_number,
                    $project->title,
                    $project->progress.'%',
                    $project->projectManager?->name ?: '-',
                    str($project->status)->headline(),
                    $project->due_date?->format('d M Y') ?: '-',
                ])))
                ->all();

            return response($this->buildPdf($lines), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="project-reports.pdf"',
            ]);
        }

        $csv = collect([['Project', 'Progress', 'Milestone', 'Owner', 'Status', 'Due Date']])
            ->merge($rows->map(fn (Project $project): array => [
                $project->project_number.' - '.$project->title,
                $project->progress.'%',
                $project->milestones->first()?->title ?: '-',
                $project->projectManager?->name ?: '-',
                str($project->status)->headline()->toString(),
                $project->due_date?->format('Y-m-d') ?: '-',
            ]))
            ->map(fn (array $row): string => implode(',', array_map(fn ($value): string => '"'.str_replace('"', '""', (string) $value).'"', $row)))
            ->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="project-reports.'.($type === 'excel' ? 'xls' : 'csv').'"',
        ]);
    }

    /** @return array<string, string> */
    protected function filters(Request $request): array
    {
        return [
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'project_id' => (string) $request->query('project_id', ''),
            'project_manager_id' => (string) $request->query('project_manager_id', ''),
            'department' => (string) $request->query('department', ''),
            'status' => (string) $request->query('status', ''),
            'priority' => (string) $request->query('priority', ''),
            'customer_id' => (string) $request->query('customer_id', ''),
        ];
    }

    /** @param array<string, string> $filters */
    protected function filteredProjects(array $filters)
    {
        return Project::query()
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('created_at', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('created_at', '<=', $filters['date_to']))
            ->when($filters['project_id'] !== '', fn ($query) => $query->whereKey((int) $filters['project_id']))
            ->when($filters['project_manager_id'] !== '', fn ($query) => $query->where('project_manager_id', (int) $filters['project_manager_id']))
            ->when($filters['customer_id'] !== '', fn ($query) => $query->where('customer_id', (int) $filters['customer_id']))
            ->when(array_key_exists($filters['status'], $this->statusOptions()), fn ($query) => $query->where('status', $filters['status']))
            ->when(array_key_exists($filters['priority'], $this->priorityOptions()), fn ($query) => $query->whereHas('tasks', fn ($task) => $task->where('priority', $filters['priority'])))
            ->when(array_key_exists($filters['department'], $this->departmentOptions()), fn ($query) => $query->whereHas('members', fn ($member) => $member->where('role', $filters['department'])));
    }

    protected function kpis($projectsQuery): array
    {
        $projects = (clone $projectsQuery)->get(['id', 'status', 'progress', 'budget']);
        $totalProjects = $projects->count();
        $completedProjects = $projects->where('status', 'completed')->count();
        $budgetTotal = (float) $projects->sum('budget');
        $weightedProgressBudget = (float) $projects->sum(fn (Project $project): float => ((float) $project->budget) * ((int) $project->progress / 100));

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $projects->whereIn('status', ['active', 'in_progress'])->count(),
            'completed_projects' => $completedProjects,
            'delayed_projects' => (clone $projectsQuery)
                ->whereDate('due_date', '<', now()->toDateString())
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->count(),
            'overall_completion' => $totalProjects === 0 ? '0%' : ((int) round($projects->avg('progress'))).'%',
            'billable_hours' => $this->hours(ProjectTimesheet::query()->where('billable', true)->sum('duration_minutes')),
            'budget_utilization' => $budgetTotal <= 0 ? '0%' : ((int) round(($weightedProgressBudget / $budgetTotal) * 100)).'%',
            'total_team_members' => ProjectMember::query()
                ->whereIn('project_id', $projects->pluck('id'))
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    protected function statusDistribution($projectsQuery): array
    {
        $counts = (clone $projectsQuery)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect($this->statusOptions())
            ->map(fn (string $label, string $status): array => [
                'status' => $status,
                'label' => $label,
                'total' => (int) $counts->get($status, 0),
            ])
            ->values()
            ->all();
    }

    protected function completionTrend($projectsQuery): Collection
    {
        $start = CarbonImmutable::now()->startOfMonth()->subMonths(11);
        $projects = (clone $projectsQuery)
            ->whereDate('created_at', '>=', $start->toDateString())
            ->get(['created_at', 'progress']);

        return collect(range(0, 11))->map(function (int $month) use ($start, $projects): array {
            $date = $start->addMonths($month);
            $monthProjects = $projects->filter(fn (Project $project): bool => $project->created_at?->format('Y-m') === $date->format('Y-m'));

            return [
                'label' => $date->format('M'),
                'value' => $monthProjects->isEmpty() ? 0 : (int) round($monthProjects->avg('progress')),
            ];
        });
    }

    /** @param array<string, string> $filters */
    protected function workloadByEmployee(array $filters): Collection
    {
        return ProjectTimesheet::query()
            ->with('user:id,name')
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('work_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('work_date', '<=', $filters['date_to']))
            ->when($filters['project_id'] !== '', fn ($query) => $query->where('project_id', (int) $filters['project_id']))
            ->get(['user_id', 'duration_minutes'])
            ->groupBy('user_id')
            ->map(fn (Collection $logs): array => [
                'employee' => $logs->first()->user?->name ?: 'Unassigned',
                'minutes' => (int) $logs->sum('duration_minutes'),
                'hours' => $this->hours($logs->sum('duration_minutes')),
            ])
            ->sortByDesc('minutes')
            ->take(6)
            ->values();
    }

    /** @param array<string, string> $filters */
    protected function milestoneHealth(array $filters): array
    {
        $query = ProjectMilestone::query()
            ->when($filters['project_id'] !== '', fn ($milestone) => $milestone->where('project_id', (int) $filters['project_id']));

        return [
            'completed' => (clone $query)->where('status', 'completed')->count(),
            'upcoming' => (clone $query)
                ->whereNotIn('status', ['completed', 'cancelled'])
                ->whereDate('due_date', '>=', now()->toDateString())
                ->count(),
            'delayed' => (clone $query)
                ->where(function ($delayed): void {
                    $delayed
                        ->where('status', 'delayed')
                        ->orWhere(fn ($overdue) => $overdue
                            ->whereDate('due_date', '<', now()->toDateString())
                            ->whereNotIn('status', ['completed', 'cancelled']));
                })
                ->count(),
        ];
    }

    /** @param array<string, string> $filters */
    protected function timesheetSummary(array $filters): array
    {
        $query = ProjectTimesheet::query()
            ->when($filters['date_from'] !== '', fn ($timesheet) => $timesheet->whereDate('work_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($timesheet) => $timesheet->whereDate('work_date', '<=', $filters['date_to']))
            ->when($filters['project_id'] !== '', fn ($timesheet) => $timesheet->where('project_id', (int) $filters['project_id']));

        $totalMinutes = (clone $query)->sum('duration_minutes');
        $billableMinutes = (clone $query)->where('billable', true)->sum('duration_minutes');

        return [
            'hours' => $this->hours($totalMinutes),
            'billable' => $this->hours($billableMinutes),
            'non_billable' => $this->hours(max(0, $totalMinutes - $billableMinutes)),
            'pending_approval' => (clone $query)->where('status', ProjectTimesheet::STATUS_SUBMITTED)->count(),
        ];
    }

    protected function recentDelivery($projectsQuery, int $limit = 8): Collection
    {
        return (clone $projectsQuery)
            ->with(['projectManager:id,name', 'milestones' => fn ($query) => $query->orderBy('due_date')])
            ->latest()
            ->limit($limit)
            ->get();
    }

    protected function topDelayedProjects($projectsQuery): Collection
    {
        return (clone $projectsQuery)
            ->with('projectManager:id,name')
            ->whereDate('due_date', '<', now()->toDateString())
            ->whereNotIn('status', ['completed', 'cancelled'])
            ->orderBy('due_date')
            ->limit(6)
            ->get()
            ->map(function (Project $project): array {
                $daysLate = abs(now()->startOfDay()->diffInDays($project->due_date?->startOfDay() ?? now()->startOfDay(), false));

                return [
                    'project' => $project,
                    'days_late' => $daysLate,
                    'owner' => $project->projectManager?->name ?: '-',
                    'reason' => $project->status === 'delayed' ? 'Marked delayed' : 'Due date passed',
                ];
            });
    }

    protected function hours(int|float $minutes): string
    {
        return number_format(((float) $minutes) / 60, 1).'h';
    }

    /** @return array<string, string> */
    protected function statusOptions(): array
    {
        return [
            'planning' => 'Planning',
            'active' => 'In Progress',
            'completed' => 'Completed',
            'delayed' => 'Delayed',
            'cancelled' => 'Cancelled',
        ];
    }

    /** @return array<string, string> */
    protected function priorityOptions(): array
    {
        return [
            'low' => 'Low',
            'medium' => 'Medium',
            'high' => 'High',
            'critical' => 'Critical',
        ];
    }

    /** @return array<string, string> */
    protected function departmentOptions(): array
    {
        return [
            'project_manager' => 'Project Manager',
            'developer' => 'Developer',
            'designer' => 'Designer',
            'qa' => 'QA',
            'support' => 'Support',
            'observer' => 'Observer',
        ];
    }

    /** @param array<int, string> $lines */
    protected function buildPdf(array $lines): string
    {
        $text = "BT\n/F1 12 Tf\n14 TL\n40 800 Td\n";

        foreach (array_slice($lines, 0, 48) as $line) {
            $text .= '('.str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line).") Tj\nT*\n";
        }

        $text .= 'ET';
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($text)." >>\nstream\n{$text}\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";
    }
}
