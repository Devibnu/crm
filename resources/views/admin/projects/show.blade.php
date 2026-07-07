@extends('admin.layouts.app')

@section('title', $project->title.' - Project - Krakatau CRM')

@section('content')
    @php
        $tabs = [
            'overview' => 'Overview',
            'members' => 'Members',
            'milestones' => 'Milestones',
            'timeline' => 'Timeline',
            'tasks' => 'Tasks',
            'kanban' => 'Kanban',
            'files' => 'Files',
            'notes' => 'Notes',
            'activity' => 'Activity',
        ];
        $kanbanColumns = [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'done' => 'Done',
        ];
        $dealStatus = $project->quotation?->status === 'accepted' || $project->opportunity?->status === 'won' ? 'Won' : 'Pending';
        $completedMilestones = $project->milestones->where('status', 'completed')->count();
        $totalMilestones = $project->milestones->count();
        $taskStatusFlow = [
            'todo' => 'in_progress',
            'in_progress' => 'review',
            'review' => 'done',
        ];
        $totalTasks = $project->tasks->count();
        $doneTasks = $project->tasks->where('status', 'done')->count();
        $inProgressTasks = $project->tasks->where('status', 'in_progress')->count();
        $overdueTasks = $project->tasks
            ->filter(fn ($task) => $task->due_date && $task->due_date->lt(now()->startOfDay()) && $task->status !== 'done')
            ->count();
        $taskCompletion = $totalTasks === 0 ? 0 : (int) round(($doneTasks / $totalTasks) * 100);
        $latestMilestone = $project->milestones->sortByDesc(fn ($milestone) => $milestone->updated_at ?? $milestone->created_at)->first();
        $recentActivities = $project->activityLogs->take(5);
        $remainingDays = $project->due_date
            ? now()->startOfDay()->diffInDays($project->due_date->startOfDay(), false)
            : null;
        $remainingLabel = $remainingDays === null
            ? '-'
            : ($remainingDays < 0 ? abs($remainingDays).' days overdue' : $remainingDays.' days');
        $subtitle = collect([
            $project->customer?->name,
            $project->opportunity?->title,
            $project->quotation?->quote_number,
        ])->filter()->implode(' / ') ?: $project->project_number;
    @endphp

    <section class="crm-record-page project-record-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-detail-banner project-detail-banner">
            <div class="crm-record-heading">
                <span class="crm-record-kicker">PROJECT WORKSPACE</span>
                <div class="crm-record-title-row">
                    <h1>{{ $project->title }}</h1>
                    <span class="status-badge status-{{ str_replace('_', '-', $project->status) }}">{{ str($project->status)->headline() }}</span>
                </div>
                <p>{{ $subtitle }}</p>
            </div>
            <div class="lead-detail-action-stack">
                <div class="crm-record-actions">
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-sm lead-banner-secondary">Back</a>
                    <a href="{{ route('admin.projects.edit', $project) }}" class="btn btn-sm lead-banner-cta">Edit Project</a>
                </div>
            </div>
        </header>

        <div class="crm-metadata-row lead-detail-metadata project-summary-row">
            <div><span>Budget</span><strong>Rp {{ number_format((float) $project->budget, 2, ',', '.') }}</strong></div>
            <div><span>Progress</span><strong>{{ $project->progress }}%</strong></div>
            <div><span>Project Manager</span><strong>{{ $project->projectManager?->name ?: '-' }}</strong></div>
            <div><span>Start Date</span><strong>{{ $project->start_date?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Due Date</span><strong>{{ $project->due_date?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Remaining Days</span><strong>{{ $remainingLabel }}</strong></div>
        </div>

        <div @class([
            'crm-record-workspace',
            'lead-workspace',
            'project-detail-workspace',
            'project-kanban-workspace' => $activeTab === 'kanban',
        ])>
            <aside class="crm-workspace-sidebar crm-details-sidebar">
                <section class="crm-workspace-section">
                    <h2>Project Details</h2>
                    <dl class="crm-property-list">
                        <div><dt>Project Name</dt><dd>{{ $project->title }}</dd></div>
                        <div><dt>Project Number</dt><dd>{{ $project->project_number }}</dd></div>
                        <div><dt>Status</dt><dd><span class="status-badge status-{{ str_replace('_', '-', $project->status) }}">{{ str($project->status)->headline() }}</span></dd></div>
                        <div><dt>Progress</dt><dd>{{ $project->progress }}%</dd></div>
                        <div><dt>Budget</dt><dd>Rp {{ number_format((float) $project->budget, 2, ',', '.') }}</dd></div>
                        <div><dt>Project Manager</dt><dd>{{ $project->projectManager?->name ?: '-' }}</dd></div>
                        <div><dt>Created By</dt><dd>{{ $project->creator?->name ?: '-' }}</dd></div>
                    </dl>
                </section>

                <section class="crm-workspace-section">
                    <h2>Delivery Health</h2>
                    <div class="project-progress-card compact">
                        <div>
                            <span>Milestone Completion</span>
                            <strong>{{ $completedMilestones }} / {{ $totalMilestones }}</strong>
                        </div>
                        <div class="project-progress-track"><span style="width: {{ max(0, min(100, (int) $project->progress)) }}%"></span></div>
                        <small>{{ $project->progress }}% overall progress</small>
                    </div>
                </section>
            </aside>

            <main class="crm-workspace-main project-workspace-main">
                <nav class="crm-record-tabs project-record-tabs" aria-label="Project detail sections">
                    @foreach ($tabs as $tabKey => $tabLabel)
                        <a href="{{ route('admin.projects.show', ['project' => $project, 'tab' => $tabKey]) }}" @class(['active' => $activeTab === $tabKey])>{{ $tabLabel }}</a>
                    @endforeach
                </nav>

                @if ($activeTab === 'overview')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading">
                            <div><h2>Overview</h2><p>Delivery summary, latest progress, and project context.</p></div>
                        </div>

                        <div class="project-overview-grid">
                            <article class="project-overview-card">
                                <span>Progress</span>
                                <strong>{{ $project->progress }}%</strong>
                                <div class="project-progress-track"><span style="width: {{ max(0, min(100, (int) $project->progress)) }}%"></span></div>
                                <small>{{ $completedMilestones }} of {{ $totalMilestones }} milestones completed</small>
                            </article>
                            <article class="project-overview-card">
                                <span>Budget Summary</span>
                                <strong>Rp {{ number_format((float) $project->budget, 0, ',', '.') }}</strong>
                                <small>Linked quotation: {{ $project->quotation?->quote_number ?: '-' }}</small>
                            </article>
                            <article class="project-overview-card">
                                <span>Delivery Status</span>
                                <strong>{{ str($project->status)->headline() }}</strong>
                                <small>{{ $remainingLabel === '-' ? 'No due date set' : 'Remaining: '.$remainingLabel }}</small>
                            </article>
                            <article class="project-overview-card">
                                <span>Latest Milestone</span>
                                <strong>{{ $latestMilestone?->title ?: 'No milestone yet' }}</strong>
                                <small>{{ $latestMilestone ? ($milestoneStatusOptions[$latestMilestone->status] ?? str($latestMilestone->status)->headline()) : 'Add milestones to track delivery' }}</small>
                            </article>
                        </div>

                        <div class="crm-content-heading crm-section-divider">
                            <div><h2>Recent Activity</h2><p>Latest automatic events from this project.</p></div>
                            <a href="{{ route('admin.projects.show', ['project' => $project, 'tab' => 'activity']) }}" class="btn btn-sm btn-muted">View All</a>
                        </div>
                        <div class="crm-activity-feed compact">
                            @forelse ($recentActivities as $activity)
                                <article class="crm-activity-entry">
                                    <span class="crm-feed-marker project-event-{{ $activity->event }}"></span>
                                    <div class="crm-feed-body">
                                        <div class="crm-feed-title"><strong>{{ $activity->description }}</strong><span>{{ str($activity->event)->headline() }}</span></div>
                                        <p>{{ $activity->actor?->name ?: 'System' }}</p>
                                        <small>{{ $activity->created_at?->format('d M Y H:i') }}</small>
                                    </div>
                                </article>
                            @empty
                                <div class="crm-workspace-empty project-empty-panel">
                                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span>
                                    <strong>No recent activity</strong>
                                    <p>Automatic project events will appear here.</p>
                                </div>
                            @endforelse
                        </div>

                        <div class="crm-content-heading crm-section-divider">
                            <div><h2>Description</h2><p>Project scope and delivery notes.</p></div>
                        </div>
                        <div class="crm-notes-content">{{ $project->description ?: 'No description available.' }}</div>
                    </section>
                @endif

                @if ($activeTab === 'members')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading">
                            <div><h2>Members</h2><p>Project team and delivery responsibility.</p></div>
                        </div>
                        <form method="POST" action="{{ route('admin.projects.members.store', $project) }}" class="lead-workspace-form project-inline-form">
                            @csrf
                            <div class="customer-form-grid">
                                <label class="field">
                                    <span>User</span>
                                    <select name="user_id" required>
                                        <option value="">Pilih user</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                        @endforeach
                                    </select>
                                    @error('user_id')<small class="error">{{ $message }}</small>@enderror
                                </label>
                                <label class="field">
                                    <span>Role</span>
                                    <select name="role" required>
                                        @foreach ($memberRoles as $role => $label)
                                            <option value="{{ $role }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('role')<small class="error">{{ $message }}</small>@enderror
                                </label>
                            </div>
                            <button class="btn btn-sm lead-banner-cta" type="submit">Add Member</button>
                        </form>

                        <div class="project-member-grid">
                            @forelse ($project->members as $member)
                                <article class="project-member-card">
                                    <span class="lead-avatar">{{ strtoupper(str($member->user?->name ?: '?')->substr(0, 2)) }}</span>
                                    <div>
                                        <strong>{{ $member->user?->name ?: '-' }}</strong>
                                        <small>{{ $memberRoles[$member->role] ?? str($member->role)->headline() }}</small>
                                        <small>Assigned {{ $member->created_at?->format('d M Y') ?: '-' }}</small>
                                    </div>
                                    <form method="POST" action="{{ route('admin.projects.members.destroy', [$project, $member]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-muted" type="submit">Remove</button>
                                    </form>
                                </article>
                            @empty
                                <div class="crm-workspace-empty project-empty-panel">
                                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'user'])</span>
                                    <strong>No members yet</strong>
                                    <p>Add delivery members to assign responsibility.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'milestones')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading">
                            <div><h2>Milestones</h2><p>Progress is calculated from completed milestones.</p></div>
                        </div>
                        <form method="POST" action="{{ route('admin.projects.milestones.store', $project) }}" class="lead-workspace-form project-inline-form">
                            @csrf
                            <div class="customer-form-grid">
                                <label class="field">
                                    <span>Milestone</span>
                                    <input type="text" name="title" placeholder="Requirement, Design, Development..." required>
                                    @error('title')<small class="error">{{ $message }}</small>@enderror
                                </label>
                                <label class="field">
                                    <span>Status</span>
                                    <select name="status" required>
                                        @foreach ($milestoneStatusOptions as $status => $label)
                                            <option value="{{ $status }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('status')<small class="error">{{ $message }}</small>@enderror
                                </label>
                                <label class="field">
                                    <span>Due Date</span>
                                    <input type="date" name="due_date">
                                    @error('due_date')<small class="error">{{ $message }}</small>@enderror
                                </label>
                            </div>
                            <label class="field">
                                <span>Description</span>
                                <textarea name="description" rows="2"></textarea>
                            </label>
                            <button class="btn btn-sm lead-banner-cta" type="submit">Add Milestone</button>
                        </form>

                        <div class="project-timeline-list detail">
                            @forelse ($project->milestones as $milestone)
                                <article class="project-timeline-item">
                                    <span class="project-timeline-dot"></span>
                                    <div>
                                        <time>{{ $milestone->due_date?->format('d M Y') ?: 'No due date' }}</time>
                                        <div class="project-milestone-title">
                                            <strong>{{ $milestone->title }}</strong>
                                            <span class="status-badge status-{{ str_replace('_', '-', $milestone->status) }}">{{ $milestoneStatusOptions[$milestone->status] ?? str($milestone->status)->headline() }}</span>
                                        </div>
                                        <small>Completed: {{ $milestone->completed_at?->format('d M Y H:i') ?: '-' }}</small>
                                        @if ($milestone->description)
                                            <p>{{ $milestone->description }}</p>
                                        @endif
                                        <form method="POST" action="{{ route('admin.projects.milestones.update', [$project, $milestone]) }}" class="project-milestone-update">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="title" value="{{ $milestone->title }}">
                                            <input type="hidden" name="description" value="{{ $milestone->description }}">
                                            <input type="hidden" name="due_date" value="{{ $milestone->due_date?->format('Y-m-d') }}">
                                            <select name="status">
                                                @foreach ($milestoneStatusOptions as $status => $label)
                                                    <option value="{{ $status }}" @selected($milestone->status === $status)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-sm btn-muted" type="submit">Update Status</button>
                                        </form>
                                    </div>
                                </article>
                            @empty
                                <div class="crm-workspace-empty project-empty-panel">
                                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                                    <strong>No milestones yet</strong>
                                    <p>Add milestones to drive project progress.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'timeline' || $activeTab === 'activity')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading">
                            <div>
                                <h2>{{ $activeTab === 'activity' ? 'Activity' : 'Timeline' }}</h2>
                                <p>Project Created, Member Added, Milestone Created, Milestone Completed, and Status Changed events.</p>
                            </div>
                        </div>
                        <div class="crm-activity-feed">
                            @forelse ($project->activityLogs as $activity)
                                <article class="crm-activity-entry">
                                    <span class="crm-feed-marker project-event-{{ $activity->event }}"></span>
                                    <div class="crm-feed-body">
                                        <div class="crm-feed-title"><strong>{{ $activity->description }}</strong><span>{{ str($activity->event)->headline() }}</span></div>
                                        <p>{{ $activity->actor?->name ?: 'System' }}</p>
                                        <small>{{ $activity->created_at?->format('d M Y H:i') }}</small>
                                    </div>
                                </article>
                            @empty
                                <div class="crm-workspace-empty project-empty-panel">
                                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span>
                                    <strong>No activity yet</strong>
                                    <p>Automatic project activity will appear after delivery updates.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'files')
                    <section class="crm-tab-content">
                        <div class="project-placeholder-panel">
                            <span>@include('admin.partials.sidebar-icon', ['icon' => 'book'])</span>
                            <h2>Files</h2>
                            <p>File management akan dikerjakan pada sprint berikutnya.</p>
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'notes')
                    <section class="crm-tab-content">
                        <div class="project-placeholder-panel">
                            <span>@include('admin.partials.sidebar-icon', ['icon' => 'mail'])</span>
                            <h2>Notes</h2>
                            <p>Project notes akan dikerjakan pada sprint berikutnya.</p>
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'tasks')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading">
                            <div><h2>Tasks</h2><p>Delivery task MVP untuk memecah pekerjaan project.</p></div>
                        </div>

                        <div class="project-task-summary">
                            <div><span>Total Task</span><strong>{{ $totalTasks }}</strong></div>
                            <div><span>Done</span><strong>{{ $doneTasks }}</strong></div>
                            <div><span>In Progress</span><strong>{{ $inProgressTasks }}</strong></div>
                            <div><span>Overdue</span><strong>{{ $overdueTasks }}</strong></div>
                            <div><span>Completion</span><strong>{{ $taskCompletion }}%</strong></div>
                        </div>

                        <form method="POST" action="{{ route('admin.projects.tasks.store', $project) }}" class="lead-workspace-form project-inline-form project-task-form">
                            @csrf
                            <div class="customer-form-grid">
                                <label class="field">
                                    <span>Task Title</span>
                                    <input type="text" name="title" value="{{ old('title') }}" placeholder="Prepare delivery plan" required>
                                    @error('title')<small class="error">{{ $message }}</small>@enderror
                                </label>
                                <label class="field">
                                    <span>Milestone</span>
                                    <select name="milestone_id">
                                        <option value="">No milestone</option>
                                        @foreach ($project->milestones as $milestone)
                                            <option value="{{ $milestone->id }}" @selected((string) old('milestone_id') === (string) $milestone->id)>{{ $milestone->title }}</option>
                                        @endforeach
                                    </select>
                                    @error('milestone_id')<small class="error">{{ $message }}</small>@enderror
                                </label>
                                <label class="field">
                                    <span>Assignee</span>
                                    <select name="assignee_id">
                                        <option value="">Unassigned</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}" @selected((string) old('assignee_id') === (string) $user->id)>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('assignee_id')<small class="error">{{ $message }}</small>@enderror
                                </label>
                                <label class="field">
                                    <span>Priority</span>
                                    <select name="priority">
                                        @foreach ($taskPriorityOptions as $priority => $label)
                                            <option value="{{ $priority }}" @selected(old('priority', 'medium') === $priority)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @error('priority')<small class="error">{{ $message }}</small>@enderror
                                </label>
                                <label class="field">
                                    <span>Start Date</span>
                                    <input type="date" name="start_date" value="{{ old('start_date') }}">
                                    @error('start_date')<small class="error">{{ $message }}</small>@enderror
                                </label>
                                <label class="field">
                                    <span>Due Date</span>
                                    <input type="date" name="due_date" value="{{ old('due_date') }}">
                                    @error('due_date')<small class="error">{{ $message }}</small>@enderror
                                </label>
                            </div>
                            <label class="field">
                                <span>Description</span>
                                <textarea name="description" rows="2" placeholder="Short task detail">{{ old('description') }}</textarea>
                                @error('description')<small class="error">{{ $message }}</small>@enderror
                            </label>
                            <button class="btn btn-sm lead-banner-cta" type="submit">Add Task</button>
                        </form>

                        @if ($project->tasks->isEmpty())
                            <div class="crm-workspace-empty project-empty-panel">
                                <span>@include('admin.partials.sidebar-icon', ['icon' => 'kanban'])</span>
                                <strong>Belum ada Task</strong>
                                <p>Mulai pecah pekerjaan project menjadi task delivery.</p>
                            </div>
                        @else
                            <div class="project-task-list">
                                @foreach ($project->tasks as $task)
                                    <article id="task-{{ $task->id }}" class="project-task-card">
                                        @php
                                            $checklistTotal = $task->totalChecklistCount();
                                            $checklistCompleted = $task->completedChecklistCount();
                                            $checklistPercent = $task->checklistCompletionPercentage();
                                        @endphp
                                        <div class="project-task-main">
                                            <strong>{{ $task->title }}</strong>
                                            <p>{{ $task->description ?: 'No description' }}</p>
                                            <small>Milestone: {{ $task->milestone?->title ?: '-' }}</small>
                                        </div>
                                        <div class="project-task-meta">
                                            <span class="status-badge priority-{{ $task->priority }}">{{ $taskPriorityOptions[$task->priority] ?? str($task->priority)->headline() }}</span>
                                            <span class="status-badge status-{{ str_replace('_', '-', $task->status) }}">{{ $taskStatusOptions[$task->status] ?? str($task->status)->headline() }}</span>
                                        </div>
                                        <div class="project-task-people">
                                            <span>{{ $task->assignee?->name ?: 'Unassigned' }}</span>
                                            <small>Due {{ $task->due_date?->format('d M Y') ?: '-' }}</small>
                                        </div>
                                        <div class="project-task-action">
                                            @if (isset($taskStatusFlow[$task->status]))
                                                <form method="POST" action="{{ route('admin.projects.tasks.status', [$project, $task]) }}">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="status" value="{{ $taskStatusFlow[$task->status] }}">
                                                    <button class="btn btn-sm btn-muted" type="submit">
                                                        Move to {{ $taskStatusOptions[$taskStatusFlow[$task->status]] }}
                                                    </button>
                                                </form>
                                            @else
                                                <span class="status-badge status-completed">Completed</span>
                                            @endif
                                        </div>
                                        <div class="project-task-checklist">
                                            <div class="project-checklist-head">
                                                <div>
                                                    <strong>Checklist</strong>
                                                    <span>{{ $checklistCompleted }}/{{ $checklistTotal }} completed · {{ $checklistPercent }}%</span>
                                                </div>
                                                <div class="project-checklist-progress"><span style="width: {{ $checklistPercent }}%"></span></div>
                                            </div>

                                            <form method="POST" action="{{ route('admin.projects.tasks.checklists.store', [$project, $task]) }}" class="project-checklist-form">
                                                @csrf
                                                <input type="text" name="title" placeholder="Add checklist item" required>
                                                <button class="btn btn-sm btn-muted" type="submit">Add</button>
                                            </form>

                                            @if ($task->checklists->isEmpty())
                                                <div class="project-checklist-empty">Belum ada checklist untuk task ini.</div>
                                            @else
                                                <div class="project-checklist-list">
                                                    @foreach ($task->checklists as $checklist)
                                                        <form method="POST" action="{{ route('admin.projects.tasks.checklists.toggle', [$project, $task, $checklist]) }}" @class(['project-checklist-item', 'completed' => $checklist->is_completed])>
                                                            @csrf
                                                            @method('PUT')
                                                            <button type="submit" class="project-checklist-toggle" aria-label="{{ $checklist->is_completed ? 'Reopen checklist' : 'Complete checklist' }}">
                                                                @if ($checklist->is_completed)
                                                                    &check;
                                                                @endif
                                                            </button>
                                                            <span>{{ $checklist->title }}</span>
                                                            <small>{{ $checklist->is_completed ? 'Completed '.$checklist->completed_at?->format('d M Y') : 'Open' }}</small>
                                                        </form>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif

                @if ($activeTab === 'kanban')
                    <section class="crm-tab-content project-kanban-content">
                        <div class="crm-content-heading">
                            <div><h2>Kanban</h2><p>Visualisasi task delivery berdasarkan status pekerjaan.</p></div>
                            <a href="{{ route('admin.projects.show', ['project' => $project, 'tab' => 'tasks']) }}" class="btn btn-sm btn-muted">Add Task</a>
                        </div>

                        <div class="project-task-summary">
                            <div><span>Total Task</span><strong>{{ $totalTasks }}</strong></div>
                            <div><span>Done</span><strong>{{ $doneTasks }}</strong></div>
                            <div><span>In Progress</span><strong>{{ $inProgressTasks }}</strong></div>
                            <div><span>Overdue</span><strong>{{ $overdueTasks }}</strong></div>
                            <div><span>Completion</span><strong>{{ $taskCompletion }}%</strong></div>
                        </div>

                        <div class="project-kanban-board" aria-label="Project task kanban board">
                            @foreach ($kanbanColumns as $status => $label)
                                @php
                                    $columnTasks = $project->tasks->where('status', $status);
                                @endphp
                                <section class="project-kanban-column" data-kanban-status="{{ $status }}">
                                    <header class="project-kanban-column-header">
                                        <div>
                                            <strong>{{ $label }}</strong>
                                            <span>{{ $columnTasks->count() }} task</span>
                                        </div>
                                        <span class="status-badge status-{{ str_replace('_', '-', $status) }}">{{ $label }}</span>
                                    </header>

                                    <div class="project-kanban-stack">
                                        @forelse ($columnTasks as $task)
                                            @php
                                                $isOverdue = $task->due_date && $task->due_date->lt(now()->startOfDay()) && $task->status !== 'done';
                                                $nextStatus = $taskStatusFlow[$task->status] ?? null;
                                                $checklistTotal = $task->totalChecklistCount();
                                                $checklistCompleted = $task->completedChecklistCount();
                                                $checklistPercent = $task->checklistCompletionPercentage();
                                                $taskKanbanStatusLabel = $task->status === 'done'
                                                    ? 'Completed'
                                                    : ($kanbanColumns[$task->status] ?? ($taskStatusOptions[$task->status] ?? str($task->status)->headline()));
                                            @endphp
                                            <article class="project-kanban-card" data-task-status="{{ $task->status }}">
                                                <div class="project-kanban-card-head">
                                                    <strong>{{ $task->title }}</strong>
                                                    <span class="status-badge priority-{{ $task->priority }}">{{ $taskPriorityOptions[$task->priority] ?? str($task->priority)->headline() }}</span>
                                                </div>
                                                <div class="project-kanban-meta">
                                                    <span class="status-badge status-{{ str_replace('_', '-', $task->status) }}">{{ $taskKanbanStatusLabel }}</span>
                                                    @if ($isOverdue)
                                                        <span class="project-overdue-pill">Overdue</span>
                                                    @endif
                                                </div>
                                                <dl class="project-kanban-details">
                                                    <div><dt>Assignee</dt><dd><span class="lead-avatar mini">{{ strtoupper(str($task->assignee?->name ?: '?')->substr(0, 2)) }}</span>{{ $task->assignee?->name ?: 'Unassigned' }}</dd></div>
                                                    <div><dt>Due Date</dt><dd>{{ $task->due_date?->format('d M Y') ?: '-' }}</dd></div>
                                                    <div><dt>Milestone</dt><dd>{{ $task->milestone?->title ?: '-' }}</dd></div>
                                                </dl>
                                                @if ($checklistTotal > 0)
                                                    <div class="project-kanban-checklist">
                                                        <div><span>Checklist</span><strong>{{ $checklistCompleted }}/{{ $checklistTotal }}</strong></div>
                                                        <div class="project-checklist-progress compact"><span style="width: {{ $checklistPercent }}%"></span></div>
                                                    </div>
                                                @endif
                                                <div class="project-kanban-actions">
                                                    @if ($nextStatus)
                                                        <form method="POST" action="{{ route('admin.projects.tasks.status', [$project, $task]) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status" value="{{ $nextStatus }}">
                                                            <input type="hidden" name="redirect_tab" value="kanban">
                                                            <button class="btn btn-sm btn-muted" type="submit">Move to {{ $kanbanColumns[$nextStatus] }}</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('admin.projects.tasks.status', [$project, $task]) }}">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="hidden" name="status" value="todo">
                                                            <input type="hidden" name="redirect_tab" value="kanban">
                                                            <button class="btn btn-sm btn-muted" type="submit">Reopen to Todo</button>
                                                        </form>
                                                    @endif
                                                </div>
                                            </article>
                                        @empty
                                            <div class="project-kanban-empty">
                                                <span>@include('admin.partials.sidebar-icon', ['icon' => 'kanban'])</span>
                                                <strong>No task</strong>
                                                <p>Task dengan status {{ $label }} akan muncul di kolom ini.</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </section>
                @endif
            </main>

            <aside class="crm-workspace-sidebar crm-related-sidebar">
                <section class="crm-workspace-section">
                    <h2>Related Records</h2>
                    <div class="crm-related-list">
                        <div><span>Customer</span>@if ($project->customer)<a href="{{ route('admin.customers.show', $project->customer) }}">{{ $project->customer->name }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Lead</span>@if ($project->lead)<a href="{{ route('admin.sales.leads.show', $project->lead) }}">{{ $project->lead->name }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Opportunity</span>@if ($project->opportunity)<a href="{{ route('admin.sales.opportunities.show', $project->opportunity) }}">{{ $project->opportunity->title }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Quotation</span>@if ($project->quotation)<a href="{{ route('admin.sales.deals.show', $project->quotation) }}">{{ $project->quotation->quote_number }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Deal Status</span><strong>{{ $dealStatus }}</strong></div>
                        <div><span>Project Number</span><strong>{{ $project->project_number }}</strong></div>
                        <div><span>Created Date</span><strong>{{ $project->created_at?->format('d M Y H:i') ?: '-' }}</strong></div>
                        <div><span>Updated Date</span><strong>{{ $project->updated_at?->format('d M Y H:i') ?: '-' }}</strong></div>
                    </div>
                </section>

                <section class="crm-workspace-section">
                    <h2>Quick Stats</h2>
                    <div class="crm-score-list project-quick-stats">
                        <div><span>Members</span><strong>{{ $project->members->count() }}</strong></div>
                        <div><span>Milestones</span><strong>{{ $totalMilestones }}</strong></div>
                        <div><span>Tasks</span><strong>{{ $totalTasks }}</strong></div>
                        <div><span>Activities</span><strong>{{ $project->activityLogs->count() }}</strong></div>
                    </div>
                </section>

                <section class="crm-workspace-section">
                    <h2>Quick Actions</h2>
                    <div class="project-quick-actions">
                        @if ($project->customer)<a href="{{ route('admin.customers.show', $project->customer) }}" class="btn btn-sm btn-muted">Open Customer</a>@endif
                        @if ($project->lead)<a href="{{ route('admin.sales.leads.show', $project->lead) }}" class="btn btn-sm btn-muted">Open Lead</a>@endif
                        @if ($project->opportunity)<a href="{{ route('admin.sales.opportunities.show', $project->opportunity) }}" class="btn btn-sm btn-muted">Open Opportunity</a>@endif
                        @if ($project->quotation)<a href="{{ route('admin.sales.deals.show', $project->quotation) }}" class="btn btn-sm btn-muted">Open Quotation</a>@endif
                    </div>
                </section>
            </aside>
        </div>
    </section>
@endsection
