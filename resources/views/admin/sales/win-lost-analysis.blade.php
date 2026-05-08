@extends('admin.layouts.app')

@section('title', 'Win/Lost Analysis - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace winlost-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'analysis'])
            </div>
            <div>
                <h1>Win/Lost Analysis</h1>
                <p>Insight hasil quotation berdasarkan customer, opportunity, status akhir, dan nilai deal.</p>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Analysis Filters</h2>
                    <p>Gunakan filter untuk membaca hasil quotation per periode, customer, atau opportunity.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.sales.win-lost-analysis') }}" class="winlost-filter-grid">
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="all" @selected($filters['status'] === 'all')>All</option>
                        <option value="won" @selected($filters['status'] === 'won')>Won</option>
                        <option value="lost" @selected($filters['status'] === 'lost')>Lost</option>
                    </select>
                </label>

                <label class="field">
                    <span>Date From</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}">
                </label>

                <label class="field">
                    <span>Date To</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}">
                </label>

                <label class="field">
                    <span>Customer</span>
                    <select name="customer_id">
                        <option value="">All customers</option>
                        @foreach ($customers as $customer)
                            <option value="{{ $customer->id }}" @selected((string) $filters['customer_id'] === (string) $customer->id)>{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Opportunity</span>
                    <select name="opportunity_id">
                        <option value="">All opportunities</option>
                        @foreach ($opportunities as $opportunity)
                            <option value="{{ $opportunity->id }}" @selected((string) $filters['opportunity_id'] === (string) $opportunity->id)>{{ $opportunity->title }}</option>
                        @endforeach
                    </select>
                </label>

                <div class="winlost-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($filters['status'] !== 'all' || $filters['date_from'] || $filters['date_to'] || $filters['customer_id'] || $filters['opportunity_id'])
                        <a href="{{ route('admin.sales.win-lost-analysis') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>
        </article>

        <div class="winlost-summary-grid">
            <article class="card sales-summary-card winlost-summary-card">
                <span>Total Won</span>
                <strong>{{ $summary['total_won'] }}</strong>
                <small>Accepted quotations</small>
            </article>
            <article class="card sales-summary-card winlost-summary-card">
                <span>Total Lost</span>
                <strong>{{ $summary['total_lost'] }}</strong>
                <small>Rejected atau expired quotations</small>
            </article>
            <article class="card sales-summary-card winlost-summary-card">
                <span>Win Rate</span>
                <strong>{{ number_format($summary['win_rate'], 2) }}%</strong>
                <small>{{ $summary['total_won'] }} dari {{ $summary['total_closed'] }} final deals</small>
            </article>
            <article class="card sales-summary-card winlost-summary-card">
                <span>Lost Rate</span>
                <strong>{{ number_format($summary['lost_rate'], 2) }}%</strong>
                <small>{{ $summary['total_lost'] }} dari {{ $summary['total_closed'] }} final deals</small>
            </article>
            <article class="card sales-summary-card winlost-summary-card">
                <span>Total Won Value</span>
                <strong>Rp {{ number_format($summary['won_value'], 2, ',', '.') }}</strong>
                <small>Accepted quotation amount</small>
            </article>
            <article class="card sales-summary-card winlost-summary-card">
                <span>Total Lost Value</span>
                <strong>Rp {{ number_format($summary['lost_value'], 2, ',', '.') }}</strong>
                <small>Rejected dan expired amount</small>
            </article>
        </div>

        <div class="winlost-analytics-grid">
            <article class="card customer-table-card">
                <div class="customer-show-head">
                    <div>
                        <h2>Top Lost Reasons</h2>
                        <p>Alasan disimpulkan dari status akhir quotation.</p>
                    </div>
                </div>

                @if ($lostReasonBreakdown->isEmpty())
                    <div class="sales-empty-state compact">
                        <strong>Belum ada lost deal</strong>
                        <span>Filter saat ini tidak menemukan quotation rejected atau expired.</span>
                    </div>
                @else
                    @php($maxLostCount = max(1, (int) $lostReasonBreakdown->max('count')))
                    @foreach ($lostReasonBreakdown as $reason)
                        <div class="winlost-reason-row" data-chart="{{ json_encode($reason) }}">
                            <div class="winlost-reason-head">
                                <div>
                                    <strong>{{ $reason['reason'] }}</strong>
                                    <small>{{ $reason['count'] }} deals</small>
                                </div>
                                <span>Rp {{ number_format($reason['value'], 2, ',', '.') }}</span>
                            </div>
                            <div class="winlost-reason-track">
                                <span style="width: {{ ($reason['count'] / $maxLostCount) * 100 }}%"></span>
                            </div>
                        </div>
                    @endforeach
                @endif
            </article>

            <article class="card customer-table-card">
                <div class="customer-show-head">
                    <div>
                        <h2>Recent Won/Lost Deals</h2>
                        <p>Deal terbaru dengan status accepted, rejected, atau expired.</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-table sales-table">
                        <thead>
                            <tr>
                                <th>Quote Number</th>
                                <th>Title</th>
                                <th>Customer</th>
                                <th>Opportunity</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Issued At</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentDeals as $deal)
                                <tr>
                                    <td><strong class="sales-code">{{ $deal->quote_number }}</strong></td>
                                    <td>
                                        <a href="{{ route('admin.sales.deals.show', $deal) }}" class="sales-title-link">{{ $deal->title }}</a>
                                    </td>
                                    <td>{{ $deal->customer?->name ?: '-' }}</td>
                                    <td>{{ $deal->opportunity?->title ?: '-' }}</td>
                                    <td><span class="status-badge status-{{ $deal->status }}">{{ ucfirst($deal->status) }}</span></td>
                                    <td class="sales-amount">Rp {{ number_format((float) $deal->amount, 2, ',', '.') }}</td>
                                    <td>{{ $deal->issued_at?->format('d M Y') ?: '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="customer-empty">
                                        <div class="sales-empty-state compact">
                                            <strong>Belum ada won/lost deals</strong>
                                            <span>Filter saat ini belum menghasilkan quotation final.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="customer-show-head">
                <div>
                    <h2>Result Mapping</h2>
                    <p>Status accepted dihitung sebagai won, sedangkan rejected dan expired dihitung sebagai lost.</p>
                </div>
            </div>

            <div class="winlost-mapping-grid">
                <div><strong>Won</strong><span>accepted</span></div>
                <div><strong>Lost</strong><span>rejected, expired</span></div>
                <div><strong>Date Range</strong><span>issued_at</span></div>
                <div><strong>Relations</strong><span>customers, opportunities</span></div>
            </div>
        </article>
    </section>

    <style>
        .winlost-filter-grid,
        .winlost-summary-grid,
        .winlost-analytics-grid {
            display: grid;
            gap: 16px;
        }

        .winlost-filter-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            align-items: end;
        }

        .winlost-filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .winlost-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .winlost-summary-card {
            padding: 20px;
        }

        .winlost-summary-card span {
            display: block;
            color: #6f6b7d;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .winlost-summary-card strong {
            display: block;
            font-size: 28px;
            color: #3b384c;
            margin-bottom: 6px;
        }

        .winlost-summary-card small {
            color: #8b879a;
            font-size: 13px;
        }

        .winlost-analytics-grid {
            grid-template-columns: minmax(280px, .8fr) minmax(0, 1.4fr);
        }

        .winlost-mapping-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
        }

        .winlost-mapping-grid div {
            border: 1px solid #e7e5ef;
            border-radius: 8px;
            padding: 14px;
            background: #fff;
        }

        .winlost-mapping-grid strong,
        .winlost-mapping-grid span {
            display: block;
        }

        .winlost-mapping-grid strong {
            color: #3b384c;
            margin-bottom: 4px;
        }

        .winlost-mapping-grid span {
            color: #6f6b7d;
            font-size: 13px;
        }

        .winlost-reason-row + .winlost-reason-row {
            margin-top: 14px;
        }

        .winlost-reason-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .winlost-reason-head span {
            color: #6f6b7d;
        }

        .winlost-reason-track {
            height: 10px;
            border-radius: 999px;
            background: #f3f2f7;
            overflow: hidden;
        }

        .winlost-reason-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #ff4c51, #ff9f43);
        }

        @media (max-width: 1080px) {
            .winlost-summary-grid,
            .winlost-filter-grid,
            .winlost-analytics-grid,
            .winlost-mapping-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .winlost-summary-grid,
            .winlost-filter-grid,
            .winlost-analytics-grid,
            .winlost-mapping-grid {
                grid-template-columns: 1fr;
            }

            .winlost-filter-actions {
                justify-content: stretch;
                flex-wrap: wrap;
            }
        }
    </style>
@endsection
