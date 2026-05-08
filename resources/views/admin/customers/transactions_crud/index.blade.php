@extends('admin.layouts.app')

@section('title', 'Transactions - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'cart'])
            </div>
            <div>
                <h1>Transactions</h1>
                <p>Riwayat transaksi, deal, quotation, purchase, dan revenue customer.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <form method="GET" action="{{ route('admin.customers.transactions') }}" class="customer-search-form">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari customer atau title"
                        aria-label="Search transaction"
                    >
                    <select name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.customers.transactions') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                @if ($firstCustomerId)
                    <a href="{{ route('admin.customers.transactions.create', ['customer' => $firstCustomerId]) }}" class="btn btn-primary">Add Transaction</a>
                @else
                    <span class="btn btn-disabled">Add Transaction</span>
                @endif
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Title</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Closing Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td>{{ $transaction->customer?->name ?: '-' }}</td>
                                <td>
                                    <div>{{ $transaction->title }}</div>
                                    <small>{{ \Illuminate\Support\Str::limit($transaction->description ?: '-', 70) }}</small>
                                </td>
                                <td>Rp {{ number_format((float) $transaction->amount, 2, ',', '.') }}</td>
                                <td>
                                    <span class="status-badge status-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
                                </td>
                                <td>{{ $transaction->closing_date?->format('d M Y') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.customers.transactions.edit', $transaction) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.transactions.destroy', $transaction) }}" onsubmit="return confirm('Delete transaction ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="customer-empty">Belum ada transaction.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} dari {{ $transactions->total() }} transaction
                    </div>
                    <div class="pagination-links">
                        @if ($transactions->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $transactions->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($transactions->getUrlRange(max(1, $transactions->currentPage() - 2), min($transactions->lastPage(), $transactions->currentPage() + 2)) as $page => $url)
                            @if ($page === $transactions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($transactions->hasMorePages())
                            <a href="{{ $transactions->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
