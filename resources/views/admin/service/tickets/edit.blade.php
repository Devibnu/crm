@extends('admin.layouts.app')

@section('title', 'Edit Ticket - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'ticket'])
            </div>
            <div>
                <h1>Edit Ticket</h1>
                <p>Perbarui status, prioritas, assignment, dan timeline tiket layanan.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $ticket->ticket_number }}</h2>
                    <p>{{ $ticket->subject }}</p>
                </div>
                <span class="status-badge status-{{ $ticket->status }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.service.tickets.update', $ticket) }}">
                @csrf
                @method('PUT')

                @include('admin.service.tickets._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.tickets.show', $ticket) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Ticket</button>
                </div>
            </form>
        </article>
    </section>
@endsection
