@extends('admin.layouts.app')

@section('title', 'Sales Activity Tracking - Krakatau CRM')

@section('content')
    @php
        $activityCollection = $activities->getCollection();
        $activeFilter = $search || $selectedType || $selectedRelatedType;
    @endphp
    <span hidden data-doc-title-en="Sales Activity Tracking - Krakatau CRM" data-doc-title-id="Pelacakan Aktivitas Sales - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-activities-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'activity'])
            </div>
            <div>
                <span class="service-badge dashboard-hero-badge" data-lang-en="Field Motion" data-lang-id="Gerak Lapangan">Field Motion</span>
                <h1 data-lang-en="Sales Activity Tracking" data-lang-id="Pelacakan Aktivitas Sales">Sales Activity Tracking</h1>
                <p data-lang-en="Track sales activities: calls, meetings, email, notes, and follow-ups." data-lang-id="Tracking aktivitas sales: call, meeting, email, note, dan follow-up.">Tracking aktivitas sales: call, meeting, email, note, dan follow-up.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Activities" data-lang-id="Total Aktivitas">Total Activities</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All sales activities" data-lang-id="Semua aktivitas sales">All sales activities</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Calls" data-lang-id="Call">Calls</span>
                <strong>{{ number_format($summary['calls']) }}</strong>
                <small data-lang-en="Recorded call activities" data-lang-id="Aktivitas call tercatat">Recorded call activities</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Meetings" data-lang-id="Meeting">Meetings</span>
                <strong>{{ number_format($summary['meetings']) }}</strong>
                <small data-lang-en="Meetings with prospects" data-lang-id="Meeting dengan prospek">Meetings with prospects</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Follow Ups" data-lang-id="Tindak Lanjut">Follow Ups</span>
                <strong>{{ number_format($summary['followUps']) }}</strong>
                <small data-lang-en="Follow-up activities" data-lang-id="Aktivitas tindak lanjut">Follow-up activities</small>
            </article>
        </div>

        <article class="card customer-table-card sales-activities-shell">
            <div class="sales-section-head sales-activities-head">
                <div>
                    <h2 data-lang-en="Activity List" data-lang-id="Daftar Aktivitas">Activity List</h2>
                    <p data-lang-en="Search subject, description, or assigned to. Filter by type and related data." data-lang-id="Search subject, description, atau assigned to. Filter berdasarkan type dan related data.">Search subject, description, atau assigned to. Filter berdasarkan type dan related data.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.sales.activities.create') }}" class="btn btn-primary" data-lang-en="Add Activity" data-lang-id="Tambah Aktivitas">Add Activity</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.sales.activities.index') }}" class="activity-filter-form sales-activities-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Subject, description, assigned to" data-placeholder-en="Subject, description, assigned to" data-placeholder-id="Subject, description, assigned to">
                </label>
                <label class="field">
                    <span data-lang-en="Type" data-lang-id="Tipe">Type</span>
                    <select name="type">
                        <option value="" data-lang-en="All types" data-lang-id="Semua tipe">Semua type</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Related Type" data-lang-id="Tipe Relasi">Related Type</span>
                    <select name="related_type">
                        <option value="" data-lang-en="All related" data-lang-id="Semua relasi">Semua related</option>
                        @foreach ($relatedTypeOptions as $relatedType)
                            <option value="{{ $relatedType }}" @selected($selectedRelatedType === $relatedType)>{{ ucfirst($relatedType) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($activeFilter)
                        <a href="{{ route('admin.sales.activities.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table sales-activities-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                            <th data-lang-en="Subject" data-lang-id="Subjek">Subject</th>
                            <th data-lang-en="Related" data-lang-id="Relasi">Related</th>
                            <th data-lang-en="Activity Date" data-lang-id="Tanggal Aktivitas">Activity Date</th>
                            <th data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</th>
                            <th data-lang-en="Outcome" data-lang-id="Hasil">Outcome</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td><span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></td>
                                <td>
                                    <div class="sales-activities-subject-cell">
                                        <a href="{{ route('admin.sales.activities.show', $activity) }}" class="sales-title-link">{{ $activity->subject }}</a>
                                        <small>{{ \Illuminate\Support\Str::limit($activity->description ?: '-', 70) }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="sales-activities-related-cell">
                                        <strong>{{ ucfirst($activity->related_type) }}</strong>
                                        <small>{{ $activity->related_label }}</small>
                                    </div>
                                </td>
                                <td>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td><span class="sales-assignee-pill">{{ $activity->assigned_to ?: '-' }}</span></td>
                                <td>{{ $activity->outcome ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.sales.activities.show', $activity) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a>
                                        <a href="{{ route('admin.sales.activities.edit', $activity) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.sales.activities.destroy', $activity) }}" data-confirm-en="Delete this activity?" data-confirm-id="Hapus aktivitas ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus aktivitas ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No activities yet" data-lang-id="Belum ada aktivitas">No activities yet</strong>
                                        <span data-lang-en="Add the first activity to start tracking sales follow-ups." data-lang-id="Tambahkan aktivitas pertama untuk mulai tracking follow up sales.">Add the first activity to start tracking sales follow-ups.</span>
                                        <a href="{{ route('admin.sales.activities.create') }}" class="btn btn-primary" data-lang-en="Add Activity" data-lang-id="Tambah Aktivitas">Add Activity</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($activities->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $activities->firstItem() }}-{{ $activities->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $activities->total() }} <span data-lang-en="activities" data-lang-id="aktivitas">activities</span>
                    </div>
                    <div class="pagination-links">
                        @if ($activities->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $activities->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($activities->getUrlRange(max(1, $activities->currentPage() - 2), min($activities->lastPage(), $activities->currentPage() + 2)) as $page => $url)
                            @if ($page === $activities->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($activities->hasMorePages())
                            <a href="{{ $activities->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
