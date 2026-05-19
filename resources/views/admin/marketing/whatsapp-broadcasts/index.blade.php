@extends('admin.layouts.app')

@section('title', 'WhatsApp Broadcast - Krakatau CRM')

@section('content')
    @php($asRate = fn ($numerator, $denominator) => $denominator > 0 ? number_format(($numerator / $denominator) * 100, 2) . '%' : '0.00%')

    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="WhatsApp Broadcast - Krakatau CRM" data-doc-title-id="Broadcast WhatsApp - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1 data-lang-en="WhatsApp Broadcast" data-lang-id="Broadcast WhatsApp">WhatsApp Broadcast</h1>
                <p data-lang-en="Manage bulk WhatsApp message delivery for marketing campaigns." data-lang-id="Kelola pengiriman pesan WhatsApp massal untuk campaign marketing.">Kelola pengiriman pesan WhatsApp massal untuk campaign marketing.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <section class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span data-lang-en="Total Broadcasts" data-lang-id="Total Broadcast">Total Broadcasts</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small data-lang-en="All WhatsApp broadcast campaigns" data-lang-id="Semua campaign broadcast WA">Semua campaign broadcast WA</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Scheduled" data-lang-id="Terjadwal">Scheduled</span>
                <strong>{{ number_format($summary['scheduled']) }}</strong>
                <small data-lang-en="Waiting for send schedule" data-lang-id="Menunggu jadwal kirim">Menunggu jadwal kirim</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Sending" data-lang-id="Mengirim">Sending</span>
                <strong>{{ number_format($summary['sending']) }}</strong>
                <small data-lang-en="Currently being processed" data-lang-id="Sedang diproses">Sedang diproses</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Completed" data-lang-id="Selesai">Completed</span>
                <strong>{{ number_format($summary['completed']) }}</strong>
                <small data-lang-en="Delivery completed" data-lang-id="Kirim selesai">Kirim selesai</small>
            </article>
            <article class="card sales-summary-card">
                <span data-lang-en="Total Replies" data-lang-id="Total Balasan">Total Replies</span>
                <strong>{{ number_format($summary['total_replies']) }}</strong>
                <small data-lang-en="Accumulated replied recipients" data-lang-id="Akumulasi recipient yang membalas">Akumulasi reply recipients</small>
            </article>
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Broadcast List" data-lang-id="Daftar Broadcast">Broadcast List</h2>
                    <p data-lang-en="Manage WhatsApp campaigns, recipients, and status tracking." data-lang-id="Kelola campaign WhatsApp, recipient, dan tracking status.">Kelola campaign WhatsApp, recipients, dan status tracking.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.create') }}" class="btn btn-primary" data-lang-en="Add Broadcast" data-lang-id="Tambah Broadcast">Add Broadcast</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="sales-filter-form">
                <label class="field">
                    <span data-lang-en="Search" data-lang-id="Cari">Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Name, template, campaign" aria-label="Search broadcasts" data-placeholder-en="Name, template, campaign" data-placeholder-id="Nama, template, campaign" data-title-en="Search broadcasts" data-title-id="Cari broadcast">
                </label>
                <label class="field">
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <select name="status">
                        <option value="" data-lang-en="All statuses" data-lang-id="Semua status">All statuses</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span data-lang-en="Target Type" data-lang-id="Tipe Target">Target Type</span>
                    <select name="target_type">
                        <option value="" data-lang-en="All targets" data-lang-id="Semua target">All targets</option>
                        @foreach ($targetTypeOptions as $target)
                            <option value="{{ $target }}" @selected($selectedTargetType === $target)>{{ ucfirst($target) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Apply Filter" data-lang-id="Terapkan Filter">Apply Filter</button>
                    @if ($search || $selectedStatus || $selectedTargetType)
                        <a href="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Broadcast Name" data-lang-id="Nama Broadcast">Broadcast Name</th>
                            <th data-lang-en="Related Campaign" data-lang-id="Campaign Terkait">Related Campaign</th>
                            <th data-lang-en="Target Type" data-lang-id="Tipe Target">Target Type</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Recipients" data-lang-id="Recipient">Recipients</th>
                            <th data-lang-en="Delivery Rate" data-lang-id="Rasio Terkirim">Delivery Rate</th>
                            <th data-lang-en="Reply Rate" data-lang-id="Rasio Balasan">Reply Rate</th>
                            <th data-lang-en="Scheduled At" data-lang-id="Dijadwalkan Pada">Scheduled At</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($broadcasts as $broadcast)
                            <tr>
                                <td><a href="{{ route('admin.marketing.whatsapp-broadcasts.show', $broadcast) }}" class="sales-title-link">{{ $broadcast->name }}</a></td>
                                <td>{{ $broadcast->marketingCampaign?->name ?: '-' }}</td>
                                <td><span class="status-badge type-{{ $broadcast->target_type }}">{{ ucfirst($broadcast->target_type) }}</span></td>
                                <td><span class="status-badge status-{{ $broadcast->status }}">{{ ucfirst($broadcast->status) }}</span></td>
                                <td>{{ number_format($broadcast->total_recipients) }}</td>
                                <td>{{ $asRate($broadcast->delivered_count, $broadcast->sent_count) }}</td>
                                <td>{{ $asRate($broadcast->replied_count, $broadcast->total_recipients) }}</td>
                                <td>{{ $broadcast->scheduled_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.marketing.whatsapp-broadcasts.show', $broadcast) }}" class="btn btn-sm btn-muted" data-lang-en="Show" data-lang-id="Lihat">Show</a>
                                        <a href="{{ route('admin.marketing.whatsapp-broadcasts.edit', $broadcast) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                                        <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.destroy', $broadcast) }}" data-confirm-en="Delete this broadcast?" data-confirm-id="Hapus broadcast ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this broadcast?');">
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
                                    <div class="sales-empty-state">
                                        <strong data-lang-en="No WhatsApp broadcasts yet" data-lang-id="Belum ada broadcast WhatsApp">Belum ada broadcast WhatsApp</strong>
                                        <span data-lang-en="Create the first broadcast to start sending campaigns to customers or leads." data-lang-id="Buat broadcast pertama untuk mulai kirim campaign ke customer atau lead.">Buat broadcast pertama untuk mulai kirim campaign ke customer/lead.</span>
                                        <a href="{{ route('admin.marketing.whatsapp-broadcasts.create') }}" class="btn btn-primary" data-lang-en="Add Broadcast" data-lang-id="Tambah Broadcast">Add Broadcast</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($broadcasts->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info" data-lang-en="Showing {{ $broadcasts->firstItem() }}-{{ $broadcasts->lastItem() }} of {{ $broadcasts->total() }} broadcasts" data-lang-id="Menampilkan {{ $broadcasts->firstItem() }}-{{ $broadcasts->lastItem() }} dari {{ $broadcasts->total() }} broadcast">
                        Menampilkan {{ $broadcasts->firstItem() }}-{{ $broadcasts->lastItem() }} dari {{ $broadcasts->total() }} broadcast
                    </div>
                    <div class="pagination-links">
                        @if ($broadcasts->onFirstPage())
                            <span class="btn btn-sm btn-disabled" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</span>
                        @else
                            <a href="{{ $broadcasts->previousPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Prev" data-lang-id="Sebelumnya">Prev</a>
                        @endif

                        @foreach ($broadcasts->getUrlRange(max(1, $broadcasts->currentPage() - 2), min($broadcasts->lastPage(), $broadcasts->currentPage() + 2)) as $page => $url)
                            @if ($page === $broadcasts->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($broadcasts->hasMorePages())
                            <a href="{{ $broadcasts->nextPageUrl() }}" class="btn btn-sm btn-muted" data-lang-en="Next" data-lang-id="Berikutnya">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled" data-lang-en="Next" data-lang-id="Berikutnya">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
