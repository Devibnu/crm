@extends('admin.layouts.app')

@section('title', $calendar->name.' - Business Calendar - Krakatau CRM')

@section('content')
    @php
        $upcomingHolidays = $calendar->holidays->filter(fn ($holiday) => $holiday->holiday_date->isToday() || $holiday->holiday_date->isFuture())->sortBy('holiday_date')->values();
    @endphp

    <section class="lead-list-page customer-profile-page customer-360-dashboard sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">{{ strtoupper(substr($calendar->name, 0, 1)) }}</div>
                <div>
                    <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                    <h1>Business Calendar 360</h1>
                    <div class="customer-profile-hero-meta" aria-label="Business calendar summary">
                        <span>{{ $calendar->name }}</span>
                        <span>{{ $calendar->timezone }}</span>
                        <span>{{ $calendar->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="customer-360-hero-meta-line">
                        <span>{{ $calendar->is_default ? 'Default calendar' : 'Not default' }}</span>
                        <span>Updated: {{ $calendar->updated_at?->format('d M Y H:i') ?: '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                @if ($calendar->is_default)
                    <span class="status-badge status-active">Default</span>
                @endif
                <span class="status-badge status-{{ $calendar->is_active ? 'active' : 'inactive' }}">{{ $calendar->is_active ? 'Active' : 'Inactive' }}</span>
                @can('business-calendar.update')
                    <a href="{{ route('admin.service.business-calendars.edit', $calendar) }}" class="btn lead-banner-cta">Edit</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="card customer-alert danger">{{ $errors->first() }}</div>
        @endif

        <div class="lead-kpi-strip customer-profile-kpi-strip customer-360-kpi-strip" aria-label="Business calendar KPI">
            <div>
                <span>Timezone</span>
                <strong>{{ $calendar->timezone }}</strong>
            </div>
            <div>
                <span>Working Days</span>
                <strong>{{ $calendar->workingHours->where('is_working_day', true)->count() }}</strong>
            </div>
            <div>
                <span>Holidays</span>
                <strong>{{ $calendar->holidays->count() }}</strong>
            </div>
            <div>
                <span>Upcoming</span>
                <strong>{{ $upcomingHolidays->count() }}</strong>
            </div>
        </div>

        <section class="customer-360-dashboard-grid" aria-label="Calendar summary">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Calendar Summary</span>
                        <h2>{{ $calendar->name }}</h2>
                    </div>
                </div>
                <div class="customer-notes">
                    <p>{{ $calendar->description ?: 'No description available' }}</p>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>State</span>
                        <h2>Operational status</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Active</span>
                        <strong>{{ $calendar->is_active ? 'Yes' : 'No' }}</strong>
                        <small>{{ $calendar->is_active ? 'Available for future SLA usage' : 'Disabled' }}</small>
                    </div>
                    <div>
                        <span>Default</span>
                        <strong>{{ $calendar->is_default ? 'Yes' : 'No' }}</strong>
                        <small>{{ $calendar->is_default ? 'Primary support calendar' : 'Secondary calendar' }}</small>
                    </div>
                </div>
            </article>
        </section>

        <section class="customer-profile-workspace customer-360-section" aria-label="Weekly operating hours">
            <div class="customer-profile-section-head">
                <div>
                    <span>Weekly Operating Hours</span>
                    <h2>Monday to Sunday</h2>
                </div>
            </div>
            <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                <table class="customer-table lead-modern-table sales-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Status</th>
                            <th>Start</th>
                            <th>End</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($dayLabels as $day => $label)
                            @php($hours = $calendar->workingHourForDay($day))
                            <tr>
                                <td><strong>{{ $label }}</strong></td>
                                <td><span class="status-badge status-{{ $hours?->is_working_day ? 'active' : 'inactive' }}">{{ $hours?->is_working_day ? 'Working' : 'Closed' }}</span></td>
                                <td>{{ $hours?->is_working_day ? substr($hours->start_time, 0, 5) : '-' }}</td>
                                <td>{{ $hours?->is_working_day ? substr($hours->end_time, 0, 5) : '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="customer-profile-workspace customer-360-section" aria-label="Holiday management">
            <div class="customer-profile-section-head">
                <div>
                    <span>Holidays</span>
                    <h2>Non-working dates</h2>
                </div>
            </div>

            @can('business-calendar.manage-holidays')
                <form method="POST" action="{{ route('admin.service.business-calendars.holidays.store', $calendar) }}" class="lead-list-toolbar customer-profile-search-form sla-filter-form">
                    @csrf
                    <input type="date" name="holiday_date" value="{{ old('holiday_date') }}" aria-label="Holiday date" required>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Holiday name" aria-label="Holiday name" required>
                    <select name="is_recurring" aria-label="Recurring holiday">
                        <option value="0" @selected(old('is_recurring', '0') === '0')>One-time</option>
                        <option value="1" @selected(old('is_recurring') === '1')>Recurring</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Add Holiday</button>
                </form>
            @endcan

            @if ($calendar->holidays->isEmpty())
                <div class="lead-empty-state customer-profile-enterprise-empty">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                    <strong>No holidays configured</strong>
                    <p>Holiday records will appear here when non-working dates are added.</p>
                </div>
            @else
                <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                    <table class="customer-table lead-modern-table sales-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Name</th>
                                <th>Recurring</th>
                                <th>Description</th>
                                @can('business-calendar.manage-holidays')
                                    <th>Action</th>
                                @endcan
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($calendar->holidays as $holiday)
                                <tr>
                                    <td><strong class="sales-code">{{ $holiday->holiday_date->format('d M Y') }}</strong></td>
                                    <td>{{ $holiday->name }}</td>
                                    <td><span class="status-badge status-{{ $holiday->is_recurring ? 'active' : 'inactive' }}">{{ $holiday->is_recurring ? 'Yes' : 'No' }}</span></td>
                                    <td>{{ $holiday->description ?: '-' }}</td>
                                    @can('business-calendar.manage-holidays')
                                        <td>
                                            <details class="lead-row-menu customer-profile-row-menu">
                                                <summary aria-label="Open holiday actions">⋮</summary>
                                                <div>
                                                    <form method="POST" action="{{ route('admin.service.business-calendars.holidays.update', [$calendar, $holiday]) }}">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="date" name="holiday_date" value="{{ $holiday->holiday_date->format('Y-m-d') }}" required>
                                                        <input type="text" name="name" value="{{ $holiday->name }}" required>
                                                        <input type="hidden" name="is_recurring" value="0">
                                                        <label class="field" style="margin:0;">
                                                            <span>Recurring</span>
                                                            <input type="checkbox" name="is_recurring" value="1" @checked($holiday->is_recurring)>
                                                        </label>
                                                        <textarea name="description" rows="2">{{ $holiday->description }}</textarea>
                                                        <button type="submit">Update</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.service.business-calendars.holidays.destroy', [$calendar, $holiday]) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Delete holiday ini?')">Delete</button>
                                                    </form>
                                                </div>
                                            </details>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        <section class="customer-360-action-toolbar" aria-label="Quick actions">
            <span>Quick Actions</span>
            <div>
                <a href="{{ route('admin.service.business-calendars.index') }}" class="customer-360-action-pill">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                    <strong>Back</strong>
                </a>
                @can('business-calendar.update')
                    <a href="{{ route('admin.service.business-calendars.edit', $calendar) }}" class="customer-360-action-pill">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'calendar'])</span>
                        <strong>Edit</strong>
                    </a>
                @endcan
                @can('business-calendar.set-default')
                    @unless ($calendar->is_default)
                        <form method="POST" action="{{ route('admin.service.business-calendars.set-default', $calendar) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Set Default</button>
                        </form>
                    @endunless
                @endcan
                @can('business-calendar.delete')
                    <form method="POST" action="{{ route('admin.service.business-calendars.destroy', $calendar) }}" onsubmit="return confirm('Delete business calendar ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                @endcan
            </div>
        </section>
    </section>
@endsection
