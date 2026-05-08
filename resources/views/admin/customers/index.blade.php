@extends('admin.layouts.app')

@section('title', 'Customer List - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'user'])
            </div>
            <div>
                <h1>Customer List</h1>
                <p>Daftar seluruh customer/contact dengan informasi dasar, status, owner, dan sumber data.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <form method="GET" action="{{ route('admin.customers.index') }}" class="customer-search-form">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari name, email, phone, company"
                        aria-label="Search customer"
                    >
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search)
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                <a href="{{ route('admin.customers.create') }}" class="btn btn-primary">Add Customer</a>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Company</th>
                            <th>Email</th>
                            <th>Phone/WhatsApp</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Owner</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->company_name ?: '-' }}</td>
                                <td>{{ $customer->email ?: '-' }}</td>
                                <td>
                                    <div>{{ $customer->phone ?: '-' }}</div>
                                    <small>{{ $customer->whatsapp ? 'WA: '.$customer->whatsapp : '-' }}</small>
                                </td>
                                <td>{{ $customer->source ?: '-' }}</td>
                                <td>
                                    <span class="status-badge status-{{ $customer->status }}">{{ ucfirst($customer->status) }}</span>
                                </td>
                                <td>{{ $customer->owner_name ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" onsubmit="return confirm('Delete customer ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">Belum ada customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $customers->firstItem() }}-{{ $customers->lastItem() }} dari {{ $customers->total() }} customer
                    </div>
                    <div class="pagination-links">
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
                </div>
            @endif
        </article>
    </section>
@endsection
