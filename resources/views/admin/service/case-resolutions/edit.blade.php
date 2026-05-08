@extends('admin.layouts.app')

@section('title', 'Edit Case Resolution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'case'])
            </div>
            <div>
                <h1>Edit Case Resolution</h1>
                <p>Perbarui penyelesaian kasus, root cause, resolver, dan status notifikasi customer.</p>
            </div>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $resolution->resolution_summary }}</h2>
                    <p>{{ $resolution->ticket?->ticket_number ?: 'Ticket #'.$resolution->ticket_id }}</p>
                </div>
                <span class="status-badge resolution-{{ $resolution->resolution_type }}">{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</span>
            </div>

            <form method="POST" action="{{ route('admin.service.case-resolutions.update', $resolution) }}">
                @csrf
                @method('PUT')

                @include('admin.service.case-resolutions._form')

                <div class="form-actions">
                    <a href="{{ route('admin.service.case-resolutions.show', $resolution) }}" class="btn btn-muted">Back</a>
                    <button type="submit" class="btn btn-primary">Update Resolution</button>
                </div>
            </form>
        </article>
    </section>
@endsection
