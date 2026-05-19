@extends('admin.layouts.app')

@section('title', 'Edit Sales Activity - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Edit Sales Activity - Krakatau CRM" data-doc-title-id="Edit Aktivitas Sales - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-activities-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Field Motion" data-lang-id="Field Motion">Field Motion</span>
                <h1 data-lang-en="Edit Sales Activity" data-lang-id="Edit Aktivitas Sales">Edit Sales Activity</h1>
                <p data-lang-en="Update the sales activity, data relation, owner, and outcome." data-lang-id="Perbarui aktivitas sales, relasi data, owner, dan outcome.">Perbarui aktivitas sales, relasi data, owner, dan outcome.</p>
            </div>
        </article>

        <article class="card customer-form-card sales-activities-form-shell">
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
                    <a href="{{ route('admin.sales.activities.show', $activity) }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                    <button type="submit" class="btn btn-primary" data-lang-en="Update Activity" data-lang-id="Ubah Aktivitas">Update Activity</button>
                </div>
            </form>
        </article>
    </section>
@endsection
