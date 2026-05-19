@extends('admin.layouts.app')

@section('title', $activity->subject.' - Sales Activity - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="{{ $activity->subject }} - Sales Activity - Krakatau CRM" data-doc-title-id="{{ $activity->subject }} - Aktivitas Sales - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-activities-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Field Motion" data-lang-id="Field Motion">Field Motion</span>
                <h1 data-lang-en="Sales Activity Tracking" data-lang-id="Pelacakan Aktivitas Sales">Sales Activity Tracking</h1>
                <p data-lang-en="Track sales activities: calls, meetings, email, notes, and follow-ups." data-lang-id="Tracking aktivitas sales: call, meeting, email, note, dan follow-up.">Tracking aktivitas sales: call, meeting, email, note, dan follow-up.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card sales-activities-show-shell">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $activity->subject }}</h2>
                    <p>{{ ucfirst($activity->related_type) }}: {{ $activity->related_label }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span>
                    <a href="{{ route('admin.sales.activities.edit', $activity) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Type" data-lang-id="Tipe">Type</span>
                    <strong>{{ ucwords(str_replace('_', ' ', $activity->type)) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Related" data-lang-id="Relasi">Related</span>
                    <strong>{{ $activity->related_label }}</strong>
                </div>
                <div>
                    <span data-lang-en="Activity Date" data-lang-id="Tanggal Aktivitas">Activity Date</span>
                    <strong>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Type" data-lang-id="Tipe">Type</strong><span><span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></span></div>
                <div><strong data-lang-en="Related Data" data-lang-id="Data Terkait">Related Data</strong><span>{{ ucfirst($activity->related_type) }}: {{ $activity->related_label }}</span></div>
                <div><strong data-lang-en="Subject" data-lang-id="Subjek">Subject</strong><span>{{ $activity->subject }}</span></div>
                <div><strong data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</strong><span>{{ $activity->assigned_to ?: '-' }}</span></div>
                <div><strong data-lang-en="Outcome" data-lang-id="Hasil">Outcome</strong><span>{{ $activity->outcome ?: '-' }}</span></div>
                <div><strong data-lang-en="Activity Date" data-lang-id="Tanggal Aktivitas">Activity Date</strong><span>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Description" data-lang-id="Deskripsi">Description</h3>
                <div>{!! nl2br(e($activity->description ?: '')) !!}@unless($activity->description)<span data-lang-en="No description available" data-lang-id="Belum ada deskripsi">No description available</span>@endunless</div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.sales.activities.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                <form method="POST" action="{{ route('admin.sales.activities.destroy', $activity) }}" data-confirm-en="Delete this activity?" data-confirm-id="Hapus aktivitas ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus aktivitas ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
