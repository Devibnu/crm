@extends('admin.layouts.app')

@section('title', 'Audience Segmentation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'audience'])
            </div>
            <div>
                <h1>Audience Segmentation</h1>
                <p>Kelola segmentasi audience untuk targeting campaign marketing.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Segments</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua audience segment</small>
            </article>
            <article class="card sales-summary-card">
                <span>Active Segments</span>
                <strong>{{ number_format($summary['active']) }}</strong>
                <small>Siap dipakai campaign</small>
            </article>
            <article class="card sales-summary-card">
                <span>Inactive Segments</span>
                <strong>{{ number_format($summary['inactive']) }}</strong>
                <small>Segment nonaktif</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Estimated Audience</span>
                <strong>{{ number_format($summary['estimated_audience']) }}</strong>
                <small>Estimasi reach gabungan</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Segment List</h2>
                    <p>Search name atau description, lalu filter type dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.audiences.create') }}" class="btn btn-primary">Add Segment</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.audiences.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or description" aria-label="Search audience segments">
                </label>
                <label class="field">
                    <span>Type</span>
                    <select name="type" aria-label="Filter type">
                        <option value="">All types</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status" aria-label="Filter status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($search || $selectedType || $selectedStatus)
                        <a href="{{ route('admin.marketing.audiences.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Segment Name</th>
                            <th>Type</th>
                            <th>Estimated Audience</th>
                            <th>Status</th>
                            <th>Created By</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($segments as $segment)
                            <tr>
                                <td><a href="{{ route('admin.marketing.audiences.show', $segment) }}" class="sales-title-link">{{ $segment->name }}</a></td>
                                <td><span class="status-badge type-{{ $segment->type }}">{{ ucfirst($segment->type) }}</span></td>
                                <td>{{ number_format($segment->estimated_audience) }}</td>
                                <td><span class="status-badge status-{{ $segment->status }}">{{ ucfirst($segment->status) }}</span></td>
                                <td>{{ $segment->created_by ?: '-' }}</td>
                                <td>{{ $segment->created_at?->format('d M Y H:i') }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.audiences.show', $segment) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.marketing.audiences.edit', $segment) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.audiences.destroy', $segment) }}" onsubmit="return confirm('Delete segment ini?');">
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
                                        <strong>Belum ada segment</strong>
                                        <span>Tambahkan audience segment pertama untuk targeting campaign marketing.</span>
                                        <a href="{{ route('admin.marketing.audiences.create') }}" class="btn btn-primary">Add Segment</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($segments->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $segments->firstItem() }}-{{ $segments->lastItem() }} dari {{ $segments->total() }} segment
                    </div>
                    <div class="pagination-links">
                        @if ($segments->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $segments->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($segments->getUrlRange(max(1, $segments->currentPage() - 2), min($segments->lastPage(), $segments->currentPage() + 2)) as $page => $url)
                            @if ($page === $segments->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($segments->hasMorePages())
                            <a href="{{ $segments->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
