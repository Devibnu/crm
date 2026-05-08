@extends('admin.layouts.app')

@section('title', 'Ticket Management - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'ticket'])
            </div>
            <div>
                <h1>Ticket Management</h1>
                <p>Kelola tiket layanan pelanggan dari berbagai channel.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Tickets</span>
                <strong>{{ number_format($summary['total']) }}</strong>
                <small>Semua tiket layanan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Open Tickets</span>
                <strong>{{ number_format($summary['open']) }}</strong>
                <small>Belum mulai ditangani</small>
            </article>
            <article class="card sales-summary-card">
                <span>In Progress</span>
                <strong>{{ number_format($summary['in_progress']) }}</strong>
                <small>Sedang dikerjakan</small>
            </article>
            <article class="card sales-summary-card">
                <span>Resolved</span>
                <strong>{{ number_format($summary['resolved']) }}</strong>
                <small>Sudah selesai</small>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Ticket List</h2>
                    <p>Search ticket number, subject, customer, atau assignee.</p>
                </div>
                <div class="table-actions">
                    <a href="{{ route('admin.service.tickets.create') }}" class="btn btn-primary">Add Ticket</a>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.service.tickets.index') }}" class="ticket-filter-form">
                <label class="field">
                    <span>Search</span>
                    <input type="search" name="q" value="{{ $search }}" placeholder="Ticket number, subject, customer, assigned">
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
                <label class="field">
                    <span>Priority</span>
                    <select name="priority">
                        <option value="">Semua priority</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field">
                    <span>Channel</span>
                    <select name="channel">
                        <option value="">Semua channel</option>
                        @foreach ($channelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst(str_replace('_', ' ', $channel)) }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="sales-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedStatus || $selectedPriority || $selectedChannel)
                        <a href="{{ route('admin.service.tickets.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Ticket Number</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Channel</th>
                            <th>Assigned To</th>
                            <th>Due At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tickets as $ticket)
                            <tr>
                                <td><strong class="sales-code">{{ $ticket->ticket_number }}</strong></td>
                                <td>
                                    <a href="{{ route('admin.service.tickets.show', $ticket) }}" class="sales-title-link">{{ $ticket->subject }}</a>
                                </td>
                                <td>{{ $ticket->customer?->name ?: '-' }}</td>
                                <td><span class="status-badge status-{{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></td>
                                <td><span class="status-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td>
                                <td><span class="status-badge channel-{{ $ticket->channel }}">{{ ucfirst(str_replace('_', ' ', $ticket->channel)) }}</span></td>
                                <td>{{ $ticket->assigned_to ?: '-' }}</td>
                                <td>{{ $ticket->due_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <a href="{{ route('admin.service.tickets.show', $ticket) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.service.tickets.edit', $ticket) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <form method="POST" action="{{ route('admin.service.tickets.destroy', $ticket) }}" onsubmit="return confirm('Delete ticket ini?');">
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
                                        <strong>Belum ada ticket</strong>
                                        <span>Tambahkan ticket pertama untuk mulai melacak layanan pelanggan.</span>
                                        <a href="{{ route('admin.service.tickets.create') }}" class="btn btn-primary">Add Ticket</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($tickets->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $tickets->firstItem() }}-{{ $tickets->lastItem() }} dari {{ $tickets->total() }} ticket
                    </div>
                    <div class="pagination-links">
                        @if ($tickets->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $tickets->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($tickets->getUrlRange(max(1, $tickets->currentPage() - 2), min($tickets->lastPage(), $tickets->currentPage() + 2)) as $page => $url)
                            @if ($page === $tickets->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($tickets->hasMorePages())
                            <a href="{{ $tickets->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
