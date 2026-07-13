@extends('admin.layouts.app')

@section('title', 'Project Milestones - Krakatau CRM')

@section('content')
    @php
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
            <a href="{{ route('admin.projects.index') }}" class="btn lead-banner-cta">Open Projects</a>
        </header>

        <div class="lead-kpi-strip project-task-kpi-strip" aria-label="Milestone summary">
            <div><span>Total</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>Open</span><strong>{{ number_format($summary['open']) }}</strong></div>
            <div><span>Completed</span><strong>{{ number_format($summary['completed']) }}</strong></div>
            <div><span>Delayed</span><strong>{{ number_format($summary['delayed']) }}</strong></div>
        </div>

        <section class="lead-list-workspace">
            <div class="lead-smart-filters">
                <nav class="lead-filter-chips" aria-label="Milestone status filters">
                    <a href="{{ route('admin.projects.milestones.index', $query(['status' => ''])) }}" @class(['active' => $selectedStatus === ''])>All</a>
                    @foreach ($statusOptions as $status => $label)
                        <a href="{{ route('admin.projects.milestones.index', $query(['status' => $status])) }}" @class(['active' => $selectedStatus === $status])>{{ $label }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('admin.projects.milestones.index') }}" class="lead-list-toolbar project-task-toolbar">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search milestone or project" aria-label="Search milestones">
                    <select name="project_id" aria-label="Filter project">
                        <option value="">All projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $selectedProject === (string) $project->id)>{{ $project->project_number }} - {{ $project->title }}</option>
                        @endforeach
                    </select>
                    <select name="status" aria-label="Filter status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status => $label)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    @if ($search || $selectedProject || $selectedStatus)
                        <a href="{{ route('admin.projects.milestones.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
            </div>

            @if ($milestones->isNotEmpty())
                <div class="project-milestone-grid">
                    @foreach ($milestones as $milestone)
                        @php
                            $displayStatus = $milestone->displayStatus();
                            $progress = $milestone->progressPercentage();
                            $totalTasks = $milestone->totalTaskCount();
                            $completedTasks = $milestone->completedTaskCount();
                        @endphp
                        <article class="project-milestone-card milestone-color-{{ $milestone->color ?: 'blue' }}">
                            <header>
                                <span class="project-milestone-icon">@include('admin.partials.sidebar-icon', ['icon' => $milestone->icon ?: 'calendar'])</span>
                                <div>
                                    <a href="{{ route('admin.projects.milestones.show', [$milestone->project, $milestone]) }}">{{ $milestone->title }}</a>
                                    <small>{{ $milestone->project?->project_number }} - {{ $milestone->project?->title }}</small>
                                </div>
                                <span class="status-badge status-{{ str_replace('_', '-', $displayStatus) }}">{{ $statusOptions[$displayStatus] ?? str($displayStatus)->headline() }}</span>
                            </header>
                            <p>{{ str($milestone->description ?: 'No description')->limit(120) }}</p>
                            <div class="project-milestone-progress">
                                <div><span>Progress</span><strong>{{ $progress }}%</strong></div>
                                <div class="project-progress-track"><span style="width: {{ $progress }}%"></span></div>
                            </div>
                            <dl class="project-milestone-meta">
                                <div><dt>Tasks</dt><dd>{{ $completedTasks }} / {{ $totalTasks }}</dd></div>
                                <div><dt>Start</dt><dd>{{ $milestone->start_date?->format('d M Y') ?: '-' }}</dd></div>
                                <div><dt>Due</dt><dd>{{ $milestone->due_date?->format('d M Y') ?: '-' }}</dd></div>
                            </dl>
                            <footer>
                                <a href="{{ route('admin.projects.show', ['project' => $milestone->project, 'tab' => 'milestones']) }}" class="btn btn-sm btn-muted">Project</a>
                                <a href="{{ route('admin.projects.milestones.edit', [$milestone->project, $milestone]) }}" class="btn btn-sm lead-banner-cta">Edit</a>
                            </footer>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="lead-empty-state project-empty-state project-milestone-empty">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                    <strong>Belum ada Milestone</strong>
                    <p>Buat milestone dari Project Detail untuk membagi delivery menjadi checkpoint yang jelas.</p>
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-primary">Open Projects</a>
                </div>
            @endif

            @if ($milestones->hasPages())
                <div class="customer-pagination lead-pagination">{{ $milestones->links() }}</div>
            @endif
        </section>
    </section>
@endsection
