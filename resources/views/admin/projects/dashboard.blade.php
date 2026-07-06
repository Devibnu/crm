@extends('admin.layouts.app')

@section('title', 'Project Dashboard - Krakatau CRM')

@section('content')
    <section class="lead-list-page project-dashboard-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">Project Management</span>
                <h1>Dashboard</h1>
                <p>Project delivery overview, progress, milestones, and team ownership.</p>
            </div>
            <a href="{{ route('admin.projects.create') }}" class="btn lead-banner-cta">+ New Project</a>
        </header>

        <div class="lead-kpi-strip project-kpi-strip project-dashboard-kpis">
            <div><span>Total Project</span><strong>{{ $totalProjects }}</strong></div>
            <div><span>Active</span><strong>{{ $activeProjects }}</strong></div>
            <div><span>Completed</span><strong>{{ $completedProjects }}</strong></div>
            <div><span>Delayed</span><strong>{{ $delayedProjects }}</strong></div>
            <div><span>Overall Progress</span><strong>{{ $averageProgress }}%</strong></div>
        </div>

        <div class="project-dashboard-layout">
            <main class="project-dashboard-main">
                <section class="lead-list-card">
                    <div class="lead-list-card-header">
                        <div>
                            <h2>Progress Chart</h2>
                            <p>Distribution by project progress range.</p>
                        </div>
                    </div>
                    <div class="project-chart-list">
                        @foreach ($progressBuckets as $bucket => $total)
                            @php($percentage = $totalProjects > 0 ? round(($total / $totalProjects) * 100) : 0)
                            <div class="project-chart-row">
                                <div class="project-chart-meta">
                                    <span>{{ $bucket }}</span>
                                    <strong>{{ $total }} project</strong>
                                </div>
                                <div class="project-chart-track">
                                    <span style="width: {{ $percentage }}%"></span>
                                </div>
                                <small>{{ $percentage }}% of portfolio</small>
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
                    <div class="project-chart-list project-status-chart">
                        @forelse ($statusCounts as $status => $total)
                            @php($percentage = $totalProjects > 0 ? round(((int) $total / $totalProjects) * 100) : 0)
                            <div class="project-chart-row">
                                <div class="project-chart-meta">
                                    <span>{{ str($status)->headline() }}</span>
                                    <strong>{{ $total }} project</strong>
                                </div>
                                <div class="project-chart-track">
                                    <span class="status-{{ str_replace('_', '-', $status) }}" style="width: {{ $percentage }}%"></span>
                                </div>
                                <small>{{ $percentage }}% of portfolio</small>
                            </div>
                        @empty
                            <div class="lead-empty-state">
                                <span>i</span>
                                <strong>No project yet.</strong>
                                <p>Status chart will appear after projects are created.</p>
                            </div>
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
                    <div class="project-feed-list">
                        @forelse ($recentActivities as $activity)
                            <div class="project-feed-item">
                                <span>{{ $activity->created_at->format('d M Y H:i') }}</span>
                                <strong>{{ $activity->description }}</strong>
                                <small>{{ $activity->project?->project_number ?: '-' }} · {{ $activity->actor?->name ?: 'System' }}</small>
                            </div>
                        @empty
                            <div class="lead-empty-state">
                                <span>i</span>
                                <strong>No activity yet.</strong>
                                <p>Timeline activity will appear here.</p>
                            </div>
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
                    <div class="customer-table-wrap lead-table-wrap">
                        <table class="customer-table lead-modern-table project-modern-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Customer</th>
                                    <th>Manager</th>
                                    <th>Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentProjects as $project)
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
                                        <td>{{ $project->projectManager?->name ?: '-' }}</td>
                                        <td>
                                            <div class="project-progress-cell">
                                                <div class="project-progress-track">
                                                    <span style="width: {{ max(0, min(100, (int) $project->progress)) }}%"></span>
                                                </div>
                                                <small>{{ $project->progress }}%</small>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="customer-empty">
                                            <div class="lead-empty-state">
                                                <span>+</span>
                                                <strong>No project yet.</strong>
                                                <p>Latest projects will appear after a Deal Won creates a project.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <aside class="project-dashboard-sidebar">
                <section class="lead-list-card">
                    <div class="lead-list-card-header">
                        <div>
                            <h2>Upcoming Milestones</h2>
                            <p>Nearest delivery checkpoints.</p>
                        </div>
                    </div>
                    <div class="project-feed-list">
                        @forelse ($upcomingMilestones as $milestone)
                            <div class="project-feed-item">
                                <span>{{ $milestone->due_date?->format('d M Y') }}</span>
                                <strong>{{ $milestone->title }}</strong>
                                <small>{{ $milestone->project?->title ?: '-' }} · {{ str($milestone->status)->headline() }}</small>
                            </div>
                        @empty
                            <div class="lead-empty-state">
                                <span>i</span>
                                <strong>No upcoming milestones.</strong>
                                <p>Milestones with due dates will appear here.</p>
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="lead-list-card">
                    <div class="lead-list-card-header">
                        <div>
                            <h2>Project Managers</h2>
                            <p>Current project ownership.</p>
                        </div>
                    </div>
                    <div class="project-feed-list">
                        @forelse ($projectManagers as $manager)
                            <div class="project-feed-item">
                                <span>{{ $manager['total'] }} project</span>
                                <strong>{{ $manager['user']->name }}</strong>
                                <small>{{ $manager['user']->email }}</small>
                            </div>
                        @empty
                            <div class="lead-empty-state">
                                <span>i</span>
                                <strong>No project manager assigned.</strong>
                                <p>Assigned project managers will appear here.</p>
                            </div>
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
