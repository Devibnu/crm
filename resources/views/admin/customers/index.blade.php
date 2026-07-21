@extends('admin.layouts.app')

@section('title', 'Customer List - Krakatau CRM')

@section('content')
    @php
        $visibleCustomers = collect($customers->items());
        $scoreFor = fn ($customer) => min(100, ($customer->status === 'active' ? 42 : 18) + ($customer->updated_at && $customer->updated_at->gte(now()->subDays(30)) ? 28 : 0));
        $statusLabel = fn (?string $status) => match ($status) {
            'active' => 'Active',
            'inactive' => 'Inactive',
            'blacklist' => 'Blocked',
            'new' => 'Prospect',
            default => 'Prospect',
        };
        $profileKpis = [
            ['label' => 'Customers', 'value' => number_format($customers->total())],
            ['label' => 'Active', 'value' => number_format($visibleCustomers->where('status', 'active')->count())],
            ['label' => 'Prospects', 'value' => number_format($visibleCustomers->where('status', 'new')->count())],
            ['label' => 'Recent Activity', 'value' => number_format($visibleCustomers->filter(fn ($customer) => $customer->updated_at && $customer->updated_at->gte(now()->subDays(30)))->count())],
        ];
    @endphp

    <section class="lead-list-page customer-profile-page">
        @include('admin.customers._success-toast')

        <header class="lead-list-header customer-profile-lead-hero">
            <div>
                <span class="crm-record-kicker">CUSTOMER PROFILE 360</span>
                <h1>Customer List</h1>
                <p>Manage all customers, companies, contacts, lifecycle, and business relationships from one workspace.</p>
            </div>
            @can('customers.create')
                <a href="{{ route('admin.customers.create') }}" class="btn lead-banner-cta" aria-label="Add customer">Add Customer</a>
            @endcan
        </header>

        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-profile-kpi-strip" aria-label="Customer list summary">
            @foreach ($profileKpis as $kpi)
                <div>
                    <span>{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                </div>
            @endforeach
        </div>

        <section class="lead-list-workspace customer-profile-workspace" aria-label="Customer list workspace">
            <span class="sr-only">Customer List</span>
            <div class="lead-smart-filters customer-profile-smart-filters">
                <nav class="lead-filter-chips customer-profile-status-tabs" aria-label="Customer status filters">
                    <button type="button" class="active" data-customer-status-tab="all">All</button>
                    <button type="button" data-customer-status-tab="active">Active</button>
                    <button type="button" data-customer-status-tab="inactive">Inactive</button>
                    <button type="button" data-customer-status-tab="new">Prospect</button>
                    <button type="button" data-customer-status-tab="blacklist">Blocked</button>
                </nav>

                <form method="GET" action="{{ route('admin.customers.index') }}" class="lead-list-toolbar customer-profile-search-form" data-customer-profile-search>
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Search customers..."
                        aria-label="Search customer"
                        autocomplete="off"
                    >
                    @if ($search)
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
            </div>

            @if ($customers->isEmpty())
                <div class="lead-empty-state customer-profile-enterprise-empty">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'user'])</span>
                    <strong>No customers found</strong>
                    <p>Create your first customer to start managing customer relationships.</p>
                    @can('customers.create')
                        <a href="{{ route('admin.customers.create') }}" class="btn btn-sm btn-primary">Add Customer</a>
                    @endcan
                </div>
            @else
                <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                    <table class="customer-table lead-modern-table customer-profile-directory-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Company</th>
                                <th>Contact</th>
                                <th>Lifecycle</th>
                                <th>Customer Score</th>
                                <th>Last Activity</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($customers as $customer)
                                @php
                                    $score = $scoreFor($customer);
                                    $scoreLabel = $score >= 70 ? 'High' : ($score >= 40 ? 'Medium' : 'Low');
                                @endphp
                                <tr
                                    data-customer-status="{{ $customer->status }}"
                                    data-customer-search="{{ strtolower(trim($customer->name.' '.$customer->email.' '.$customer->phone.' '.$customer->whatsapp.' '.$customer->company_name.' '.$customer->owner_name)) }}"
                                >
                                    <td>
                                        <div class="lead-primary-cell">
                                            <span class="lead-avatar">{{ strtoupper(substr($customer->name, 0, 2)) }}</span>
                                            <div>
                                                <a href="{{ route('admin.customers.show', $customer) }}" class="lead-name-link">{{ $customer->name }}</a>
                                                <small>{{ $customer->source ?: 'Direct' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $customer->company_name ?: '-' }}</td>
                                    <td>
                                        <div class="lead-contact-cell">
                                            <span>{{ $customer->email ?: '-' }}</span>
                                            <small>{{ $customer->phone ?: ($customer->whatsapp ? 'WA: '.$customer->whatsapp : '-') }}</small>
                                        </div>
                                    </td>
                                    <td><span class="customer-profile-lifecycle">{{ $statusLabel($customer->status) }}</span></td>
                                    <td>
                                        <div class="lead-score-cell customer-profile-score-cell">
                                            <strong>{{ $score }}</strong>
                                            <span>{{ $scoreLabel }}</span>
                                        </div>
                                    </td>
                                    <td>{{ $customer->updated_at?->format('d M Y') ?: '-' }}</td>
                                    <td><span class="status-badge status-{{ $customer->status }}">{{ ucfirst($customer->status) }}</span></td>
                                    <td>
                                        <details class="lead-row-menu customer-profile-row-menu">
                                            <summary aria-label="Open customer actions">⋮</summary>
                                            <div>
                                                <a href="{{ route('admin.customers.show', $customer) }}">View 360</a>
                                                @can('customers.update')
                                                    <a href="{{ route('admin.customers.edit', $customer) }}">Edit</a>
                                                @endcan
                                                @can('interactions.create')
                                                    <a href="{{ route('admin.customers.interactions.create', $customer) }}">Add Interaction</a>
                                                @endcan
                                                <a href="{{ route('admin.customers.transactions', ['q' => $customer->name]) }}">View Transactions</a>
                                                @can('customers.delete')
                                                    <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Delete this customer?')">Delete</button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="lead-empty-state customer-profile-enterprise-empty" data-customer-filter-empty hidden>
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'user'])</span>
                    <strong>No customers found</strong>
                    <p>Create your first customer to start managing customer relationships.</p>
                    @can('customers.create')
                        <a href="{{ route('admin.customers.create') }}" class="btn btn-sm btn-primary">Add Customer</a>
                    @endcan
                </div>

                @if ($customers->hasPages())
                    <div class="customer-pagination lead-pagination customer-profile-pagination">
                        @if ($customers->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $customers->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($customers->getUrlRange(max(1, $customers->currentPage() - 2), min($customers->lastPage(), $customers->currentPage() + 2)) as $page => $url)
                            @if ($page === $customers->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($customers->hasMorePages())
                            <a href="{{ $customers->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                @endif
            @endif
        </section>

        <script>
            (() => {
                const form = document.querySelector('[data-customer-profile-search]');
                const search = form?.querySelector('input[type="search"]');
                let searchTimer;

                search?.addEventListener('input', () => {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(() => form.requestSubmit(), 450);
                });

                const tabs = Array.from(document.querySelectorAll('[data-customer-status-tab]'));
                const rows = Array.from(document.querySelectorAll('[data-customer-status]'));
                const empty = document.querySelector('[data-customer-filter-empty]');

                const syncEmptyState = () => {
                    if (! empty) {
                        return;
                    }

                    empty.hidden = rows.some((row) => ! row.hidden);
                };

                tabs.forEach((tab) => {
                    tab.addEventListener('click', () => {
                        const status = tab.dataset.customerStatusTab;

                        tabs.forEach((item) => item.classList.toggle('active', item === tab));
                        rows.forEach((row) => {
                            row.hidden = status !== 'all' && row.dataset.customerStatus !== status;
                        });
                        syncEmptyState();
                    });
                });
            })();
        </script>
    </section>
@endsection
