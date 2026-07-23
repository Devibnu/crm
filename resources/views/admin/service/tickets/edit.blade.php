@extends('admin.layouts.app')

@section('title', 'Edit Ticket - Krakatau CRM')

@section('content')
    @php
        $ticketStatusBadge = $ticket->status === 'reopened' ? 'status-pending' : 'status-'.$ticket->status;
    @endphp

    <section class="lead-form-page customer-crud-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>Edit Ticket</h1>
                <p>{{ $ticket->ticket_number }} · {{ $ticket->subject }}</p>
            </div>
            <span class="status-badge {{ $ticketStatusBadge }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
        </header>

        <form method="POST" action="{{ route('admin.service.tickets.update', $ticket) }}" class="lead-workspace-form customer-workspace-form">
            @csrf
            @method('PUT')

            @include('admin.service.tickets._form')

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.service.tickets.show', $ticket) }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Update Ticket</button>
            </div>
        </form>
    </section>
@endsection
