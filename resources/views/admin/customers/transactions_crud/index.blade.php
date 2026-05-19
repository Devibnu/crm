@extends('admin.layouts.app')

@section('title', 'Transactions - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Transactions - Krakatau CRM" data-doc-title-id="Transaksi - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'cart'])
            </div>
            <div>
                <h1 data-lang-en="Transactions" data-lang-id="Transaksi">Transactions</h1>
                <p data-lang-en="Customer transaction history, deals, quotations, purchases, and revenue." data-lang-id="Riwayat transaksi, deal, quotation, purchase, dan revenue customer.">Riwayat transaksi, deal, quotation, purchase, dan revenue customer.</p>
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
                        data-placeholder-en="Search customer or title"
                        data-placeholder-id="Cari customer atau title"
                        data-title-en="Search transaction"
                        data-title-id="Cari transaksi"
                    >
                    <select name="status" aria-label="Filter status" data-title-en="Filter status" data-title-id="Filter status">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">Semua status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.customers.transactions') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </form>

                @if ($firstCustomerId)
                    <a href="{{ route('admin.customers.transactions.create', ['customer' => $firstCustomerId]) }}" class="btn btn-primary" data-lang-en="Add Transaction" data-lang-id="Tambah Transaksi">Add Transaction</a>
                @else
                    <span class="btn btn-disabled" data-lang-en="Add Transaction" data-lang-id="Tambah Transaksi">Add Transaction</span>
                @endif
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Customer" data-lang-id="Customer">Customer</th>
                            <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                            <th data-lang-en="Amount" data-lang-id="Nominal">Amount</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Closing Date" data-lang-id="Tanggal Closing">Closing Date</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                        <a href="{{ route('admin.customers.transactions.edit', $transaction) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.transactions.destroy', $transaction) }}" data-confirm-en="Delete this transaction?" data-confirm-id="Hapus transaksi ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus transaksi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="customer-empty" data-lang-en="No transactions yet." data-lang-id="Belum ada transaksi.">Belum ada transaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $transactions->total() }} <span data-lang-en="transactions" data-lang-id="transaksi">transactions</span>
                    </div>
                    <div class="pagination-links">
                        @if ($transactions->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $transactions->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($transactions->getUrlRange(max(1, $transactions->currentPage() - 2), min($transactions->lastPage(), $transactions->currentPage() + 2)) as $page => $url)
                            @if ($page === $transactions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($transactions->hasMorePages())
                            <a href="{{ $transactions->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
