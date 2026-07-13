@extends('admin.layouts.app')

@section('title', 'Project Dashboard - Krakatau CRM')

@section('content')
    <section class="lead-list-page project-dashboard-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">PROJECT MANAGEMENT</span>
                <h1>Project Dashboard</h1>
                <p>Monitor project delivery, progress health, milestones, and recent activity from one workspace.</p>
            </div>
            <a href="{{ route('admin.projects.create') }}" class="btn lead-banner-cta">+ New Project</a>
        </header>

        @if (! $hasProjects)
            <section class="project-dashboard-empty">
                <span class="project-dashboard-empty-icon" aria-hidden="true">
                    @include('admin.partials.sidebar-icon', ['icon' => 'case'])
                </span>
                <strong>Belum ada Project</strong>
                <p>Mulai dengan membuat project pertama atau ubah Deal Won menjadi Project.</p>
                <a href="{{ route('admin.projects.create') }}" class="btn btn-primary">Create Project</a>
            </section>
        @else
            <div class="project-dashboard-kpis">
                @foreach ($dashboardKpis as $kpi)
                    <article class="project-kpi-card">
                        <span class="project-kpi-icon" aria-hidden="true">
                            @include('admin.partials.sidebar-icon', ['icon' => $kpi['icon']])
                        </span>
                        <div>
                            <span>{{ $kpi['title'] }}</span>
                            <strong>{{ $kpi['value'] }}</strong>
                            <small>{{ $kpi['helper'] }}</small>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="project-dashboard-layout">
                <main class="project-dashboard-main">
                    <section class="lead-list-card">
                        <div class="lead-list-card-header">
                            <div>
                                <h2>Progress Overview</h2>
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
                                <h2>Project Status</h2>
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
                                <div class="project-compact-empty">
                                    <strong>No project statistics available.</strong>
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
                        <div class="project-timeline-list">
                            @forelse ($recentActivities as $activity)
                                <div class="project-timeline-item">
                                    <span class="project-timeline-dot" aria-hidden="true"></span>
                                    <div>
                                        <time>{{ $activity->created_at->format('d M Y H:i') }}</time>
                                        <strong>{{ $activity->description }}</strong>
                                        <small>{{ $activity->project?->project_number ?: '-' }} - {{ $activity->actor?->name ?: 'System' }}</small>
                                    </div>
                                </div>
                            @empty
                                <div class="project-compact-empty">
                                    <strong>Belum ada aktivitas project.</strong>
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
                            <table class="customer-table lead-modern-table project-dashboard-table">
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
                                                <div class="project-compact-empty">
                                                    <strong>No project statistics available.</strong>
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
                                <h2>Milestone Health</h2>
                                <p>Delivery checkpoints across all projects.</p>
                            </div>
                            <a href="{{ route('admin.projects.milestones.index') }}" class="btn btn-sm btn-muted">View All</a>
                        </div>
                        <div class="project-milestone-mini-grid">
                            <div><span>Total</span><strong>{{ number_format($milestoneSummary['total']) }}</strong></div>
                            <div><span>Open</span><strong>{{ number_format($milestoneSummary['open']) }}</strong></div>
                            <div><span>Completed</span><strong>{{ number_format($milestoneSummary['completed']) }}</strong></div>
                            <div><span>Delayed</span><strong>{{ number_format($milestoneSummary['delayed']) }}</strong></div>
                        </div>
                    </section>

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
                                    <small>{{ $milestone->project?->title ?: '-' }} - {{ str($milestone->status)->headline() }}</small>
                                </div>
                            @empty
                                <div class="project-compact-empty">
                                    <strong>No upcoming milestones.</strong>
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
                                <div class="project-compact-empty">
                                    <strong>No project manager assigned.</strong>
                                </div>
                            @endforelse
                        </div>
                    </section>
                </aside>
            </div>
        @endif
    </section>
@endsection
