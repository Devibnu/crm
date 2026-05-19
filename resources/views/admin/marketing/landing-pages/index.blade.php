@extends('admin.layouts.app')

@section('title', 'Landing Page & Form - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Landing Page & Form - Krakatau CRM" data-doc-title-id="Landing Page & Formulir - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'landing'])
            </div>
            <div>
                <h1 data-lang-en="Landing Page & Form" data-lang-id="Landing Page & Formulir">Landing Page & Form</h1>
                <p data-lang-en="Manage campaign landing pages and lead capture forms." data-lang-id="Kelola landing page campaign dan formulir lead capture.">Kelola landing page campaign dan form lead capture.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Landing Pages" data-lang-id="Total Landing Page">Total Landing Pages</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All landing pages" data-lang-id="Semua landing page">Semua landing page</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Published" data-lang-id="Dipublikasikan">Published</span>
                <strong>{{ number_format($summary['published']) }}</strong>
                <small data-lang-en="Live and active" data-lang-id="Live dan aktif">Live dan aktif</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Draft" data-lang-id="Draft">Draft</span>
                <strong>{{ number_format($summary['draft']) }}</strong>
                <small data-lang-en="Still being prepared" data-lang-id="Masih disiapkan">Masih disiapkan</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Total Submissions" data-lang-id="Total Submission">Total Submissions</span>
                <strong>{{ number_format($summary['submissions']) }}</strong>
                <small data-lang-en="Captured leads collected" data-lang-id="Lead capture terkumpul">Lead capture terkumpul</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Landing Page List" data-lang-id="Daftar Landing Page">Landing Page List</h2>
                    <p data-lang-en="Search by title or headline, then filter by status." data-lang-id="Cari berdasarkan judul atau headline, lalu filter berdasarkan status.">Search title atau headline, lalu filter status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.landing-pages.create') }}" class="btn btn-primary" data-lang-en="Add Landing Page" data-lang-id="Tambah Landing Page">Add Landing Page</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.landing-pages.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Title or headline" aria-label="Search landing pages" data-placeholder-en="Title or headline" data-placeholder-id="Judul atau headline" data-title-en="Search landing pages" data-title-id="Cari landing page">
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
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.marketing.landing-pages.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                            <th data-lang-en="Campaign" data-lang-id="Campaign">Campaign</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Views" data-lang-id="Dilihat">Views</th>
                            <th data-lang-en="Submissions" data-lang-id="Submission">Submissions</th>
                            <th data-lang-en="Published At" data-lang-id="Dipublikasikan Pada">Published At</th>
                            <th data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($landingPages as $landingPage)
                            <tr>
                                <td><a href="{{ route('admin.marketing.landing-pages.show', $landingPage) }}" class="sales-title-link">{{ $landingPage->title }}</a></td>
                                <td>{{ $landingPage->marketingCampaign?->name ?: '-' }}</td>
                                <td><span class="status-badge status-{{ $landingPage->status }}">{{ ucfirst($landingPage->status) }}</span></td>
                                <td>{{ number_format($landingPage->views_count) }}</td>
                                <td>{{ number_format($landingPage->submissions_count) }}</td>
                                <td>{{ $landingPage->published_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $landingPage->created_by ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.landing-pages.show', $landingPage) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.marketing.landing-pages.edit', $landingPage) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.landing-pages.destroy', $landingPage) }}" data-confirm-en="Delete this landing page?" data-confirm-id="Hapus landing page ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this landing page?');">
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
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No landing pages yet" data-lang-id="Belum ada landing page">Belum ada landing page</strong>
                                        <span data-lang-en="Add the first landing page for campaign lead capture." data-lang-id="Tambahkan landing page pertama untuk lead capture campaign.">Tambahkan landing page pertama untuk lead capture campaign.</span>
                                        <a href="{{ route('admin.marketing.landing-pages.create') }}" class="btn btn-primary" data-lang-en="Add Landing Page" data-lang-id="Tambah Landing Page">Add Landing Page</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($landingPages->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info" data-lang-en="Showing {{ $landingPages->firstItem() }}-{{ $landingPages->lastItem() }} of {{ $landingPages->total() }} landing pages" data-lang-id="Menampilkan {{ $landingPages->firstItem() }}-{{ $landingPages->lastItem() }} dari {{ $landingPages->total() }} landing page">
                        Menampilkan {{ $landingPages->firstItem() }}-{{ $landingPages->lastItem() }} dari {{ $landingPages->total() }} landing page
                    </div>
                    <div class="pagination-links">
                        @if ($landingPages->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</span>
                        @else
                            <a href="{{ $landingPages->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</a>
                        @endif

                        @foreach ($landingPages->getUrlRange(max(1, $landingPages->currentPage() - 2), min($landingPages->lastPage(), $landingPages->currentPage() + 2)) as $page => $url)
                            @if ($page === $landingPages->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($landingPages->hasMorePages())
                            <a href="{{ $landingPages->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Berikutnya">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Berikutnya">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
