@extends('admin.layouts.app')

@section('title', 'Campaign Execution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="Campaign Execution - Krakatau CRM" data-doc-title-id="Eksekusi Campaign - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'execution'])
            </div>
            <div>
                <h1 data-lang-en="Campaign Execution" data-lang-id="Eksekusi Campaign">Campaign Execution</h1>
                <p data-lang-en="Manage the campaign delivery process and execution tracking." data-lang-id="Kelola proses pengiriman dan tracking eksekusi campaign.">Kelola proses pengiriman dan tracking eksekusi campaign.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Executions" data-lang-id="Total Eksekusi">Total Executions</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All campaign executions" data-lang-id="Semua eksekusi campaign">Semua eksekusi campaign</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Running" data-lang-id="Berjalan">Running</span>
                <strong>{{ number_format($summary['running']) }}</strong>
                <small data-lang-en="Currently running" data-lang-id="Sedang berjalan">Sedang berjalan</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Completed" data-lang-id="Selesai">Completed</span>
                <strong>{{ number_format($summary['completed']) }}</strong>
                <small data-lang-en="Completed executions" data-lang-id="Eksekusi selesai">Eksekusi selesai</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Total Sent" data-lang-id="Total Terkirim">Total Sent</span>
                <strong>{{ number_format($summary['total_sent']) }}</strong>
                <small data-lang-en="Total messages sent" data-lang-id="Total pesan terkirim">Total pesan terkirim</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Execution List" data-lang-id="Daftar Eksekusi">Execution List</h2>
                    <p data-lang-en="Search by execution, campaign, or audience segment." data-lang-id="Cari berdasarkan eksekusi, campaign, atau segmen audiens.">Search execution, campaign, atau audience segment.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.executions.create') }}" class="btn btn-primary" data-lang-en="Add Execution" data-lang-id="Tambah Eksekusi">Add Execution</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.executions.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Execution, campaign, segment" aria-label="Search executions" data-placeholder-en="Execution, campaign, segment" data-placeholder-id="Eksekusi, campaign, segmen" data-title-en="Search executions" data-title-id="Cari eksekusi">
                </label>
                <label class="field">
                    <span data-lang-en="Channel" data-lang-id="Channel">Channel</span>
                    <select name="channel" aria-label="Filter channel">
                        <option value="" data-lang-en="All channels" data-lang-id="Semua channel">All channels</option>
                        @foreach ($channelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucwords(str_replace('_', ' ', $channel)) }}</option>
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
                    @if ($search || $selectedChannel || $selectedStatus)
                        <a href="{{ route('admin.marketing.executions.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Execution Name" data-lang-id="Nama Eksekusi">Execution Name</th>
                            <th data-lang-en="Campaign" data-lang-id="Campaign">Campaign</th>
                            <th data-lang-en="Audience Segment" data-lang-id="Segmen Audiens">Audience Segment</th>
                            <th data-lang-en="Channel" data-lang-id="Channel">Channel</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Sent" data-lang-id="Terkirim">Sent</th>
                            <th data-lang-en="Delivered" data-lang-id="Sampai">Delivered</th>
                            <th data-lang-en="Opened" data-lang-id="Dibuka">Opened</th>
                            <th data-lang-en="Clicked" data-lang-id="Diklik">Clicked</th>
                            <th data-lang-en="Scheduled At" data-lang-id="Dijadwalkan Pada">Scheduled At</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
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
                                        <a href="{{ route('admin.marketing.executions.show', $execution) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.marketing.executions.edit', $execution) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.executions.destroy', $execution) }}" data-confirm-en="Delete this execution?" data-confirm-id="Hapus eksekusi ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this execution?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No executions yet" data-lang-id="Belum ada eksekusi">Belum ada execution</strong>
                                        <span data-lang-en="Add the first execution to start tracking campaign delivery." data-lang-id="Tambahkan eksekusi pertama untuk mulai tracking pengiriman campaign.">Tambahkan eksekusi pertama untuk mulai tracking pengiriman campaign.</span>
                                        <a href="{{ route('admin.marketing.executions.create') }}" class="btn btn-primary" data-lang-en="Add Execution" data-lang-id="Tambah Eksekusi">Add Execution</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($executions->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info" data-lang-en="Showing {{ $executions->firstItem() }}-{{ $executions->lastItem() }} of {{ $executions->total() }} executions" data-lang-id="Menampilkan {{ $executions->firstItem() }}-{{ $executions->lastItem() }} dari {{ $executions->total() }} eksekusi">
                        Menampilkan {{ $executions->firstItem() }}-{{ $executions->lastItem() }} dari {{ $executions->total() }} execution
                    </div>
                    <div class="pagination-links">
                        @if ($executions->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</span>
                        @else
                            <a href="{{ $executions->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</a>
                        @endif

                        @foreach ($executions->getUrlRange(max(1, $executions->currentPage() - 2), min($executions->lastPage(), $executions->currentPage() + 2)) as $page => $url)
                            @if ($page === $executions->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($executions->hasMorePages())
                            <a href="{{ $executions->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Berikutnya">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Berikutnya">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
