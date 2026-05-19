@extends('admin.layouts.app')

@section('title', 'Opportunity Management - Krakatau CRM')

@section('content')
    @php
        $tx = static fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
        $opportunityCollection = $opportunities->getCollection();
        $activeFilter = $search || $selectedStatus;
        $openPipelineCount = $opportunityCollection->filter(fn ($opportunity) => in_array($opportunity->status, ['open', 'proposal', 'negotiation'], true))->count();
        $wonOpportunityCount = $opportunityCollection->where('status', 'won')->count();
        $pipelineValue = $opportunityCollection->sum(fn ($opportunity) => (float) $opportunity->estimated_value);
        $summaryCardItems = [
            [
                'label' => $tx('Total Opportunities', 'Total Opportunity'),
                'value' => number_format($opportunities->total()),
                'hint' => $tx('All opportunity records in workspace', 'Seluruh record opportunity di workspace'),
            ],
            [
                'label' => $tx('Visible Results', 'Hasil Tampil'),
                'value' => number_format($opportunityCollection->count()),
                'hint' => $tx('Based on current search and status filter', 'Berdasarkan pencarian dan filter status saat ini'),
            ],
            [
                'label' => $tx('Open Pipeline', 'Pipeline Aktif'),
                'value' => number_format($openPipelineCount),
                'hint' => $tx(number_format($wonOpportunityCount).' won deals on this page', number_format($wonOpportunityCount).' deal won pada halaman ini'),
            ],
            [
                'label' => $tx('Estimated Value', 'Estimasi Nilai'),
                'value' => 'Rp '.number_format($pipelineValue, 0, ',', '.'),
                'hint' => $tx('Visible pipeline value on this page', 'Nilai pipeline yang tampil pada halaman ini'),
            ],
        ];
    @endphp
    <span hidden data-doc-title-en="Opportunity Management - Krakatau CRM" data-doc-title-id="Manajemen Opportunity - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-opportunities-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'opportunity'])
            </div>
            <div>
                <span class="service-badge dashboard-hero-badge" data-lang-en="Pipeline Momentum" data-lang-id="Momentum Pipeline">Pipeline Momentum</span>
                <h1 data-lang-en="Opportunity Management" data-lang-id="Manajemen Opportunity">Opportunity Management</h1>
                <p data-lang-en="Manage business opportunities and discovery processes." data-lang-id="Kelola peluang bisnis dan proses discovery.">Kelola peluang bisnis dan proses discovery.</p>
            </div>
        </article>

        <section class="sales-summary-grid">
            @foreach ($summaryCardItems as $card)
                <article class="card sales-summary-card">
                    <span data-lang-en="{{ $card['label']['en'] }}" data-lang-id="{{ $card['label']['id'] }}">{{ $card['label']['en'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small data-lang-en="{{ $card['hint']['en'] }}" data-lang-id="{{ $card['hint']['id'] }}">{{ $card['hint']['en'] }}</small>
                </article>
            @endforeach
        </section>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-table-card sales-opportunities-shell">
            <div class="sales-section-head sales-opportunities-head">
                <div>
                    <h2 data-lang-en="Opportunity Directory" data-lang-id="Direktori Opportunity">Opportunity Directory</h2>
                    <p data-lang-en="Track active pipeline, expected close timing, owner assignment, and revenue potential." data-lang-id="Pantau pipeline aktif, estimasi closing, penugasan owner, dan potensi pendapatan.">Track active pipeline, expected close timing, owner assignment, and revenue potential.</p>
                </div>
                <a href="{{ route('admin.sales.opportunities.create') }}" class="btn btn-primary" data-lang-en="Add Opportunity" data-lang-id="Tambah Opportunity">Add Opportunity</a>
            </div>

            <form method="GET" action="{{ route('admin.sales.opportunities') }}" class="sales-filter-form sales-opportunities-filter-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari title, company, contact, assigned" aria-label="Search opportunities" data-placeholder-en="Search title, company, contact, assigned" data-placeholder-id="Cari title, company, contact, assigned" data-title-en="Search opportunities" data-title-id="Cari opportunity">
                <select name="status" aria-label="Filter status" data-title-en="Filter status" data-title-id="Filter status">
                    <option value="" data-lang-en="All statuses" data-lang-id="Semua status">Semua status</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($activeFilter)
                        <a href="{{ route('admin.sales.opportunities') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table sales-opportunities-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                            <th data-lang-en="Company" data-lang-id="Perusahaan">Company</th>
                            <th data-lang-en="Contact" data-lang-id="Kontak">Contact</th>
                            <th data-lang-en="Estimated Value" data-lang-id="Estimasi Nilai">Estimated Value</th>
                            <th data-lang-en="Probability" data-lang-id="Probabilitas">Probability</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Expected Close" data-lang-id="Estimasi Closing">Expected Close</th>
                            <th data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($opportunities as $opportunity)
                            <tr>
                                <td>
                                    <div class="sales-opportunities-title-cell">
                                        <strong>{{ $opportunity->title }}</strong>
                                    <small>
                                        @if ($opportunity->lead?->name)
                                            <span data-lang-en="Lead" data-lang-id="Lead">Lead</span>: {{ $opportunity->lead->name }}
                                        @elseif ($opportunity->customer?->name)
                                            <span data-lang-en="Customer" data-lang-id="Customer">Customer</span>: {{ $opportunity->customer->name }}
                                        @else
                                            -
                                        @endif
                                    </small>
                                    </div>
                                </td>
                                <td><span class="sales-source-pill">{{ $opportunity->company_name ?: '-' }}</span></td>
                                <td>{{ $opportunity->contact_name ?: '-' }}</td>
                                <td class="sales-amount">Rp {{ number_format((float) $opportunity->estimated_value, 2, ',', '.') }}</td>
                                <td><span class="sales-probability-pill">{{ $opportunity->probability }}%</span></td>
                                <td><span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span></td>
                                <td>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</td>
                                <td><span class="sales-assignee-pill">{{ $opportunity->assigned_to ?: '-' }}</span></td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a>
                                        <a href="{{ route('admin.sales.opportunities.edit', $opportunity) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.sales.opportunities.destroy', $opportunity) }}" data-confirm-en="Delete this opportunity?" data-confirm-id="Hapus opportunity ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus opportunity ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">
                                    <div class="sales-empty-state compact">
                                        <strong data-lang-en="No opportunities yet" data-lang-id="Belum ada opportunity">No opportunities yet</strong>
                                        <span data-lang-en="Add the first opportunity to start tracking deal stages, owner assignment, and projected value." data-lang-id="Tambahkan opportunity pertama untuk mulai melacak stage deal, assignment owner, dan nilai proyeksi.">Add the first opportunity to start tracking deal stages, owner assignment, and projected value.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($opportunities->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $opportunities->firstItem() }}-{{ $opportunities->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $opportunities->total() }} <span data-lang-en="opportunities" data-lang-id="opportunity">opportunities</span>
                    </div>
                    <div class="pagination-links">
                        @if ($opportunities->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $opportunities->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($opportunities->getUrlRange(max(1, $opportunities->currentPage() - 2), min($opportunities->lastPage(), $opportunities->currentPage() + 2)) as $page => $url)
                            @if ($page === $opportunities->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($opportunities->hasMorePages())
                            <a href="{{ $opportunities->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
