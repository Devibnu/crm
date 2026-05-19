@extends('admin.layouts.app')

@section('title', 'Campaign Management - Krakatau CRM')

@section('content')
    @php($currency = fn ($value) => 'Rp '.number_format((float) $value, 2, ',', '.'))

    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Campaign Management - Krakatau CRM" data-doc-title-id="Manajemen Campaign - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'campaign'])
            </div>
            <div>
                <h1 data-lang-en="Campaign Management" data-lang-id="Manajemen Campaign">Campaign Management</h1>
                <p data-lang-en="Manage marketing campaigns to increase leads and engagement." data-lang-id="Kelola campaign marketing untuk meningkatkan lead dan engagement.">Kelola campaign marketing untuk meningkatkan lead dan engagement.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Campaigns" data-lang-id="Total Campaign">Total Campaigns</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All marketing campaigns" data-lang-id="Semua campaign marketing">Semua campaign marketing</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Running Campaigns" data-lang-id="Campaign Berjalan">Running Campaigns</span>
                <strong>{{ number_format($summary['running']) }}</strong>
                <small data-lang-en="Currently running" data-lang-id="Sedang berjalan">Sedang berjalan</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Completed Campaigns" data-lang-id="Campaign Selesai">Completed Campaigns</span>
                <strong>{{ number_format($summary['completed']) }}</strong>
                <small data-lang-en="Completed campaigns" data-lang-id="Campaign selesai">Campaign selesai</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Total Leads Generated" data-lang-id="Total Lead Dihasilkan">Total Leads Generated</span>
                <strong>{{ number_format($summary['total_leads']) }}</strong>
                <small data-lang-en="Accumulated actual leads" data-lang-id="Akumulasi actual leads">Akumulasi actual leads</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Campaign List" data-lang-id="Daftar Campaign">Campaign List</h2>
                    <p data-lang-en="Search by name, description, or target audience." data-lang-id="Cari berdasarkan nama, deskripsi, atau target audiens.">Search name, description, atau target audience.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn btn-primary" data-lang-en="Add Campaign" data-lang-id="Tambah Campaign">Add Campaign</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.campaigns.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name, description, audience" aria-label="Search campaigns" data-placeholder-en="Name, description, audience" data-placeholder-id="Nama, deskripsi, audiens" data-title-en="Search campaigns" data-title-id="Cari campaign">
                </label>
                <label class="field">
                    <span data-lang-en="Type" data-lang-id="Tipe">Type</span>
                    <select name="type" aria-label="Filter type">
                        <option value="" data-lang-en="All types" data-lang-id="Semua tipe">All types</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <select name="status" aria-label="Filter status">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Apply Filter" data-lang-id="Terapkan Filter">Apply Filter</button>
                    @if ($search || $selectedType || $selectedStatus)
                        <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Campaign Name" data-lang-id="Nama Campaign">Campaign Name</th>
                            <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Audience" data-lang-id="Audiens">Audience</th>
                            <th data-lang-en="Budget" data-lang-id="Anggaran">Budget</th>
                            <th data-lang-en="Expected Leads" data-lang-id="Target Lead">Expected Leads</th>
                            <th data-lang-en="Actual Leads" data-lang-id="Lead Aktual">Actual Leads</th>
                            <th data-lang-en="Start Date" data-lang-id="Tanggal Mulai">Start Date</th>
                            <th data-lang-en="End Date" data-lang-id="Tanggal Selesai">End Date</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($campaigns as $campaign)
                            <tr>
                                <td><a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="sales-title-link">{{ $campaign->name }}</a></td>
                                <td><span class="status-badge type-{{ $campaign->type }}">{{ ucwords(str_replace('_', ' ', $campaign->type)) }}</span></td>
                                <td><span class="status-badge status-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span></td>
                                <td>{{ $campaign->target_audience ?: '-' }}</td>
                                <td class="sales-amount">{{ $currency($campaign->budget) }}</td>
                                <td>{{ number_format($campaign->expected_leads) }}</td>
                                <td>{{ number_format($campaign->actual_leads) }}</td>
                                <td>{{ $campaign->start_date?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $campaign->end_date?->format('d M Y') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.marketing.campaigns.edit', $campaign) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.campaigns.destroy', $campaign) }}" data-confirm-en="Delete this campaign?" data-confirm-id="Hapus campaign ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this campaign?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No campaigns yet" data-lang-id="Belum ada campaign">Belum ada campaign</strong>
                                        <span data-lang-en="Add the first campaign to start tracking leads and engagement." data-lang-id="Tambahkan campaign pertama untuk mulai melacak lead dan engagement.">Tambahkan campaign pertama untuk mulai melacak lead dan engagement.</span>
                                        <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn btn-primary" data-lang-en="Add Campaign" data-lang-id="Tambah Campaign">Add Campaign</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($campaigns->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info" data-lang-en="Showing {{ $campaigns->firstItem() }}-{{ $campaigns->lastItem() }} of {{ $campaigns->total() }} campaigns" data-lang-id="Menampilkan {{ $campaigns->firstItem() }}-{{ $campaigns->lastItem() }} dari {{ $campaigns->total() }} campaign">
                        Menampilkan {{ $campaigns->firstItem() }}-{{ $campaigns->lastItem() }} dari {{ $campaigns->total() }} campaign
                    </div>
                    <div class="pagination-links">
                        @if ($campaigns->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</span>
                        @else
                            <a href="{{ $campaigns->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</a>
                        @endif

                        @foreach ($campaigns->getUrlRange(max(1, $campaigns->currentPage() - 2), min($campaigns->lastPage(), $campaigns->currentPage() + 2)) as $page => $url)
                            @if ($page === $campaigns->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($campaigns->hasMorePages())
                            <a href="{{ $campaigns->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Berikutnya">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Berikutnya">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
