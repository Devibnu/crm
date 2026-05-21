@extends('admin.layouts.app')

@section('title', 'WhatsApp Broadcast - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>WhatsApp Broadcast</h1>
                <p>Kelola pengiriman pesan WhatsApp massal untuk campaign marketing.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <section class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Broadcasts</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua campaign broadcast WA</small>
            </article>
            <article class="card sales-summary-card">
                <span>Scheduled</span>
                <strong>{{ number_format($summary['scheduled']) }}</strong>
                <small>Menunggu jadwal kirim</small>
            </article>
            <article class="card sales-summary-card">
                <span>Sending</span>
                <strong>{{ number_format($summary['sending']) }}</strong>
                <small>Sedang diproses</small>
            </article>
            <article class="card sales-summary-card">
                <span>Completed</span>
                <strong>{{ number_format($summary['completed']) }}</strong>
                <small>Kirim selesai</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Replies</span>
                <strong>{{ number_format($summary['total_replies']) }}</strong>
                <small>Akumulasi reply recipients</small>
            </article>
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Broadcast List</h2>
                    <p>Kelola campaign WhatsApp, recipients, dan status tracking.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.create') }}" class="btn btn-primary">Add Broadcast</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="sales-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name, template, campaign" aria-label="Search broadcasts">
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Target Type</span>
                    <select name="target_type">
                        <option value="">All targets</option>
                        @foreach ($targetTypeOptions as $target)
                            <option value="{{ $target }}" @selected($selectedTargetType === $target)>{{ ucfirst($target) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($search || $selectedStatus || $selectedTargetType)
                        <a href="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Broadcast Name</th>
                            <th>Related Campaign</th>
                            <th>Target Type</th>
                            <th>Status</th>
                            <th>Recipients</th>
                            <th>Delivery Rate</th>
                            <th>Reply Rate</th>
                            <th>Scheduled At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($broadcasts as $broadcast)
                            <tr>
                                <td><a href="{{ route('admin.marketing.whatsapp-broadcasts.show', $broadcast) }}" class="sales-title-link">{{ $broadcast->name }}</a></td>
                                <td>{{ $broadcast->marketingCampaign?->name ?: '-' }}</td>
                                <td><span class="status-badge type-{{ $broadcast->target_type }}">{{ ucfirst($broadcast->target_type) }}</span></td>
                                <td><span class="status-badge status-{{ $broadcast->status }}">{{ ucfirst($broadcast->status) }}</span></td>
                                <td>{{ number_format($broadcast->total_recipients ?: $broadcast->recipients_count) }}</td>
                                <td>{{ number_format((float) $broadcast->delivery_rate, 2) }}%</td>
                                <td>{{ number_format((float) $broadcast->reply_rate, 2) }}%</td>
                                <td>{{ $broadcast->scheduled_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.whatsapp-broadcasts.show', $broadcast) }}" class="btn btn-sm btn-muted">Show</a>
                                        <a href="{{ route('admin.marketing.whatsapp-broadcasts.edit', $broadcast) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.destroy', $broadcast) }}" onsubmit="return confirm('Delete broadcast ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada broadcast WhatsApp</strong>
                                        <span>Buat broadcast pertama untuk mulai kirim campaign ke customer/lead.</span>
                                        <a href="{{ route('admin.marketing.whatsapp-broadcasts.create') }}" class="btn btn-primary">Add Broadcast</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($broadcasts->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $broadcasts->firstItem() }}-{{ $broadcasts->lastItem() }} dari {{ $broadcasts->total() }} broadcast
                    </div>
                    <div class="pagination-links">
                        @if ($broadcasts->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $broadcasts->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($broadcasts->getUrlRange(max(1, $broadcasts->currentPage() - 2), min($broadcasts->lastPage(), $broadcasts->currentPage() + 2)) as $page => $url)
                            @if ($page === $broadcasts->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($broadcasts->hasMorePages())
                            <a href="{{ $broadcasts->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
