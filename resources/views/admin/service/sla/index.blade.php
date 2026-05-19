@extends('admin.layouts.app')

@section('title', 'SLA Management - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'timer'])
            </div>
            <div>
                <h1>SLA Management</h1>
                <p>Kelola aturan waktu respons dan penyelesaian tiket layanan pelanggan.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total SLA Policies</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua aturan SLA</small>
            </article>
            <article class="card sales-summary-card">
                <span>Active Policies</span>
                <strong>{{ number_format($summary['active']) }}</strong>
                <small>Siap diterapkan</small>
            </article>
            <article class="card sales-summary-card">
                <span>High/Urgent Policies</span>
                <strong>{{ number_format($summary['high_urgent']) }}</strong>
                <small>Prioritas kritikal</small>
            </article>
            <article class="card sales-summary-card">
                <span>Average Resolution Time</span>
                <strong>{{ number_format($summary['average_resolution'], 0) }} min</strong>
                <small>Rata-rata target penyelesaian</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>SLA Policy List</h2>
                    <p>Search name atau description, lalu filter berdasarkan priority dan active status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.service.sla.create') }}" class="btn btn-primary">Add SLA Policy</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.sla.index') }}" class="sla-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name or description">
                </label>
                <label class="field">
                    <span>Priority</span>
                    <select name="priority">
                        <option value="">Semua priority</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="is_active">
                        <option value="">Semua status</option>
                        @foreach ($activeOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedActive === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedPriority || $selectedActive)
                        <a href="{{ route('admin.service.sla.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Priority</th>
                            <th>Response Target</th>
                            <th>Resolution Target</th>
                            <th>Status</th>
                            <th>Updated At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($policies as $policy)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.service.sla.show', $policy) }}" class="sales-title-link">{{ $policy->name }}</a>
                                    <small>{{ $policy->description ?: '-' }}</small>
                                </td>
                                <td><span class="status-badge priority-{{ $policy->priority }}">{{ ucfirst($policy->priority) }}</span></td>
                                <td>{{ number_format($policy->response_time_minutes) }} min</td>
                                <td>{{ number_format($policy->resolution_time_minutes) }} min</td>
                                <td><span class="status-badge status-{{ $policy->is_active ? 'active' : 'inactive' }}">{{ $policy->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td>{{ $policy->updated_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.service.sla.show', $policy) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.service.sla.edit', $policy) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.service.sla.destroy', $policy) }}" onsubmit="return confirm('Delete SLA policy ini?');">
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
                                        <strong>Belum ada SLA policy</strong>
                                        <span>Tambahkan aturan SLA pertama untuk mengatur target response dan resolution time.</span>
                                        <a href="{{ route('admin.service.sla.create') }}" class="btn btn-primary">Add SLA Policy</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($policies->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $policies->firstItem() }}-{{ $policies->lastItem() }} dari {{ $policies->total() }} policy
                    </div>
                    <div class="pagination-links">
                        @if ($policies->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $policies->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($policies->getUrlRange(max(1, $policies->currentPage() - 2), min($policies->lastPage(), $policies->currentPage() + 2)) as $page => $url)
                            @if ($page === $policies->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($policies->hasMorePages())
                            <a href="{{ $policies->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
