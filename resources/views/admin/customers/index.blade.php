@extends('admin.layouts.app')

@section('title', 'Customer List - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Customer List - Krakatau CRM" data-doc-title-id="Daftar Customer - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'user'])
            </div>
            <div>
                <h1 data-lang-en="Customer List" data-lang-id="Daftar Customer">Customer List</h1>
                <p data-lang-en="A full list of customers/contacts with basic information, status, owner, and data source." data-lang-id="Daftar seluruh customer/contact dengan informasi dasar, status, owner, dan sumber data.">Daftar seluruh customer/contact dengan informasi dasar, status, owner, dan sumber data.</p>
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
                        data-placeholder-en="Search name, email, phone, company"
                        data-placeholder-id="Cari name, email, phone, company"
                        data-title-en="Search customer"
                        data-title-id="Cari customer"
                    >
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($search)
                        <a href="{{ route('admin.customers.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </form>

                <a href="{{ route('admin.customers.create') }}" class="btn btn-primary" data-lang-en="Add Customer" data-lang-id="Tambah Customer">Add Customer</a>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Name" data-lang-id="Nama">Name</th>
                            <th data-lang-en="Company" data-lang-id="Perusahaan">Company</th>
                            <th data-lang-en="Email" data-lang-id="Email">Email</th>
                            <th data-lang-en="Phone/WhatsApp" data-lang-id="Telepon/WhatsApp">Phone/WhatsApp</th>
                            <th data-lang-en="Source" data-lang-id="Sumber">Source</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Owner" data-lang-id="Owner">Owner</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                        <a href="{{ route('admin.customers.show', $customer) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a>
                                        <a href="{{ route('admin.customers.edit', $customer) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.destroy', $customer) }}" data-confirm-en="Delete this customer?" data-confirm-id="Hapus customer ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus customer ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty" data-lang-en="No customers yet." data-lang-id="Belum ada customer.">Belum ada customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($customers->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $customers->firstItem() }}-{{ $customers->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $customers->total() }} <span data-lang-en="customers" data-lang-id="customer">customers</span>
                    </div>
                    <div class="pagination-links">
                        @if ($customers->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $customers->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($customers->getUrlRange(max(1, $customers->currentPage() - 2), min($customers->lastPage(), $customers->currentPage() + 2)) as $page => $url)
                            @if ($page === $customers->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($customers->hasMorePages())
                            <a href="{{ $customers->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
