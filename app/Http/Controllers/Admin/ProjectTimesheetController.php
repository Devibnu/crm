<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMilestone;
use App\Models\ProjectTask;
use App\Models\ProjectTimesheet;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectTimesheetController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $this->filters($request);
        $query = $this->filteredQuery($filters);

        return view('admin.projects.timesheets.index', [
            'timesheets' => (clone $query)
                ->with([
                    'project:id,project_number,title',
                    'milestone:id,project_id,title',
                    'task:id,project_id,milestone_id,title',
                    'user:id,name,email',
                    'approver:id,name,email',
                ])
                ->latest('work_date')
                ->latest()
                ->paginate(12)
                ->withQueryString(),
            'summary' => $this->summary(),
            'calendarDays' => $this->calendarDays($filters),
            'filters' => $filters,
            'statusOptions' => $this->statusOptions(),
            'projects' => Project::query()->orderBy('title')->get(['id', 'project_number', 'title']),
            'milestones' => ProjectMilestone::query()->with('project:id,project_number,title')->orderBy('title')->get(['id', 'project_id', 'title']),
            'tasks' => ProjectTask::query()->with('project:id,project_number,title')->orderBy('title')->get(['id', 'project_id', 'milestone_id', 'title']),
            'employees' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.projects.timesheets.form', [
            'timesheet' => new ProjectTimesheet([
                'project_id' => $request->integer('project_id') ?: null,
                'task_id' => $request->integer('task_id') ?: null,
                'user_id' => auth()->id(),
                'work_date' => now()->toDateString(),
                'start_time' => '09:00',
                'end_time' => '10:00',
                'billable' => true,
                'status' => ProjectTimesheet::STATUS_DRAFT,
            ]),
            'formMode' => 'create',
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['duration_minutes'] = $this->durationMinutes($validated['start_time'], $validated['end_time']);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();

        $timesheet = ProjectTimesheet::create($validated);

        return redirect()
            ->route('admin.projects.timesheets.show', $timesheet)
            ->with('success', 'Timesheet berhasil dibuat.');
    }

    public function show(ProjectTimesheet $timesheet): View
    {
        return view('admin.projects.timesheets.show', [
            'timesheet' => $timesheet->load([
                'project:id,project_number,title,status,progress',
                'milestone:id,project_id,title,status',
                'task:id,project_id,milestone_id,title,status',
                'user:id,name,email',
                'approver:id,name,email',
                'creator:id,name,email',
                'updater:id,name,email',
            ]),
        ]);
    }

    public function edit(ProjectTimesheet $timesheet): View
    {
        return view('admin.projects.timesheets.form', [
            'timesheet' => $timesheet,
            'formMode' => 'edit',
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, ProjectTimesheet $timesheet): RedirectResponse
    {
        $validated = $this->validatedData($request);
        $validated['duration_minutes'] = $this->durationMinutes($validated['start_time'], $validated['end_time']);
        $validated['updated_by'] = auth()->id();

        if ($validated['status'] !== ProjectTimesheet::STATUS_APPROVED) {
            $validated['approved_by'] = null;
            $validated['approved_at'] = null;
        }

        $timesheet->update($validated);

        return redirect()
            ->route('admin.projects.timesheets.show', $timesheet)
            ->with('success', 'Timesheet berhasil diperbarui.');
    }

    public function destroy(ProjectTimesheet $timesheet): RedirectResponse
    {
        $timesheet->delete();

        return redirect()
            ->route('admin.projects.timesheets.index')
            ->with('success', 'Timesheet berhasil dihapus.');
    }

    public function approve(Request $request, ProjectTimesheet $timesheet): RedirectResponse
    {
        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $timesheet->update([
            'status' => ProjectTimesheet::STATUS_APPROVED,
            'approval_note' => $validated['approval_note'] ?? null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.projects.timesheets.show', $timesheet)
            ->with('success', 'Timesheet berhasil disetujui.');
    }

    public function reject(Request $request, ProjectTimesheet $timesheet): RedirectResponse
    {
        $validated = $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $timesheet->update([
            'status' => ProjectTimesheet::STATUS_REJECTED,
            'approval_note' => $validated['approval_note'] ?? null,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.projects.timesheets.show', $timesheet)
            ->with('success', 'Timesheet berhasil ditolak.');
    }

    public function exportExcel(Request $request): Response
    {
        $rows = $this->filteredQuery($this->filters($request))
            ->with(['project:id,project_number,title', 'milestone:id,title', 'task:id,title', 'user:id,name'])
            ->latest('work_date')
            ->get();

        $csv = collect([['Date', 'Employee', 'Project', 'Milestone', 'Task', 'Duration Minutes', 'Billable', 'Status']])
            ->merge($rows->map(fn (ProjectTimesheet $timesheet): array => [
                $timesheet->work_date?->toDateString(),
                $timesheet->user?->name,
                trim(($timesheet->project?->project_number ?? '').' '.$timesheet->project?->title),
                $timesheet->milestone?->title,
                $timesheet->task?->title,
                $timesheet->duration_minutes,
                $timesheet->billable ? 'Yes' : 'No',
                $timesheet->statusLabel(),
            ]))
            ->map(fn (array $row): string => implode(',', array_map(fn ($value): string => '"'.str_replace('"', '""', (string) $value).'"', $row)))
            ->implode("\n");

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="project-timesheets.csv"',
        ]);
    }

    public function exportPdf(Request $request): Response
    {
        $timesheets = $this->filteredQuery($this->filters($request))
            ->with(['project:id,project_number,title', 'task:id,title', 'user:id,name'])
            ->latest('work_date')
            ->get();

        $lines = collect(['Project Timesheets', 'Generated at '.now()->format('d M Y H:i'), ''])
            ->merge($timesheets->map(fn (ProjectTimesheet $timesheet): string => implode(' | ', [
                $timesheet->work_date?->format('d M Y'),
                $timesheet->user?->name,
                trim(($timesheet->project?->project_number ?? '').' '.$timesheet->project?->title),
                $timesheet->task?->title ?: '-',
                $timesheet->durationLabel(),
                $timesheet->statusLabel(),
            ])))
            ->values()
            ->all();

        return response($this->buildPdf($lines), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="project-timesheets.pdf"',
        ]);
    }

    /** @return array<string, mixed> */
    protected function filters(Request $request): array
    {
        return [
            'q' => trim((string) $request->query('q', '')),
            'employee_id' => (string) $request->query('employee_id', ''),
            'project_id' => (string) $request->query('project_id', ''),
            'milestone_id' => (string) $request->query('milestone_id', ''),
            'task_id' => (string) $request->query('task_id', ''),
            'status' => (string) $request->query('status', ''),
            'billable' => (string) $request->query('billable', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
        ];
    }

    /** @param array<string, mixed> $filters */
    protected function filteredQuery(array $filters)
    {
        $statusOptions = $this->statusOptions();

        return ProjectTimesheet::query()
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $search = $filters['q'];
                $query->where(function ($inner) use ($search): void {
                    $inner
                        ->where('description', 'like', "%{$search}%")
                        ->orWhereHas('project', fn ($project) => $project
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('project_number', 'like', "%{$search}%"))
                        ->orWhereHas('task', fn ($task) => $task->where('title', 'like', "%{$search}%"))
                        ->orWhereHas('user', fn ($user) => $user->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['employee_id'] !== '', fn ($query) => $query->where('user_id', (int) $filters['employee_id']))
            ->when($filters['project_id'] !== '', fn ($query) => $query->where('project_id', (int) $filters['project_id']))
            ->when($filters['milestone_id'] !== '', fn ($query) => $query->where('milestone_id', (int) $filters['milestone_id']))
            ->when($filters['task_id'] !== '', fn ($query) => $query->where('task_id', (int) $filters['task_id']))
            ->when(array_key_exists($filters['status'], $statusOptions), fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['billable'] !== '', fn ($query) => $query->where('billable', $filters['billable'] === '1'))
            ->when($filters['date_from'] !== '', fn ($query) => $query->whereDate('work_date', '>=', $filters['date_from']))
            ->when($filters['date_to'] !== '', fn ($query) => $query->whereDate('work_date', '<=', $filters['date_to']));
    }

    /** @return array<string, string> */
    protected function statusOptions(): array
    {
        return [
            ProjectTimesheet::STATUS_DRAFT => 'Draft',
            ProjectTimesheet::STATUS_SUBMITTED => 'Submitted',
            ProjectTimesheet::STATUS_APPROVED => 'Approved',
            ProjectTimesheet::STATUS_REJECTED => 'Rejected',
        ];
    }

    /** @return array<string, mixed> */
    protected function summary(): array
    {
        $today = now()->toDateString();

        return [
            'today_hours' => $this->hours(ProjectTimesheet::query()->whereDate('work_date', $today)->sum('duration_minutes')),
            'week_hours' => $this->hours(ProjectTimesheet::query()->whereBetween('work_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()])->sum('duration_minutes')),
            'month_hours' => $this->hours(ProjectTimesheet::query()->whereBetween('work_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])->sum('duration_minutes')),
            'billable_hours' => $this->hours(ProjectTimesheet::query()->where('billable', true)->sum('duration_minutes')),
            'approved' => ProjectTimesheet::query()->where('status', ProjectTimesheet::STATUS_APPROVED)->count(),
            'pending_approval' => ProjectTimesheet::query()->where('status', ProjectTimesheet::STATUS_SUBMITTED)->count(),
        ];
    }

    /** @param array<string, mixed> $filters */
    protected function calendarDays(array $filters): Collection
    {
        $anchor = $filters['date_from'] !== '' ? CarbonImmutable::parse($filters['date_from']) : CarbonImmutable::today();
        $start = $anchor->startOfMonth();
        $end = $anchor->endOfMonth();
        $minutesByDate = (clone $this->filteredQuery([...$filters, 'date_from' => $start->toDateString(), 'date_to' => $end->toDateString()]))
            ->selectRaw('work_date, SUM(duration_minutes) as minutes, COUNT(*) as entries')
            ->groupBy('work_date')
            ->pluck('minutes', 'work_date');

        return collect(range(1, $end->day))->map(function (int $day) use ($start, $minutesByDate): array {
            $date = $start->day($day);
            $minutes = (int) ($minutesByDate->get($date->toDateString(), 0));

            return [
                'date' => $date,
                'minutes' => $minutes,
                'label' => $this->hours($minutes),
            ];
        });
    }

    /** @return array<string, mixed> */
    protected function formOptions(): array
    {
        return [
            'statusOptions' => $this->statusOptions(),
            'projects' => Project::query()->orderBy('title')->get(['id', 'project_number', 'title']),
            'milestones' => ProjectMilestone::query()->with('project:id,project_number,title')->orderBy('title')->get(['id', 'project_id', 'title']),
            'tasks' => ProjectTask::query()->with('project:id,project_number,title')->orderBy('title')->get(['id', 'project_id', 'milestone_id', 'title']),
            'employees' => User::query()->orderBy('name')->get(['id', 'name', 'email']),
        ];
    }

    /** @return array<string, mixed> */
    protected function validatedData(Request $request): array
    {
        $validated = $request->validate([
            'project_id' => ['required', 'integer', Rule::exists('projects', 'id')],
            'milestone_id' => ['nullable', 'integer', Rule::exists('project_milestones', 'id')],
            'task_id' => ['nullable', 'integer', Rule::exists('project_tasks', 'id')],
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'work_date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'billable' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(array_keys($this->statusOptions()))],
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['billable'] = $request->boolean('billable');

        if (($validated['milestone_id'] ?? null) && ! ProjectMilestone::query()
            ->whereKey($validated['milestone_id'])
            ->where('project_id', $validated['project_id'])
            ->exists()) {
            abort(422, 'Milestone harus berada pada project yang sama.');
        }

        if (($validated['task_id'] ?? null) && ! ProjectTask::query()
            ->whereKey($validated['task_id'])
            ->where('project_id', $validated['project_id'])
            ->exists()) {
            abort(422, 'Task harus berada pada project yang sama.');
        }

        return $validated;
    }

    protected function durationMinutes(string $startTime, string $endTime): int
    {
        return CarbonImmutable::createFromFormat('H:i', $startTime)
            ->diffInMinutes(CarbonImmutable::createFromFormat('H:i', $endTime));
    }

    protected function hours(int|float $minutes): string
    {
        return number_format(((float) $minutes) / 60, 1).'h';
    }

    /** @param array<int, string> $lines */
    protected function buildPdf(array $lines): string
    {
        $text = "BT\n/F1 12 Tf\n14 TL\n40 800 Td\n";

        foreach (array_slice($lines, 0, 48) as $line) {
            $text .= '('.$this->escapePdfText($line).") Tj\nT*\n";
        }

        $text .= "ET";
        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n",
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($text)." >>\nstream\n".$text."\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= str_pad((string) $offset, 10, '0', STR_PAD_LEFT)." 00000 n \n";
        }

        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    protected function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
