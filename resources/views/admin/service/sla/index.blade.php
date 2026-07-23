@extends('admin.layouts.app')

@section('title', 'SLA Management - Krakatau CRM')

@section('content')
    @php
        $visiblePolicies = $policies->getCollection();
        $formatTarget = function (?int $minutes): string {
            if (! $minutes) {
                return '-';
            }

            if ($minutes >= 1440 && $minutes % 1440 === 0) {
                return number_format($minutes / 1440).' day'.($minutes / 1440 > 1 ? 's' : '');
            }

            if ($minutes >= 60 && $minutes % 60 === 0) {
                return number_format($minutes / 60).' hour'.($minutes / 60 > 1 ? 's' : '');
            }

            return number_format($minutes).' min';
        };
        $slaKpis = [
            ['label' => 'Total SLA Policies', 'value' => number_format($summary['total'] ?? $policies->total())],
            ['label' => 'Active Policies', 'value' => number_format($summary['active'] ?? $visiblePolicies->where('is_active', true)->count())],
            ['label' => 'High/Urgent Policies', 'value' => number_format($summary['high_urgent'] ?? $visiblePolicies->whereIn('priority', ['high', 'urgent'])->count())],
            ['label' => 'Average Resolution Target', 'value' => $formatTarget((int) ($summary['average_resolution'] ?? 0))],
        ];
    @endphp

    <section class="lead-list-page customer-profile-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>SLA Management</h1>
                <p>Kelola policy response dan resolution target untuk ticket layanan pelanggan.</p>
            </div>
            <div class="customer-profile-actions">
                <div class="customer-profile-hero-meta" aria-label="SLA summary">
                    <span>{{ number_format($summary['active'] ?? 0) }} active</span>
                    <span>{{ number_format($summary['total'] ?? $policies->total()) }} policies</span>
                </div>
                @can('sla.create')
                    <a href="{{ route('admin.service.sla.create') }}" class="btn lead-banner-cta">Add SLA Policy</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-profile-kpi-strip" aria-label="SLA policy summary">
            @foreach ($slaKpis as $kpi)
                <div>
                    <span>{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                </div>
            @endforeach
        </div>

        <section class="lead-list-workspace customer-profile-workspace" aria-label="SLA policy workspace">
            <div class="lead-smart-filters customer-profile-smart-filters">
                <form method="GET" action="{{ route('admin.service.sla.index') }}" class="lead-list-toolbar customer-profile-search-form sla-filter-form">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Policy name or description" aria-label="Search SLA policies">
                    <select name="priority" aria-label="Filter priority">
                        <option value="">Semua priority</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                    <select name="is_active" aria-label="Filter active status">
                        <option value="">Semua status</option>
                        @foreach ($activeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedActive === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedPriority || $selectedActive)
                        <a href="{{ route('admin.service.sla.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                    @can('sla.create')
                        <a href="{{ route('admin.service.sla.create') }}" class="btn btn-primary">Add SLA Policy</a>
                    @endcan
                </form>
            </div>

            @if ($policies->isEmpty())
                <div class="lead-empty-state customer-profile-enterprise-empty">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'timer'])</span>
                    <strong>Belum ada SLA policy</strong>
                    <p>Tambahkan aturan SLA pertama untuk mengatur target response dan resolution time.</p>
                    @can('sla.create')
                        <a href="{{ route('admin.service.sla.create') }}" class="btn btn-sm btn-primary">Add SLA Policy</a>
                    @endcan
                </div>
            @else
                <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                    <table class="customer-table lead-modern-table sales-table">
                        <thead>
                            <tr>
                                <th>Policy Name</th>
                                <th>Priority</th>
                                <th>Business Calendar</th>
                                <th>Response Target</th>
                                <th>Resolution Target</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($policies as $policy)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.service.sla.show', $policy) }}" class="sales-title-link">{{ $policy->name }}</a>
                                        <small>{{ $policy->description ?: 'No description' }}</small>
                                    </td>
                                    <td><span class="status-badge priority-{{ $policy->priority }}">{{ ucfirst($policy->priority) }}</span></td>
                                    <td>
                                        @if ($policy->businessCalendar)
                                            <strong>{{ $policy->businessCalendar->name }}</strong>
                                            <small>{{ $policy->businessCalendar->timezone }}{{ $policy->businessCalendar->is_default ? ' · Default' : '' }}</small>
                                        @else
                                            <strong>Belum ditentukan</strong>
                                            <small>Uses active default calendar when applied</small>
                                        @endif
                                    </td>
                                    <td><strong class="sales-code">{{ $formatTarget($policy->response_time_minutes) }}</strong></td>
                                    <td>{{ $formatTarget($policy->resolution_time_minutes) }}</td>
                                    <td><span class="status-badge status-{{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        <details class="lead-row-menu customer-profile-row-menu">
                                            <summary aria-label="Open SLA policy actions">⋮</summary>
                                            <div>
                                                <a href="{{ route('admin.service.sla.show', $policy) }}">View</a>
                                                @can('sla.update')
                                                    <a href="{{ route('admin.service.sla.edit', $policy) }}">Edit</a>
                                                @endcan
                                                @can('sla.delete')
                                                    <form method="POST" action="{{ route('admin.service.sla.destroy', $policy) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Delete SLA policy ini?')">Delete</button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($policies->hasPages())
                    <div class="customer-pagination lead-pagination customer-profile-pagination">
                        @if ($policies->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $policies->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($policies->getUrlRange(max(1, $policies->currentPage() - 2), min($policies->lastPage(), $policies->currentPage() + 2)) as $page => $url)
                            @if ($page === $policies->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($policies->hasMorePages())
                            <a href="{{ $policies->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                @endif
            @endif
        </section>
    </section>
@endsection
