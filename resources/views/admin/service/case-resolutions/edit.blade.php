@extends('admin.layouts.app')

@section('title', 'Edit Case Resolution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">
                    @include('admin.partials.sidebar-icon', ['icon' => 'case'])
                </div>
                <div>
                    <span class="crm-record-kicker">CASE RESOLUTION</span>
                    <h1>Edit Resolution</h1>
                    <div class="customer-profile-hero-meta" aria-label="Resolution summary">
                        <span>{{ $resolution->ticket?->ticket_number ?: 'Ticket #'.$resolution->ticket_id }}</span>
                        <span>{{ $resolution->ticket?->customer?->name ?: 'No customer linked' }}</span>
                        <span>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_outcome ?: 'resolved')) }}</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                <span class="status-badge resolution-{{ $resolution->resolution_type }}">{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</span>
            </div>
        </header>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $resolution->resolution_summary }}</h2>
                    <p>{{ $resolution->ticket?->ticket_number ?: 'Ticket #'.$resolution->ticket_id }}</p>
                </div>
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
