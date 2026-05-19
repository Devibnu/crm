@extends('admin.layouts.app')

@section('title', $policy->name.' - SLA Policy - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'timer'])
            </div>
            <div>
                <h1>SLA Policy Detail</h1>
                <p>Ringkasan aturan waktu respons dan penyelesaian tiket layanan pelanggan.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $policy->name }}</h2>
                    <p>{{ ucfirst($policy->priority) }} priority</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span>
                    <a href="{{ route('admin.service.sla.edit', $policy) }}" class="btn btn-primary">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Response Time</span>
                    <strong>{{ number_format($policy->response_time_minutes) }} min</strong>
                </div>
                <div>
                    <span>Resolution Time</span>
                    <strong>{{ number_format($policy->resolution_time_minutes) }} min</strong>
                </div>
                <div>
                    <span>Priority</span>
                    <strong>{{ ucfirst($policy->priority) }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Name</strong><span>{{ $policy->name }}</span></div>
                <div><strong>Priority</strong><span>{{ ucfirst($policy->priority) }}</span></div>
                <div><strong>Response Time Minutes</strong><span>{{ number_format($policy->response_time_minutes) }}</span></div>
                <div><strong>Resolution Time Minutes</strong><span>{{ number_format($policy->resolution_time_minutes) }}</span></div>
                <div><strong>Status</strong><span>{{ $policy->is_active ? 'Active' : 'Inactive' }}</span></div>
                <div><strong>Created At</strong><span>{{ $policy->created_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Updated At</strong><span>{{ $policy->updated_at?->format('d M Y H:i') ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Description</h3>
                <p>{{ $policy->description ?: 'No description available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.service.sla.index') }}" class="btn btn-muted">Back</a>
                <form method="POST" action="{{ route('admin.service.sla.destroy', $policy) }}" onsubmit="return confirm('Delete SLA policy ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
