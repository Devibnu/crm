@extends('admin.layouts.app')

@section('title', 'Case Resolution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'case'])
            </div>
            <div>
                <h1>Case Resolution</h1>
                <p>Catat dan kelola penyelesaian kasus layanan pelanggan.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Resolutions</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua penyelesaian kasus</small>
            </article>
            <article class="card sales-summary-card">
                <span>Fixed Cases</span>
                <strong>{{ number_format($summary['fixed']) }}</strong>
                <small>Diselesaikan permanen</small>
            </article>
            <article class="card sales-summary-card">
                <span>Escalated Cases</span>
                <strong>{{ number_format($summary['escalated']) }}</strong>
                <small>Diteruskan untuk follow-up</small>
            </article>
            <article class="card sales-summary-card">
                <span>Customer Notified</span>
                <strong>{{ number_format($summary['customer_notified']) }}</strong>
                <small>Pelanggan sudah diberi kabar</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Resolution List</h2>
                    <p>Search ticket number, subject, summary, atau resolver.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.service.case-resolutions.create') }}" class="btn btn-primary">Add Resolution</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.case-resolutions.index') }}" class="case-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Ticket, subject, summary, resolved by">
                </label>
                <label class="field">
                    <span>Resolution Type</span>
                    <select name="resolution_type">
                        <option value="">Semua type</option>
                        @foreach ($resolutionTypeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedResolutionType === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Customer Notified</span>
                    <select name="customer_notified">
                        <option value="">Semua status</option>
                        @foreach ($customerNotifiedOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCustomerNotified === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedResolutionType || $selectedCustomerNotified)
                        <a href="{{ route('admin.service.case-resolutions.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Ticket</th>
                            <th>Resolution Summary</th>
                            <th>Type</th>
                            <th>Resolved By</th>
                            <th>Resolved At</th>
                            <th>Customer Notified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($resolutions as $resolution)
                            <tr>
                                <td>
                                    <strong class="sales-code">{{ $resolution->ticket?->ticket_number ?: '-' }}</strong>
                                    <small>{{ $resolution->ticket?->subject ?: '-' }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.service.case-resolutions.show', $resolution) }}" class="sales-title-link">{{ $resolution->resolution_summary }}</a>
                                </td>
                                <td><span class="status-badge resolution-{{ $resolution->resolution_type }}">{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</span></td>
                                <td>{{ $resolution->resolved_by ?: '-' }}</td>
                                <td>{{ $resolution->resolved_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td><span class="status-badge status-{{ $resolution->customer_notified ? 'active' : 'inactive' }}">{{ $resolution->customer_notified ? 'Notified' : 'Not notified' }}</span></td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.service.case-resolutions.show', $resolution) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.service.case-resolutions.edit', $resolution) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.service.case-resolutions.destroy', $resolution) }}" onsubmit="return confirm('Delete case resolution ini?');">
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
                                        <strong>Belum ada case resolution</strong>
                                        <span>Tambahkan penyelesaian kasus pertama untuk melacak hasil penanganan tiket.</span>
                                        <a href="{{ route('admin.service.case-resolutions.create') }}" class="btn btn-primary">Add Resolution</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($resolutions->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $resolutions->firstItem() }}-{{ $resolutions->lastItem() }} dari {{ $resolutions->total() }} resolution
                    </div>
                    <div class="pagination-links">
                        @if ($resolutions->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $resolutions->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($resolutions->getUrlRange(max(1, $resolutions->currentPage() - 2), min($resolutions->lastPage(), $resolutions->currentPage() + 2)) as $page => $url)
                            @if ($page === $resolutions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($resolutions->hasMorePages())
                            <a href="{{ $resolutions->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
