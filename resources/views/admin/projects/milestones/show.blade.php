@extends('admin.layouts.app')

@section('title', $milestone->title.' - Milestone - Krakatau CRM')

@section('content')
    @php
        $displayStatus = $milestone->displayStatus();
        $progress = $milestone->progressPercentage();
        $totalTasks = $milestone->totalTaskCount();
        $completedTasks = $milestone->completedTaskCount();
        $overdueTasks = $milestone->overdueTaskCount();
    @endphp

    <section class="crm-record-page project-record-page project-milestone-detail-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-detail-banner project-detail-banner">
            <div class="crm-record-heading">
                <span class="crm-record-kicker">PROJECT MILESTONE</span>
                <div class="crm-record-title-row">
                    <h1>{{ $milestone->title }}</h1>
                    <span class="status-badge status-{{ str_replace('_', '-', $displayStatus) }}">{{ $statusOptions[$displayStatus] ?? str($displayStatus)->headline() }}</span>
                </div>
                <p>{{ $project->project_number }} - {{ $project->title }}</p>
            </div>
            <div class="lead-detail-action-stack">
                <div class="crm-record-actions">
                    <a href="{{ route('admin.projects.milestones.index') }}" class="btn btn-sm lead-banner-secondary">All Milestones</a>
                    <a href="{{ route('admin.projects.milestones.edit', [$project, $milestone]) }}" class="btn btn-sm lead-banner-cta">Edit</a>
                </div>
            </div>
        </header>

        <div class="crm-metadata-row lead-detail-metadata project-summary-row">
            <div><span>Progress</span><strong>{{ $progress }}%</strong></div>
            <div><span>Tasks</span><strong>{{ $completedTasks }} / {{ $totalTasks }}</strong></div>
            <div><span>Overdue Tasks</span><strong>{{ $overdueTasks }}</strong></div>
            <div><span>Start Date</span><strong>{{ $milestone->start_date?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Due Date</span><strong>{{ $milestone->due_date?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Completed</span><strong>{{ $milestone->completed_at?->format('d M Y') ?: '-' }}</strong></div>
        </div>

        <div class="crm-record-workspace lead-workspace project-detail-workspace">
            <aside class="crm-workspace-sidebar crm-details-sidebar">
                <section class="crm-workspace-section">
                    <h2>Milestone Details</h2>
                    <div class="project-progress-card compact milestone-color-{{ $milestone->color ?: 'blue' }}">
                        <div>
                            <span>{{ $statusOptions[$displayStatus] ?? str($displayStatus)->headline() }}</span>
                            <strong>{{ $progress }}%</strong>
                        </div>
                        <div class="project-progress-track"><span style="width: {{ $progress }}%"></span></div>
                        <small>{{ $completedTasks }} of {{ $totalTasks }} tasks completed</small>
                    </div>
                    <dl class="crm-property-list">
                        <div><dt>Project</dt><dd><a href="{{ route('admin.projects.show', ['project' => $project, 'tab' => 'milestones']) }}">{{ $project->title }}</a></dd></div>
                        <div><dt>Color</dt><dd>{{ str($milestone->color ?: 'blue')->headline() }}</dd></div>
                        <div><dt>Icon</dt><dd>{{ str($milestone->icon ?: 'calendar')->headline() }}</dd></div>
                        <div><dt>Sort Order</dt><dd>{{ $milestone->sort_order }}</dd></div>
                    </dl>
                </section>

                <section class="crm-workspace-section">
                    <h2>Danger Zone</h2>
                    <form method="POST" action="{{ route('admin.projects.milestones.destroy', [$project, $milestone]) }}" class="project-milestone-delete-form">
                        @csrf
                        @method('DELETE')
                        <label class="field">
                            <span>Task Handling</span>
                            <select name="task_action">
                                <option value="delete_tasks">Delete tasks in this milestone</option>
                                @if ($siblingMilestones->isNotEmpty())
                                    <option value="move">Move tasks to another milestone</option>
                                @endif
                            </select>
                        </label>
                        <label class="field">
                            <span>Target Milestone</span>
                            <select name="target_milestone_id">
                                <option value="">None</option>
                                @foreach ($siblingMilestones as $targetMilestone)
                                    <option value="{{ $targetMilestone->id }}">{{ $targetMilestone->title }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button class="btn btn-sm btn-muted" type="submit">Delete Milestone</button>
                    </form>
                </section>
            </aside>

            <main class="crm-workspace-main project-workspace-main">
                <section class="crm-tab-content">
                    <div class="crm-content-heading">
                        <div><h2>Task Progress</h2><p>Tasks attached to this milestone and their delivery state.</p></div>
                        <a href="{{ route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']) }}" class="btn btn-sm lead-banner-cta">Add Task</a>
                    </div>

                    @if ($milestone->description)
                        <div class="crm-notes-content project-milestone-description">{{ $milestone->description }}</div>
                    @endif

                    <div class="project-task-list milestone-task-list">
                        @forelse ($milestone->tasks as $task)
                            <article id="task-{{ $task->id }}" class="project-task-card">
                                <div class="project-task-main">
                                    <strong>{{ $task->title }}</strong>
                                    <p>{{ $task->description ?: 'No description' }}</p>
                                </div>
                                <div class="project-task-meta">
                                    <span class="status-badge priority-{{ $task->priority }}">{{ $taskPriorityOptions[$task->priority] ?? str($task->priority)->headline() }}</span>
                                    <span class="status-badge status-{{ str_replace('_', '-', $task->status) }}">{{ $taskStatusOptions[$task->status] ?? str($task->status)->headline() }}</span>
                                </div>
                                <div class="project-task-people">
                                    <span>{{ $task->assignee?->name ?: 'Unassigned' }}</span>
                                    <small>Due {{ $task->due_date?->format('d M Y') ?: '-' }}</small>
                                </div>
                            </article>
                        @empty
                            <div class="crm-workspace-empty project-empty-panel">
                                <span>@include('admin.partials.sidebar-icon', ['icon' => 'kanban'])</span>
                                <strong>No tasks linked</strong>
                                <p>Assign tasks to this milestone from the project task form.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </main>
        </div>
    </section>
@endsection
