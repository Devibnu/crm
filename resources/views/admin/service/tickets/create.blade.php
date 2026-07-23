@extends('admin.layouts.app')

@section('title', 'Add Ticket - Krakatau CRM')

@section('content')
    <section class="lead-form-page customer-crud-form-page">
        <header class="lead-list-header lead-form-banner customer-form-hero">
            <div>
                <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                <h1>Add Ticket</h1>
                <p>Buat tiket layanan baru dari channel pelanggan dan tetapkan prioritas penanganan.</p>
            </div>
        </header>

        @if ($conversation ?? false)
            <div class="customer-alert info">
                Source Conversation:
                <strong>{{ $conversation->contact_name ?: $conversation->phone_number }}</strong>
                <span>Form sudah diprefill dari WhatsApp conversation. Silakan edit sebelum disimpan.</span>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.service.tickets.store') }}" class="lead-workspace-form customer-workspace-form">
            @csrf

            @include('admin.service.tickets._form')

            <div class="lead-form-actions customer-form-actions">
                <a href="{{ route('admin.service.tickets.index') }}" class="btn btn-muted">Back</a>
                <button type="submit" class="btn btn-primary">Save Ticket</button>
            </div>
        </form>
    </section>
@endsection
