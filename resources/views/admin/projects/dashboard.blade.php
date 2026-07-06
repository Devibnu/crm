@extends('admin.layouts.app')

@section('title', 'Project Dashboard - Krakatau CRM')

@section('content')
    <section class="lead-list-page project-dashboard-page">
        <header class="lead-list-banner">
            <div>
                <span>Project Management</span>
                <h1>Project Dashboard</h1>
                <p>Delivery overview for active projects and progress foundation.</p>
            </div>
            <a href="{{ route('admin.projects.index') }}" class="btn lead-banner-cta">Open Projects</a>
        </header>

        <div class="crm-metadata-row">
            <div><span>Total Projects</span><strong>{{ $totalProjects }}</strong></div>
            <div><span>Active</span><strong>{{ $activeProjects }}</strong></div>
            <div><span>Completed</span><strong>{{ $completedProjects }}</strong></div>
            <div><span>Average Progress</span><strong>{{ $averageProgress }}%</strong></div>
        </div>

        <section class="lead-list-card">
            <div class="lead-list-card-header">
                <div>
                    <h2>Recent Projects</h2>
                    <p>Latest delivery records created from Deal Won.</p>
                </div>
            </div>
            <div class="crm-related-list">
                @forelse ($recentProjects as $project)
                    <div>
                        <span>{{ $project->project_number }}</span>
                        <a href="{{ route('admin.projects.show', $project) }}"><strong>{{ $project->title }}</strong></a>
                        <small>{{ $project->customer?->name ?: '-' }} · PM: {{ $project->projectManager?->name ?: '-' }} · {{ $project->progress }}%</small>
                    </div>
                @empty
                    <div><span>Projects</span><strong>No project yet.</strong></div>
                @endforelse
            </div>
        </section>
    </section>
@endsection
