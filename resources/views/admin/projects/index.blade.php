@extends('admin.layouts.app')

@section('title', 'Project Management - Krakatau CRM')

@section('content')
    <section class="lead-list-page project-list-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">PROJECT MANAGEMENT</span>
                <h1>Project Management</h1>
                <p>Project hasil Deal Won dari quotation dan opportunity.</p>
            </div>
            <a href="{{ route('admin.projects.create') }}" class="btn lead-banner-cta">Add Project</a>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <section class="lead-list-workspace">
            <form method="GET" action="{{ route('admin.projects.index') }}" class="lead-list-toolbar">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Project, customer, atau quotation">
                </label>
                <button type="submit" class="btn btn-sm btn-primary">Search</button>
                @if ($search)
                    <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-muted">Reset</a>
                @endif
            </form>

            <div class="customer-table-wrap lead-table-wrap">
                <table class="customer-table lead-modern-table">
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Customer</th>
                            <th>Opportunity</th>
                            <th>Quotation</th>
                            <th>Budget</th>
                            <th>Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($projects as $project)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.projects.show', $project) }}" class="lead-name-link">{{ $project->title }}</a>
                                    <small>{{ $project->project_number }}</small>
                                </td>
                                <td>{{ $project->customer?->name ?: '-' }}</td>
                                <td>{{ $project->opportunity?->title ?: '-' }}</td>
                                <td>{{ $project->quotation?->quote_number ?: '-' }}</td>
                                <td class="sales-amount">Rp {{ number_format((float) $project->budget, 2, ',', '.') }}</td>
                                <td>{{ $project->progress }}%</td>
                                <td><span class="status-badge status-{{ str_replace('_', '-', $project->status) }}">{{ str($project->status)->headline() }}</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="lead-empty-state">
                                        <strong>Belum ada project</strong>
                                        <p>Project akan dibuat dari quotation yang sudah Deal Won.</p>
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
