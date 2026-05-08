@extends('admin.layouts.app')

@section('title', 'Omnichannel Inbox - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'inbox'])
            </div>
            <div>
                <h1>Omnichannel Inbox</h1>
                <p>Centralized inbox untuk Email, WhatsApp, Chat, Social, Phone, dan Web.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Messages</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua pesan lintas channel</small>
            </article>
            <article class="card sales-summary-card">
                <span>Unread</span>
                <strong>{{ number_format($summary['unread']) }}</strong>
                <small>Pesan belum dibaca</small>
            </article>
            <article class="card sales-summary-card">
                <span>Pending</span>
                <strong>{{ number_format($summary['pending']) }}</strong>
                <small>Masih menunggu follow up</small>
            </article>
            <article class="card sales-summary-card">
                <span>Resolved</span>
                <strong>{{ number_format($summary['resolved']) }}</strong>
                <small>Sudah selesai ditangani</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Inbox Monitoring</h2>
                    <p>Search sender, contact, subject, atau message. Filter berdasarkan channel dan status.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.service.omnichannel.create') }}" class="btn btn-primary">Add Message</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.omnichannel.index') }}" class="omni-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Sender, contact, subject, message">
                </label>
                <label class="field">
                    <span>Channel</span>
                    <select name="channel">
                        <option value="">Semua channel</option>
                        @foreach ($channelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedChannel || $selectedStatus)
                        <a href="{{ route('admin.service.omnichannel.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Channel</th>
                            <th>Direction</th>
                            <th>Sender</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Assigned To</th>
                            <th>Received At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($messages as $message)
                            <tr>
                                <td><span class="status-badge channel-{{ $message->channel }}">{{ ucfirst($message->channel) }}</span></td>
                                <td><span class="status-badge direction-{{ $message->direction }}">{{ ucfirst($message->direction) }}</span></td>
                                <td>
                                    <a href="{{ route('admin.service.omnichannel.show', $message) }}" class="sales-title-link">{{ $message->sender_name ?: 'Unknown Sender' }}</a>
                                    <small>{{ $message->sender_contact ?: ($message->customer?->name ?? '-') }}</small>
                                </td>
                                <td>{{ $message->subject ?: '-' }}</td>
                                <td><span class="status-badge status-{{ $message->status }}">{{ ucfirst(str_replace('_', ' ', $message->status)) }}</span></td>
                                <td>{{ $message->assigned_to ?: '-' }}</td>
                                <td>{{ $message->received_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.service.omnichannel.show', $message) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.service.omnichannel.edit', $message) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.service.omnichannel.destroy', $message) }}" onsubmit="return confirm('Delete message ini?');">
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
                                        <strong>Belum ada message</strong>
                                        <span>Tambahkan message pertama untuk mulai monitoring inbox lintas channel.</span>
                                        <a href="{{ route('admin.service.omnichannel.create') }}" class="btn btn-primary">Add Message</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($messages->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $messages->firstItem() }}-{{ $messages->lastItem() }} dari {{ $messages->total() }} message
                    </div>
                    <div class="pagination-links">
                        @if ($messages->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $messages->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($messages->getUrlRange(max(1, $messages->currentPage() - 2), min($messages->lastPage(), $messages->currentPage() + 2)) as $page => $url)
                            @if ($page === $messages->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($messages->hasMorePages())
                            <a href="{{ $messages->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
