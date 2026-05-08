@extends('admin.layouts.app')

@section('title', 'Quotation & Deal - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'deal'])
            </div>
            <div>
                <h1>Quotation & Deal</h1>
                <p>Kelola penawaran dan deal negotiation.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Quotations</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua penawaran tersimpan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Draft</span>
                <strong>{{ number_format($summary['draft']) }}</strong>
                <small>Penawaran masih disiapkan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Sent</span>
                <strong>{{ number_format($summary['sent']) }}</strong>
                <small>Menunggu respons customer</small>
            </article>
            <article class="card sales-summary-card">
                <span>Accepted Value</span>
                <strong>Rp {{ number_format($summary['accepted_value'], 2, ',', '.') }}</strong>
                <small>Total quotation accepted</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Quotation List</h2>
                    <p>Search quote number, title, customer, atau opportunity.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.sales.deals.create') }}" class="btn btn-primary">Add Quotation</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.sales.deals.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Quote number, title, customer, opportunity" aria-label="Search quotations">
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Quote Number</th>
                            <th>Title</th>
                            <th>Customer</th>
                            <th>Opportunity</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Issued At</th>
                            <th>Valid Until</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            <tr>
                                <td><strong class="sales-code">{{ $quotation->quote_number }}</strong></td>
                                <td>
                                    <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="sales-title-link">{{ $quotation->title }}</a>
                                </td>
                                <td>{{ $quotation->customer?->name ?: '-' }}</td>
                                <td>{{ $quotation->opportunity?->title ?: '-' }}</td>
                                <td class="sales-amount">Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</td>
                                <td><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td>
                                <td>{{ $quotation->issued_at?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.sales.deals.edit', $quotation) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.sales.deals.destroy', $quotation) }}" onsubmit="return confirm('Delete quotation ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada quotation</strong>
                                        <span>Tambahkan quotation pertama untuk mulai melacak penawaran dan deal negotiation.</span>
                                        <a href="{{ route('admin.sales.deals.create') }}" class="btn btn-primary">Add Quotation</a>
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
                        Menampilkan {{ $quotations->firstItem() }}-{{ $quotations->lastItem() }} dari {{ $quotations->total() }} quotation
                    </div>
                    <div class="pagination-links">
                        @if ($quotations->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $quotations->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($quotations->getUrlRange(max(1, $quotations->currentPage() - 2), min($quotations->lastPage(), $quotations->currentPage() + 2)) as $page => $url)
                            @if ($page === $quotations->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($quotations->hasMorePages())
                            <a href="{{ $quotations->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
