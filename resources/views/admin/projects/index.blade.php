@extends('admin.layouts.app')

@section('title', 'Project Management - Krakatau CRM')

@section('content')
    @php
        $statusChips = [
            '' => 'Semua',
            'planning' => 'Planning',
            'active' => 'Active',
            'on_hold' => 'On Hold',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'maintenance' => 'Maintenance',
        ];
        $chipQuery = fn (array $changes) => array_filter(array_merge([
            'q' => $search,
            'status' => $selectedStatus,
        ], $changes), fn ($value) => $value !== '' && $value !== null);
    @endphp

    <section class="lead-list-page project-list-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">Project workspace</span>
                <h1>Project Management</h1>
                <p>Monitor delivery project dari Deal Won, customer, opportunity, dan quotation dalam satu workspace.</p>
            </div>
            <a href="{{ route('admin.projects.create') }}" class="btn lead-banner-cta">+ Add Project</a>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip project-kpi-strip" aria-label="Project summary">
            <div>
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'case'])</span>
                <span>Total Project<small>Seluruh delivery project</small></span>
                <strong>{{ number_format($summary['total']) }}</strong>
            </div>
            <div>
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span>
                <span>Active<small>Project sedang berjalan</small></span>
                <strong>{{ number_format($summary['active']) }}</strong>
            </div>
            <div>
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'star'])</span>
                <span>Completed<small>Project selesai</small></span>
                <strong>{{ number_format($summary['completed']) }}</strong>
            </div>
            <div>
                <span class="project-kpi-icon">@include('admin.partials.sidebar-icon', ['icon' => 'analysis'])</span>
                <span>Average Progress<small>Rata-rata progres</small></span>
                <strong>{{ $summary['average_progress'] }}%</strong>
            </div>
        </div>

        <section class="lead-list-workspace">
            <div class="lead-smart-filters">
                <nav class="lead-filter-chips" aria-label="Project status filters">
                    @foreach ($statusChips as $chipStatus => $chipLabel)
                        <a href="{{ route('admin.projects.index', $chipQuery(['status' => $chipStatus])) }}" @class(['active' => $selectedStatus === $chipStatus])>{{ $chipLabel }}</a>
                    @endforeach
                </nav>

                <form method="GET" action="{{ route('admin.projects.index') }}" class="lead-list-toolbar project-list-toolbar">
                    <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari Project, Customer, Opportunity, atau Quotation" aria-label="Cari project">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
            </div>

            @if ($projects->isNotEmpty())
                <div class="customer-table-wrap lead-table-wrap">
                    <table class="customer-table lead-modern-table project-modern-table">
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Customer</th>
                                <th>Project Manager</th>
                                <th>Progress</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Last Update</th>
                                <th aria-label="Action">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($projects as $project)
                                <tr>
                                    <td>
                                        <div class="lead-primary-cell">
                                            <span class="lead-avatar">{{ strtoupper(str($project->title)->substr(0, 2)) }}</span>
                                            <div>
                                                <a href="{{ route('admin.projects.show', $project) }}" class="lead-name-link">{{ $project->title }}</a>
                                                <p>{{ $project->project_number }}</p>
                                                <small>Opportunity: {{ $project->opportunity?->title ?: '-' }}</small>
                                                <small>Quotation: {{ $project->quotation?->quote_number ?: '-' }}</small>
                                                <small>Customer: {{ $project->customer?->name ?: '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $project->customer?->name ?: '-' }}</td>
                                    <td><span class="lead-owner">{{ $project->projectManager?->name ?: 'Unassigned' }}</span></td>
                                    <td>
                                        <div class="project-progress-cell">
                                            <div class="project-progress-track">
                                                <span style="width: {{ max(0, min(100, (int) $project->progress)) }}%"></span>
                                            </div>
                                            <small>{{ $project->progress }}%</small>
                                        </div>
                                    </td>
                                    <td class="sales-amount">Rp {{ number_format((float) $project->budget, 2, ',', '.') }}</td>
                                    <td><span class="status-badge status-{{ str_replace('_', '-', $project->status) }}">{{ str($project->status)->headline() }}</span></td>
                                    <td><span class="project-last-update">{{ $project->updated_at?->format('d M Y') ?: '-' }}</span></td>
                                    <td>
                                        <details class="lead-row-menu">
                                            <summary aria-label="Actions for {{ $project->title }}">•••</summary>
                                            <div>
                                                <a href="{{ route('admin.projects.show', $project) }}">View</a>
                                                <a href="{{ route('admin.projects.edit', $project) }}">Edit</a>
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
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'case'])</span>
                    <strong>Belum ada Project</strong>
                    <p>Project akan dibuat dari Deal Won atau dapat dibuat secara manual.</p>
                    <a href="{{ route('admin.projects.create') }}" class="btn btn-sm btn-primary">+ Add Project</a>
                </div>
            @endif

            @if ($projects->hasPages())
                <div class="customer-pagination lead-pagination">{{ $projects->links() }}</div>
            @endif
        </section>
    </section>
@endsection
