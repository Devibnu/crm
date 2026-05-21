@extends('admin.layouts.app')

@section('title', 'WhatsApp Providers - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>WhatsApp Providers</h1>
                <p>Kelola koneksi provider WhatsApp untuk broadcast, inbox, dan automation CRM.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <section class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Providers</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua provider terdaftar</small>
            </article>
            <article class="card sales-summary-card">
                <span>Active Providers</span>
                <strong>{{ number_format($summary['active']) }}</strong>
                <small>Provider siap digunakan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Default Provider</span>
                <strong>{{ $summary['default'] }}</strong>
                <small>Dipakai sebagai koneksi utama</small>
            </article>
            <article class="card sales-summary-card">
                <span>Last Connected</span>
                <strong>{{ $summary['last_connected'] ? \Illuminate\Support\Carbon::parse($summary['last_connected'])->format('d M Y H:i') : '-' }}</strong>
                <small>Koneksi terakhir tercatat</small>
            </article>
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Provider List</h2>
                    <p>Atur provider aktif dan default tanpa mengubah business logic CRM.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.system.whatsapp-providers.create') }}" class="btn btn-primary">Add Provider</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.system.whatsapp-providers.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or provider" aria-label="Search providers">
                </label>
                <label class="field">
                    <span>Provider</span>
                    <select name="provider">
                        <option value="">All providers</option>
                        @foreach ($providerOptions as $provider)
                            <option value="{{ $provider }}" @selected($selectedProvider === $provider)>{{ strtoupper($provider) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($search || $selectedProvider || $selectedStatus)
                        <a href="{{ route('admin.system.whatsapp-providers.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Provider Name</th>
                            <th>Provider Type</th>
                            <th>Device ID</th>
                            <th>Status</th>
                            <th>Default</th>
                            <th>Last Connected</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($providers as $provider)
                            <tr>
                                <td><a href="{{ route('admin.system.whatsapp-providers.show', $provider) }}" class="sales-title-link">{{ $provider->name }}</a></td>
                                <td><span class="status-badge type-{{ $provider->provider }}">{{ strtoupper($provider->provider) }}</span></td>
                                <td>{{ $provider->device_id ?: '-' }}</td>
                                <td><span class="status-badge status-{{ $provider->status }}">{{ ucfirst($provider->status) }}</span></td>
                                <td><span class="status-badge status-{{ $provider->is_default ? 'active' : 'inactive' }}">{{ $provider->is_default ? 'Yes' : 'No' }}</span></td>
                                <td>{{ $provider->last_connected_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.system.whatsapp-providers.show', $provider) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.system.whatsapp-providers.edit', $provider) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.system.whatsapp-providers.destroy', $provider) }}" onsubmit="return confirm('Delete provider ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada WhatsApp provider</strong>
                                        <span>Tambahkan provider pertama untuk menyiapkan integrasi WhatsApp CRM.</span>
                                        <a href="{{ route('admin.system.whatsapp-providers.create') }}" class="btn btn-primary">Add Provider</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($providers->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $providers->firstItem() }}-{{ $providers->lastItem() }} dari {{ $providers->total() }} provider
                    </div>
                    <div class="pagination-links">
                        @if ($providers->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $providers->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($providers->getUrlRange(max(1, $providers->currentPage() - 2), min($providers->lastPage(), $providers->currentPage() + 2)) as $page => $url)
                            @if ($page === $providers->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($providers->hasMorePages())
                            <a href="{{ $providers->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
