@extends('admin.layouts.app')

@section('title', 'CRM Overview - Krakatau CRM')

@section('content')
    @php
        $tx = static fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
    @endphp
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'dashboard'])</div>
            <div>
                <span data-doc-title-en="CRM Overview - Krakatau CRM" data-doc-title-id="CRM Overview - Krakatau CRM" hidden></span>
                <h1 data-lang-en="CRM Overview" data-lang-id="CRM Overview">CRM Overview</h1>
                <p data-lang-en="Real-time summary across customer, sales, service, and marketing." data-lang-id="Ringkasan real-time dari customer, sales, service, dan marketing.">Real-time summary across customer, sales, service, and marketing.</p>
            </div>
        </article>

        <div class="dashboard-summary-grid">
            @foreach ($summary as $item)
                <article class="card dashboard-summary-card">
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['value'] }}</strong>
                    <small class="pill {{ $item['tone'] }}" data-lang-en="Real Data" data-lang-id="Data Aktual">Real Data</small>
                </article>
            @endforeach
        </div>

        <div class="dashboard-panel-grid">
            <article class="card customer-table-card">
                <div class="customer-table-toolbar"><h2 data-lang-en="Recent Customers" data-lang-id="Customer Terbaru">Recent Customers</h2></div>
                <div class="customer-table-wrap">
                    <table class="customer-table">
                        <thead><tr><th data-lang-en="Name" data-lang-id="Nama">Name</th><th data-lang-en="Status" data-lang-id="Status">Status</th><th data-lang-en="Owner" data-lang-id="Pemilik">Owner</th></tr></thead>
                        <tbody>
                            @forelse ($recentCustomers as $customer)
                                <tr><td>{{ $customer->name }}</td><td><span class="status-badge status-{{ $customer->status }}">{{ $customer->status }}</span></td><td>{{ $customer->owner_name ?: '-' }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="customer-empty" data-lang-en="No customers yet." data-lang-id="Belum ada customer.">No customers yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="customer-table-toolbar"><h2 data-lang-en="Recent Leads" data-lang-id="Lead Terbaru">Recent Leads</h2></div>
                <div class="customer-table-wrap">
                    <table class="customer-table">
                        <thead><tr><th data-lang-en="Lead" data-lang-id="Lead">Lead</th><th data-lang-en="Status" data-lang-id="Status">Status</th><th data-lang-en="Priority" data-lang-id="Prioritas">Priority</th></tr></thead>
                        <tbody>
                            @forelse ($recentLeads as $lead)
                                <tr><td>{{ $lead->name }}</td><td><span class="status-badge status-{{ $lead->status }}">{{ $lead->status }}</span></td><td>{{ $lead->priority }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="customer-empty" data-lang-en="No leads yet." data-lang-id="Belum ada lead.">No leads yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="customer-table-toolbar"><h2 data-lang-en="Recent Tickets" data-lang-id="Tiket Terbaru">Recent Tickets</h2></div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead><tr><th data-lang-en="Ticket" data-lang-id="Tiket">Ticket</th><th data-lang-en="Subject" data-lang-id="Subjek">Subject</th><th data-lang-en="Status" data-lang-id="Status">Status</th><th data-lang-en="Priority" data-lang-id="Prioritas">Priority</th></tr></thead>
                    <tbody>
                        @forelse ($recentTickets as $ticket)
                            <tr><td>{{ $ticket->ticket_number }}</td><td>{{ $ticket->subject }}</td><td><span class="status-badge status-{{ $ticket->status }}">{{ $ticket->status }}</span></td><td>{{ $ticket->priority }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="customer-empty" data-lang-en="No tickets yet." data-lang-id="Belum ada ticket.">No tickets yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
