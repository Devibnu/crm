@extends('admin.layouts.app')

@section('title', 'Project Reports - Krakatau CRM')

@section('content')
    @php
        $query = fn (array $changes = []) => array_filter(array_merge($filters, $changes), fn ($value) => $value !== '' && $value !== null);
        $statusColors = [
            'planning' => '#8b5cf6',
            'active' => '#2563eb',
            'completed' => '#16a34a',
            'delayed' => '#dc2626',
            'cancelled' => '#64748b',
        ];
        $distributionTotal = collect($statusDistribution)->sum('total');
        $cursor = 0;
        $donutSegments = [];

        foreach ($statusDistribution as $segment) {
            $size = $distributionTotal > 0 ? (($segment['total'] / $distributionTotal) * 100) : 0;
            if ($size > 0) {
                $donutSegments[] = ($statusColors[$segment['status']] ?? '#94a3b8').' '.$cursor.'% '.($cursor + $size).'%';
                $cursor += $size;
            }
        }

        $donutGradient = count($donutSegments) ? implode(', ', $donutSegments) : '#e2e8f0 0% 100%';
        $trendMax = max(100, (int) $completionTrend->max('value'));
        $trendPoints = $completionTrend->values()->map(function ($point, $index) use ($completionTrend, $trendMax) {
            $count = max(1, $completionTrend->count() - 1);
            $x = round(($index / $count) * 100, 2);
            $y = round(92 - (((int) $point['value'] / $trendMax) * 76), 2);

            return $x.','.$y;
        })->implode(' ');
        $workloadMax = max(1, (int) $workloadByEmployee->max('minutes'));
        $milestoneTotal = max(1, array_sum($milestoneHealth));
    @endphp

    <section class="lead-list-page project-report-page">
        <header class="lead-list-header project-report-hero">
            <div>
                <span class="crm-record-kicker">PROJECT MANAGEMENT</span>
                <h1>Project Reports</h1>
                <p>Portfolio analytics, delivery performance, workload, milestones, budget, and productivity insights.</p>
            </div>
            <div class="project-report-hero-actions">
                <a href="{{ route('admin.projects.reports.index', $query()) }}" class="btn lead-banner-cta" aria-label="Generate project report">Generate Report</a>
                <details class="project-report-export">
                    <summary aria-label="Open export options">Export</summary>
                    <div>
                        <a href="{{ route('admin.projects.reports.export', $query(['type' => 'pdf'])) }}">PDF</a>
                        <a href="{{ route('admin.projects.reports.export', $query(['type' => 'excel'])) }}">Excel</a>
                        <a href="{{ route('admin.projects.reports.export', $query(['type' => 'csv'])) }}">CSV</a>
                        <a href="{{ route('admin.projects.reports.export', $query(['type' => 'print'])) }}" target="_blank" rel="noopener">Print</a>
                    </div>
                </details>
            </div>
        </header>

        <section class="project-report-filter-card" aria-label="Project report filters">
            <form method="GET" action="{{ route('admin.projects.reports.index') }}" class="project-report-filter-grid">
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" aria-label="Date range from">
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" aria-label="Date range to">
                <select name="project_id" aria-label="Filter project">
                    <option value="">All projects</option>
                    @foreach ($projects as $project)
                        <option value="{{ $project->id }}" @selected((string) $filters['project_id'] === (string) $project->id)>{{ $project->project_number }} - {{ $project->title }}</option>
                    @endforeach
                </select>
                <select name="project_manager_id" aria-label="Filter project manager">
                    <option value="">All managers</option>
                    @foreach ($managers as $manager)
                        <option value="{{ $manager->id }}" @selected((string) $filters['project_manager_id'] === (string) $manager->id)>{{ $manager->name }}</option>
                    @endforeach
                </select>
                <select name="department" aria-label="Filter department">
                    <option value="">All departments</option>
                    @foreach ($departmentOptions as $department => $label)
                        <option value="{{ $department }}" @selected($filters['department'] === $department)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="status" aria-label="Filter status">
                    <option value="">All statuses</option>
                    @foreach ($statusOptions as $status => $label)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="priority" aria-label="Filter priority">
                    <option value="">All priorities</option>
                    @foreach ($priorityOptions as $priority => $label)
                        <option value="{{ $priority }}" @selected($filters['priority'] === $priority)>{{ $label }}</option>
                    @endforeach
                </select>
                <select name="customer_id" aria-label="Filter customer">
                    <option value="">All customers</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $filters['customer_id'] === (string) $customer->id)>{{ $customer->company_name ?: $customer->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-sm btn-primary" aria-label="Apply report filters">Apply</button>
                <a href="{{ route('admin.projects.reports.index') }}" class="btn btn-sm btn-muted" aria-label="Reset report filters">Reset</a>
            </form>
        </section>

        <div class="project-report-kpi-grid" aria-label="Project report summary">
            @foreach ([
                ['icon' => 'pipeline', 'label' => 'Total Projects', 'value' => number_format($kpis['total_projects']), 'helper' => 'Portfolio scope', 'tone' => 'blue'],
                ['icon' => 'activity', 'label' => 'Active Projects', 'value' => number_format($kpis['active_projects']), 'helper' => 'In delivery', 'tone' => 'violet'],
                ['icon' => 'case', 'label' => 'Completed Projects', 'value' => number_format($kpis['completed_projects']), 'helper' => 'Closed delivery', 'tone' => 'green'],
                ['icon' => 'timer', 'label' => 'Delayed Projects', 'value' => number_format($kpis['delayed_projects']), 'helper' => 'Needs attention', 'tone' => 'red'],
                ['icon' => 'analysis', 'label' => 'Overall Completion %', 'value' => $kpis['overall_completion'], 'helper' => 'Average progress', 'tone' => 'blue'],
                ['icon' => 'deal', 'label' => 'Billable Hours', 'value' => $kpis['billable_hours'], 'helper' => 'Revenue eligible', 'tone' => 'amber'],
                ['icon' => 'dashboard', 'label' => 'Budget Utilization %', 'value' => $kpis['budget_utilization'], 'helper' => 'Weighted by budget', 'tone' => 'violet'],
                ['icon' => 'user', 'label' => 'Total Team Members', 'value' => number_format($kpis['total_team_members']), 'helper' => 'Unique contributors', 'tone' => 'green'],
            ] as $kpi)
                <article class="project-report-kpi tone-{{ $kpi['tone'] }}">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => $kpi['icon']])</span>
                    <div>
                        <strong>{{ $kpi['value'] }}</strong>
                        <small>{{ $kpi['label'] }}</small>
                        <em>{{ $kpi['helper'] }}</em>
                    </div>
                </article>
            @endforeach
        </div>

        @if ($kpis['total_projects'] === 0)
            <section class="project-report-empty">
                <span>@include('admin.partials.sidebar-icon', ['icon' => 'analysis'])</span>
                <strong>No report data available.</strong>
                <p>Create your first project or adjust filters to generate executive delivery analytics.</p>
                <a href="{{ route('admin.projects.create') }}" class="btn btn-sm btn-primary" aria-label="Create project from reports empty state">Create Project</a>
            </section>
        @else
            <section class="project-report-chart-grid" aria-label="Project report charts">
                <article class="project-report-card project-report-donut-card">
                    <div class="project-report-card-header">
                        <div><h2>Project Status Distribution</h2><p>Current portfolio by lifecycle status.</p></div>
                    </div>
                    <div class="project-report-donut-layout">
                        <div class="project-report-donut" style="--donut: {{ $donutGradient }}">
                            <strong>{{ number_format($distributionTotal) }}</strong>
                            <span>Projects</span>
                        </div>
                        <div class="project-report-legend">
                            @foreach ($statusDistribution as $segment)
                                <div><i style="background: {{ $statusColors[$segment['status']] ?? '#94a3b8' }}"></i><span>{{ $segment['label'] }}</span><strong>{{ number_format($segment['total']) }}</strong></div>
                            @endforeach
                        </div>
                    </div>
                </article>

                <article class="project-report-card">
                    <div class="project-report-card-header">
                        <div><h2>Project Completion Trend</h2><p>Last 12 months average progress.</p></div>
                    </div>
                    <div class="project-report-line-chart">
                        <svg viewBox="0 0 100 100" role="img" aria-label="Project completion trend line">
                            <polyline points="{{ $trendPoints }}" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div>
                            @foreach ($completionTrend as $point)
                                <span>{{ $point['label'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </article>

                <article class="project-report-card">
                    <div class="project-report-card-header">
                        <div><h2>Workload By Employee</h2><p>Logged project hours by contributor.</p></div>
                    </div>
                    <div class="project-report-bar-list">
                        @forelse ($workloadByEmployee as $workload)
                            <div>
                                <span>{{ $workload['employee'] }}</span>
                                <b>{{ $workload['hours'] }}</b>
                                <i><em style="width: {{ max(4, round(($workload['minutes'] / $workloadMax) * 100)) }}%"></em></i>
                            </div>
                        @empty
                            <p class="project-report-muted">No workload data yet.</p>
                        @endforelse
                    </div>
                </article>

                <article class="project-report-card">
                    <div class="project-report-card-header">
                        <div><h2>Milestone Health</h2><p>Completed, upcoming, and delayed milestones.</p></div>
                    </div>
                    <div class="project-report-stacked">
                        <div>
                            <span class="completed" style="width: {{ ($milestoneHealth['completed'] / $milestoneTotal) * 100 }}%"></span>
                            <span class="upcoming" style="width: {{ ($milestoneHealth['upcoming'] / $milestoneTotal) * 100 }}%"></span>
                            <span class="delayed" style="width: {{ ($milestoneHealth['delayed'] / $milestoneTotal) * 100 }}%"></span>
                        </div>
                        <dl>
                            <div><dt>Completed</dt><dd>{{ number_format($milestoneHealth['completed']) }}</dd></div>
                            <div><dt>Upcoming</dt><dd>{{ number_format($milestoneHealth['upcoming']) }}</dd></div>
                            <div><dt>Delayed</dt><dd>{{ number_format($milestoneHealth['delayed']) }}</dd></div>
                        </dl>
                    </div>
                </article>

                <article class="project-report-card project-report-timesheet-card">
                    <div class="project-report-card-header">
                        <div><h2>Timesheet Summary</h2><p>Hours, billable effort, and pending approvals.</p></div>
                    </div>
                    <div class="project-report-timesheet-grid">
                        @foreach ([
                            ['label' => 'Hours', 'value' => $timesheetSummary['hours']],
                            ['label' => 'Billable', 'value' => $timesheetSummary['billable']],
                            ['label' => 'Non Billable', 'value' => $timesheetSummary['non_billable']],
                            ['label' => 'Pending Approval', 'value' => number_format($timesheetSummary['pending_approval'])],
                        ] as $summary)
                            <div><strong>{{ $summary['value'] }}</strong><span>{{ $summary['label'] }}</span></div>
                        @endforeach
                    </div>
                </article>
            </section>

            <section class="project-report-table-grid">
                <article class="project-report-card">
                    <div class="project-report-card-header">
                        <div><h2>Recent Delivery</h2><p>Newest project records and delivery health.</p></div>
                    </div>
                    <div class="customer-table-wrap lead-table-wrap">
                        <table class="customer-table lead-modern-table project-report-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Progress</th>
                                    <th>Milestone</th>
                                    <th>Owner</th>
                                    <th>Status</th>
                                    <th>Due Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentDelivery as $project)
                                    <tr>
                                        <td><a href="{{ route('admin.projects.show', $project) }}">{{ $project->title }}</a><small>{{ $project->project_number }}</small></td>
                                        <td><div class="project-report-progress"><span style="width: {{ max(0, min(100, (int) $project->progress)) }}%"></span></div><small>{{ $project->progress }}%</small></td>
                                        <td>{{ $project->milestones->first()?->title ?: '-' }}</td>
                                        <td>{{ $project->projectManager?->name ?: '-' }}</td>
                                        <td><span class="status-badge status-{{ str_replace('_', '-', $project->status) }}">{{ $statusOptions[$project->status] ?? str($project->status)->headline() }}</span></td>
                                        <td>{{ $project->due_date?->format('d M Y') ?: '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="project-report-card">
                    <div class="project-report-card-header">
                        <div><h2>Top Delayed Projects</h2><p>Projects past due and still open.</p></div>
                    </div>
                    <div class="customer-table-wrap lead-table-wrap">
                        <table class="customer-table lead-modern-table project-report-table">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Days Late</th>
                                    <th>Owner</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($topDelayedProjects as $delay)
                                    <tr>
                                        <td><a href="{{ route('admin.projects.show', $delay['project']) }}">{{ $delay['project']->title }}</a><small>{{ $delay['project']->project_number }}</small></td>
                                        <td><span class="project-report-delay">{{ number_format($delay['days_late']) }} days</span></td>
                                        <td>{{ $delay['owner'] }}</td>
                                        <td>{{ $delay['reason'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4"><span class="project-report-muted">No delayed projects in this filter.</span></td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>
        @endif
    </section>
@endsection
