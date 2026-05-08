@extends('admin.layouts.app')

@section('title', 'Win/Lost Analysis - Krakatau CRM')

@section('content')
    @php
        $currency = fn ($value) => 'Rp '.number_format((float) $value, 2, ',', '.');
        $hasFilters = $filters['status'] !== 'all' || $filters['assigned_to'] || $filters['date_from'] || $filters['date_to'];
        $hasData = $opportunities->isNotEmpty() || $quotations->isNotEmpty();
    @endphp

    <section class="service-page customer-list-page sales-workspace winloss-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'analysis'])
            </div>
            <div>
                <h1>Win/Lost Analysis</h1>
                <p>Analisa hasil deal untuk memahami faktor kemenangan, kekalahan, dan performa closing.</p>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Filter</h2>
                    <p>Batasi data berdasarkan status closing, owner, dan rentang tanggal.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.sales.win-loss') }}" class="winloss-filter-grid">
                <label class="field">
                    <span>Status</span>
                    <select name="status">
                        <option value="all" @selected($filters['status'] === 'all')>All</option>
                        <option value="won" @selected($filters['status'] === 'won')>Won</option>
                        <option value="lost" @selected($filters['status'] === 'lost')>Lost</option>
                    </select>
                </label>

                <label class="field">
                    <span>Assigned To</span>
                    <input type="text" name="assigned_to" list="assigned-to-options" value="{{ $filters['assigned_to'] }}" placeholder="All owners">
                    <datalist id="assigned-to-options">
                        @foreach ($assignedToOptions as $assignedTo)
                            <option value="{{ $assignedTo }}"></option>
                        @endforeach
                    </datalist>
                </label>

                <label class="field">
                    <span>Date From</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}">
                </label>

                <label class="field">
                    <span>Date To</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}">
                </label>

                <div class="winloss-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($hasFilters)
                        <a href="{{ route('admin.sales.win-loss') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>
        </article>

        <div class="winloss-summary-grid">
            <article class="card sales-summary-card winloss-summary-card">
                <span>Won Deals</span>
                <strong>{{ number_format($summary['total_won_count']) }}</strong>
                <small>{{ $currency($summary['average_won_value']) }} average value</small>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span>Lost Deals</span>
                <strong>{{ number_format($summary['total_lost_count']) }}</strong>
                <small>{{ number_format($summary['lost_rate'], 2) }}% lost rate · {{ $currency($summary['average_lost_value']) }} average value</small>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span>Win Rate</span>
                <strong>{{ number_format($summary['win_rate'], 2) }}%</strong>
                <div class="winloss-rate-track"><span style="width: {{ min(100, $summary['win_rate']) }}%"></span></div>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span>Won Value</span>
                <strong>{{ $currency($summary['total_won_value']) }}</strong>
                <small>{{ number_format($summary['total_won_count']) }} won opportunities</small>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span>Lost Value</span>
                <strong>{{ $currency($summary['total_lost_value']) }}</strong>
                <small>{{ number_format($summary['total_lost_count']) }} lost opportunities</small>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span>Quote Acceptance Rate</span>
                <strong>{{ number_format($summary['quote_acceptance_rate'], 2) }}%</strong>
                <small>{{ $summary['accepted_count'] }} accepted of {{ $summary['accepted_count'] + $summary['rejected_count'] + $summary['expired_count'] }} final quotes</small>
            </article>
        </div>

        @unless ($hasData)
            <article class="card customer-table-card">
                <div class="sales-empty-state compact">
                    <strong>Belum ada data win/lost</strong>
                    <span>Filter saat ini tidak menemukan opportunity won/lost atau quotation final.</span>
                </div>
            </article>
        @endunless

        <article class="card customer-table-card">
            <div class="customer-show-head">
                <div>
                    <h2>Opportunity Analysis</h2>
                    <p>Opportunities dengan status won atau lost.</p>
                </div>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Company</th>
                            <th>Estimated Value</th>
                            <th>Probability</th>
                            <th>Status</th>
                            <th>Expected Close</th>
                            <th>Assigned To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($opportunities as $opportunity)
                            <tr>
                                <td>{{ $opportunity->title }}</td>
                                <td>{{ $opportunity->company_name ?: '-' }}</td>
                                <td class="sales-amount">{{ $currency($opportunity->estimated_value) }}</td>
                                <td>
                                    <div class="winloss-probability">
                                        <span>{{ (int) $opportunity->probability }}%</span>
                                        <div class="winloss-rate-track small"><span style="width: {{ min(100, max(0, (int) $opportunity->probability)) }}%"></span></div>
                                    </div>
                                </td>
                                <td><span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span></td>
                                <td>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $opportunity->assigned_to ?: '-' }}</td>
                                <td><a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-sm btn-muted">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">
                                    <div class="sales-empty-state compact">
                                        <strong>Tidak ada opportunity</strong>
                                        <span>Belum ada opportunity won/lost sesuai filter.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="customer-show-head">
                <div>
                    <h2>Quotation Analysis</h2>
                    <p>Quotations dengan status accepted, rejected, atau expired.</p>
                </div>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Quote Number</th>
                            <th>Title</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Valid Until</th>
                            <th>Customer</th>
                            <th>Opportunity</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            <tr>
                                <td><strong class="sales-code">{{ $quotation->quote_number }}</strong></td>
                                <td>{{ $quotation->title }}</td>
                                <td class="sales-amount">{{ $currency($quotation->amount) }}</td>
                                <td><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td>
                                <td>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</td>
                                <td>{{ $quotation->customer?->name ?: '-' }}</td>
                                <td>{{ $quotation->opportunity?->title ?: '-' }}</td>
                                <td><a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-sm btn-muted">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">
                                    <div class="sales-empty-state compact">
                                        <strong>Tidak ada quotation</strong>
                                        <span>Belum ada quotation final sesuai filter.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <div class="winloss-breakdown-grid">
            <article class="card customer-table-card">
                <div class="customer-show-head">
                    <div>
                        <h2>Breakdown by Assigned To</h2>
                        <p>Performa closing berdasarkan owner opportunity.</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-table sales-table">
                        <thead>
                            <tr>
                                <th>Assigned To</th>
                                <th>Won Count</th>
                                <th>Lost Count</th>
                                <th>Won Value</th>
                                <th>Lost Value</th>
                                <th>Win Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($assignedBreakdown as $row)
                                <tr>
                                    <td>{{ $row['assigned_to'] }}</td>
                                    <td>{{ $row['won_count'] }}</td>
                                    <td>{{ $row['lost_count'] }}</td>
                                    <td>{{ $currency($row['won_value']) }}</td>
                                    <td>{{ $currency($row['lost_value']) }}</td>
                                    <td>
                                        <div class="winloss-probability">
                                            <span>{{ number_format($row['win_rate'], 2) }}%</span>
                                            <div class="winloss-rate-track small"><span style="width: {{ min(100, $row['win_rate']) }}%"></span></div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="customer-empty">No assigned-to breakdown available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="customer-show-head">
                    <div>
                        <h2>Breakdown by Status</h2>
                        <p>Distribusi nilai opportunity won/lost.</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-table sales-table">
                        <thead>
                            <tr>
                                <th>Status</th>
                                <th>Count</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($statusBreakdown as $row)
                                <tr>
                                    <td><span class="status-badge status-{{ $row['status'] }}">{{ ucfirst($row['status']) }}</span></td>
                                    <td>{{ $row['count'] }}</td>
                                    <td>{{ $currency($row['value']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="customer-empty">No status breakdown available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="customer-show-head">
                    <div>
                        <h2>Monthly Breakdown</h2>
                        <p>Won/lost berdasarkan expected close month.</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-table sales-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Won Count</th>
                                <th>Lost Count</th>
                                <th>Won Value</th>
                                <th>Lost Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($monthlyBreakdown as $row)
                                <tr>
                                    <td>{{ $row['month'] }}</td>
                                    <td>{{ $row['won_count'] }}</td>
                                    <td>{{ $row['lost_count'] }}</td>
                                    <td>{{ $currency($row['won_value']) }}</td>
                                    <td>{{ $currency($row['lost_value']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="customer-empty">No monthly breakdown available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>

    <style>
        .winloss-filter-grid,
        .winloss-summary-grid,
        .winloss-breakdown-grid {
            display: grid;
            gap: 16px;
        }

        .winloss-filter-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            align-items: end;
        }

        .winloss-filter-actions {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }

        .winloss-summary-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .winloss-summary-card {
            padding: 20px;
        }

        .winloss-summary-card span {
            display: block;
            color: #6f6b7d;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .winloss-summary-card strong {
            display: block;
            color: #3b384c;
            font-size: 25px;
            margin-bottom: 8px;
        }

        .winloss-summary-card small {
            color: #8b879a;
            font-size: 13px;
        }

        .winloss-breakdown-grid {
            grid-template-columns: minmax(0, 1.1fr) minmax(0, .9fr);
        }

        .winloss-rate-track {
            height: 9px;
            border-radius: 999px;
            background: #ece9ff;
            overflow: hidden;
        }

        .winloss-rate-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #28c76f, #7367f0);
        }

        .winloss-rate-track.small {
            width: 120px;
            height: 7px;
        }

        .winloss-probability {
            display: grid;
            gap: 6px;
            min-width: 130px;
        }

        .winloss-probability span {
            color: #6f6b7d;
            font-size: 12px;
            font-weight: 600;
        }

        @media (max-width: 1100px) {
            .winloss-filter-grid,
            .winloss-summary-grid,
            .winloss-breakdown-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 720px) {
            .winloss-filter-grid,
            .winloss-summary-grid,
            .winloss-breakdown-grid {
                grid-template-columns: 1fr;
            }

            .winloss-filter-actions {
                justify-content: flex-start;
            }
        }
    </style>
@endsection
