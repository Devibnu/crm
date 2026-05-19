@extends('admin.layouts.app')

@section('title', $resolution->resolution_summary.' - Case Resolution - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="{{ $resolution->resolution_summary }} - Case Resolution - Krakatau CRM" data-doc-title-id="{{ $resolution->resolution_summary }} - Penyelesaian Kasus - Krakatau CRM"></span>
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'case'])
            </div>
            <div>
                <h1 data-lang-en="Case Resolution Detail" data-lang-id="Detail Penyelesaian Kasus">Case Resolution Detail</h1>
                <p data-lang-en="Case resolution detail, root cause, resolver, and customer notification." data-lang-id="Detail penyelesaian kasus, root cause, resolver, dan notifikasi pelanggan.">Detail penyelesaian kasus, root cause, resolver, dan notifikasi pelanggan.</p>
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
                    <a href="{{ route('admin.service.case-resolutions.edit', $resolution) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Ticket" data-lang-id="Tiket">Ticket</span>
                    <strong>{{ $resolution->ticket?->ticket_number ?: '-' }}</strong>
                </div>
                <div>
                    <span data-lang-en="Type" data-lang-id="Tipe">Type</span>
                    <strong>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Customer Notified" data-lang-id="Customer Diberi Kabar">Customer Notified</span>
                    <strong><span data-lang-en="{{ $resolution->customer_notified ? 'Yes' : 'No' }}" data-lang-id="{{ $resolution->customer_notified ? 'Ya' : 'Tidak' }}">{{ $resolution->customer_notified ? 'Yes' : 'No' }}</span></strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Ticket Number" data-lang-id="Nomor Tiket">Ticket Number</strong><span>{{ $resolution->ticket?->ticket_number ?: '-' }}</span></div>
                <div><strong data-lang-en="Ticket Subject" data-lang-id="Subjek Tiket">Ticket Subject</strong><span>{{ $resolution->ticket?->subject ?: '-' }}</span></div>
                <div><strong data-lang-en="Resolution Summary" data-lang-id="Ringkasan Penyelesaian">Resolution Summary</strong><span>{{ $resolution->resolution_summary }}</span></div>
                <div><strong data-lang-en="Resolution Type" data-lang-id="Tipe Penyelesaian">Resolution Type</strong><span>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</span></div>
                <div><strong data-lang-en="Resolved By" data-lang-id="Diselesaikan Oleh">Resolved By</strong><span>{{ $resolution->resolved_by ?: '-' }}</span></div>
                <div><strong data-lang-en="Resolved At" data-lang-id="Diselesaikan Pada">Resolved At</strong><span>{{ $resolution->resolved_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Customer Notified" data-lang-id="Customer Diberi Kabar">Customer Notified</strong><span><span data-lang-en="{{ $resolution->customer_notified ? 'Yes' : 'No' }}" data-lang-id="{{ $resolution->customer_notified ? 'Ya' : 'Tidak' }}">{{ $resolution->customer_notified ? 'Yes' : 'No' }}</span></span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Resolution Notes" data-lang-id="Catatan Penyelesaian">Resolution Notes</h3>
                <p>{{ $resolution->resolution_notes ?: '' }}@unless($resolution->resolution_notes)<span data-lang-en="No resolution notes available" data-lang-id="Belum ada catatan penyelesaian">No resolution notes available</span>@endunless</p>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Root Cause" data-lang-id="Akar Masalah">Root Cause</h3>
                <p>{{ $resolution->root_cause ?: '' }}@unless($resolution->root_cause)<span data-lang-en="No root cause available" data-lang-id="Belum ada akar masalah">No root cause available</span>@endunless</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.service.case-resolutions.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
                <form method="POST" action="{{ route('admin.service.case-resolutions.destroy', $resolution) }}" data-confirm-en="Delete this case resolution?" data-confirm-id="Hapus penyelesaian kasus ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus penyelesaian kasus ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
