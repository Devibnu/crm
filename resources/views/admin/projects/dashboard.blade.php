@extends('admin.layouts.app')

@section('title', 'Project Dashboard - Krakatau CRM')

@section('content')
    <section class="lead-list-page project-dashboard-page">
        <header class="lead-list-banner">
            <div>
                <span>Project Management</span>
                <h1>Dashboard</h1>
                <p>Project delivery overview, progress, milestones, and team ownership.</p>
            </div>
            <a href="{{ route('admin.projects.create') }}" class="btn lead-banner-cta">+ New Project</a>
        </header>

        <div class="crm-metadata-row">
            <div><span>Total Project</span><strong>{{ $totalProjects }}</strong></div>
            <div><span>Active</span><strong>{{ $activeProjects }}</strong></div>
            <div><span>Completed</span><strong>{{ $completedProjects }}</strong></div>
            <div><span>Delayed</span><strong>{{ $delayedProjects }}</strong></div>
            <div><span>Overall Progress</span><strong>{{ $averageProgress }}%</strong></div>
        </div>

        <div class="crm-record-workspace">
            <main class="crm-workspace-main">
                <section class="lead-list-card">
                    <div class="lead-list-card-header">
                        <div>
                            <h2>Progress Chart</h2>
                            <p>Distribution by project progress range.</p>
                        </div>
                    </div>
                    <div class="crm-related-list">
                        @foreach ($progressBuckets as $bucket => $total)
                            <div>
                                <span>{{ $bucket }}</span>
                                <strong>{{ $total }} project</strong>
                                <small>{{ $totalProjects > 0 ? round(($total / $totalProjects) * 100) : 0 }}% of portfolio</small>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="lead-list-card">
                    <div class="lead-list-card-header">
                        <div>
                            <h2>Project Status Chart</h2>
                            <p>Current portfolio grouped by status.</p>
                        </div>
                    </div>
                    <div class="crm-related-list">
                        @forelse ($statusCounts as $status => $total)
                            <div>
                                <span>{{ str($status)->headline() }}</span>
                                <strong>{{ $total }} project</strong>
                                <small>{{ $totalProjects > 0 ? round(((int) $total / $totalProjects) * 100) : 0 }}% of portfolio</small>
                            </div>
                        @empty
                            <div><span>Status</span><strong>No project yet.</strong></div>
                        @endforelse
                    </div>
                </section>

                <section class="lead-list-card">
                    <div class="lead-list-card-header">
                        <div>
                            <h2>Recent Activities</h2>
                            <p>Latest project timeline events.</p>
                        </div>
                    </div>
                    <div class="crm-related-list">
                        @forelse ($recentActivities as $activity)
                            <div>
                                <span>{{ $activity->created_at->format('d M Y H:i') }}</span>
                                <strong>{{ $activity->description }}</strong>
                                <small>{{ $activity->project?->project_number ?: '-' }} · {{ $activity->actor?->name ?: 'System' }}</small>
                            </div>
                        @empty
                            <div><span>Activities</span><strong>No activity yet.</strong></div>
                        @endforelse
                    </div>
                </section>

                <section class="lead-list-card">
                    <div class="lead-list-card-header">
                        <div>
                            <h2>Latest Projects</h2>
                            <p>Newest delivery records created from Deal Won.</p>
                        </div>
                        <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-muted">View All</a>
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
            </main>

            <aside class="crm-workspace-sidebar crm-related-sidebar">
                <section class="crm-workspace-section">
                    <h2>Upcoming Milestones</h2>
                    <div class="crm-related-list">
                        @forelse ($upcomingMilestones as $milestone)
                            <div>
                                <span>{{ $milestone->due_date?->format('d M Y') }}</span>
                                <strong>{{ $milestone->title }}</strong>
                                <small>{{ $milestone->project?->title ?: '-' }} · {{ str($milestone->status)->headline() }}</small>
                            </div>
                        @empty
                            <div><span>Milestones</span><strong>No upcoming milestones.</strong></div>
                        @endforelse
                    </div>
                </section>

                <section class="crm-workspace-section">
                    <h2>Project Managers</h2>
                    <div class="crm-related-list">
                        @forelse ($projectManagers as $manager)
                            <div>
                                <span>{{ $manager['total'] }} project</span>
                                <strong>{{ $manager['user']->name }}</strong>
                                <small>{{ $manager['user']->email }}</small>
                            </div>
                        @empty
                            <div><span>Managers</span><strong>No project manager assigned.</strong></div>
                        @endforelse
                    </div>
                </section>
            </aside>
        </div>

        <section class="lead-list-card">
            <div class="lead-list-card-header">
                <div>
                    <h2>Project Management</h2>
                    <p>Quick access to the project workspace.</p>
                </div>
                <a href="{{ route('admin.projects.index') }}" class="btn btn-sm btn-muted">Open Projects</a>
            </div>
        </section>
    </section>
@endsection
