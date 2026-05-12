@extends('admin.layouts.app')

@section('title', 'CRM Overview - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'dashboard'])</div>
            <div>
                <h1>{{ $title }}</h1>
                <p>Ringkasan real-time dari customer, sales, service, dan marketing.</p>
            </div>
        </article>

        <div class="dashboard-summary-grid">
            @foreach ($summary as $item)
                <article class="card dashboard-summary-card">
                    <span>{{ $item['label'] }}</span>
                    <strong>{{ $item['value'] }}</strong>
                    <small class="pill {{ $item['tone'] }}">Real Data</small>
                </article>
            @endforeach
        </div>

        <div class="dashboard-panel-grid">
            <article class="card customer-table-card">
                <div class="customer-table-toolbar"><h2>Recent Customers</h2></div>
                <div class="customer-table-wrap">
                    <table class="customer-table">
                        <thead><tr><th>Name</th><th>Status</th><th>Owner</th></tr></thead>
                        <tbody>
                            @forelse ($recentCustomers as $customer)
                                <tr><td>{{ $customer->name }}</td><td><span class="status-badge status-{{ $customer->status }}">{{ $customer->status }}</span></td><td>{{ $customer->owner_name ?: '-' }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="customer-empty">Belum ada customer.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="customer-table-toolbar"><h2>Recent Leads</h2></div>
                <div class="customer-table-wrap">
                    <table class="customer-table">
                        <thead><tr><th>Lead</th><th>Status</th><th>Priority</th></tr></thead>
                        <tbody>
                            @forelse ($recentLeads as $lead)
                                <tr><td>{{ $lead->name }}</td><td><span class="status-badge status-{{ $lead->status }}">{{ $lead->status }}</span></td><td>{{ $lead->priority }}</td></tr>
                            @empty
                                <tr><td colspan="3" class="customer-empty">Belum ada lead.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="customer-table-toolbar"><h2>Recent Tickets</h2></div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead><tr><th>Ticket</th><th>Subject</th><th>Status</th><th>Priority</th></tr></thead>
                    <tbody>
                        @forelse ($recentTickets as $ticket)
                            <tr><td>{{ $ticket->ticket_number }}</td><td>{{ $ticket->subject }}</td><td><span class="status-badge status-{{ $ticket->status }}">{{ $ticket->status }}</span></td><td>{{ $ticket->priority }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="customer-empty">Belum ada ticket.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
