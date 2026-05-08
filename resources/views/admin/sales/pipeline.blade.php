@extends('admin.layouts.app')

@section('title', 'Pipeline & Forecasting - Krakatau CRM')

@section('content')
    @php
        $totalOpportunities = collect($stageRows)->sum('count');
        $activeFilterCount = collect($filters)->filter(fn ($value) => $value !== '')->count();
    @endphp

    <section class="service-page customer-list-page pipeline-page">
        <article class="card service-card customer-list-card pipeline-hero-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'pipeline'])
            </div>
            <div>
                <h1>Pipeline & Forecasting</h1>
                <p>Visualisasi pipeline penjualan dan prediksi closing.</p>
            </div>
            <div class="pipeline-hero-meta">
                <span>{{ $totalOpportunities }} opportunities</span>
                @if ($activeFilterCount > 0)
                    <span>{{ $activeFilterCount }} active filters</span>
                @endif
            </div>
        </article>

        <article class="card customer-table-card pipeline-filter-card">
            <form method="GET" action="{{ route('admin.sales.pipeline') }}" class="pipeline-filter-form">
                <label class="field">
                    <span>Assigned To</span>
                    <input type="text" name="assigned_to" value="{{ $filters['assigned_to'] }}" placeholder="Cari assigned_to">
                </label>

                <label class="field">
                    <span>Expected Close Date From</span>
                    <input type="date" name="expected_close_date_from" value="{{ $filters['expected_close_date_from'] }}">
                </label>

                <label class="field">
                    <span>Expected Close Date To</span>
                    <input type="date" name="expected_close_date_to" value="{{ $filters['expected_close_date_to'] }}">
                </label>

                <div class="pipeline-filter-actions">
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                    @if ($filters['assigned_to'] !== '' || $filters['expected_close_date_from'] !== '' || $filters['expected_close_date_to'] !== '')
                        <a href="{{ route('admin.sales.pipeline') }}" class="btn btn-muted">Reset</a>
                    @endif
                </div>
            </form>
        </article>

        <section class="pipeline-summary-grid">
            <article class="card pipeline-summary-card">
                <span>Total Pipeline Value</span>
                <strong>Rp {{ number_format((float) $summary['total_pipeline_value'], 2, ',', '.') }}</strong>
            </article>
            <article class="card pipeline-summary-card">
                <span>Weighted Forecast</span>
                <strong>Rp {{ number_format((float) $summary['weighted_forecast'], 2, ',', '.') }}</strong>
            </article>
            <article class="card pipeline-summary-card">
                <span>Won Value</span>
                <strong>Rp {{ number_format((float) $summary['won_value'], 2, ',', '.') }}</strong>
            </article>
            <article class="card pipeline-summary-card">
                <span>Open Opportunities</span>
                <strong>{{ $summary['open_opportunities_count'] }}</strong>
            </article>
        </section>

        <section class="pipeline-board">
            @foreach ($stages as $stageKey => $stageName)
                @php
                    $rows = collect($stageRows)->firstWhere('key', $stageKey);
                    $items = $stageOpportunities[$stageKey] ?? collect();
                @endphp
                <article class="card pipeline-stage-column" data-stage="{{ $stageKey }}">
                    <header class="pipeline-stage-head">
                        <div class="pipeline-stage-title">
                            <h3>{{ $stageName }}</h3>
                            <span class="pipeline-stage-count">{{ $rows['count'] ?? 0 }}</span>
                        </div>
                        <p>{{ $rows['count'] ?? 0 }} opportunities</p>
                        <strong>Rp {{ number_format((float) ($rows['total_value'] ?? 0), 2, ',', '.') }}</strong>
                    </header>

                    <div class="pipeline-stage-list">
                        @forelse ($items as $opportunity)
                            @php
                                $probability = min(max((int) $opportunity->probability, 0), 100);
                            @endphp
                            <article class="pipeline-opportunity-card">
                                <div class="pipeline-opportunity-top">
                                    <h4>{{ $opportunity->title }}</h4>
                                    <span class="status-badge status-{{ $opportunity->status }}">{{ ucfirst($opportunity->status) }}</span>
                                </div>

                                <p class="pipeline-company">{{ $opportunity->company_name ?: 'No company' }}</p>

                                <div class="pipeline-opportunity-meta">
                                    <div><span>Value</span><strong>Rp {{ number_format((float) $opportunity->estimated_value, 2, ',', '.') }}</strong></div>
                                    <div><span>Close Date</span><strong>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</strong></div>
                                    <div><span>Assigned</span><strong>{{ $opportunity->assigned_to ?: '-' }}</strong></div>
                                </div>

                                <div class="pipeline-probability-wrap">
                                    <span>{{ $probability }}%</span>
                                    <div class="pipeline-probability-track"><span style="width: {{ $probability }}%"></span></div>
                                </div>

                                <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" class="btn btn-sm btn-muted pipeline-view-btn">View</a>
                            </article>
                        @empty
                            <div class="pipeline-empty">No opportunities in this stage</div>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </section>

        <article class="card customer-table-card">
            <h3 class="pipeline-table-title">Forecast by Stage</h3>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Stage</th>
                            <th>Opportunity Count</th>
                            <th>Total Value</th>
                            <th>Average Probability</th>
                            <th>Weighted Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($stageRows as $row)
                            <tr>
                                <td>{{ $row['name'] }}</td>
                                <td>{{ $row['count'] }}</td>
                                <td>Rp {{ number_format((float) $row['total_value'], 2, ',', '.') }}</td>
                                <td>{{ number_format((float) $row['avg_probability'], 2, ',', '.') }}%</td>
                                <td>Rp {{ number_format((float) $row['weighted_value'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <style>
        .pipeline-page {
            --pipeline-ink: #3c3a4a;
            --pipeline-muted: #7c7891;
            --pipeline-border: #ebe8f4;
            --pipeline-card: #ffffff;
            --pipeline-surface: #f8f7fc;
        }

        .pipeline-page {
            gap: 18px;
        }

        .pipeline-hero-card {
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #ffffff 0%, #f6f5ff 55%, #f3f1ff 100%);
        }

        .pipeline-hero-meta {
            margin-left: auto;
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: end;
        }

        .pipeline-hero-meta span {
            font-size: 12px;
            color: #5f5a77;
            background: #ffffff;
            border: 1px solid #e8e4f6;
            border-radius: 999px;
            padding: 5px 10px;
            font-weight: 600;
        }

        .pipeline-filter-card {
            border: 1px solid var(--pipeline-border);
            background: var(--pipeline-surface);
        }

        .pipeline-filter-form {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .pipeline-filter-actions {
            display: flex;
            align-items: end;
            gap: 8px;
        }

        .pipeline-summary-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 12px;
        }

        .pipeline-summary-card {
            padding: 16px;
            border-radius: 6px;
            display: grid;
            gap: 8px;
            border: 1px solid var(--pipeline-border);
            background: var(--pipeline-card);
        }

        .pipeline-summary-card span {
            color: #6f6b7d;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: .02em;
        }

        .pipeline-summary-card strong {
            font-size: 22px;
            font-weight: 600;
            color: #3b384c;
        }

        .pipeline-board {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(280px, 320px);
            gap: 12px;
            overflow-x: auto;
            overflow-y: hidden;
            padding-bottom: 6px;
            align-items: start;
            scroll-snap-type: x proximity;
            border: 1px solid var(--pipeline-border);
            border-radius: 10px;
            background: #f6f5fb;
            padding: 10px;
        }

        .pipeline-stage-column {
            padding: 14px;
            border-radius: 8px;
            min-height: 220px;
            max-height: 70vh;
            box-shadow: 0 4px 18px rgba(47, 43, 61, .10);
            display: grid;
            grid-template-rows: auto minmax(0, 1fr);
            scroll-snap-align: start;
            border: 1px solid #e8e5f3;
            background: #fbfafe;
        }

        .pipeline-stage-column[data-stage='open'] {
            background: #f8fbff;
        }

        .pipeline-stage-column[data-stage='qualified'] {
            background: #f6fff9;
        }

        .pipeline-stage-column[data-stage='proposal'] {
            background: #fffaf4;
        }

        .pipeline-stage-column[data-stage='negotiation'] {
            background: #f9f8ff;
        }

        .pipeline-stage-column[data-stage='won'] {
            background: #f4fff8;
        }

        .pipeline-stage-column[data-stage='lost'] {
            background: #fff7f7;
        }

        .pipeline-stage-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
        }

        .pipeline-stage-count {
            min-width: 26px;
            text-align: center;
            border-radius: 999px;
            padding: 2px 8px;
            font-size: 11px;
            font-weight: 700;
            color: #5c5775;
            background: #ece9fb;
            border: 1px solid #dfd8fa;
        }

        .pipeline-stage-head h3 {
            margin: 0;
            font-size: 16px;
        }

        .pipeline-stage-head p {
            margin: 6px 0 4px;
            font-size: 12px;
            color: #8b879a;
        }

        .pipeline-stage-head strong {
            font-size: 13px;
            color: #4c4861;
        }

        .pipeline-stage-list {
            display: grid;
            gap: 10px;
            margin-top: 12px;
            overflow-y: auto;
            min-height: 0;
            padding-right: 3px;
        }

        .pipeline-opportunity-card {
            border: 1px solid #eceaf3;
            border-radius: 8px;
            padding: 10px;
            display: grid;
            gap: 8px;
            background: #fff;
            box-shadow: 0 1px 0 rgba(47, 43, 61, .04);
        }

        .pipeline-opportunity-top {
            display: flex;
            justify-content: space-between;
            align-items: start;
            gap: 8px;
        }

        .pipeline-opportunity-top h4 {
            margin: 0;
            font-size: 13px;
            line-height: 1.4;
            word-break: break-word;
        }

        .pipeline-company {
            margin: 0;
            font-size: 12px;
            color: #8b879a;
            word-break: break-word;
        }

        .pipeline-opportunity-meta {
            display: grid;
            gap: 6px;
        }

        .pipeline-opportunity-meta div {
            display: flex;
            justify-content: space-between;
            gap: 8px;
            font-size: 11px;
        }

        .pipeline-opportunity-meta span {
            color: #8b879a;
        }

        .pipeline-opportunity-meta strong {
            font-weight: 600;
            color: #4c4861;
            text-align: right;
            word-break: break-word;
        }

        .pipeline-probability-wrap {
            display: grid;
            gap: 5px;
        }

        .pipeline-probability-wrap span {
            font-size: 11px;
            color: #6f6b7d;
            font-weight: 600;
        }

        .pipeline-probability-track {
            height: 8px;
            border-radius: 999px;
            background: #ece9ff;
            overflow: hidden;
        }

        .pipeline-probability-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #7367f0, #8f84ff);
        }

        .pipeline-view-btn {
            width: 100%;
            justify-content: center;
            font-weight: 600;
        }

        .pipeline-empty {
            border: 1px dashed #d9d5e7;
            border-radius: 8px;
            color: #8b879a;
            font-size: 12px;
            padding: 12px;
            text-align: center;
            background: #fbfbfd;
        }

        .pipeline-table-title {
            margin: 0 0 10px;
            font-size: 18px;
        }

        @media (max-width: 1200px) {
            .pipeline-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pipeline-summary-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .pipeline-stage-column {
                max-height: 64vh;
            }

            .pipeline-hero-meta {
                width: 100%;
                justify-content: start;
                margin-left: 0;
                margin-top: 8px;
            }
        }

        @media (max-width: 700px) {
            .pipeline-filter-form,
            .pipeline-summary-grid {
                grid-template-columns: 1fr;
            }

            .pipeline-board {
                grid-auto-columns: minmax(88vw, 88vw);
            }

            .pipeline-stage-column {
                max-height: none;
            }

            .pipeline-stage-list {
                overflow-y: visible;
            }
        }
    </style>
@endsection
