@extends('admin.layouts.app')

@section('title', 'Audience Segmentation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Audience Segmentation - Krakatau CRM" data-doc-title-id="Segmentasi Audiens - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'audience'])
            </div>
            <div>
                <h1 data-lang-en="Audience Segmentation" data-lang-id="Segmentasi Audiens">Audience Segmentation</h1>
                <p data-lang-en="Manage audience segmentation for marketing campaign targeting." data-lang-id="Kelola segmentasi audiens untuk penargetan campaign marketing.">Kelola segmentasi audience untuk targeting campaign marketing.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Segments" data-lang-id="Total Segmen">Total Segments</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All audience segments" data-lang-id="Semua segmen audiens">Semua audience segment</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Active Segments" data-lang-id="Segmen Aktif">Active Segments</span>
                <strong>{{ number_format($summary['active']) }}</strong>
                <small data-lang-en="Ready for campaign use" data-lang-id="Siap dipakai campaign">Siap dipakai campaign</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Inactive Segments" data-lang-id="Segmen Nonaktif">Inactive Segments</span>
                <strong>{{ number_format($summary['inactive']) }}</strong>
                <small data-lang-en="Inactive segments" data-lang-id="Segmen nonaktif">Segment nonaktif</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Total Estimated Audience" data-lang-id="Total Estimasi Audiens">Total Estimated Audience</span>
                <strong>{{ number_format($summary['estimated_audience']) }}</strong>
                <small data-lang-en="Combined estimated reach" data-lang-id="Estimasi reach gabungan">Estimasi reach gabungan</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Segment List" data-lang-id="Daftar Segmen">Segment List</h2>
                    <p data-lang-en="Search by name or description, then filter by type and status." data-lang-id="Cari berdasarkan nama atau deskripsi, lalu filter berdasarkan tipe dan status.">Search name atau description, lalu filter type dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.audiences.create') }}" class="btn btn-primary" data-lang-en="Add Segment" data-lang-id="Tambah Segmen">Add Segment</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.audiences.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or description" aria-label="Search audience segments" data-placeholder-en="Name or description" data-placeholder-id="Nama atau deskripsi" data-title-en="Search audience segments" data-title-id="Cari segmen audiens">
                </label>
                <label class="field">
                    <span data-lang-en="Type" data-lang-id="Tipe">Type</span>
                    <select name="type" aria-label="Filter type" data-title-en="Filter type" data-title-id="Filter tipe">
                        <option value="" data-lang-en="All types" data-lang-id="Semua tipe">All types</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucfirst($type) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <select name="status" aria-label="Filter status" data-title-en="Filter status" data-title-id="Filter status">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Apply Filter" data-lang-id="Terapkan Filter">Apply Filter</button>
                    @if ($search || $selectedType || $selectedStatus)
                        <a href="{{ route('admin.marketing.audiences.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Segment Name" data-lang-id="Nama Segmen">Segment Name</th>
                            <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                            <th data-lang-en="Estimated Audience" data-lang-id="Estimasi Audiens">Estimated Audience</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</th>
                            <th data-lang-en="Created At" data-lang-id="Dibuat Pada">Created At</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                        <a href="{{ route('admin.marketing.audiences.show', $segment) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.marketing.audiences.edit', $segment) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.audiences.destroy', $segment) }}" data-confirm-en="Delete this segment?" data-confirm-id="Hapus segmen ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this segment?');">
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
                                        <strong data-lang-en="No segments yet" data-lang-id="Belum ada segmen">Belum ada segment</strong>
                                        <span data-lang-en="Add the first audience segment for marketing campaign targeting." data-lang-id="Tambahkan segmen audiens pertama untuk penargetan campaign marketing.">Tambahkan audience segment pertama untuk targeting campaign marketing.</span>
                                        <a href="{{ route('admin.marketing.audiences.create') }}" class="btn btn-primary" data-lang-en="Add Segment" data-lang-id="Tambah Segmen">Add Segment</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($segments->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info" data-lang-en="Showing {{ $segments->firstItem() }}-{{ $segments->lastItem() }} of {{ $segments->total() }} segments" data-lang-id="Menampilkan {{ $segments->firstItem() }}-{{ $segments->lastItem() }} dari {{ $segments->total() }} segmen">
                        Menampilkan {{ $segments->firstItem() }}-{{ $segments->lastItem() }} dari {{ $segments->total() }} segment
                    </div>
                    <div class="pagination-links">
                        @if ($segments->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</span>
                        @else
                            <a href="{{ $segments->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</a>
                        @endif

                        @foreach ($segments->getUrlRange(max(1, $segments->currentPage() - 2), min($segments->lastPage(), $segments->currentPage() + 2)) as $page => $url)
                            @if ($page === $segments->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($segments->hasMorePages())
                            <a href="{{ $segments->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Berikutnya">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Berikutnya">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
