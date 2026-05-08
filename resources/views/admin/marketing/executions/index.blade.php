@extends('admin.layouts.app')

@section('title', 'Campaign Execution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'execution'])
            </div>
            <div>
                <h1>Campaign Execution</h1>
                <p>Kelola proses pengiriman dan tracking eksekusi campaign.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Executions</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua eksekusi campaign</small>
            </article>
            <article class="card sales-summary-card">
                <span>Running</span>
                <strong>{{ number_format($summary['running']) }}</strong>
                <small>Sedang berjalan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Completed</span>
                <strong>{{ number_format($summary['completed']) }}</strong>
                <small>Eksekusi selesai</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Sent</span>
                <strong>{{ number_format($summary['total_sent']) }}</strong>
                <small>Total pesan terkirim</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Execution List</h2>
                    <p>Search execution, campaign, atau audience segment.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.executions.create') }}" class="btn btn-primary">Add Execution</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.executions.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Execution, campaign, segment" aria-label="Search executions">
                </label>
                <label class="field">
                    <span>Channel</span>
                    <select name="channel" aria-label="Filter channel">
                        <option value="">All channels</option>
                        @foreach ($channelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucwords(str_replace('_', ' ', $channel)) }}</option>
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
                    @if ($search || $selectedChannel || $selectedStatus)
                        <a href="{{ route('admin.marketing.executions.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Execution Name</th>
                            <th>Campaign</th>
                            <th>Audience Segment</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Sent</th>
                            <th>Delivered</th>
                            <th>Opened</th>
                            <th>Clicked</th>
                            <th>Scheduled At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($executions as $execution)
                            <tr>
                                <td><a href="{{ route('admin.marketing.executions.show', $execution) }}" class="sales-title-link">{{ $execution->execution_name }}</a></td>
                                <td>{{ $execution->marketingCampaign?->name ?: '-' }}</td>
                                <td>{{ $execution->audienceSegment?->name ?: '-' }}</td>
                                <td><span class="status-badge channel-{{ $execution->channel }}">{{ ucwords(str_replace('_', ' ', $execution->channel)) }}</span></td>
                                <td><span class="status-badge status-{{ $execution->status }}">{{ ucfirst($execution->status) }}</span></td>
                                <td>{{ number_format($execution->sent_count) }}</td>
                                <td>{{ number_format($execution->delivered_count) }}</td>
                                <td>{{ number_format($execution->opened_count) }}</td>
                                <td>{{ number_format($execution->clicked_count) }}</td>
                                <td>{{ $execution->scheduled_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.executions.show', $execution) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.marketing.executions.edit', $execution) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.executions.destroy', $execution) }}" onsubmit="return confirm('Delete execution ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada execution</strong>
                                        <span>Tambahkan eksekusi pertama untuk mulai tracking pengiriman campaign.</span>
                                        <a href="{{ route('admin.marketing.executions.create') }}" class="btn btn-primary">Add Execution</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($executions->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $executions->firstItem() }}-{{ $executions->lastItem() }} dari {{ $executions->total() }} execution
                    </div>
                    <div class="pagination-links">
                        @if ($executions->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $executions->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($executions->getUrlRange(max(1, $executions->currentPage() - 2), min($executions->lastPage(), $executions->currentPage() + 2)) as $page => $url)
                            @if ($page === $executions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($executions->hasMorePages())
                            <a href="{{ $executions->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
