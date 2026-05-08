@extends('admin.layouts.app')

@section('title', $activity->subject.' - Sales Activity - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1>Sales Activity Tracking</h1>
                <p>Tracking aktivitas sales: call, meeting, email, note, dan follow-up.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $activity->subject }}</h2>
                    <p>{{ ucfirst($activity->related_type) }}: {{ $activity->related_label }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span>
                    <a href="{{ route('admin.sales.activities.edit', $activity) }}" class="btn btn-primary">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Type</span>
                    <strong>{{ ucwords(str_replace('_', ' ', $activity->type)) }}</strong>
                </div>
                <div>
                    <span>Related</span>
                    <strong>{{ $activity->related_label }}</strong>
                </div>
                <div>
                    <span>Activity Date</span>
                    <strong>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Type</strong><span><span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></span></div>
                <div><strong>Related Data</strong><span>{{ ucfirst($activity->related_type) }}: {{ $activity->related_label }}</span></div>
                <div><strong>Subject</strong><span>{{ $activity->subject }}</span></div>
                <div><strong>Assigned To</strong><span>{{ $activity->assigned_to ?: '-' }}</span></div>
                <div><strong>Outcome</strong><span>{{ $activity->outcome ?: '-' }}</span></div>
                <div><strong>Activity Date</strong><span>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Description</h3>
                <div>{!! nl2br(e($activity->description ?: 'No description available')) !!}</div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.sales.activities.index') }}" class="btn btn-muted">Back</a>
                <form method="POST" action="{{ route('admin.sales.activities.destroy', $activity) }}" onsubmit="return confirm('Delete activity ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
