@extends('admin.layouts.app')

@section('title', 'Campaign Management - Krakatau CRM')

@section('content')
    @php($currency = fn ($value) => 'Rp '.number_format((float) $value, 2, ',', '.'))

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'campaign'])
            </div>
            <div>
                <h1>Campaign Management</h1>
                <p>Kelola campaign marketing untuk meningkatkan lead dan engagement.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Campaigns</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua campaign marketing</small>
            </article>
            <article class="card sales-summary-card">
                <span>Running Campaigns</span>
                <strong>{{ number_format($summary['running']) }}</strong>
                <small>Sedang berjalan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Completed Campaigns</span>
                <strong>{{ number_format($summary['completed']) }}</strong>
                <small>Campaign selesai</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Leads Generated</span>
                <strong>{{ number_format($summary['total_leads']) }}</strong>
                <small>Akumulasi actual leads</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Campaign List</h2>
                    <p>Search name, description, atau target audience.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn btn-primary">Add Campaign</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.campaigns.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name, description, audience" aria-label="Search campaigns">
                </label>
                <label class="field">
                    <span>Type</span>
                    <select name="type" aria-label="Filter type">
                        <option value="">All types</option>
                        @foreach ($typeOptions as $type)
                            <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
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
                    @if ($search || $selectedType || $selectedStatus)
                        <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Campaign Name</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Audience</th>
                            <th>Budget</th>
                            <th>Expected Leads</th>
                            <th>Actual Leads</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Actions</th>
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
                                        <a href="{{ route('admin.marketing.campaigns.show', $campaign) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.marketing.campaigns.edit', $campaign) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete campaign ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada campaign</strong>
                                        <span>Tambahkan campaign pertama untuk mulai melacak lead dan engagement.</span>
                                        <a href="{{ route('admin.marketing.campaigns.create') }}" class="btn btn-primary">Add Campaign</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($campaigns->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $campaigns->firstItem() }}-{{ $campaigns->lastItem() }} dari {{ $campaigns->total() }} campaign
                    </div>
                    <div class="pagination-links">
                        @if ($campaigns->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $campaigns->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($campaigns->getUrlRange(max(1, $campaigns->currentPage() - 2), min($campaigns->lastPage(), $campaigns->currentPage() + 2)) as $page => $url)
                            @if ($page === $campaigns->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($campaigns->hasMorePages())
                            <a href="{{ $campaigns->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
