@extends('admin.layouts.app')

@section('title', 'Project Timesheets - Krakatau CRM')

@section('content')
    @php
        $query = fn (array $changes = []) => array_filter(array_merge($filters, $changes), fn ($value) => $value !== '' && $value !== null);
    @endphp

    <section class="lead-list-page project-timesheet-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">PROJECT MANAGEMENT</span>
                <h1>Timesheets</h1>
                <p>Track work logs, billable hours, approvals, and project effort without changing project progress rules.</p>
            </div>
            <div class="project-timesheet-hero-actions">
                <a href="{{ route('admin.projects.timesheets.create') }}" class="btn lead-banner-cta" aria-label="Add timesheet">Add Timesheet</a>
                <a href="{{ route('admin.projects.index') }}" class="btn lead-banner-secondary" aria-label="Open projects">Open Projects</a>
            </div>
        </header>

        <div class="project-timesheet-kpi-grid" aria-label="Timesheet summary">
            @foreach ([
                ['icon' => 'timer', 'label' => 'Today Hours', 'value' => $summary['today_hours'], 'helper' => 'Daily logs', 'meta' => 'Entries today', 'tone' => 'blue'],
                ['icon' => 'calendar', 'label' => 'This Week', 'value' => $summary['week_hours'], 'helper' => 'Weekly effort', 'meta' => 'Logs this week', 'tone' => 'green'],
                ['icon' => 'analysis', 'label' => 'This Month', 'value' => $summary['month_hours'], 'helper' => 'Monthly effort', 'meta' => 'Active period', 'tone' => 'purple'],
                ['icon' => 'deal', 'label' => 'Billable Hours', 'value' => $summary['billable_hours'], 'helper' => 'Revenue eligible', 'meta' => 'Billable logs', 'tone' => 'amber'],
                ['icon' => 'case', 'label' => 'Approved', 'value' => number_format($summary['approved']), 'helper' => 'Accepted logs', 'meta' => 'Ready for reporting', 'tone' => 'green'],
                ['icon' => 'activity', 'label' => 'Pending Approval', 'value' => number_format($summary['pending_approval']), 'helper' => 'Submitted logs', 'meta' => 'Needs review', 'tone' => 'red'],
            ] as $kpi)
                <article class="project-timesheet-kpi tone-{{ $kpi['tone'] }}">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => $kpi['icon']])</span>
                    <div><strong>{{ $kpi['value'] }}</strong><small>{{ $kpi['label'] }}</small><em>{{ $kpi['helper'] }}</em><b>{{ $kpi['meta'] }}</b></div>
                </article>
            @endforeach
        </div>

        <section class="lead-list-workspace project-timesheet-workspace">
            <div class="lead-smart-filters project-timesheet-filters">
                <nav class="lead-filter-chips" aria-label="Timesheet status filters">
                    <a href="{{ route('admin.projects.timesheets.index', $query(['status' => ''])) }}" @class(['active' => $filters['status'] === ''])>All</a>
                    @foreach ($statusOptions as $status => $label)
                        <a href="{{ route('admin.projects.timesheets.index', $query(['status' => $status])) }}" @class(['active' => $filters['status'] === $status])>{{ $label }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('admin.projects.timesheets.index') }}" class="lead-list-toolbar project-timesheet-toolbar">
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Search timesheets..." aria-label="Search timesheets">
                    <select name="employee_id" aria-label="Filter employee">
                        <option value="">All employees</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected((string) $filters['employee_id'] === (string) $employee->id)>{{ $employee->name }}</option>
                        @endforeach
                    </select>
                    <select name="project_id" aria-label="Filter project">
                        <option value="">All projects</option>
                        @foreach ($projects as $project)
                            <option value="{{ $project->id }}" @selected((string) $filters['project_id'] === (string) $project->id)>{{ $project->project_number }} - {{ $project->title }}</option>
                        @endforeach
                    </select>
                    <select name="milestone_id" aria-label="Filter milestone">
                        <option value="">All milestones</option>
                        @foreach ($milestones as $milestone)
                            <option value="{{ $milestone->id }}" @selected((string) $filters['milestone_id'] === (string) $milestone->id)>{{ $milestone->title }}</option>
                        @endforeach
                    </select>
                    <select name="task_id" aria-label="Filter task">
                        <option value="">All tasks</option>
                        @foreach ($tasks as $task)
                            <option value="{{ $task->id }}" @selected((string) $filters['task_id'] === (string) $task->id)>{{ $task->title }}</option>
                        @endforeach
                    </select>
                    <select name="billable" aria-label="Filter billable">
                        <option value="">All billable</option>
                        <option value="1" @selected($filters['billable'] === '1')>Billable</option>
                        <option value="0" @selected($filters['billable'] === '0')>Non billable</option>
                    </select>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}" aria-label="Date from">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}" aria-label="Date to">
                    <button type="submit" class="btn btn-sm btn-primary" aria-label="Apply timesheet filters">Apply</button>
                    <a href="{{ route('admin.projects.timesheets.index') }}" class="btn btn-sm btn-muted" aria-label="Reset timesheet filters">Reset</a>
                    <details class="project-timesheet-export">
                        <summary aria-label="Open export options">Export</summary>
                        <div>
                            <a href="{{ route('admin.projects.timesheets.export.excel', $query()) }}" aria-label="Export Excel">Excel</a>
                            <a href="{{ route('admin.projects.timesheets.export.pdf', $query()) }}" aria-label="Export PDF">PDF</a>
                        </div>
                    </details>
                </form>
            </div>

            @if ($timesheets->isNotEmpty())
                <div class="customer-table-wrap lead-table-wrap">
                    <table class="customer-table lead-modern-table project-timesheet-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th>Project</th>
                                <th>Milestone</th>
                                <th>Task</th>
                                <th>Duration</th>
                                <th>Billable</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($timesheets as $timesheet)
                                <tr>
                                    <td><strong>{{ $timesheet->work_date?->format('d M Y') }}</strong><small>{{ substr((string) $timesheet->start_time, 0, 5) }} - {{ substr((string) $timesheet->end_time, 0, 5) }}</small></td>
                                    <td><strong>{{ $timesheet->user?->name }}</strong><small>{{ $timesheet->user?->email }}</small></td>
                                    <td><a href="{{ route('admin.projects.show', $timesheet->project) }}">{{ $timesheet->project?->title }}</a><small>{{ $timesheet->project?->project_number }}</small></td>
                                    <td>{{ $timesheet->milestone?->title ?: '-' }}</td>
                                    <td>{{ $timesheet->task?->title ?: '-' }}</td>
                                    <td><span class="project-timesheet-duration">{{ $timesheet->durationLabel() }}</span></td>
                                    <td><span @class(['project-timesheet-pill', 'is-billable' => $timesheet->billable])>{{ $timesheet->billable ? 'Billable' : 'Non billable' }}</span></td>
                                    <td><span class="status-badge status-{{ str_replace('_', '-', $timesheet->status) }}">{{ $timesheet->statusLabel() }}</span></td>
                                    <td>
                                        <div class="project-timesheet-actions">
                                            <a href="{{ route('admin.projects.timesheets.show', $timesheet) }}" class="btn btn-sm lead-banner-cta">View</a>
                                            <a href="{{ route('admin.projects.timesheets.edit', $timesheet) }}" class="btn btn-sm btn-muted">Edit</a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="project-timesheet-empty-layout">
                    <div class="lead-empty-state project-empty-state project-timesheet-empty">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'timer'])</span>
                        <strong>Belum ada Timesheet</strong>
                        <p>Tambahkan log kerja untuk memantau effort project, billable hours, approval, dan aktivitas delivery tim.</p>
                        <a href="{{ route('admin.projects.timesheets.create') }}" class="btn btn-sm btn-primary">Add Timesheet</a>
                    </div>
                    <aside class="project-timesheet-quick-summary" aria-label="Timesheet quick summary">
                        <div>
                            <span>@include('admin.partials.sidebar-icon', ['icon' => 'pipeline'])</span>
                            <strong>{{ number_format($projects->count()) }}</strong>
                            <small>Projects ready for time logging</small>
                        </div>
                        <div>
                            <span>@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span>
                            <strong>{{ number_format($tasks->count()) }}</strong>
                            <small>Tasks can receive logged hours</small>
                        </div>
                        <div>
                            <span>@include('admin.partials.sidebar-icon', ['icon' => 'user'])</span>
                            <strong>{{ number_format($employees->count()) }}</strong>
                            <small>Employees available for assignment</small>
                        </div>
                    </aside>
                </div>
            @endif

            <section class="project-timesheet-calendar" aria-label="Timesheet calendar view">
                <div class="crm-content-heading"><div><span class="crm-record-kicker">WORKLOAD CALENDAR</span><h2>Calendar View</h2><p>Total logged hours per work date for the selected filter.</p></div></div>
                <div class="project-timesheet-calendar-grid">
                    @foreach ($calendarDays as $day)
                        <article @class(['has-hours' => $day['minutes'] > 0])>
                            <span>{{ $day['date']->format('j') }}</span>
                            <strong>{{ $day['minutes'] > 0 ? $day['label'] : '-' }}</strong>
                        </article>
                    @endforeach
                </div>
            </section>

            @if ($timesheets->hasPages())
                <div class="customer-pagination lead-pagination">{{ $timesheets->links() }}</div>
            @endif
        </section>
    </section>
@endsection
