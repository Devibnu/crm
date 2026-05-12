@extends('admin.layouts.app')

@section('title', 'Sales Enablement Dashboard - Krakatau CRM')

@section('content')
    @php
        $badgeClass = static function (?string $status): string {
            return match ((string) $status) {
                'new', 'open', 'qualified', 'high', 'accepted' => 'status-active',
                'contacted', 'proposal', 'negotiation', 'medium', 'sent', 'draft' => 'status-pending',
                'won', 'converted' => 'status-won',
                'lost', 'unqualified', 'rejected', 'expired', 'low' => 'status-lost',
                default => 'status-inactive',
            };
        };
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'pipeline'])</div>
            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
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

        <div class="dashboard-panel-grid">
            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Lead Status Overview</h2>
                        <p>Distribusi status lead dari funnel akuisisi hingga konversi.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Total Leads <small>Qualified / Converted</small></span>
                        <strong>{{ number_format($metrics['total_leads']) }}</strong>
                    </div>
                    @forelse ($leadStatusOverview as $status)
                        <div>
                            <span>{{ str($status->status)->headline() }}</span>
                            <strong>{{ number_format($status->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No lead statuses</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Opportunity Pipeline Overview</h2>
                        <p>Status opportunity dan nilai tiap stage pipeline.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Pipeline Value <small>Open Opportunities</small></span>
                        <strong>{{ number_format($metrics['open_opportunities']) }}</strong>
                    </div>
                    @forelse ($opportunityPipelineOverview as $opportunity)
                        <div>
                            <span>{{ str($opportunity->status)->headline() }} <small>{{ 'Rp '.number_format((float) $opportunity->value_total, 0, ',', '.') }}</small></span>
                            <strong>{{ number_format($opportunity->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No opportunity stages</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Quotation Status Overview</h2>
                    <p>Distribusi quotation berdasarkan status dan total nilai.</p>
                </div>
            </div>
            <div class="dashboard-status-list">
                <div>
                    <span>Total Quotations <small>Accepted: {{ number_format($metrics['accepted_quotations']) }}</small></span>
                    <strong>{{ number_format($metrics['total_quotations']) }}</strong>
                </div>
                @forelse ($quotationStatusOverview as $quotation)
                    <div>
                        <span>{{ str($quotation->status)->headline() }} <small>{{ 'Rp '.number_format((float) $quotation->value_total, 0, ',', '.') }}</small></span>
                        <strong>{{ number_format($quotation->total) }}</strong>
                    </div>
                @empty
                    <div><span>No quotation statuses</span><strong>0</strong></div>
                @endforelse
            </div>
        </div>

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
                            <th>Company</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLeads as $lead)
                            <tr>
                                <td>{{ $lead->name }}</td>
                                <td>{{ $lead->company_name ?: '-' }}</td>
                                <td><span class="status-badge {{ $badgeClass($lead->status) }}">{{ str($lead->status)->headline() }}</span></td>
                                <td><span class="status-badge {{ $badgeClass($lead->priority) }}">{{ str($lead->priority)->headline() }}</span></td>
                                <td>{{ $lead->source ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty">No leads found.</td></tr>
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
                            <th>Opportunity</th>
                            <th>Status</th>
                            <th>Probability</th>
                            <th>Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOpportunities as $opportunity)
                            <tr>
                                <td>{{ $opportunity->title }}</td>
                                <td><span class="status-badge {{ $badgeClass($opportunity->status) }}">{{ str($opportunity->status)->headline() }}</span></td>
                                <td>{{ number_format((float) $opportunity->probability, 0, ',', '.') }}%</td>
                                <td>{{ 'Rp '.number_format((float) $opportunity->estimated_value, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="customer-empty">No opportunities found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Sales Activities</h2>
                    <p>5 aktivitas sales terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Subject</th>
                            <th>Related To</th>
                            <th>Assigned To</th>
                            <th>Activity At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSalesActivities as $activity)
                            <tr>
                                <td><span class="status-badge status-pending">{{ str($activity->type)->headline() }}</span></td>
                                <td>{{ $activity->subject }}</td>
                                <td>{{ str($activity->related_type)->headline() }}</td>
                                <td>{{ $activity->assigned_to ?: '-' }}</td>
                                <td>{{ optional($activity->activity_at)->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty">No sales activities found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Quotations</h2>
                    <p>5 quotation terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Quote Number</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Amount</th>
                            <th>Issued At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentQuotations as $quotation)
                            <tr>
                                <td>{{ $quotation->quote_number }}</td>
                                <td>{{ $quotation->title }}</td>
                                <td><span class="status-badge {{ $badgeClass($quotation->status) }}">{{ str($quotation->status)->headline() }}</span></td>
                                <td>{{ 'Rp '.number_format((float) $quotation->amount, 0, ',', '.') }}</td>
                                <td>{{ optional($quotation->issued_at)->format('d M Y') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty">No quotations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
