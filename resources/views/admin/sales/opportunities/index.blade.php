@extends('admin.layouts.app')

@section('title', 'Opportunity Management - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'opportunity'])
            </div>
            <div>
                <h1>Opportunity Management</h1>
                <p>Kelola peluang bisnis dan proses discovery.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <form method="GET" action="{{ route('admin.sales.opportunities') }}" class="customer-search-form">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari title, company, contact, assigned" aria-label="Search opportunities">
                    <select name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-muted">Reset</a>
                    @endif
                </form>

                <a href="{{ route('admin.sales.opportunities.create') }}" class="btn btn-primary">Add Opportunity</a>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Contact</th>
                            <th>Estimated Value</th>
                            <th>Probability</th>
                            <th>Status</th>
                            <th>Expected Close</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($opportunities as $opportunity)
                            <tr>
                                <td>
                                    <div>{{ $opportunity->title }}</div>
                                    <small>
                                        @if ($opportunity->lead?->name)
                                            Lead: {{ $opportunity->lead->name }}
                                        @elseif ($opportunity->customer?->name)
                                            Customer: {{ $opportunity->customer->name }}
                                        @else
                                            -
                                        @endif
                                    </small>
                                </td>
                                <td>{{ $opportunity->company_name ?: '-' }}</td>
                                <td>{{ $opportunity->contact_name ?: '-' }}</td>
                                <td>Rp {{ number_format((float) $opportunity->estimated_value, 2, ',', '.') }}</td>
                                <td>{{ $opportunity->probability }}%</td>
                                <td><span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span></td>
                                <td>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $opportunity->assigned_to ?: '-' }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.sales.opportunities.edit', $opportunity) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.sales.opportunities.destroy', $opportunity) }}" onsubmit="return confirm('Delete opportunity ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">Belum ada opportunity.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($opportunities->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $opportunities->firstItem() }}-{{ $opportunities->lastItem() }} dari {{ $opportunities->total() }} opportunity
                    </div>
                    <div class="pagination-links">
                        @if ($opportunities->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $opportunities->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($opportunities->getUrlRange(max(1, $opportunities->currentPage() - 2), min($opportunities->lastPage(), $opportunities->currentPage() + 2)) as $page => $url)
                            @if ($page === $opportunities->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($opportunities->hasMorePages())
                            <a href="{{ $opportunities->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
