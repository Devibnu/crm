@extends('admin.layouts.app')

@section('title', 'Case Resolution - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Case Resolution - Krakatau CRM" data-doc-title-id="Penyelesaian Kasus - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'case'])
            </div>
            <div>
                <h1 data-lang-en="Case Resolution" data-lang-id="Penyelesaian Kasus">Case Resolution</h1>
                <p data-lang-en="Record and manage customer service case resolutions." data-lang-id="Catat dan kelola penyelesaian kasus layanan pelanggan.">Catat dan kelola penyelesaian kasus layanan pelanggan.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Resolutions" data-lang-id="Total Penyelesaian">Total Resolutions</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All case resolutions" data-lang-id="Semua penyelesaian kasus">All case resolutions</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Fixed Cases" data-lang-id="Kasus Selesai">Fixed Cases</span>
                <strong>{{ number_format($summary['fixed']) }}</strong>
                <small data-lang-en="Resolved permanently" data-lang-id="Diselesaikan permanen">Resolved permanently</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Escalated Cases" data-lang-id="Kasus Eskalasi">Escalated Cases</span>
                <strong>{{ number_format($summary['escalated']) }}</strong>
                <small data-lang-en="Escalated for follow-up" data-lang-id="Diteruskan untuk follow-up">Escalated for follow-up</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Customer Notified" data-lang-id="Customer Diberi Kabar">Customer Notified</span>
                <strong>{{ number_format($summary['customer_notified']) }}</strong>
                <small data-lang-en="Customers already informed" data-lang-id="Pelanggan sudah diberi kabar">Customers already informed</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Resolution List" data-lang-id="Daftar Penyelesaian">Resolution List</h2>
                    <p data-lang-en="Search ticket number, subject, summary, or resolver." data-lang-id="Search ticket number, subject, summary, atau resolver.">Search ticket number, subject, summary, atau resolver.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.service.case-resolutions.create') }}" class="btn btn-primary" data-lang-en="Add Resolution" data-lang-id="Tambah Penyelesaian">Add Resolution</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.case-resolutions.index') }}" class="case-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Ticket, subject, summary, resolved by" data-placeholder-en="Ticket, subject, summary, resolved by" data-placeholder-id="Ticket, subject, summary, resolved by">
                </label>
                <label class="field">
                    <span data-lang-en="Resolution Type" data-lang-id="Tipe Penyelesaian">Resolution Type</span>
                    <select name="resolution_type">
                        <option value="" data-lang-en="All types" data-lang-id="Semua tipe">Semua type</option>
                        @foreach ($resolutionTypeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedResolutionType === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Customer Notified" data-lang-id="Customer Diberi Kabar">Customer Notified</span>
                    <select name="customer_notified">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">Semua status</option>
                        @foreach ($customerNotifiedOptions as $value => $label)
                            <option value="{{ $value }}" @selected($selectedCustomerNotified === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($search || $selectedResolutionType || $selectedCustomerNotified)
                        <a href="{{ route('admin.service.case-resolutions.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Ticket" data-lang-id="Tiket">Ticket</th>
                            <th data-lang-en="Resolution Summary" data-lang-id="Ringkasan Penyelesaian">Resolution Summary</th>
                            <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                            <th data-lang-en="Resolved By" data-lang-id="Diselesaikan Oleh">Resolved By</th>
                            <th data-lang-en="Resolved At" data-lang-id="Diselesaikan Pada">Resolved At</th>
                            <th data-lang-en="Customer Notified" data-lang-id="Customer Diberi Kabar">Customer Notified</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                <td><span class="status-badge status-{{ $resolution->customer_notified ? 'active' : 'inactive' }}"><span data-lang-en="{{ $resolution->customer_notified ? 'Notified' : 'Not notified' }}" data-lang-id="{{ $resolution->customer_notified ? 'Sudah dikabari' : 'Belum dikabari' }}">{{ $resolution->customer_notified ? 'Notified' : 'Not notified' }}</span></span></td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.service.case-resolutions.show', $resolution) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a>
                                        <a href="{{ route('admin.service.case-resolutions.edit', $resolution) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.service.case-resolutions.destroy', $resolution) }}" data-confirm-en="Delete this case resolution?" data-confirm-id="Hapus penyelesaian kasus ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus penyelesaian kasus ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No case resolutions yet" data-lang-id="Belum ada penyelesaian kasus">No case resolutions yet</strong>
                                        <span data-lang-en="Add the first case resolution to track ticket handling outcomes." data-lang-id="Tambahkan penyelesaian kasus pertama untuk melacak hasil penanganan tiket.">Add the first case resolution to track ticket handling outcomes.</span>
                                        <a href="{{ route('admin.service.case-resolutions.create') }}" class="btn btn-primary" data-lang-en="Add Resolution" data-lang-id="Tambah Penyelesaian">Add Resolution</a>
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
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $resolutions->firstItem() }}-{{ $resolutions->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $resolutions->total() }} <span data-lang-en="resolutions" data-lang-id="penyelesaian">resolutions</span>
                    </div>
                    <div class="pagination-links">
                        @if ($resolutions->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $resolutions->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($resolutions->getUrlRange(max(1, $resolutions->currentPage() - 2), min($resolutions->lastPage(), $resolutions->currentPage() + 2)) as $page => $url)
                            @if ($page === $resolutions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($resolutions->hasMorePages())
                            <a href="{{ $resolutions->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
