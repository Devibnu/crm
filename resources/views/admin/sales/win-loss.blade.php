@extends('admin.layouts.app')

@section('title', 'Win/Lost Analysis - Krakatau CRM')

@section('content')
    @php
        $currency = fn ($value) => 'Rp '.number_format((float) $value, 2, ',', '.');
        $hasFilters = $filters['status'] !== 'all' || $filters['assigned_to'] || $filters['date_from'] || $filters['date_to'];
        $finalQuoteCount = $summary['accepted_count'] + $summary['rejected_count'] + $summary['expired_count'];
    @endphp

    <section class="winloss-workspace-page">
        <header class="lead-list-header winloss-workspace-banner">
            <div>
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Win/Lost Analysis</h1>
                <p>Analisa deal yang berhasil, gagal, dan performa closing sales.</p>
            </div>
            <div class="winloss-banner-meta">
                <span>{{ number_format($summary['total_won_count']) }} Won · {{ number_format($summary['total_lost_count']) }} Lost · {{ number_format($summary['win_rate'], 2) }}% Win Rate</span>
            </div>
        </header>

        <form method="GET" action="{{ route('admin.sales.win-loss') }}" class="winloss-filter-toolbar">
            <label><span>Status</span><select name="status"><option value="all" @selected($filters['status'] === 'all')>All</option><option value="won" @selected($filters['status'] === 'won')>Won</option><option value="lost" @selected($filters['status'] === 'lost')>Lost</option></select></label>
            <label><span>Owner</span><input type="text" name="assigned_to" list="assigned-to-options" value="{{ $filters['assigned_to'] }}" placeholder="All owners"><datalist id="assigned-to-options">@foreach ($assignedToOptions as $assignedTo)<option value="{{ $assignedTo }}"></option>@endforeach</datalist></label>
            <label><span>Date From</span><input type="date" name="date_from" value="{{ $filters['date_from'] }}"></label>
            <label><span>Date To</span><input type="date" name="date_to" value="{{ $filters['date_to'] }}"></label>
            <div class="winloss-filter-actions">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                @if ($hasFilters)<a href="{{ route('admin.sales.win-loss') }}" class="btn btn-sm btn-muted">Reset</a>@endif
            </div>
        </form>

        <div class="winloss-kpi-grid" aria-label="Win loss summary">
            <div><span>Won Deals</span><strong>{{ number_format($summary['total_won_count']) }}</strong><small>{{ $currency($summary['average_won_value']) }} average</small></div>
            <div><span>Lost Deals</span><strong>{{ number_format($summary['total_lost_count']) }}</strong><small>{{ number_format($summary['lost_rate'], 2) }}% lost rate</small></div>
            <div class="primary"><span>Win Rate</span><strong>{{ number_format($summary['win_rate'], 2) }}%</strong><div class="winloss-rate-track"><i style="width: {{ min(100, $summary['win_rate']) }}%"></i></div></div>
            <div><span>Quote Acceptance Rate</span><strong>{{ number_format($summary['quote_acceptance_rate'], 2) }}%</strong><small>{{ $summary['accepted_count'] }} accepted of {{ $finalQuoteCount }} final quotes</small></div>
        </div>

        <div class="winloss-value-strip" aria-label="Won and lost value">
            <div class="won"><span>Won Value</span><strong>{{ $currency($summary['total_won_value']) }}</strong></div>
            <div class="lost"><span>Lost Value</span><strong>{{ $currency($summary['total_lost_value']) }}</strong></div>
        </div>

        <section class="winloss-comparison-section">
            <div class="winloss-section-heading"><div><h2>Won vs Lost</h2><p>Perbandingan hasil closing berdasarkan jumlah dan nilai deal.</p></div></div>
            <div class="winloss-comparison-grid">
                <article class="won">
                    <header><span>Won</span><strong>{{ number_format($summary['total_won_count']) }} deals</strong></header>
                    <dl><div><dt>Count</dt><dd>{{ number_format($summary['total_won_count']) }}</dd></div><div><dt>Value</dt><dd>{{ $currency($summary['total_won_value']) }}</dd></div><div><dt>Average Value</dt><dd>{{ $currency($summary['average_won_value']) }}</dd></div></dl>
                </article>
                <article class="lost">
                    <header><span>Lost</span><strong>{{ number_format($summary['total_lost_count']) }} deals</strong></header>
                    <dl><div><dt>Count</dt><dd>{{ number_format($summary['total_lost_count']) }}</dd></div><div><dt>Value</dt><dd>{{ $currency($summary['total_lost_value']) }}</dd></div><div><dt>Average Value</dt><dd>{{ $currency($summary['average_lost_value']) }}</dd></div></dl>
                </article>
            </div>
        </section>

        <section class="winloss-table-section winloss-opportunity-analysis">
            <div class="winloss-section-heading"><div><h2>Opportunity Analysis</h2><p>Opportunities dengan status won atau lost.</p></div></div>
            <div class="customer-table-wrap">
                <table class="customer-table winloss-compact-table">
                    <thead><tr><th>Opportunity</th><th>Company</th><th>Estimated Value</th><th>Probability</th><th>Status</th><th>Expected Close</th><th>Owner</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse ($opportunities as $opportunity)
                            @php $probability = min(100, max(0, (int) $opportunity->probability)); @endphp
                            <tr>
                                <td><a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="winloss-title-link">{{ $opportunity->title }}</a></td>
                                <td>{{ $opportunity->company_name ?: '-' }}</td>
                                <td class="sales-amount">{{ $currency($opportunity->estimated_value) }}</td>
                                <td><div class="winloss-probability"><span><i style="width: {{ $probability }}%"></i></span><strong>{{ $probability }}%</strong></div></td>
                                <td><span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span></td>
                                <td>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $opportunity->assigned_to ?: '-' }}</td>
                                <td><a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-sm btn-muted winloss-view-button">View</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="customer-empty"><div class="winloss-empty-state"><strong>Belum ada data won/lost untuk filter ini.</strong><span>Ubah filter atau selesaikan opportunity untuk melihat analisis.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="winloss-table-section">
            <div class="winloss-section-heading"><div><h2>Quotation Analysis</h2><p>Quotation final: accepted, rejected, atau expired.</p></div></div>
            <div class="customer-table-wrap">
                <table class="customer-table winloss-compact-table">
                    <thead><tr><th>Quote Number</th><th>Title</th><th>Amount</th><th>Status</th><th>Valid Until</th><th>Customer</th><th>Opportunity</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            <tr><td><strong class="sales-code">{{ $quotation->quote_number }}</strong></td><td>{{ $quotation->title }}</td><td class="sales-amount">{{ $currency($quotation->amount) }}</td><td><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td><td>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</td><td>{{ $quotation->customer?->name ?: '-' }}</td><td>{{ $quotation->opportunity?->title ?: '-' }}</td><td><a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-sm btn-muted winloss-view-button">View</a></td></tr>
                        @empty
                            <tr><td colspan="8" class="customer-empty"><div class="winloss-empty-state"><strong>Belum ada quotation final.</strong><span>Tidak ada quotation sesuai filter saat ini.</span></div></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <div class="winloss-breakdown-grid">
            <section class="winloss-table-section">
                <div class="winloss-section-heading"><div><h2>Breakdown by Assigned To</h2><p>Performa closing berdasarkan owner.</p></div></div>
                <div class="customer-table-wrap"><table class="customer-table winloss-compact-table"><thead><tr><th>Owner</th><th>Won</th><th>Lost</th><th>Won Value</th><th>Lost Value</th><th>Win Rate</th></tr></thead><tbody>
                    @forelse ($assignedBreakdown as $row)<tr><td>{{ $row['assigned_to'] }}</td><td>{{ $row['won_count'] }}</td><td>{{ $row['lost_count'] }}</td><td>{{ $currency($row['won_value']) }}</td><td>{{ $currency($row['lost_value']) }}</td><td>{{ number_format($row['win_rate'], 2) }}%</td></tr>@empty<tr><td colspan="6" class="customer-empty">No assigned-to breakdown available.</td></tr>@endforelse
                </tbody></table></div>
            </section>

            <section class="winloss-table-section">
                <div class="winloss-section-heading"><div><h2>Breakdown by Status</h2><p>Distribusi nilai opportunity.</p></div></div>
                <div class="customer-table-wrap"><table class="customer-table winloss-compact-table"><thead><tr><th>Status</th><th>Count</th><th>Value</th></tr></thead><tbody>
                    @forelse ($statusBreakdown as $row)<tr><td><span class="status-badge status-{{ $row['status'] }}">{{ ucfirst($row['status']) }}</span></td><td>{{ $row['count'] }}</td><td>{{ $currency($row['value']) }}</td></tr>@empty<tr><td colspan="3" class="customer-empty">No status breakdown available.</td></tr>@endforelse
                </tbody></table></div>
            </section>

            <section class="winloss-table-section winloss-monthly-section">
                <div class="winloss-section-heading"><div><h2>Monthly Breakdown</h2><p>Won/lost berdasarkan expected close month.</p></div></div>
                <div class="customer-table-wrap"><table class="customer-table winloss-compact-table"><thead><tr><th>Month</th><th>Won</th><th>Lost</th><th>Won Value</th><th>Lost Value</th></tr></thead><tbody>
                    @forelse ($monthlyBreakdown as $row)<tr><td>{{ $row['month'] }}</td><td>{{ $row['won_count'] }}</td><td>{{ $row['lost_count'] }}</td><td>{{ $currency($row['won_value']) }}</td><td>{{ $currency($row['lost_value']) }}</td></tr>@empty<tr><td colspan="5" class="customer-empty">No monthly breakdown available.</td></tr>@endforelse
                </tbody></table></div>
            </section>
        </div>
    </section>
@endsection
