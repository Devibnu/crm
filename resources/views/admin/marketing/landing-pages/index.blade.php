@extends('admin.layouts.app')

@section('title', 'Landing Page & Form - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'landing'])
            </div>
            <div>
                <h1>Landing Page & Form</h1>
                <p>Kelola landing page campaign dan form lead capture.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Landing Pages</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua landing page</small>
            </article>
            <article class="card sales-summary-card">
                <span>Published</span>
                <strong>{{ number_format($summary['published']) }}</strong>
                <small>Live dan aktif</small>
            </article>
            <article class="card sales-summary-card">
                <span>Draft</span>
                <strong>{{ number_format($summary['draft']) }}</strong>
                <small>Masih disiapkan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Submissions</span>
                <strong>{{ number_format($summary['submissions']) }}</strong>
                <small>Lead capture terkumpul</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Landing Page List</h2>
                    <p>Search title atau headline, lalu filter status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.landing-pages.create') }}" class="btn btn-primary">Add Landing Page</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.landing-pages.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Title or headline" aria-label="Search landing pages">
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status" aria-label="Filter status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($search || $selectedStatus)
                        <a href="{{ route('admin.marketing.landing-pages.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Campaign</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Submissions</th>
                            <th>Published At</th>
                            <th>Created By</th>
                            <th>Actions</th>
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
                                        <a href="{{ route('admin.marketing.landing-pages.show', $landingPage) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.marketing.landing-pages.edit', $landingPage) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.landing-pages.destroy', $landingPage) }}" onsubmit="return confirm('Delete landing page ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada landing page</strong>
                                        <span>Tambahkan landing page pertama untuk lead capture campaign.</span>
                                        <a href="{{ route('admin.marketing.landing-pages.create') }}" class="btn btn-primary">Add Landing Page</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($landingPages->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $landingPages->firstItem() }}-{{ $landingPages->lastItem() }} dari {{ $landingPages->total() }} landing page
                    </div>
                    <div class="pagination-links">
                        @if ($landingPages->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $landingPages->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($landingPages->getUrlRange(max(1, $landingPages->currentPage() - 2), min($landingPages->lastPage(), $landingPages->currentPage() + 2)) as $page => $url)
                            @if ($page === $landingPages->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($landingPages->hasMorePages())
                            <a href="{{ $landingPages->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
