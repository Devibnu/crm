@extends('admin.layouts.app')

@section('title', 'Quotation & Deal - Krakatau CRM')

@section('content')
    <section class="lead-list-page deal-list-page">
        <header class="lead-list-header">
            <div>
                <span class="crm-record-kicker">SALES WORKSPACE</span>
                <h1>Quotation & Deal</h1>
                <p>Kelola quotation, penawaran, dan status deal customer.</p>
            </div>
            <a href="{{ route('admin.sales.deals.create') }}" class="btn lead-banner-cta">Add Quotation</a>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip deal-kpi-strip" aria-label="Quotation summary">
            <div><span>Total Quotations</span><strong>{{ number_format($summary['total']) }}</strong></div>
            <div><span>Draft</span><strong>{{ number_format($summary['draft']) }}</strong></div>
            <div><span>Sent</span><strong>{{ number_format($summary['sent']) }}</strong></div>
            <div><span>Accepted Value</span><strong>Rp {{ number_format($summary['accepted_value'], 0, ',', '.') }}</strong></div>
        </div>

        <section class="lead-list-workspace deal-list-workspace">
            <header class="deal-list-workspace-head">
                <div>
                    <h2>Quotation List</h2>
                    <p>Pantau penawaran customer, opportunity terkait, nilai, dan status deal.</p>
                </div>
            </header>

            <form method="GET" action="{{ route('admin.sales.deals.index') }}" class="lead-list-toolbar deal-list-toolbar">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari quotation, customer, atau opportunity" aria-label="Search quotations">
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
                <button type="submit" class="btn btn-sm btn-primary">Search</button>
                @if ($search || $selectedStatus)
                    <a href="{{ route('admin.sales.deals.index') }}" class="btn btn-sm btn-muted">Reset</a>
                @endif
            </form>

            <div class="customer-table-wrap lead-table-wrap deal-table-wrap">
                <table class="customer-table lead-modern-table deal-modern-table">
                    <thead>
                        <tr>
                            <th>Quotation</th>
                            <th>Customer</th>
                            <th>Opportunity</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Created Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            <tr>
                                <td>
                                    <div class="deal-primary-cell">
                                        <span class="deal-quote-icon" aria-hidden="true">@include('admin.partials.sidebar-icon', ['icon' => 'deal'])</span>
                                        <div>
                                            <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="lead-name-link">{{ $quotation->title }}</a>
                                            <small>{{ $quotation->quote_number }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $quotation->customer?->name ?: '-' }}</td>
                                <td>{{ $quotation->opportunity?->title ?: '-' }}</td>
                                <td class="sales-amount">Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</td>
                                <td><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td>
                                <td>{{ $quotation->created_at?->format('d M Y') ?: '-' }}</td>
                                <td>
                                    <details class="lead-row-menu">
                                        <summary aria-label="Quotation actions">•••</summary>
                                        <div>
                                            <a href="{{ route('admin.sales.deals.show', $quotation) }}">View</a>
                                            <a href="{{ route('admin.sales.deals.edit', $quotation) }}">Edit</a>
                                            <form method="POST" action="{{ route('admin.sales.deals.destroy', $quotation) }}" onsubmit="return confirm('Delete quotation ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit">Delete</button>
                                            </form>
                                        </div>
                                    </details>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="lead-empty-state deal-empty-state">
                                        <span aria-hidden="true">@include('admin.partials.sidebar-icon', ['icon' => 'deal'])</span>
                                        <strong>Belum ada quotation atau deal</strong>
                                        <p>Buat quotation pertama untuk mulai melacak penawaran, nilai deal, dan respons customer.</p>
                                        <a href="{{ route('admin.sales.deals.create') }}" class="btn btn-primary">Add Quotation</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($quotations->hasPages())
                <div class="customer-pagination lead-pagination">
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
        </section>
    </section>
@endsection
