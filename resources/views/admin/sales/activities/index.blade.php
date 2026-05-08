@extends('admin.layouts.app')

@section('title', 'Sales Activity Tracking - Krakatau CRM')

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

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Activities</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua aktivitas sales</small>
            </article>
            <article class="card sales-summary-card">
                <span>Calls</span>
                <strong>{{ number_format($summary['calls']) }}</strong>
                <small>Aktivitas call tercatat</small>
            </article>
            <article class="card sales-summary-card">
                <span>Meetings</span>
                <strong>{{ number_format($summary['meetings']) }}</strong>
                <small>Meeting dengan prospek</small>
            </article>
            <article class="card sales-summary-card">
                <span>Follow Ups</span>
                <strong>{{ number_format($summary['followUps']) }}</strong>
                <small>Aktivitas tindak lanjut</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Activity List</h2>
                    <p>Search subject, description, atau assigned to. Filter berdasarkan type dan related data.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.sales.activities.create') }}" class="btn btn-primary">Add Activity</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.sales.activities.index') }}" class="activity-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Subject, description, assigned to">
                </label>
                <label class="field">
                    <span>Type</span>
                    <select name="type">
                        <option value="">Semua type</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Related Type</span>
                    <select name="related_type">
                        <option value="">Semua related</option>
                        @foreach ($relatedTypeOptions as $relatedType)
                            <option value="{{ $relatedType }}" @selected($selectedRelatedType === $relatedType)>{{ ucfirst($relatedType) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedType || $selectedRelatedType)
                        <a href="{{ route('admin.sales.activities.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Related</th>
                            <th>Activity Date</th>
                            <th>Assigned To</th>
                            <th>Outcome</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activities as $activity)
                            <tr>
                                <td><span class="status-badge activity-{{ $activity->type }}">{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></td>
                                <td>
                                    <a href="{{ route('admin.sales.activities.show', $activity) }}" class="sales-title-link">{{ $activity->subject }}</a>
                                    <small>{{ \Illuminate\Support\Str::limit($activity->description ?: '-', 70) }}</small>
                                </td>
                                <td>
                                    <div>{{ ucfirst($activity->related_type) }}</div>
                                    <small>{{ $activity->related_label }}</small>
                                </td>
                                <td>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $activity->assigned_to ?: '-' }}</td>
                                <td>{{ $activity->outcome ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.sales.activities.show', $activity) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.sales.activities.edit', $activity) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.sales.activities.destroy', $activity) }}" onsubmit="return confirm('Delete activity ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada activity</strong>
                                        <span>Tambahkan aktivitas pertama untuk mulai tracking follow up sales.</span>
                                        <a href="{{ route('admin.sales.activities.create') }}" class="btn btn-primary">Add Activity</a>
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
                        Menampilkan {{ $activities->firstItem() }}-{{ $activities->lastItem() }} dari {{ $activities->total() }} activity
                    </div>
                    <div class="pagination-links">
                        @if ($activities->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $activities->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($activities->getUrlRange(max(1, $activities->currentPage() - 2), min($activities->lastPage(), $activities->currentPage() + 2)) as $page => $url)
                            @if ($page === $activities->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($activities->hasMorePages())
                            <a href="{{ $activities->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
