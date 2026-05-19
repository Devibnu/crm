@extends('admin.layouts.app')

@section('title', 'Win/Lost Analysis - Krakatau CRM')

@section('content')
    @php
        $currency = fn ($value) => 'Rp '.number_format((float) $value, 2, ',', '.');
        $hasFilters = $filters['status'] !== 'all' || $filters['assigned_to'] || $filters['date_from'] || $filters['date_to'];
        $hasData = $opportunities->isNotEmpty() || $quotations->isNotEmpty();
    @endphp

    <span hidden data-doc-title-en="Win/Lost Analysis - Krakatau CRM" data-doc-title-id="Analisa Win/Lost - Krakatau CRM"></span>

    <section class="service-page customer-list-page sales-workspace winloss-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'analysis'])
            </div>
            <div>
                <span class="dashboard-hero-badge" data-lang-en="Outcome Intelligence" data-lang-id="Outcome Intelligence">Outcome Intelligence</span>
                <h1 data-lang-en="Win/Lost Analysis" data-lang-id="Analisa Win/Lost">Win/Lost Analysis</h1>
                <p data-lang-en="Review closing outcomes to understand win signals, loss patterns, and quote acceptance performance." data-lang-id="Tinjau hasil closing untuk memahami sinyal kemenangan, pola kekalahan, dan performa penerimaan quotation.">Review closing outcomes to understand win signals, loss patterns, and quote acceptance performance.</p>
            </div>
        </article>

        <article class="card customer-table-card winloss-filter-shell">
            <div class="sales-section-head winloss-head">
                <div>
                    <h2 data-lang-en="Filters" data-lang-id="Filter">Filters</h2>
                    <p data-lang-en="Scope the analysis by closing status, owner, and reporting date range." data-lang-id="Batasi analisa berdasarkan status closing, owner, dan rentang tanggal laporan.">Scope the analysis by closing status, owner, and reporting date range.</p>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.sales.win-loss') }}" class="winloss-filter-grid">
                <label class="field">
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <select name="status">
                        <option value="all" data-lang-en="All" data-lang-id="Semua" @selected($filters['status'] === 'all')>All</option>
                        <option value="won" data-lang-en="Won" data-lang-id="Won" @selected($filters['status'] === 'won')>Won</option>
                        <option value="lost" data-lang-en="Lost" data-lang-id="Lost" @selected($filters['status'] === 'lost')>Lost</option>
                    </select>
                </label>

                <label class="field">
                    <span data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</span>
                    <input type="text" name="assigned_to" list="assigned-to-options" value="{{ $filters['assigned_to'] }}" placeholder="All owners" data-placeholder-en="All owners" data-placeholder-id="Semua owner">
                    <datalist id="assigned-to-options">
                        @foreach ($assignedToOptions as $assignedTo)
                            <option value="{{ $assignedTo }}"></option>
                        @endforeach
                    </datalist>
                </label>

                <label class="field">
                    <span data-lang-en="Date From" data-lang-id="Tanggal Dari">Date From</span>
                    <input type="date" name="date_from" value="{{ $filters['date_from'] }}">
                </label>

                <label class="field">
                    <span data-lang-en="Date To" data-lang-id="Tanggal Sampai">Date To</span>
                    <input type="date" name="date_to" value="{{ $filters['date_to'] }}">
                </label>

                <div class="winloss-filter-actions">
                    <button type="submit" class="btn btn-primary" data-lang-en="Apply Filter" data-lang-id="Terapkan Filter">Apply Filter</button>
                    @if ($hasFilters)
                        <a href="{{ route('admin.sales.win-loss') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                    @endif
                </div>
            </form>
        </article>

        <div class="winloss-summary-grid">
            <article class="card sales-summary-card winloss-summary-card">
                <span data-lang-en="Won Deals" data-lang-id="Deal Menang">Won Deals</span>
                <strong>{{ number_format($summary['total_won_count']) }}</strong>
                <small data-lang-en="{{ $currency($summary['average_won_value']) }} average value" data-lang-id="Rata-rata nilai {{ $currency($summary['average_won_value']) }}">{{ $currency($summary['average_won_value']) }} average value</small>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span data-lang-en="Lost Deals" data-lang-id="Deal Kalah">Lost Deals</span>
                <strong>{{ number_format($summary['total_lost_count']) }}</strong>
                <small data-lang-en="{{ number_format($summary['lost_rate'], 2) }}% lost rate and {{ $currency($summary['average_lost_value']) }} average value" data-lang-id="Lost rate {{ number_format($summary['lost_rate'], 2) }}% dan rata-rata nilai {{ $currency($summary['average_lost_value']) }}">{{ number_format($summary['lost_rate'], 2) }}% lost rate and {{ $currency($summary['average_lost_value']) }} average value</small>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span data-lang-en="Win Rate" data-lang-id="Win Rate">Win Rate</span>
                <strong>{{ number_format($summary['win_rate'], 2) }}%</strong>
                <div class="winloss-rate-track"><span style="width: {{ min(100, $summary['win_rate']) }}%"></span></div>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span data-lang-en="Won Value" data-lang-id="Nilai Menang">Won Value</span>
                <strong>{{ $currency($summary['total_won_value']) }}</strong>
                <small data-lang-en="{{ number_format($summary['total_won_count']) }} won opportunities" data-lang-id="{{ number_format($summary['total_won_count']) }} opportunity menang">{{ number_format($summary['total_won_count']) }} won opportunities</small>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span data-lang-en="Lost Value" data-lang-id="Nilai Kalah">Lost Value</span>
                <strong>{{ $currency($summary['total_lost_value']) }}</strong>
                <small data-lang-en="{{ number_format($summary['total_lost_count']) }} lost opportunities" data-lang-id="{{ number_format($summary['total_lost_count']) }} opportunity kalah">{{ number_format($summary['total_lost_count']) }} lost opportunities</small>
            </article>
            <article class="card sales-summary-card winloss-summary-card">
                <span data-lang-en="Quote Acceptance Rate" data-lang-id="Acceptance Rate Quotation">Quote Acceptance Rate</span>
                <strong>{{ number_format($summary['quote_acceptance_rate'], 2) }}%</strong>
                <small data-lang-en="{{ $summary['accepted_count'] }} accepted of {{ $summary['accepted_count'] + $summary['rejected_count'] + $summary['expired_count'] }} final quotes" data-lang-id="{{ $summary['accepted_count'] }} diterima dari {{ $summary['accepted_count'] + $summary['rejected_count'] + $summary['expired_count'] }} quotation final">{{ $summary['accepted_count'] }} accepted of {{ $summary['accepted_count'] + $summary['rejected_count'] + $summary['expired_count'] }} final quotes</small>
            </article>
        </div>

        @unless ($hasData)
            <article class="card customer-table-card winloss-shell">
                <div class="sales-empty-state compact">
                    <strong data-lang-en="No win/lost data yet" data-lang-id="Belum ada data win/lost">No win/lost data yet</strong>
                    <span data-lang-en="The current filters did not find won or lost opportunities, or any final quotations." data-lang-id="Filter saat ini tidak menemukan opportunity won/lost atau quotation final.">The current filters did not find won or lost opportunities, or any final quotations.</span>
                </div>
            </article>
        @endunless

        <article class="card customer-table-card winloss-shell">
            <div class="customer-show-head winloss-head">
                <div>
                    <h2 data-lang-en="Opportunity Analysis" data-lang-id="Analisa Opportunity">Opportunity Analysis</h2>
                    <p data-lang-en="Closed opportunities currently marked as won or lost." data-lang-id="Opportunity closing yang saat ini berstatus won atau lost.">Closed opportunities currently marked as won or lost.</p>
                </div>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table winloss-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                            <th data-lang-en="Company" data-lang-id="Perusahaan">Company</th>
                            <th data-lang-en="Estimated Value" data-lang-id="Estimasi Nilai">Estimated Value</th>
                            <th data-lang-en="Probability" data-lang-id="Probabilitas">Probability</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Expected Close" data-lang-id="Estimasi Closing">Expected Close</th>
                            <th data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($opportunities as $opportunity)
                            <tr>
                                <td>
                                    <div class="winloss-title-cell">
                                        <strong>{{ $opportunity->title }}</strong>
                                        <small>{{ $opportunity->contact_name ?: ($opportunity->company_name ?: '-') }}</small>
                                    </div>
                                </td>
                                <td><span class="sales-source-pill">{{ $opportunity->company_name ?: '-' }}</span></td>
                                <td class="sales-amount">{{ $currency($opportunity->estimated_value) }}</td>
                                <td>
                                    <div class="winloss-probability">
                                        <span>{{ (int) $opportunity->probability }}%</span>
                                        <div class="winloss-rate-track small"><span style="width: {{ min(100, max(0, (int) $opportunity->probability)) }}%"></span></div>
                                    </div>
                                </td>
                                <td><span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span></td>
                                <td>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</td>
                                <td><span class="sales-assignee-pill">{{ $opportunity->assigned_to ?: '-' }}</span></td>
                                <td><a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">
                                    <div class="sales-empty-state compact">
                                        <strong data-lang-en="No opportunities" data-lang-id="Tidak ada opportunity">No opportunities</strong>
                                        <span data-lang-en="There are no won or lost opportunities for the current filters." data-lang-id="Belum ada opportunity won/lost sesuai filter.">There are no won or lost opportunities for the current filters.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card winloss-shell">
            <div class="customer-show-head winloss-head">
                <div>
                    <h2 data-lang-en="Quotation Analysis" data-lang-id="Analisa Quotation">Quotation Analysis</h2>
                    <p data-lang-en="Final quotations currently accepted, rejected, or expired." data-lang-id="Quotation final yang saat ini berstatus accepted, rejected, atau expired.">Final quotations currently accepted, rejected, or expired.</p>
                </div>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table winloss-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Quote Number" data-lang-id="Nomor Quotation">Quote Number</th>
                            <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                            <th data-lang-en="Amount" data-lang-id="Nominal">Amount</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Valid Until" data-lang-id="Berlaku Sampai">Valid Until</th>
                            <th data-lang-en="Customer" data-lang-id="Customer">Customer</th>
                            <th data-lang-en="Opportunity" data-lang-id="Opportunity">Opportunity</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($quotations as $quotation)
                            <tr>
                                <td><strong class="sales-code">{{ $quotation->quote_number }}</strong></td>
                                <td>
                                    <div class="winloss-title-cell">
                                        <strong>{{ $quotation->title }}</strong>
                                        <small>{{ $quotation->customer?->name ?: '-' }}</small>
                                    </div>
                                </td>
                                <td class="sales-amount">{{ $currency($quotation->amount) }}</td>
                                <td><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></td>
                                <td>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</td>
                                <td><span class="sales-source-pill">{{ $quotation->customer?->name ?: '-' }}</span></td>
                                <td>{{ $quotation->opportunity?->title ?: '-' }}</td>
                                <td><a href="{{ route('admin.sales.deals.show', $quotation) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">
                                    <div class="sales-empty-state compact">
                                        <strong data-lang-en="No quotations" data-lang-id="Tidak ada quotation">No quotations</strong>
                                        <span data-lang-en="There are no final quotations for the current filters." data-lang-id="Belum ada quotation final sesuai filter.">There are no final quotations for the current filters.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <div class="winloss-breakdown-grid">
            <article class="card customer-table-card winloss-shell">
                <div class="customer-show-head winloss-head">
                    <div>
                        <h2 data-lang-en="Breakdown by Assigned To" data-lang-id="Breakdown per Assigned To">Breakdown by Assigned To</h2>
                        <p data-lang-en="Closing performance grouped by opportunity owner." data-lang-id="Performa closing berdasarkan owner opportunity.">Closing performance grouped by opportunity owner.</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-table sales-table winloss-table">
                        <thead>
                            <tr>
                                <th data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</th>
                                <th data-lang-en="Won Count" data-lang-id="Jumlah Won">Won Count</th>
                                <th data-lang-en="Lost Count" data-lang-id="Jumlah Lost">Lost Count</th>
                                <th data-lang-en="Won Value" data-lang-id="Nilai Won">Won Value</th>
                                <th data-lang-en="Lost Value" data-lang-id="Nilai Lost">Lost Value</th>
                                <th data-lang-en="Win Rate" data-lang-id="Win Rate">Win Rate</th>
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
                                    <td colspan="6" class="customer-empty" data-lang-en="No assigned-to breakdown available." data-lang-id="Belum ada breakdown assigned-to.">No assigned-to breakdown available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="card customer-table-card winloss-shell">
                <div class="customer-show-head winloss-head">
                    <div>
                        <h2 data-lang-en="Breakdown by Status" data-lang-id="Breakdown per Status">Breakdown by Status</h2>
                        <p data-lang-en="Value distribution across won and lost opportunity outcomes." data-lang-id="Distribusi nilai berdasarkan outcome won dan lost.">Value distribution across won and lost opportunity outcomes.</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-table sales-table winloss-table">
                        <thead>
                            <tr>
                                <th data-lang-en="Status" data-lang-id="Status">Status</th>
                                <th data-lang-en="Count" data-lang-id="Jumlah">Count</th>
                                <th data-lang-en="Value" data-lang-id="Nilai">Value</th>
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
                                    <td colspan="3" class="customer-empty" data-lang-en="No status breakdown available." data-lang-id="Belum ada breakdown status.">No status breakdown available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>

            <article class="card customer-table-card winloss-shell">
                <div class="customer-show-head winloss-head">
                    <div>
                        <h2 data-lang-en="Monthly Breakdown" data-lang-id="Breakdown Bulanan">Monthly Breakdown</h2>
                        <p data-lang-en="Won and lost outcomes grouped by expected close month." data-lang-id="Outcome won dan lost berdasarkan expected close month.">Won and lost outcomes grouped by expected close month.</p>
                    </div>
                </div>

                <div class="customer-table-wrap">
                    <table class="customer-table sales-table winloss-table">
                        <thead>
                            <tr>
                                <th data-lang-en="Month" data-lang-id="Bulan">Month</th>
                                <th data-lang-en="Won Count" data-lang-id="Jumlah Won">Won Count</th>
                                <th data-lang-en="Lost Count" data-lang-id="Jumlah Lost">Lost Count</th>
                                <th data-lang-en="Won Value" data-lang-id="Nilai Won">Won Value</th>
                                <th data-lang-en="Lost Value" data-lang-id="Nilai Lost">Lost Value</th>
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
                                    <td colspan="5" class="customer-empty" data-lang-en="No monthly breakdown available." data-lang-id="Belum ada breakdown bulanan.">No monthly breakdown available.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
@endsection
