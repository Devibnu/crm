@extends('admin.layouts.app')

@section('title', 'Quotation & Deal - Krakatau CRM')

@section('content')
    @php
        $activeFilter = $search || $selectedStatus;
    @endphp
    <span hidden data-doc-title-en="Quotation & Deal - Krakatau CRM" data-doc-title-id="Quotation & Deal - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-deals-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'deal'])
            </div>
            <div>
                <span class="service-badge dashboard-hero-badge" data-lang-en="Offer Desk" data-lang-id="Meja Penawaran">Offer Desk</span>
                <h1 data-lang-en="Quotation & Deal" data-lang-id="Quotation & Deal">Quotation & Deal</h1>
                <p data-lang-en="Manage quotations and deal negotiation." data-lang-id="Kelola penawaran dan deal negotiation.">Kelola penawaran dan deal negotiation.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Quotations" data-lang-id="Total Quotation">Total Quotations</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All saved quotations" data-lang-id="Semua penawaran tersimpan">All saved quotations</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Draft" data-lang-id="Draft">Draft</span>
                <strong>{{ number_format($summary['draft']) }}</strong>
                <small data-lang-en="Quotations still being prepared" data-lang-id="Penawaran masih disiapkan">Quotations still being prepared</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Sent" data-lang-id="Terkirim">Sent</span>
                <strong>{{ number_format($summary['sent']) }}</strong>
                <small data-lang-en="Waiting for customer response" data-lang-id="Menunggu respons customer">Waiting for customer response</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Accepted Value" data-lang-id="Nilai Accepted">Accepted Value</span>
                <strong>Rp {{ number_format($summary['accepted_value'], 2, ',', '.') }}</strong>
                <small data-lang-en="Total accepted quotations" data-lang-id="Total quotation accepted">Total accepted quotations</small>
            </article>
        </div>

        <article class="card customer-table-card sales-deals-shell">
            <div class="sales-section-head sales-deals-head">
                <div>
                    <h2 data-lang-en="Quotation List" data-lang-id="Daftar Quotation">Quotation List</h2>
                    <p data-lang-en="Search quote number, title, customer, or opportunity." data-lang-id="Search quote number, title, customer, atau opportunity.">Search quote number, title, customer, atau opportunity.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.sales.deals.create') }}" class="btn btn-primary" data-lang-en="Add Quotation" data-lang-id="Tambah Quotation">Add Quotation</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.sales.deals.index') }}" class="sales-filter-form sales-deals-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Quote number, title, customer, opportunity" aria-label="Search quotations" data-placeholder-en="Quote number, title, customer, opportunity" data-placeholder-id="Quote number, title, customer, opportunity" data-title-en="Search quotations" data-title-id="Cari quotation">
                </label>
                <label class="field">
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <select name="status" aria-label="Filter status">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">Semua status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($activeFilter)
                        <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table sales-deals-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Quote Number" data-lang-id="Nomor Quotation">Quote Number</th>
                            <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                            <th data-lang-en="Customer" data-lang-id="Customer">Customer</th>
                            <th data-lang-en="Opportunity" data-lang-id="Opportunity">Opportunity</th>
                            <th data-lang-en="Amount" data-lang-id="Nominal">Amount</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Issued At" data-lang-id="Diterbitkan Pada">Issued At</th>
                            <th data-lang-en="Valid Until" data-lang-id="Berlaku Sampai">Valid Until</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            <tr>
                                <td><strong class="sales-code">{{ $quotation->quote_number }}</strong></td>
                                <td>
                                    <div class="sales-deals-title-cell">
                                        <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="sales-title-link">{{ $quotation->title }}</a>
                                    </div>
                                </td>
                                <td><span class="sales-source-pill">{{ $quotation->customer?->name ?: '-' }}</span></td>
                                <td>{{ $quotation->opportunity?->title ?: '-' }}</td>
                                <td class="sales-amount">Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</td>
                                <td><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td>
                                <td>{{ $quotation->issued_at?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.sales.deals.edit', $quotation) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.sales.deals.destroy', $quotation) }}" data-confirm-en="Delete this quotation?" data-confirm-id="Hapus quotation ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus quotation ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No quotations yet" data-lang-id="Belum ada quotation">No quotations yet</strong>
                                        <span data-lang-en="Add the first quotation to start tracking offers and deal negotiation." data-lang-id="Tambahkan quotation pertama untuk mulai melacak penawaran dan deal negotiation.">Add the first quotation to start tracking offers and deal negotiation.</span>
                                        <a href="{{ route('admin.sales.deals.create') }}" class="btn btn-primary" data-lang-en="Add Quotation" data-lang-id="Tambah Quotation">Add Quotation</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($quotations->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $quotations->firstItem() }}-{{ $quotations->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $quotations->total() }} <span data-lang-en="quotations" data-lang-id="quotation">quotations</span>
                    </div>
                    <div class="pagination-links">
                        @if ($quotations->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $quotations->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($quotations->getUrlRange(max(1, $quotations->currentPage() - 2), min($quotations->lastPage(), $quotations->currentPage() + 2)) as $page => $url)
                            @if ($page === $quotations->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($quotations->hasMorePages())
                            <a href="{{ $quotations->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
