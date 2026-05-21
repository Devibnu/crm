@extends('admin.layouts.app')

@section('title', 'Customer List - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        @include('admin.customers._success-toast')

        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'user'])
            </div>
            <div>
                <h1>Customer List</h1>
                <p>Daftar seluruh customer/contact dengan informasi dasar, status, owner, dan sumber data.</p>
            </div>
        </article>

        <article class="card customer-table-card">
            @if (session('success'))
                <div class="alert alert-success customer-success-fallback">{{ session('success') }}</div>
            @endif

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
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-danger js-open-customer-delete-modal"
                                            data-delete-action="{{ route('admin.customers.destroy', $customer) }}"
                                            data-customer-name="{{ $customer->name }}"
                                        >Delete</button>
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

        <div class="crm-modal-backdrop" data-customer-delete-modal hidden>
            <div class="crm-confirm-modal" role="dialog" aria-modal="true" aria-labelledby="customer-delete-modal-title">
                <div class="crm-confirm-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24"><path d="M12 3 2.5 20h19z"/><path d="M12 9v5"/><path d="M12 17h.01"/></svg>
                </div>
                <div class="crm-confirm-content">
                    <h2 id="customer-delete-modal-title">Hapus Customer?</h2>
                    <p>Data customer akan dihapus dari CRM. Tindakan ini tidak dapat dibatalkan.</p>
                    <div class="crm-confirm-target">
                        <span>Customer</span>
                        <strong data-customer-delete-name>-</strong>
                    </div>
                </div>
                <form method="POST" action="#" data-customer-delete-form class="crm-confirm-actions">
                    @csrf
                    @method('DELETE')
                    <button type="button" class="btn btn-muted" data-customer-delete-cancel>Batal</button>
                    <button type="submit" class="btn btn-danger">Ya, Hapus</button>
                </form>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-customer-delete-modal]');
            const form = document.querySelector('[data-customer-delete-form]');
            const nameTarget = document.querySelector('[data-customer-delete-name]');
            const cancelButton = document.querySelector('[data-customer-delete-cancel]');
            const openButtons = document.querySelectorAll('.js-open-customer-delete-modal');

            if (!modal || !form || !nameTarget || !cancelButton) {
                return;
            }

            const closeModal = () => {
                modal.hidden = true;
                form.action = '#';
                nameTarget.textContent = '-';
            };

            openButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.deleteAction;
                    nameTarget.textContent = button.dataset.customerName || '-';
                    modal.hidden = false;
                    cancelButton.focus();
                });
            });

            cancelButton.addEventListener('click', closeModal);
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.hidden) {
                    closeModal();
                }
            });
        });
    </script>
@endsection
