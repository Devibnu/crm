@extends('admin.layouts.app')

@section('title', 'Project Management - Krakatau CRM')

@section('content')
    <section class="lead-list-page project-list-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">Project workspace</span>
                <h1>Project Management</h1>
                <p>Monitor delivery project dari Deal Won, customer, opportunity, dan quotation dalam satu workspace.</p>
            </div>
            <a href="{{ route('admin.projects.create') }}" class="btn lead-banner-cta">+ Add Project</a>
        </header>

        <div class="lead-kpi-strip project-kpi-strip">
            <div><span>Total Projects</span><strong>{{ $summary['total'] }}</strong></div>
            <div><span>Active</span><strong>{{ $summary['active'] }}</strong></div>
            <div><span>Completed</span><strong>{{ $summary['completed'] }}</strong></div>
            <div><span>Average Progress</span><strong>{{ $summary['average_progress'] }}%</strong></div>
        </div>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <section class="lead-list-workspace">
            <div class="lead-smart-filters">
                <div class="lead-filter-chips">
                    <a href="{{ route('admin.projects.index', array_filter(['q' => $search])) }}" class="{{ $selectedStatus === '' ? 'active' : '' }}">All statuses</a>
                    @foreach ($statusOptions as $status)
                        <a href="{{ route('admin.projects.index', array_filter(['q' => $search, 'status' => $status])) }}" class="{{ $selectedStatus === $status ? 'active' : '' }}">
                            {{ str($status)->headline() }}
                        </a>
                    @endforeach
                </div>

                <form method="GET" action="{{ route('admin.projects.index') }}" class="lead-list-toolbar project-list-toolbar">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search project, customer, quotation">
                    <select name="status" aria-label="Filter status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ str($status)->headline() }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-sm btn-primary">Search</button>
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
            </div>

            <div class="customer-table-wrap lead-table-wrap">
                <table class="customer-table lead-modern-table project-modern-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Customer</th>
                            <th>Opportunity</th>
                            <th>Quotation</th>
                            <th>Budget</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr>
                                <td>
                                    <div class="lead-primary-cell">
                                        <span class="lead-avatar">{{ strtoupper(str($project->title)->substr(0, 2)) }}</span>
                                        <div>
                                            <a href="{{ route('admin.projects.show', $project) }}" class="lead-name-link">{{ $project->title }}</a>
                                            <small>{{ $project->project_number }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $project->customer?->name ?: '-' }}</td>
                                <td>{{ $project->opportunity?->title ?: '-' }}</td>
                                <td>{{ $project->quotation?->quote_number ?: '-' }}</td>
                                <td class="sales-amount">Rp {{ number_format((float) $project->budget, 2, ',', '.') }}</td>
                                <td>
                                    <div class="project-progress-cell">
                                        <div class="project-progress-track">
                                            <span style="width: {{ max(0, min(100, (int) $project->progress)) }}%"></span>
                                        </div>
                                        <small>{{ $project->progress }}%</small>
                                    </div>
                                </td>
                                <td><span class="status-badge status-{{ str_replace('_', '-', $project->status) }}">{{ str($project->status)->headline() }}</span></td>
                                <td>{{ $project->created_at?->format('d M Y') ?: '-' }}</td>
                                <td>
                                    <details class="lead-row-menu">
                                        <summary aria-label="Project actions">...</summary>
                                        <div>
                                            <a href="{{ route('admin.projects.show', $project) }}">View</a>
                                            <a href="{{ route('admin.projects.edit', $project) }}">Edit</a>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">
                                    <div class="lead-empty-state">
                                        <span>+</span>
                                        <strong>Belum ada project</strong>
                                        <p>Project akan dibuat dari quotation yang sudah Deal Won.</p>
                                        <a href="{{ route('admin.projects.create') }}" class="btn btn-sm btn-primary">Add Project</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($projects->hasPages())
                <div class="customer-pagination lead-pagination">{{ $projects->links() }}</div>
            @endif
        </section>
    </section>
@endsection
