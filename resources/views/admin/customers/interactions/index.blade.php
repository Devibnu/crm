@extends('admin.layouts.app')

@section('title', 'Interaction History - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Interaction History - Krakatau CRM" data-doc-title-id="Riwayat Interaksi - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'mail'])
            </div>
            <div>
                <h1 data-lang-en="Interaction History" data-lang-id="Riwayat Interaksi">Interaction History</h1>
                <p data-lang-en="Customer interaction history: calls, WhatsApp, email, meetings, notes, and follow-ups." data-lang-id="Riwayat interaksi customer: call, WhatsApp, email, meeting, note, dan follow-up.">Riwayat interaksi customer: call, WhatsApp, email, meeting, note, dan follow-up.</p>
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
                        data-placeholder-en="Search customer, subject, description, handled by"
                        data-placeholder-id="Cari customer, subject, description, handled by"
                        data-title-en="Search interaction"
                        data-title-id="Cari interaksi"
                    >
                    <select name="type" aria-label="Filter type" data-title-en="Filter type" data-title-id="Filter tipe">
                        <option value="" data-lang-en="All types" data-lang-id="Semua tipe">Semua type</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($search || $selectedType)
                        <a href="{{ route('admin.customers.interactions') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </form>

                @if ($firstCustomerId)
                    <a href="{{ route('admin.customers.interactions.create', ['customer' => $firstCustomerId]) }}" class="btn btn-primary" data-lang-en="Add Interaction" data-lang-id="Tambah Interaksi">Add Interaction</a>
                @else
                    <span class="btn btn-disabled" data-lang-en="Add Interaction" data-lang-id="Tambah Interaksi">Add Interaction</span>
                @endif
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Customer" data-lang-id="Customer">Customer</th>
                            <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                            <th data-lang-en="Subject" data-lang-id="Subjek">Subject</th>
                            <th data-lang-en="Date" data-lang-id="Tanggal">Date</th>
                            <th data-lang-en="Handled By" data-lang-id="Ditangani Oleh">Handled By</th>
                            <th data-lang-en="Outcome" data-lang-id="Hasil">Outcome</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                        <a href="{{ route('admin.customers.interactions.edit', $interaction) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.customers.interactions.destroy', $interaction) }}" data-confirm-en="Delete this interaction?" data-confirm-id="Hapus interaksi ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus interaksi ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty" data-lang-en="No interactions yet." data-lang-id="Belum ada interaksi.">Belum ada interaksi.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($interactions->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $interactions->firstItem() }}-{{ $interactions->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $interactions->total() }} <span data-lang-en="interactions" data-lang-id="interaksi">interactions</span>
                    </div>
                    <div class="pagination-links">
                        @if ($interactions->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $interactions->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($interactions->getUrlRange(max(1, $interactions->currentPage() - 2), min($interactions->lastPage(), $interactions->currentPage() + 2)) as $page => $url)
                            @if ($page === $interactions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($interactions->hasMorePages())
                            <a href="{{ $interactions->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
