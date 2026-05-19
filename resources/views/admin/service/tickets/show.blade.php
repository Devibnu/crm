@extends('admin.layouts.app')

@section('title', $ticket->ticket_number.' - Ticket - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'ticket'])
            </div>
            <div>
                <h1>Ticket Detail</h1>
                <p>Ringkasan tiket layanan pelanggan, prioritas, channel, assignment, dan timeline.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $ticket->subject }}</h2>
                    <p>{{ $ticket->ticket_number }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                    <a href="{{ route('admin.service.tickets.edit', $ticket) }}" class="btn btn-primary">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Ticket Number</span>
                    <strong>{{ $ticket->ticket_number }}</strong>
                </div>
                <div>
                    <span>Priority</span>
                    <strong>{{ ucfirst($ticket->priority) }}</strong>
                </div>
                <div>
                    <span>Channel</span>
                    <strong>{{ ucfirst(str_replace('_', ' ', $ticket->channel)) }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Subject</strong><span>{{ $ticket->subject }}</span></div>
                <div><strong>Customer</strong><span>{{ $ticket->customer?->name ?: '-' }}</span></div>
                <div><strong>Status</strong><span>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></div>
                <div><strong>Priority</strong><span>{{ ucfirst($ticket->priority) }}</span></div>
                <div><strong>Channel</strong><span>{{ ucfirst(str_replace('_', ' ', $ticket->channel)) }}</span></div>
                <div><strong>Assigned To</strong><span>{{ $ticket->assigned_to ?: '-' }}</span></div>
                <div><strong>Due At</strong><span>{{ $ticket->due_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Resolved At</strong><span>{{ $ticket->resolved_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Closed At</strong><span>{{ $ticket->closed_at?->format('d M Y H:i') ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Description</h3>
                <p>{{ $ticket->description ?: 'No description available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.service.tickets.index') }}" class="btn btn-muted">Back</a>
                <form method="POST" action="{{ route('admin.service.tickets.destroy', $ticket) }}" onsubmit="return confirm('Delete ticket ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
