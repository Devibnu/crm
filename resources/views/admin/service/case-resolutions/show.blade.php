@extends('admin.layouts.app')

@section('title', $resolution->resolution_summary.' - Case Resolution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'case'])
            </div>
            <div>
                <h1>Case Resolution Detail</h1>
                <p>Detail penyelesaian kasus, root cause, resolver, dan notifikasi pelanggan.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $resolution->resolution_summary }}</h2>
                    <p>{{ $resolution->ticket?->ticket_number ?: 'Ticket #'.$resolution->ticket_id }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge resolution-{{ $resolution->resolution_type }}">{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</span>
                    <a href="{{ route('admin.service.case-resolutions.edit', $resolution) }}" class="btn btn-primary">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Ticket</span>
                    <strong>{{ $resolution->ticket?->ticket_number ?: '-' }}</strong>
                </div>
                <div>
                    <span>Type</span>
                    <strong>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</strong>
                </div>
                <div>
                    <span>Customer Notified</span>
                    <strong>{{ $resolution->customer_notified ? 'Yes' : 'No' }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Ticket Number</strong><span>{{ $resolution->ticket?->ticket_number ?: '-' }}</span></div>
                <div><strong>Ticket Subject</strong><span>{{ $resolution->ticket?->subject ?: '-' }}</span></div>
                <div><strong>Resolution Summary</strong><span>{{ $resolution->resolution_summary }}</span></div>
                <div><strong>Resolution Type</strong><span>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</span></div>
                <div><strong>Resolved By</strong><span>{{ $resolution->resolved_by ?: '-' }}</span></div>
                <div><strong>Resolved At</strong><span>{{ $resolution->resolved_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Customer Notified</strong><span>{{ $resolution->customer_notified ? 'Yes' : 'No' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Resolution Notes</h3>
                <p>{{ $resolution->resolution_notes ?: 'No resolution notes available' }}</p>
            </div>

            <div class="customer-notes">
                <h3>Root Cause</h3>
                <p>{{ $resolution->root_cause ?: 'No root cause available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.service.case-resolutions.index') }}" class="btn btn-muted">Back</a>
                <form method="POST" action="{{ route('admin.service.case-resolutions.destroy', $resolution) }}" onsubmit="return confirm('Delete case resolution ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
