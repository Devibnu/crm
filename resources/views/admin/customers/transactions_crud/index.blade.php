@extends('admin.layouts.app')

@section('title', 'Transactions - Krakatau CRM')

@section('content')
    @php
        $visibleTransactions = $transactions->getCollection();
        $latestTransaction = $visibleTransactions->first();
        $totalVisibleValue = $visibleTransactions->sum(fn ($transaction) => (float) $transaction->amount);
        $selectedStatusLabel = $selectedStatus ? ucfirst($selectedStatus) : 'All Status';
        $customerSelectorCustomers = \App\Models\Customer::query()
            ->orderBy('name')
            ->get(['id', 'name', 'company_name', 'email', 'phone']);
    @endphp

    <section class="lead-list-page customer-transaction-list-page">
        <header class="lead-list-header lead-form-banner customer-form-hero customer-interaction-list-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PROFILE 360</span>
                <h1>Transactions</h1>
                <p>Track customer deals, revenue records, closing status, and transaction history across all customer records.</p>
                <div class="customer-form-hero-meta">
                    <span>{{ $selectedStatusLabel }}</span>
                    @if ($search)
                        <span>Search: {{ $search }}</span>
                    @endif
                </div>
            </div>
            <div class="customer-interaction-hero-summary" aria-label="Transaction quick summary">
                <div>
                    <span>Total Transactions</span>
                    <strong>{{ number_format($transactions->total()) }}</strong>
                </div>
                <div>
                    <span>Latest Closing</span>
                    <strong>{{ $latestTransaction?->closing_date?->format('d M Y') ?: '-' }}</strong>
                </div>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-interaction-kpi-strip customer-transaction-kpi-strip" aria-label="Transaction summary">
            <div>
                <strong>{{ number_format($transactions->total()) }}</strong>
                <span>Transactions</span>
            </div>
            <div>
                <strong>{{ number_format($visibleTransactions->where('status', 'won')->count()) }}</strong>
                <span>Won</span>
            </div>
            <div>
                <strong>{{ number_format($visibleTransactions->where('status', 'pending')->count()) }}</strong>
                <span>Pending</span>
            </div>
            <div>
                <strong>Rp {{ number_format($totalVisibleValue, 0, ',', '.') }}</strong>
                <span>Visible Value</span>
            </div>
        </div>

        <article class="card customer-table-card customer-interaction-table-card customer-transaction-table-card">
            <div class="customer-table-toolbar lead-list-toolbar customer-interaction-toolbar">
                <form method="GET" action="{{ route('admin.customers.transactions') }}" class="customer-search-form lead-smart-filters customer-interaction-filters">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search transactions..."
                        aria-label="Search transaction"
                    >
                    <select name="status" aria-label="Filter status">
                        <option value="">All Status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Apply</button>
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.customers.transactions') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                @can('customers.create')
                    @if ($customerSelectorCustomers->isNotEmpty())
                        <button type="button" class="btn btn-primary" data-customer-selector-trigger="newTransaction">New Transaction</button>
                    @else
                        <span class="btn btn-disabled">New Transaction</span>
                    @endif
                @endcan
            </div>

            <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                <table class="customer-table lead-modern-table customer-interaction-table customer-transaction-table">
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
                                <td>
                                    <strong>{{ $transaction->customer?->name ?: '-' }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $transaction->title }}</strong>
                                    <small>{{ \Illuminate\Support\Str::limit($transaction->description ?: '-', 70) }}</small>
                                </td>
                                <td><strong>Rp {{ number_format((float) $transaction->amount, 2, ',', '.') }}</strong></td>
                                <td>
                                    <span class="status-badge status-{{ $transaction->status }}">{{ ucfirst($transaction->status) }}</span>
                                </td>
                                <td>{{ $transaction->closing_date?->format('d M Y') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        @can('customers.update')
                                            <a href="{{ route('admin.customers.transactions.edit', $transaction) }}" class="btn btn-sm btn-primary">Edit</a>
                                        @endcan
                                        @can('customers.delete')
                                            <form method="POST" action="{{ route('admin.customers.transactions.destroy', $transaction) }}" onsubmit="return confirm('Delete transaction ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="customer-profile-enterprise-empty customer-interaction-empty customer-transaction-empty">
                                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'cart'])</span>
                                        <strong>No Transactions Yet</strong>
                                        <p>Customer revenue and deal history will appear here.</p>
                                        @can('customers.create')
                                            @if ($customerSelectorCustomers->isNotEmpty())
                                                <button type="button" class="btn btn-primary" data-customer-selector-trigger="newTransaction">New Transaction</button>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($transactions->hasPages())
                <div class="customer-pagination lead-pagination">
                    <div class="pagination-info">
                        Showing {{ $transactions->firstItem() }}-{{ $transactions->lastItem() }} of {{ $transactions->total() }} transactions
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

        @can('customers.create')
            <x-crm.customer-selector-modal
                modal-id="newTransaction"
                title="New Transaction"
                description="Select a customer before creating a transaction record."
                :customers="$customerSelectorCustomers"
                route-name="admin.customers.transactions.create"
                empty-message="No customers available for transaction records."
            />
        @endcan
    </section>
@endsection
