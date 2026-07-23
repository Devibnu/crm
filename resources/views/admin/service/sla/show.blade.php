@extends('admin.layouts.app')

@section('title', $policy->name.' - SLA Policy - Krakatau CRM')

@section('content')
    @php
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
    @endphp

    <section class="lead-list-page customer-profile-page customer-360-dashboard sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">{{ strtoupper(substr($policy->name, 0, 1)) }}</div>
                <div>
                    <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                    <h1>SLA Policy 360</h1>
                    <div class="customer-profile-hero-meta" aria-label="SLA policy summary">
                        <span>{{ $policy->name }}</span>
                        <span>{{ ucfirst($policy->priority) }} priority</span>
                        <span>{{ $policy->is_active ? 'Active' : 'Inactive' }}</span>
                    </div>
                    <div class="customer-360-hero-meta-line">
                        <span>Response: {{ $formatTarget($policy->response_time_minutes) }}</span>
                        <span>Resolution: {{ $formatTarget($policy->resolution_time_minutes) }}</span>
                        <span>Updated: {{ $policy->updated_at?->format('d M Y H:i') ?: '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                <span class="status-badge priority-{{ $policy->priority }}">{{ ucfirst($policy->priority) }}</span>
                <span class="status-badge status-{{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span>
                @can('sla.update')
                    <a href="{{ route('admin.service.sla.edit', $policy) }}" class="btn lead-banner-cta">Edit</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-profile-kpi-strip customer-360-kpi-strip" aria-label="SLA targets">
            <div>
                <span>Response Target</span>
                <strong>{{ $formatTarget($policy->response_time_minutes) }}</strong>
            </div>
            <div>
                <span>Resolution Target</span>
                <strong>{{ $formatTarget($policy->resolution_time_minutes) }}</strong>
            </div>
            <div>
                <span>Priority</span>
                <strong>{{ ucfirst($policy->priority) }}</strong>
            </div>
            <div>
                <span>Last Updated</span>
                <strong>{{ $policy->updated_at?->format('d M') ?: '-' }}</strong>
            </div>
        </div>

        <section class="customer-360-dashboard-grid" aria-label="SLA policy detail">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Policy Information</span>
                        <h2>{{ $policy->name }}</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Status</span>
                        <strong><span class="status-badge status-{{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span></strong>
                        <small>{{ $policy->is_active ? 'Ready for new ticket snapshots' : 'Not applied to new tickets' }}</small>
                    </div>
                    <div>
                        <span>Priority</span>
                        <strong><span class="status-badge priority-{{ $policy->priority }}">{{ ucfirst($policy->priority) }}</span></strong>
                        <small>Ticket priority match</small>
                    </div>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Target Window</span>
                        <h2>Response and resolution</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>First Response</span>
                        <strong>{{ number_format($policy->response_time_minutes) }} min</strong>
                        <small>{{ $formatTarget($policy->response_time_minutes) }}</small>
                    </div>
                    <div>
                        <span>Resolution</span>
                        <strong>{{ number_format($policy->resolution_time_minutes) }} min</strong>
                        <small>{{ $formatTarget($policy->resolution_time_minutes) }}</small>
                    </div>
                </div>
            </article>
        </section>

        <section class="customer-profile-workspace customer-360-section" aria-label="SLA description">
            <div class="customer-profile-section-head">
                <div>
                    <span>Description</span>
                    <h2>Policy guidance</h2>
                </div>
            </div>
            <div class="customer-notes">
                <p>{{ $policy->description ?: 'No description available' }}</p>
            </div>
        </section>

        <section class="customer-360-dashboard-grid" aria-label="SLA metadata">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Lifecycle</span>
                        <h2>Policy timestamps</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Created At</span>
                        <strong>{{ $policy->created_at?->format('d M Y') ?: '-' }}</strong>
                        <small>{{ $policy->created_at?->format('H:i') ?: '-' }}</small>
                    </div>
                    <div>
                        <span>Updated At</span>
                        <strong>{{ $policy->updated_at?->format('d M Y') ?: '-' }}</strong>
                        <small>{{ $policy->updated_at?->format('H:i') ?: '-' }}</small>
                    </div>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Snapshot Behavior</span>
                        <h2>Ticket history protection</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Policy Changes</span>
                        <strong>Future tickets</strong>
                        <small>Existing ticket SLA snapshots remain unchanged.</small>
                    </div>
                </div>
            </article>
        </section>

        <section class="customer-360-action-toolbar" aria-label="Quick actions">
            <span>Quick Actions</span>
            <div>
                <a href="{{ route('admin.service.sla.index') }}" class="customer-360-action-pill">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'timer'])</span>
                    <strong>Back</strong>
                </a>
                @can('sla.update')
                    <a href="{{ route('admin.service.sla.edit', $policy) }}" class="customer-360-action-pill">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'timer'])</span>
                        <strong>Edit</strong>
                    </a>
                @endcan
                @can('sla.delete')
                    <form method="POST" action="{{ route('admin.service.sla.destroy', $policy) }}" onsubmit="return confirm('Delete SLA policy ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                @endcan
            </div>
        </section>
    </section>
@endsection
