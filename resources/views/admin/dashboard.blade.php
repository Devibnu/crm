@extends('admin.layouts.app')

@section('title', 'CRM Overview - Krakatau CRM')

@section('content')
    @php
        $badgeClass = static function (?string $status): string {
            return match ((string) $status) {
                'new' => 'status-new',
                'active', 'qualified', 'running', 'open', 'in_progress' => 'status-active',
                'pending', 'scheduled', 'waiting_customer', 'contacted', 'proposal', 'negotiation' => 'status-pending',
                'won', 'resolved', 'closed', 'completed' => 'status-won',
                'lost', 'cancelled', 'failed', 'unqualified' => 'status-lost',
                'inactive' => 'status-inactive',
                'blacklist' => 'status-blacklist',
                default => 'status-inactive',
            };
        };
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'dashboard'])
            </div>
            <div>
                <h1>{{ $pageTitle }}</h1>
                <p>{{ $pageDescription }}</p>
            </div>
        </article>

        <section class="sales-summary-grid">
            @foreach ($summaryCards as $card)
                <article class="card sales-summary-card">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small>{{ $card['hint'] }}</small>
                </article>
            @endforeach
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Module Health</h2>
                    <p>Snapshot kesehatan Customer 360, Sales, Service, dan Marketing dari data real-time database.</p>
                </div>
            </div>
            <div class="sales-summary-grid">
                @foreach ($moduleHealthCards as $module)
                    <article class="card sales-summary-card">
                        <span>{{ $module['title'] }}</span>
                        @foreach ($module['metrics'] as $metric)
                            <small><strong style="display:inline;font-size:14px;margin:0;">{{ $metric['value'] }}</strong> {{ $metric['label'] }}</small>
                        @endforeach
                        <a href="{{ route($module['link_route']) }}" class="btn btn-sm btn-muted" style="margin-top:10px;">{{ $module['link_label'] }}</a>
                    </article>
                @endforeach
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Customers</h2>
                    <p>5 customer terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentCustomers as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->email ?: '-' }}</td>
                            <td><span class="status-badge {{ $badgeClass($item->status) }}">{{ str($item->status)->headline() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="customer-empty">No customers found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Leads</h2>
                    <p>5 lead terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Source</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentLeads as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ $item->source ?: '-' }}</td>
                            <td><span class="status-badge {{ $badgeClass($item->status) }}">{{ str($item->status)->headline() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="customer-empty">No leads found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Opportunities</h2>
                    <p>5 opportunity terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Est. Value</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentOpportunities as $item)
                        <tr>
                            <td>{{ $item->title }}</td>
                            <td>Rp {{ number_format((float) $item->estimated_value, 0, ',', '.') }}</td>
                            <td><span class="status-badge {{ $badgeClass($item->status) }}">{{ str($item->status)->headline() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="customer-empty">No opportunities found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Tickets</h2>
                    <p>5 ticket terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Subject</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentTickets as $item)
                        <tr>
                            <td>{{ $item->ticket_number }}</td>
                            <td>{{ $item->subject }}</td>
                            <td><span class="status-badge {{ $badgeClass($item->status) }}">{{ str($item->status)->headline() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="customer-empty">No tickets found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Campaigns</h2>
                    <p>5 campaign terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($recentCampaigns as $item)
                        <tr>
                            <td>{{ $item->name }}</td>
                            <td>{{ str($item->type)->headline() }}</td>
                            <td><span class="status-badge {{ $badgeClass($item->status) }}">{{ str($item->status)->headline() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="customer-empty">No campaigns found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
