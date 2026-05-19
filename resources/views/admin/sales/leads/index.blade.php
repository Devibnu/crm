@extends('admin.layouts.app')

@section('title', 'Lead Management - Krakatau CRM')

@section('content')
    @php
        $tx = static fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
        $leadCollection = $leads->getCollection();
        $activeFilter = $search || $selectedStatus || $selectedPriority;
        $qualifiedLeadCount = $leadCollection->filter(fn ($lead) => in_array($lead->status, ['qualified', 'converted'], true))->count();
        $highPriorityCount = $leadCollection->filter(fn ($lead) => in_array($lead->priority, ['high', 'urgent'], true))->count();
        $assignedLeadCount = $leadCollection->filter(fn ($lead) => filled($lead->assigned_to))->count();
        $summaryCardItems = [
            [
                'label' => $tx('Total Leads', 'Total Lead'),
                'value' => number_format($leads->total()),
                'hint' => $tx('All lead records in workspace', 'Seluruh record lead di workspace'),
            ],
            [
                'label' => $tx('Visible Results', 'Hasil Tampil'),
                'value' => number_format($leadCollection->count()),
                'hint' => $tx('Based on current search and filters', 'Berdasarkan pencarian dan filter saat ini'),
            ],
            [
                'label' => $tx('Qualified Pipeline', 'Pipeline Qualified'),
                'value' => number_format($qualifiedLeadCount),
                'hint' => $tx('Qualified and converted leads on this page', 'Lead qualified dan converted pada halaman ini'),
            ],
            [
                'label' => $tx('High Priority', 'Prioritas Tinggi'),
                'value' => number_format($highPriorityCount),
                'hint' => $tx(number_format($assignedLeadCount).' already assigned', number_format($assignedLeadCount).' sudah ditugaskan'),
            ],
        ];
    @endphp
    <span hidden data-doc-title-en="Lead Management - Krakatau CRM" data-doc-title-id="Manajemen Lead - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace sales-leads-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lead'])
            </div>
            <div>
                <span class="service-badge dashboard-hero-badge" data-lang-en="Pipeline Intake" data-lang-id="Pintu Masuk Pipeline">Pipeline Intake</span>
                <h1 data-lang-en="Lead Management" data-lang-id="Manajemen Lead">Lead Management</h1>
                <p data-lang-en="Manage lead data: capture, assign, and qualification." data-lang-id="Kelola data lead: capture, assign, dan kualifikasi.">Kelola data lead: capture, assign, dan kualifikasi.</p>
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

        <article class="card customer-table-card sales-leads-shell">
            <div class="sales-section-head sales-leads-head">
                <div>
                    <h2 data-lang-en="Lead Directory" data-lang-id="Direktori Lead">Lead Directory</h2>
                    <p data-lang-en="Track incoming leads, qualification status, owner assignment, and conversion readiness." data-lang-id="Pantau lead masuk, status kualifikasi, penugasan owner, dan kesiapan konversi.">Track incoming leads, qualification status, owner assignment, and conversion readiness.</p>
                </div>
                <a href="{{ route('admin.sales.leads.create') }}" class="btn btn-primary" data-lang-en="Add Lead" data-lang-id="Tambah Lead">Add Lead</a>
            </div>

            <form method="GET" action="{{ route('admin.sales.leads') }}" class="sales-filter-form sales-leads-filter-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari name, company, email, phone, assigned" aria-label="Search leads" data-placeholder-en="Search name, company, email, phone, assigned" data-placeholder-id="Cari name, company, email, phone, assigned" data-title-en="Search leads" data-title-id="Cari lead">
                <select name="status" aria-label="Filter status" data-title-en="Filter status" data-title-id="Filter status">
                    <option value="" data-lang-en="All statuses" data-lang-id="Semua status">Semua status</option>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <select name="priority" aria-label="Filter priority" data-title-en="Filter priority" data-title-id="Filter prioritas">
                    <option value="" data-lang-en="All priorities" data-lang-id="Semua prioritas">Semua priority</option>
                    @foreach ($priorityOptions as $priority)
                        <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Search</button>
                    @if ($activeFilter)
                        <a href="{{ route('admin.sales.leads') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table sales-leads-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Lead Name" data-lang-id="Nama Lead">Lead Name</th>
                            <th data-lang-en="Company" data-lang-id="Perusahaan">Company</th>
                            <th data-lang-en="Contact" data-lang-id="Kontak">Contact</th>
                            <th data-lang-en="Source" data-lang-id="Sumber">Source</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Priority" data-lang-id="Prioritas">Priority</th>
                            <th data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            <tr>
                                <td>
                                    <div class="sales-leads-name-cell">
                                        <strong>{{ $lead->name }}</strong>
                                        <small>@if ($lead->customer?->name)<span data-lang-en="Customer" data-lang-id="Customer">Customer</span>: {{ $lead->customer->name }}@else-@endif</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="sales-leads-company-cell">
                                        <strong>{{ $lead->company_name ?: '-' }}</strong>
                                        <small @if (! $lead->assigned_to) data-lang-en="Unassigned" data-lang-id="Belum ditugaskan" @endif>{{ $lead->assigned_to ?: 'Unassigned' }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $lead->email ?: '-' }}</div>
                                    <small>{{ $lead->phone ?: '-' }}</small>
                                </td>
                                <td><span class="sales-source-pill">{{ $lead->source ?: '-' }}</span></td>
                                <td><span class="status-badge status-{{ $lead->status }}">{{ ucfirst($lead->status) }}</span></td>
                                <td><span class="status-badge priority-{{ $lead->priority }}">{{ ucfirst($lead->priority) }}</span></td>
                                <td><span class="sales-assignee-pill">{{ $lead->assigned_to ?: '-' }}</span></td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.sales.leads.show', $lead) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a>
                                        <a href="{{ route('admin.sales.leads.edit', $lead) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        <form method="POST" action="{{ route('admin.sales.leads.destroy', $lead) }}" data-confirm-en="Delete this lead?" data-confirm-id="Hapus lead ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus lead ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">
                                    <div class="sales-empty-state compact">
                                        <strong data-lang-en="No leads yet" data-lang-id="Belum ada lead">No leads yet</strong>
                                        <span data-lang-en="Capture the first inbound lead or import your existing pipeline to start tracking assignments and qualification." data-lang-id="Capture lead inbound pertama atau impor pipeline yang sudah ada untuk mulai melacak assignment dan kualifikasi.">Capture the first inbound lead or import your existing pipeline to start tracking assignments and qualification.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($leads->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        <span data-lang-en="Showing" data-lang-id="Menampilkan">Showing</span> {{ $leads->firstItem() }}-{{ $leads->lastItem() }} <span data-lang-en="of" data-lang-id="dari">of</span> {{ $leads->total() }} <span data-lang-en="leads" data-lang-id="lead">leads</span>
                    </div>
                    <div class="pagination-links">
                        @if ($leads->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Prev">Prev</span>
                        @else
                            <a href="{{ $leads->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Prev">Prev</a>
                        @endif

                        @foreach ($leads->getUrlRange(max(1, $leads->currentPage() - 2), min($leads->lastPage(), $leads->currentPage() + 2)) as $page => $url)
                            @if ($page === $leads->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($leads->hasMorePages())
                            <a href="{{ $leads->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Next">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Next">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
