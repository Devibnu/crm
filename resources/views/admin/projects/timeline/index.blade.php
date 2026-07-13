@extends('admin.layouts.app')

@section('title', 'Project Timeline - Krakatau CRM')

@section('content')
    @php
        $query = fn (array $changes = []) => array_filter(array_merge([
            'q' => $search,
            'project_id' => $selectedProject,
            'owner_id' => $selectedOwner,
            'status' => $selectedStatus,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ], $changes), fn ($value) => $value !== '' && $value !== null);
        $timelineDays = max(1, $timelineStart->diffInDays($timelineEnd));
        $todayPosition = max(0, min(100, ($timelineStart->diffInDays($today, false) / $timelineDays) * 100));
        $timelineTicks = collect(range(0, 6))->map(fn ($index) => $timelineStart->addDays((int) round(($timelineDays / 6) * $index)));
        $projectColors = ['violet', 'blue', 'green', 'amber', 'rose', 'slate'];
        $normalizeStatus = function (?string $status, bool $isOverdue = false): string {
            if ($status === 'active') {
                return 'in_progress';
            }

            if ($status === 'done') {
                return 'completed';
            }

            if ($isOverdue && ! in_array($status, ['completed', 'done', 'cancelled'], true)) {
                return 'delayed';
            }

            return $status ?: 'planning';
        };
        $dateRangeStyle = function ($startDate, $dueDate) use ($timelineStart, $timelineDays): string {
            $start = $startDate ? \Carbon\CarbonImmutable::parse($startDate)->startOfDay() : ($dueDate ? \Carbon\CarbonImmutable::parse($dueDate)->startOfDay() : $timelineStart);
            $end = $dueDate ? \Carbon\CarbonImmutable::parse($dueDate)->startOfDay() : $start;
            $left = max(0, min(100, ($timelineStart->diffInDays($start, false) / $timelineDays) * 100));
            $right = max(0, min(100, ($timelineStart->diffInDays($end, false) / $timelineDays) * 100));
            $width = max(4, $right - $left);

            return 'left: '.$left.'%; width: '.$width.'%;';
        };
        $dateSignal = function ($dueDate, string $status): array {
            if (! $dueDate) {
                return ['label' => 'No due date', 'class' => 'neutral'];
            }

            $due = \Carbon\CarbonImmutable::parse($dueDate)->startOfDay();
            $days = now()->startOfDay()->diffInDays($due, false);

            if (in_array($status, ['completed', 'done'], true)) {
                return ['label' => 'Completed', 'class' => 'completed'];
            }

            if ($days < 0) {
                return ['label' => 'Overdue by '.abs($days).' days', 'class' => 'overdue'];
            }

            if ($days <= 7) {
                return ['label' => 'Due in '.$days.' days', 'class' => 'upcoming'];
            }

            return ['label' => $days.' days remaining', 'class' => 'neutral'];
        };
    @endphp

    <section class="lead-list-page project-timeline-page" data-project-timeline>
        <header class="lead-list-header project-timeline-hero">
            <div>
                <span class="crm-record-kicker">PROJECT MANAGEMENT</span>
                <h1>Project Timeline</h1>
                <p>Visualize project schedules, milestones, deadlines, and task delivery progress.</p>
            </div>
            <div class="project-timeline-hero-actions">
                <a href="{{ route('admin.projects.index') }}" class="btn lead-banner-secondary">Open Projects</a>
                <a href="{{ route('admin.projects.timeline.index') }}" class="btn lead-banner-cta">Today</a>
            </div>
        </header>

        <div class="project-timeline-kpi-grid" aria-label="Project timeline summary">
            <article><span>@include('admin.partials.sidebar-icon', ['icon' => 'pipeline'])</span><div><small>Total Projects</small><strong>{{ number_format($summary['total_projects']) }}</strong><em>Portfolio scope</em></div></article>
            <article><span>@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span><div><small>Active Timelines</small><strong>{{ number_format($summary['active_timelines']) }}</strong><em>Open schedules</em></div></article>
            <article><span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span><div><small>Upcoming Milestones</small><strong>{{ number_format($summary['upcoming_milestones']) }}</strong><em>Next 7 days</em></div></article>
            <article><span>@include('admin.partials.sidebar-icon', ['icon' => 'timer'])</span><div><small>Due This Week</small><strong>{{ number_format($summary['due_this_week']) }}</strong><em>Open tasks</em></div></article>
            <article><span>@include('admin.partials.sidebar-icon', ['icon' => 'case'])</span><div><small>Overdue Tasks</small><strong>{{ number_format($summary['overdue_tasks']) }}</strong><em>Needs attention</em></div></article>
            <article><span>@include('admin.partials.sidebar-icon', ['icon' => 'analysis'])</span><div><small>Completion %</small><strong>{{ $summary['completion_percentage'] }}</strong><em>Completed projects</em></div></article>
        </div>

        <section class="lead-list-workspace project-timeline-workspace">
            <div class="project-timeline-controls">
                <form method="GET" action="{{ route('admin.projects.timeline.index') }}" class="project-timeline-filter">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search project, milestone, or task" aria-label="Search timeline">
                    <select name="project_id" aria-label="Filter project">
                        <option value="">All projects</option>
                        @foreach ($projectOptions as $projectOption)
                            <option value="{{ $projectOption->id }}" @selected((string) $selectedProject === (string) $projectOption->id)>{{ $projectOption->project_number }} - {{ $projectOption->title }}</option>
                        @endforeach
                    </select>
                    <select name="owner_id" aria-label="Filter owner">
                        <option value="">All owners</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->id }}" @selected((string) $selectedOwner === (string) $owner->id)>{{ $owner->name }}</option>
                        @endforeach
                    </select>
                    <select name="status" aria-label="Filter status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status => $label)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" aria-label="Date range from">
                    <input type="date" name="date_to" value="{{ $dateTo }}" aria-label="Date range to">
                    <button class="btn btn-sm btn-primary" type="submit">Apply</button>
                    @if ($search || $selectedProject || $selectedOwner || $selectedStatus || $dateFrom || $dateTo)
                        <a href="{{ route('admin.projects.timeline.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>

                <div class="project-timeline-view-switch" aria-label="Timeline view mode">
                    @foreach (['today' => 'Today', 'week' => 'Week', 'month' => 'Month', 'quarter' => 'Quarter'] as $mode => $label)
                        <button type="button" @class(['active' => $mode === 'month']) data-timeline-mode="{{ $mode }}">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            @if ($projects->isEmpty())
                <div class="project-timeline-empty">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                    <strong>No timeline available</strong>
                    <p>Create your first project to start planning.</p>
                    <a href="{{ route('admin.projects.create') }}" class="btn btn-sm btn-primary">Add Project</a>
                </div>
            @else
                <div class="project-timeline-board" data-view-mode="month">
                    <div class="project-timeline-axis">
                        <div class="project-timeline-axis-label">Schedule</div>
                        <div class="project-timeline-scale">
                            @foreach ($timelineTicks as $tick)
                                <span>{{ $tick->format('d M') }}</span>
                            @endforeach
                            <i class="project-timeline-today" style="left: {{ $todayPosition }}%"><span>TODAY</span></i>
                        </div>
                    </div>

                    @foreach ($projects as $projectIndex => $project)
                        @php
                            $projectColor = $projectColors[$projectIndex % count($projectColors)];
                            $projectStatus = $normalizeStatus($project->status, $project->due_date && $project->due_date->lt(now()->startOfDay()));
                            $projectSignal = $dateSignal($project->due_date, $project->status);
                        @endphp
                        <article class="project-timeline-group project-timeline-color-{{ $projectColor }}">
                            <div class="project-timeline-row project-row">
                                <div class="project-timeline-info">
                                    <span class="project-timeline-kind">Project</span>
                                    <strong>{{ $project->title }}</strong>
                                    <small>{{ $project->project_number }} · {{ $project->projectManager?->name ?: 'Unassigned' }}</small>
                                </div>
                                <div class="project-timeline-track">
                                    <span class="project-timeline-bar project-bar status-{{ str_replace('_', '-', $projectStatus) }}" style="{{ $dateRangeStyle($project->start_date, $project->due_date) }}">
                                        <b>{{ $project->progress }}%</b>
                                    </span>
                                    <i class="project-timeline-today" style="left: {{ $todayPosition }}%"></i>
                                </div>
                                <div class="project-timeline-meta">
                                    <span class="status-badge status-{{ str_replace('_', '-', $projectStatus) }}">{{ $statusOptions[$projectStatus] ?? str($projectStatus)->headline() }}</span>
                                    <small>{{ $project->start_date?->format('d M Y') ?: '-' }} → {{ $project->due_date?->format('d M Y') ?: '-' }}</small>
                                    <em class="timeline-signal {{ $projectSignal['class'] }}">{{ $projectSignal['label'] }}</em>
                                </div>
                            </div>

                            @foreach ($project->milestones as $milestone)
                                @php
                                    $milestoneStatus = $normalizeStatus($milestone->status, $milestone->isOverdue());
                                    $milestoneSignal = $dateSignal($milestone->due_date, $milestoneStatus);
                                    $milestoneProgress = $milestone->progressPercentage();
                                @endphp
                                <div class="project-timeline-row milestone-row">
                                    <div class="project-timeline-info">
                                        <span class="project-timeline-kind">Milestone</span>
                                        <strong>{{ $milestone->title }}</strong>
                                        <small>{{ $milestone->completedTaskCount() }} / {{ $milestone->totalTaskCount() }} tasks · {{ $project->projectManager?->name ?: 'Owner pending' }}</small>
                                    </div>
                                    <div class="project-timeline-track">
                                        <span class="project-timeline-bar milestone-bar status-{{ str_replace('_', '-', $milestoneStatus) }}" style="{{ $dateRangeStyle($milestone->start_date, $milestone->due_date) }}">
                                            <b>{{ $milestoneProgress }}%</b>
                                        </span>
                                        <i class="project-timeline-today" style="left: {{ $todayPosition }}%"></i>
                                    </div>
                                    <div class="project-timeline-meta">
                                        <span class="status-badge status-{{ str_replace('_', '-', $milestoneStatus) }}">{{ $statusOptions[$milestoneStatus] ?? str($milestoneStatus)->headline() }}</span>
                                        <small>Due {{ $milestone->due_date?->format('d M Y') ?: '-' }}</small>
                                        <em class="timeline-signal {{ $milestoneSignal['class'] }}">{{ $milestoneSignal['label'] }}</em>
                                    </div>
                                </div>

                                @foreach ($milestone->tasks as $task)
                                    @php
                                        $taskStatus = $normalizeStatus($task->status, $task->due_date && $task->due_date->lt(now()->startOfDay()));
                                        $taskSignal = $dateSignal($task->due_date, $task->status);
                                        $taskProgress = $task->status === 'done' ? 100 : ($task->status === 'review' ? 75 : ($task->status === 'in_progress' ? 45 : 10));
                                    @endphp
                                    <div class="project-timeline-row task-row">
                                        <div class="project-timeline-info">
                                            <span class="project-timeline-kind">Task</span>
                                            <strong>{{ $task->title }}</strong>
                                            <small>{{ str($task->priority)->headline() }} · {{ $task->assignee?->name ?: 'Unassigned' }}</small>
                                        </div>
                                        <div class="project-timeline-track">
                                            <span class="project-timeline-bar task-bar status-{{ str_replace('_', '-', $taskStatus) }}" style="{{ $dateRangeStyle($task->start_date, $task->due_date) }}">
                                                <b>{{ $taskProgress }}%</b>
                                            </span>
                                            <i class="project-timeline-today" style="left: {{ $todayPosition }}%"></i>
                                        </div>
                                        <div class="project-timeline-meta">
                                            <span class="status-badge priority-{{ $task->priority }}">{{ str($task->priority)->headline() }}</span>
                                            <small>{{ $task->start_date?->format('d M Y') ?: '-' }} → {{ $task->due_date?->format('d M Y') ?: '-' }}</small>
                                            <em class="timeline-signal {{ $taskSignal['class'] }}">{{ $taskSignal['label'] }}</em>
                                        </div>
                                    </div>
                                @endforeach
                            @endforeach

                            @foreach ($project->tasks->whereNull('milestone_id') as $task)
                                @php
                                    $taskStatus = $normalizeStatus($task->status, $task->due_date && $task->due_date->lt(now()->startOfDay()));
                                    $taskSignal = $dateSignal($task->due_date, $task->status);
                                    $taskProgress = $task->status === 'done' ? 100 : ($task->status === 'review' ? 75 : ($task->status === 'in_progress' ? 45 : 10));
                                @endphp
                                <div class="project-timeline-row task-row">
                                    <div class="project-timeline-info">
                                        <span class="project-timeline-kind">Task</span>
                                        <strong>{{ $task->title }}</strong>
                                        <small>{{ str($task->priority)->headline() }} · {{ $task->assignee?->name ?: 'Unassigned' }}</small>
                                    </div>
                                    <div class="project-timeline-track">
                                        <span class="project-timeline-bar task-bar status-{{ str_replace('_', '-', $taskStatus) }}" style="{{ $dateRangeStyle($task->start_date, $task->due_date) }}">
                                            <b>{{ $taskProgress }}%</b>
                                        </span>
                                        <i class="project-timeline-today" style="left: {{ $todayPosition }}%"></i>
                                    </div>
                                    <div class="project-timeline-meta">
                                        <span class="status-badge priority-{{ $task->priority }}">{{ str($task->priority)->headline() }}</span>
                                        <small>{{ $task->start_date?->format('d M Y') ?: '-' }} → {{ $task->due_date?->format('d M Y') ?: '-' }}</small>
                                        <em class="timeline-signal {{ $taskSignal['class'] }}">{{ $taskSignal['label'] }}</em>
                                    </div>
                                </div>
                            @endforeach
                        </article>
                    @endforeach
                </div>
            @endif

            @if ($projects->hasPages())
                <div class="customer-pagination lead-pagination">{{ $projects->links() }}</div>
            @endif
        </section>
    </section>

    <script>
        document.querySelectorAll('[data-timeline-mode]').forEach((button) => {
            button.addEventListener('click', () => {
                const board = document.querySelector('[data-view-mode]');

                document.querySelectorAll('[data-timeline-mode]').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');

                if (board) {
                    board.dataset.viewMode = button.dataset.timelineMode;
                }
            });
        });
    </script>
@endsection
