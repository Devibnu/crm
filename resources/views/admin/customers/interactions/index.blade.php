@extends('admin.layouts.app')

@section('title', 'Interaction History - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'mail'])
            </div>
            <div>
                <h1>Interaction History</h1>
                <p>Riwayat interaksi customer: call, WhatsApp, email, meeting, note, dan follow-up.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <form method="GET" action="{{ route('admin.customers.interactions') }}" class="customer-search-form">
                    <input
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="Cari customer, subject, description, handled by"
                        aria-label="Search interaction"
                    >
                    <select name="type" aria-label="Filter type">
                        <option value="">Semua type</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedType)
                        <a href="{{ route('admin.customers.interactions') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                @if ($firstCustomerId)
                    <a href="{{ route('admin.customers.interactions.create', ['customer' => $firstCustomerId]) }}" class="btn btn-primary">Add Interaction</a>
                @else
                    <span class="btn btn-disabled">Add Interaction</span>
                @endif
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Date</th>
                            <th>Handled By</th>
                            <th>Outcome</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($interactions as $interaction)
                            <tr>
                                <td>{{ $interaction->customer?->name ?: '-' }}</td>
                                <td>
                                    <span class="status-badge status-new">{{ ucwords(str_replace('_', ' ', $interaction->type)) }}</span>
                                </td>
                                <td>
                                    <div>{{ $interaction->subject }}</div>
                                    <small>{{ \Illuminate\Support\Str::limit($interaction->description ?: '-', 70) }}</small>
                                </td>
                                <td>{{ $interaction->interaction_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $interaction->handled_by ?: '-' }}</td>
                                <td>{{ $interaction->outcome ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.customers.interactions.edit', $interaction) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.interactions.destroy', $interaction) }}" onsubmit="return confirm('Delete interaction ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">Belum ada interaction.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($interactions->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $interactions->firstItem() }}-{{ $interactions->lastItem() }} dari {{ $interactions->total() }} interaction
                    </div>
                    <div class="pagination-links">
                        @if ($interactions->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $interactions->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($interactions->getUrlRange(max(1, $interactions->currentPage() - 2), min($interactions->lastPage(), $interactions->currentPage() + 2)) as $page => $url)
                            @if ($page === $interactions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($interactions->hasMorePages())
                            <a href="{{ $interactions->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
