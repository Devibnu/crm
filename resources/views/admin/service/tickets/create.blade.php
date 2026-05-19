@extends('admin.layouts.app')

@section('title', 'Add Ticket - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'ticket'])
            </div>
            <div>
                <h1>Add Ticket</h1>
                <p>Buat tiket layanan baru dari channel pelanggan dan tetapkan prioritas penanganan.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>New Ticket</h2>
                    <p>Ticket number akan dibuat otomatis saat tiket disimpan.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.service.tickets.store') }}">
                @csrf

                @include('admin.service.tickets._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.tickets.index') }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Save Ticket</button>
                </div>
            </form>
        </article>
    </section>
@endsection
