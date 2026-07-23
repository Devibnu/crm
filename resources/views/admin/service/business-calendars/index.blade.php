@extends('admin.layouts.app')

@section('title', 'Business Calendar - Krakatau CRM')

@section('content')
    @php
        $weeklySummary = function ($calendar): string {
            $workingDays = $calendar->workingHours->where('is_working_day', true);

            if ($workingDays->isEmpty()) {
                return 'No working days';
            }

            $first = $workingDays->first();
            $last = $workingDays->last();

            return $workingDays->count().' days, '.substr($first->start_time, 0, 5).'-'.substr($last->end_time, 0, 5);
        };
        $calendarKpis = [
            ['label' => 'Total Calendars', 'value' => number_format($summary['total'] ?? $calendars->total())],
            ['label' => 'Active Calendars', 'value' => number_format($summary['active'] ?? $calendars->getCollection()->where('is_active', true)->count())],
            ['label' => 'Default Calendar', 'value' => $summary['default'] ?? '-'],
            ['label' => 'Upcoming Holidays', 'value' => number_format($summary['upcoming_holidays'] ?? 0)],
        ];
    @endphp

    <section class="lead-list-page customer-profile-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>Business Calendar</h1>
                <p>Manage operating hours, holidays, timezone, and active default calendar profiles.</p>
            </div>
            @can('business-calendar.create')
                <a href="{{ route('admin.service.business-calendars.create') }}" class="btn lead-banner-cta">Add Calendar</a>
            @endcan
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-profile-kpi-strip" aria-label="Business calendar summary">
            @foreach ($calendarKpis as $kpi)
                <div>
                    <span>{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                </div>
            @endforeach
        </div>

        <section class="lead-list-workspace customer-profile-workspace" aria-label="Business calendar workspace">
            <div class="lead-smart-filters customer-profile-smart-filters">
                <form method="GET" action="{{ route('admin.service.business-calendars.index') }}" class="lead-list-toolbar customer-profile-search-form sla-filter-form">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Calendar name, description, timezone" aria-label="Search business calendars">
                    <select name="is_active" aria-label="Filter active status">
                        <option value="">Semua status</option>
                        <option value="active" @selected($selectedActive === 'active')>Active</option>
                        <option value="inactive" @selected($selectedActive === 'inactive')>Inactive</option>
                    </select>
                    <select name="is_default" aria-label="Filter default status">
                        <option value="">Default status</option>
                        <option value="yes" @selected($selectedDefault === 'yes')>Default</option>
                        <option value="no" @selected($selectedDefault === 'no')>Not Default</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedActive || $selectedDefault)
                        <a href="{{ route('admin.service.business-calendars.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                    @can('business-calendar.create')
                        <a href="{{ route('admin.service.business-calendars.create') }}" class="btn btn-primary">Add Calendar</a>
                    @endcan
                </form>
            </div>

            @if ($calendars->isEmpty())
                <div class="lead-empty-state customer-profile-enterprise-empty">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                    <strong>No business calendars found</strong>
                    <p>Create the first calendar to manage working hours and holidays for SLA planning.</p>
                    @can('business-calendar.create')
                        <a href="{{ route('admin.service.business-calendars.create') }}" class="btn btn-sm btn-primary">Add Calendar</a>
                    @endcan
                </div>
            @else
                <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                    <table class="customer-table lead-modern-table sales-table">
                        <thead>
                            <tr>
                                <th>Calendar</th>
                                <th>Timezone</th>
                                <th>Weekly Hours</th>
                                <th>Status</th>
                                <th>Default</th>
                                <th>Updated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($calendars as $calendar)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.service.business-calendars.show', $calendar) }}" class="sales-title-link">{{ $calendar->name }}</a>
                                        <small>{{ $calendar->description ?: 'No description' }}</small>
                                    </td>
                                    <td><strong class="sales-code">{{ $calendar->timezone }}</strong></td>
                                    <td>{{ $weeklySummary($calendar) }}</td>
                                    <td><span class="status-badge status-{{ $calendar->is_active ? 'active' : 'inactive' }}">{{ $calendar->is_active ? 'Active' : 'Inactive' }}</span></td>
                                    <td>
                                        @if ($calendar->is_default)
                                            <span class="status-badge status-active">Default</span>
                                        @else
                                            <span class="status-badge status-inactive">No</span>
                                        @endif
                                    </td>
                                    <td>{{ $calendar->updated_at?->format('d M Y H:i') ?: '-' }}</td>
                                    <td>
                                        <details class="lead-row-menu customer-profile-row-menu">
                                            <summary aria-label="Open business calendar actions">⋮</summary>
                                            <div>
                                                <a href="{{ route('admin.service.business-calendars.show', $calendar) }}">View</a>
                                                @can('business-calendar.update')
                                                    <a href="{{ route('admin.service.business-calendars.edit', $calendar) }}">Edit</a>
                                                @endcan
                                                @can('business-calendar.set-default')
                                                    @unless ($calendar->is_default)
                                                        <form method="POST" action="{{ route('admin.service.business-calendars.set-default', $calendar) }}">
                                                            @csrf
                                                            <button type="submit">Set Default</button>
                                                        </form>
                                                    @endunless
                                                @endcan
                                                @can('business-calendar.delete')
                                                    <form method="POST" action="{{ route('admin.service.business-calendars.destroy', $calendar) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Delete business calendar ini?')">Delete</button>
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

                @if ($calendars->hasPages())
                    <div class="customer-pagination lead-pagination customer-profile-pagination">
                        @if ($calendars->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $calendars->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($calendars->getUrlRange(max(1, $calendars->currentPage() - 2), min($calendars->lastPage(), $calendars->currentPage() + 2)) as $page => $url)
                            @if ($page === $calendars->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($calendars->hasMorePages())
                            <a href="{{ $calendars->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                @endif
            @endif
        </section>
    </section>
@endsection
