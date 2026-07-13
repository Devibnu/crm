@extends('admin.layouts.app')

@section('title', 'Project Milestones - Krakatau CRM')

@section('content')
    @php
        $visibleStatusOptions = collect($statusOptions)
            ->only(['planning', 'in_progress', 'completed', 'delayed', 'cancelled'])
            ->all();
        $addMilestoneUrl = $createMilestoneProject
            ? route('admin.projects.milestones.create', $createMilestoneProject)
            : route('admin.projects.index');
        $query = fn (array $changes = []) => array_filter(array_merge([
            'q' => $search,
            'project_id' => $selectedProject,
            'status' => $selectedStatus,
        ], $changes), fn ($value) => $value !== '' && $value !== null);
    @endphp

    <section class="lead-list-page project-milestone-index-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">PROJECT MANAGEMENT</span>
                <h1>Milestones</h1>
                <p>Track delivery checkpoints across projects with task progress, deadlines, and ownership context.</p>
            </div>
            <div class="project-milestone-hero-actions">
                <a href="{{ $addMilestoneUrl }}" class="btn lead-banner-cta" aria-label="Add milestone">Add Milestone</a>
                <a href="{{ route('admin.projects.index') }}" class="btn lead-banner-secondary" aria-label="Open projects">Open Projects</a>
            </div>
        </header>

        <div class="project-milestone-kpi-grid" aria-label="Milestone summary">
            <article class="project-milestone-kpi kpi-total">
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                <div><span>Total Milestones</span><strong>{{ number_format($summary['total']) }}</strong><small>All delivery phases</small></div>
            </article>
            <article class="project-milestone-kpi kpi-planning">
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'book'])</span>
                <div><span>Planning</span><strong>{{ number_format($summary['planning']) }}</strong><small>Scoped or queued</small></div>
            </article>
            <article class="project-milestone-kpi kpi-progress">
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span>
                <div><span>In Progress</span><strong>{{ number_format($summary['in_progress']) }}</strong><small>Currently moving</small></div>
            </article>
            <article class="project-milestone-kpi kpi-completed">
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'deal'])</span>
                <div><span>Completed</span><strong>{{ number_format($summary['completed']) }}</strong><small>Closed phases</small></div>
            </article>
            <article class="project-milestone-kpi kpi-delayed">
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'timer'])</span>
                <div><span>Delayed</span><strong>{{ number_format($summary['delayed']) }}</strong><small>Needs attention</small></div>
            </article>
            <article class="project-milestone-kpi kpi-completion">
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'analysis'])</span>
                <div><span>Completion %</span><strong>{{ number_format($summary['completion_percentage']) }}%</strong><small>Portfolio milestone rate</small></div>
            </article>
        </div>

        <section class="lead-list-workspace">
            <div class="lead-smart-filters project-milestone-filter-panel">
                <nav class="lead-filter-chips project-milestone-tabs" aria-label="Milestone status filters">
                    <a href="{{ route('admin.projects.milestones.index', $query(['status' => ''])) }}" @class(['active' => $selectedStatus === ''])>All</a>
                    @foreach ($visibleStatusOptions as $status => $label)
                        <a href="{{ route('admin.projects.milestones.index', $query(['status' => $status])) }}" @class(['active' => $selectedStatus === $status])>{{ $label }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('admin.projects.milestones.index') }}" class="lead-list-toolbar project-task-toolbar project-milestone-toolbar">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search milestone or project" aria-label="Search milestones">
                    <select name="project_id" aria-label="Filter project">
                        <option value="">All projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $selectedProject === (string) $project->id)>{{ $project->project_number }} - {{ $project->title }}</option>
                        @endforeach
                    </select>
                    <select name="status" aria-label="Filter status">
                        <option value="">All statuses</option>
                        @foreach ($visibleStatusOptions as $status => $label)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary" aria-label="Apply milestone filters">Apply</button>
                    @if ($search || $selectedProject || $selectedStatus)
                        <a href="{{ route('admin.projects.milestones.index') }}" class="btn btn-sm btn-muted" aria-label="Reset milestone filters">Reset</a>
                    @endif
                </form>
            </div>

            @if ($milestones->isNotEmpty())
                <div class="project-milestone-grid">
                    @foreach ($milestones as $milestone)
                        @php
                            $displayStatus = $milestone->displayStatus();
                            $displayStatus = $displayStatus === 'pending' ? 'planning' : $displayStatus;
                            $progress = $milestone->progressPercentage();
                            $totalTasks = $milestone->totalTaskCount();
                            $completedTasks = $milestone->completedTaskCount();
                            $isOverdue = $milestone->due_date
                                && $milestone->due_date->lt(now()->startOfDay())
                                && ! in_array($displayStatus, ['completed', 'cancelled'], true);
                            $overdueDays = $isOverdue
                                ? abs(now()->startOfDay()->diffInDays($milestone->due_date->startOfDay(), false))
                                : 0;
                        @endphp
                        <article @class([
                            'project-milestone-card',
                            'milestone-color-'.($milestone->color ?: 'blue'),
                            'is-overdue' => $isOverdue,
                        ])>
                            <header class="project-milestone-card-head">
                                <span class="project-milestone-icon">@include('admin.partials.sidebar-icon', ['icon' => $milestone->icon ?: 'calendar'])</span>
                                <div class="project-milestone-title-block">
                                    <a href="{{ route('admin.projects.milestones.show', [$milestone->project, $milestone]) }}" aria-label="View milestone {{ $milestone->title }}">{{ $milestone->title }}</a>
                                    <small>{{ $milestone->project?->project_number }} - {{ $milestone->project?->title }}</small>
                                </div>
                                <div class="project-milestone-state-stack">
                                    <span class="status-badge status-{{ str_replace('_', '-', $displayStatus) }}">{{ $visibleStatusOptions[$displayStatus] ?? str($displayStatus)->headline() }}</span>
                                    @if ($isOverdue)
                                        <span class="project-overdue-pill">Overdue</span>
                                    @endif
                                </div>
                            </header>
                            <p class="project-milestone-description-text">{{ $milestone->description ?: 'No description added for this delivery phase yet.' }}</p>
                            <div class="project-milestone-progress-focus" aria-label="Milestone progress {{ $progress }} percent">
                                <div>
                                    <strong>{{ $progress }}%</strong>
                                    <span>{{ $completedTasks }} of {{ $totalTasks }} tasks completed</span>
                                </div>
                                <div class="project-progress-track"><span style="width: {{ $progress }}%"></span></div>
                            </div>
                            <dl class="project-milestone-meta">
                                <div><dt>Tasks</dt><dd>{{ $completedTasks }} / {{ $totalTasks }}</dd><small>completed</small></div>
                                <div><dt>Start</dt><dd>{{ $milestone->start_date?->format('d M Y') ?: '-' }}</dd><small>phase begins</small></div>
                                <div @class(['is-danger' => $isOverdue])><dt>Due</dt><dd>{{ $milestone->due_date?->format('d M Y') ?: '-' }}</dd><small>{{ $isOverdue ? 'Overdue by '.$overdueDays.' days' : 'target date' }}</small></div>
                            </dl>
                            <footer class="project-milestone-card-actions">
                                <a href="{{ route('admin.projects.milestones.show', [$milestone->project, $milestone]) }}" class="btn btn-sm lead-banner-cta project-milestone-action-primary" aria-label="View detail for {{ $milestone->title }}"><span aria-hidden="true">👁</span> View Detail</a>
                                <a href="{{ route('admin.projects.show', ['project' => $milestone->project, 'tab' => 'milestones']) }}" class="btn btn-sm btn-muted project-milestone-action-secondary" aria-label="Open project {{ $milestone->project?->title }}"><span aria-hidden="true">📁</span> Open Project</a>
                                <a href="{{ route('admin.projects.milestones.edit', [$milestone->project, $milestone]) }}" class="btn btn-sm btn-muted project-milestone-action-edit" aria-label="Edit milestone {{ $milestone->title }}"><span aria-hidden="true">✏</span> Edit</a>
                                <details class="lead-row-menu project-milestone-more">
                                    <summary aria-label="More actions for {{ $milestone->title }}"><span aria-hidden="true">⋮</span><span class="project-milestone-more-label">More</span></summary>
                                    <div>
                                        <a href="{{ route('admin.projects.milestones.edit', [$milestone->project, $milestone]) }}" class="project-milestone-mobile-edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.projects.milestones.destroy', [$milestone->project, $milestone]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="task_action" value="delete_tasks">
                                            <button type="submit">Delete</button>
                                        </form>
                                    </div>
                                </details>
                            </footer>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="lead-empty-state project-empty-state project-milestone-empty">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                    <strong>Belum ada Milestone</strong>
                    <p>Buat milestone untuk membagi project menjadi fase delivery yang lebih terstruktur.</p>
                    <a href="{{ $addMilestoneUrl }}" class="btn btn-sm btn-primary" aria-label="Add milestone from empty state">Add Milestone</a>
                </div>
            @endif

            @if ($milestones->hasPages())
                <div class="customer-pagination lead-pagination">{{ $milestones->links() }}</div>
            @endif
        </section>
    </section>
@endsection
