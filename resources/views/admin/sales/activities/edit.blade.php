@extends('admin.layouts.app')

@section('title', 'Edit Sales Activity - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <h1>Edit Sales Activity</h1>
                <p>Perbarui aktivitas sales, relasi data, owner, dan outcome.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $activity->subject }}</h2>
                    <p>{{ ucfirst($activity->related_type) }}: {{ $activity->related_label }}</p>
                </div>
                <span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.sales.activities.update', $activity) }}">
                @csrf
                @method('PUT')

                @include('admin.sales.activities._form')

                <div class="form-actions">
                    <a href="{{ route('admin.sales.activities.show', $activity) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Activity</button>
                </div>
            </form>
        </article>
    </section>
@endsection
