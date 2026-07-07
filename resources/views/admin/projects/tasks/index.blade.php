@extends('admin.layouts.app')

@section('title', 'Task Management - Krakatau CRM')

@section('content')
    @php
        $query = fn (array $changes = []) => array_filter(array_merge([
            'q' => $search,
            'project_id' => $selectedProject,
            'status' => $selectedStatus,
            'priority' => $selectedPriority,
            'assignee_id' => $selectedAssignee,
        ], $changes), fn ($value) => $value !== '' && $value !== null);
        $taskProgress = fn (string $status): int => match ($status) {
            'done' => 100,
            'review' => 75,
            'in_progress' => 45,
            default => 0,
        };
    @endphp

    <section class="lead-list-page project-task-index-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">Project workspace</span>
                <h1>Task Management</h1>
                <p>Kelola task delivery lintas project dengan status, priority, assignee, dan due date.</p>
            </div>
            <a href="{{ route('admin.projects.index') }}" class="btn lead-banner-cta">Open Projects</a>
        </header>

        <div class="lead-kpi-strip project-task-kpi-strip" aria-label="Task summary">
            <div><span>Total Task</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>Todo</span><strong>{{ number_format($summary['todo']) }}</strong></div>
            <div><span>In Progress</span><strong>{{ number_format($summary['in_progress']) }}</strong></div>
            <div><span>Review</span><strong>{{ number_format($summary['review']) }}</strong></div>
            <div><span>Done</span><strong>{{ number_format($summary['done']) }}</strong></div>
            <div><span>Overdue</span><strong>{{ number_format($summary['overdue']) }}</strong></div>
        </div>

        <section class="lead-list-workspace">
            <div class="lead-smart-filters">
                <nav class="lead-filter-chips" aria-label="Task status filters">
                    <a href="{{ route('admin.projects.tasks.index', $query(['status' => ''])) }}" @class(['active' => $selectedStatus === ''])>All</a>
                    @foreach ($taskStatuses as $status => $label)
                        <a href="{{ route('admin.projects.tasks.index', $query(['status' => $status])) }}" @class(['active' => $selectedStatus === $status])>{{ $label }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('admin.projects.tasks.index') }}" class="lead-list-toolbar project-task-toolbar">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search task, project, or description" aria-label="Search tasks">
                    <select name="project_id" aria-label="Filter project">
                        <option value="">All projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $selectedProject === (string) $project->id)>{{ $project->project_number }} - {{ $project->title }}</option>
                        @endforeach
                    </select>
                    <select name="status" aria-label="Filter status">
                        <option value="">All statuses</option>
                        @foreach ($taskStatuses as $status => $label)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="priority" aria-label="Filter priority">
                        <option value="">All priorities</option>
                        @foreach ($taskPriorities as $priority => $label)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <select name="assignee_id" aria-label="Filter assignee">
                        <option value="">All assignees</option>
                        @foreach ($assignees as $assignee)
                            <option value="{{ $assignee->id }}" @selected((string) $selectedAssignee === (string) $assignee->id)>{{ $assignee->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    @if ($search || $selectedProject || $selectedStatus || $selectedPriority || $selectedAssignee)
                        <a href="{{ route('admin.projects.tasks.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
            </div>

            @if ($tasks->isNotEmpty())
                <div class="customer-table-wrap lead-table-wrap">
                    <table class="customer-table lead-modern-table project-task-modern-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Task</th>
                                <th>Assignee</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Progress</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tasks as $task)
                                @php($progress = $taskProgress($task->status))
                                <tr>
                                    <td>
                                        <div class="project-task-project-cell">
                                            <a href="{{ route('admin.projects.show', $task->project) }}">{{ $task->project?->title ?: '-' }}</a>
                                            <small>{{ $task->project?->project_number ?: '-' }}</small>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="project-task-title-cell">
                                            <strong>{{ $task->title }}</strong>
                                            <small>{{ str($task->description ?: 'No description')->limit(70) }}</small>
                                        </div>
                                    </td>
                                    <td><span class="lead-owner">{{ $task->assignee?->name ?: 'Unassigned' }}</span></td>
                                    <td><span class="status-badge priority-{{ $task->priority }}">{{ $taskPriorities[$task->priority] ?? str($task->priority)->headline() }}</span></td>
                                    <td><span class="status-badge status-{{ str_replace('_', '-', $task->status) }}">{{ $taskStatuses[$task->status] ?? str($task->status)->headline() }}</span></td>
                                    <td><span class="project-last-update">{{ $task->due_date?->format('d M Y') ?: '-' }}</span></td>
                                    <td>
                                        <div class="project-progress-cell">
                                            <div class="project-progress-track"><span style="width: {{ $progress }}%"></span></div>
                                            <small>{{ $progress }}%</small>
                                        </div>
                                    </td>
                                    <td>
                                        <details class="lead-row-menu">
                                            <summary aria-label="Actions for {{ $task->title }}">•••</summary>
                                            <div>
                                                <a href="{{ route('admin.projects.show', $task->project) }}">Open Project</a>
                                                <a href="{{ route('admin.projects.show', ['project' => $task->project, 'tab' => 'tasks']).'#task-'.$task->id }}">Open Detail</a>
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="lead-empty-state project-empty-state">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'kanban'])</span>
                    <strong>Belum ada Task</strong>
                    <p>Task delivery akan muncul setelah dibuat dari Project Detail.</p>
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-primary">Open Projects</a>
                </div>
            @endif

            @if ($tasks->hasPages())
                <div class="customer-pagination lead-pagination">{{ $tasks->links() }}</div>
            @endif
        </section>
    </section>
@endsection
