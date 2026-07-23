@extends('admin.layouts.app')

@section('title', 'Ticket Management - Krakatau CRM')

@section('content')
    @php
        $visibleTickets = $tickets->getCollection();
        $ticketStatusBadge = fn (?string $status): string => $status === 'reopened' ? 'status-pending' : 'status-'.$status;
        $ticketKpis = [
            ['label' => 'Total Ticket', 'value' => number_format($summary['total'] ?? $tickets->total())],
            ['label' => 'Open', 'value' => number_format($summary['open'] ?? $visibleTickets->where('status', 'open')->count())],
            ['label' => 'In Progress', 'value' => number_format($summary['in_progress'] ?? $visibleTickets->where('status', 'in_progress')->count())],
            ['label' => 'Waiting Customer', 'value' => number_format($visibleTickets->where('status', 'waiting_customer')->count())],
            ['label' => 'Resolved', 'value' => number_format($summary['resolved'] ?? $visibleTickets->where('status', 'resolved')->count())],
            ['label' => 'Closed', 'value' => number_format($visibleTickets->where('status', 'closed')->count())],
        ];
    @endphp

    <section class="lead-list-page customer-profile-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>Ticket Management</h1>
                <p>Kelola tiket layanan pelanggan dari berbagai channel.</p>
            </div>
            @can('tickets.create')
                <a href="{{ route('admin.service.tickets.create') }}" class="btn lead-banner-cta">Add Ticket</a>
            @endcan
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <div class="lead-kpi-strip customer-profile-kpi-strip" aria-label="Ticket summary">
            @foreach ($ticketKpis as $kpi)
                <div>
                    <span>{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                </div>
            @endforeach
        </div>

        <section class="lead-list-workspace customer-profile-workspace" aria-label="Ticket workspace">
            <div class="lead-smart-filters customer-profile-smart-filters">
                <form method="GET" action="{{ route('admin.service.tickets.index') }}" class="lead-list-toolbar customer-profile-search-form ticket-filter-form">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Ticket number, subject, customer, assigned" aria-label="Search tickets">
                    <select name="status" aria-label="Filter status">
                        <option value="">Semua status</option>
                        @foreach ($statusOptions as $status)
                            <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                    <select name="priority" aria-label="Filter priority">
                        <option value="">Semua priority</option>
                        @foreach ($priorityOptions as $priority)
                            <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                    <select name="channel" aria-label="Filter channel">
                        <option value="">Semua channel</option>
                        @foreach ($channelOptions as $channel)
                            <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst(str_replace('_', ' ', $channel)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    @if ($search || $selectedStatus || $selectedPriority || $selectedChannel)
                        <a href="{{ route('admin.service.tickets.index') }}" class="btn btn-muted">Reset</a>
                    @endif
                    @can('tickets.create')
                        <a href="{{ route('admin.service.tickets.create') }}" class="btn btn-primary">Add Ticket</a>
                    @endcan
                </form>
            </div>

            @if ($tickets->isEmpty())
                <div class="lead-empty-state customer-profile-enterprise-empty">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'ticket'])</span>
                    <strong>Belum ada ticket</strong>
                    <p>Tambahkan ticket pertama untuk mulai melacak layanan pelanggan.</p>
                    @can('tickets.create')
                        <a href="{{ route('admin.service.tickets.create') }}" class="btn btn-sm btn-primary">Add Ticket</a>
                    @endcan
                </div>
            @else
                <div class="customer-table-wrap lead-table-wrap customer-profile-table-wrap">
                    <table class="customer-table lead-modern-table sales-table">
                        <thead>
                            <tr>
                                <th>Ticket Number</th>
                                <th>Subject</th>
                                <th>Customer</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Channel</th>
                                <th>Due Date</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tickets as $ticket)
                                <tr>
                                    <td><strong class="sales-code">{{ $ticket->ticket_number }}</strong></td>
                                    <td>
                                        <a href="{{ route('admin.service.tickets.show', $ticket) }}" class="sales-title-link">{{ $ticket->subject }}</a>
                                        <small>{{ $ticket->assigned_to ?: 'Unassigned' }}</small>
                                    </td>
                                    <td>{{ $ticket->customer?->name ?: '-' }}</td>
                                    <td><span class="status-badge {{ $ticketStatusBadge($ticket->status) }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></td>
                                    <td><span class="status-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></td>
                                    <td><span class="status-badge channel-{{ $ticket->channel }}">{{ ucfirst(str_replace('_', ' ', $ticket->channel)) }}</span></td>
                                    <td>{{ $ticket->due_at?->format('d M Y H:i') ?: '-' }}</td>
                                    <td>{{ $ticket->created_at?->format('d M Y') ?: '-' }}</td>
                                    <td>
                                        <details class="lead-row-menu customer-profile-row-menu">
                                            <summary aria-label="Open ticket actions">⋮</summary>
                                            <div>
                                                <a href="{{ route('admin.service.tickets.show', $ticket) }}">View</a>
                                                @can('tickets.update')
                                                    <a href="{{ route('admin.service.tickets.edit', $ticket) }}">Edit</a>
                                                @endcan
                                                @can('tickets.delete')
                                                    <form method="POST" action="{{ route('admin.service.tickets.destroy', $ticket) }}">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" onclick="return confirm('Delete ticket ini?')">Delete</button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </details>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($tickets->hasPages())
                    <div class="customer-pagination lead-pagination customer-profile-pagination">
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
                @endif
            @endif
        </section>
    </section>
@endsection
