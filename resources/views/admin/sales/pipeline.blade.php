@extends('admin.layouts.app')

@section('title', 'Pipeline & Forecasting - Krakatau CRM')

@section('content')
    @php
        $totalOpportunities = collect($stageRows)->sum('count');
        $activeFilterCount = collect($filters)->filter(fn ($value) => $value !== '')->count();
        $stageRowsByKey = collect($stageRows)->keyBy('key');
    @endphp

    <section class="crm-pipeline-page pipeline-workspace-page">
        <header class="lead-list-header pipeline-workspace-banner">
            <div class="pipeline-heading-block">
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>Pipeline & Forecasting</h1>
                <p>Pantau peluang bisnis berdasarkan stage, nilai, probabilitas, dan estimasi closing.</p>
            </div>
            <div class="pipeline-banner-meta">
                <span class="pipeline-count-badge">{{ number_format($totalOpportunities) }} opportunities · {{ count($stages) }} stages</span>
            </div>
        </header>

        <form method="GET" action="{{ route('admin.sales.pipeline') }}" class="crm-pipeline-toolbar pipeline-filter-toolbar">
            <label><span>Owner</span><input type="text" name="assigned_to" value="{{ $filters['assigned_to'] }}" placeholder="All owners"></label>
            <label><span>Close Date From</span><input type="date" name="expected_close_date_from" value="{{ $filters['expected_close_date_from'] }}"></label>
            <label><span>Close Date To</span><input type="date" name="expected_close_date_to" value="{{ $filters['expected_close_date_to'] }}"></label>
            <div class="pipeline-filter-actions">
                <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                @if ($activeFilterCount > 0)
                    <a href="{{ route('admin.sales.pipeline') }}" class="btn btn-sm btn-muted">Reset</a>
                @endif
            </div>
        </form>

        <div class="crm-pipeline-summary pipeline-kpi-grid" aria-label="Pipeline summary">
            <div><span>Total Pipeline</span><strong>Rp {{ number_format((float) $summary['total_pipeline_value'], 2, ',', '.') }}</strong></div>
            <div><span>Weighted Forecast</span><strong>Rp {{ number_format((float) $summary['weighted_forecast'], 2, ',', '.') }}</strong></div>
            <div><span>Won Value</span><strong>Rp {{ number_format((float) $summary['won_value'], 2, ',', '.') }}</strong></div>
            <div><span>Open Opportunities</span><strong>{{ number_format($summary['open_opportunities_count']) }}</strong></div>
        </div>

        <div class="pipeline-board-heading">
            <div><h2>Opportunity Stages</h2><p>Prioritas berdasarkan closing date, probability, dan value.</p></div>
            <div class="pipeline-legend" aria-label="Pipeline status legend">
                <span class="overdue">Overdue</span>
                <span class="soon">Closing soon</span>
                <span class="won">Won</span>
            </div>
        </div>

        <section class="crm-pipeline-board pipeline-stage-board" aria-label="Sales pipeline board">
            @foreach ($stages as $stageKey => $stageName)
                @php
                    $rows = $stageRowsByKey->get($stageKey, []);
                    $items = $stageOpportunities[$stageKey] ?? collect();
                @endphp
                <section class="crm-pipeline-column pipeline-stage-column" data-stage="{{ $stageKey }}">
                    <header class="crm-pipeline-column-head">
                        <div><h2>{{ $stageName }}</h2><span>{{ $rows['count'] ?? 0 }}</span></div>
                        <strong>Rp {{ number_format((float) ($rows['total_value'] ?? 0), 0, ',', '.') }}</strong>
                    </header>

                    <div class="crm-pipeline-deals">
                        @forelse ($items as $opportunity)
                            @php
                                $probability = min(max((int) $opportunity->probability, 0), 100);
                                $isTerminal = in_array($opportunity->status, ['won', 'lost'], true);
                                $isOverdue = ! $isTerminal && $opportunity->expected_close_date?->lt(today());
                                $isClosingSoon = ! $isTerminal
                                    && $opportunity->expected_close_date
                                    && $opportunity->expected_close_date->gte(today())
                                    && $opportunity->expected_close_date->lte(today()->addDays(14));
                                $isPriority = $isOverdue
                                    || $isClosingSoon
                                    || $probability >= 70
                                    || (float) $opportunity->estimated_value >= 100000000;
                            @endphp
                            <a href="{{ route('admin.sales.opportunities.show', $opportunity) }}" @class([
                                'crm-deal-card',
                                'pipeline-opportunity-card',
                                'is-priority' => $isPriority,
                                'is-overdue' => $isOverdue,
                                'is-closing-soon' => $isClosingSoon,
                                'is-won' => $opportunity->status === 'won',
                            ])>
                                <div class="crm-deal-card-title">
                                    <strong>{{ $opportunity->title }}</strong>
                                    @if ($isOverdue)
                                        <span class="pipeline-urgency overdue">Overdue</span>
                                    @elseif ($isClosingSoon)
                                        <span class="pipeline-urgency soon">Closing soon</span>
                                    @elseif ($opportunity->status === 'won')
                                        <span class="pipeline-urgency won">Won</span>
                                    @endif
                                </div>
                                <p>{{ $opportunity->company_name ?: 'No company' }}{{ $opportunity->contact_name ? ' · '.$opportunity->contact_name : '' }}</p>
                                <b>Rp {{ number_format((float) $opportunity->estimated_value, 0, ',', '.') }}</b>
                                <div class="pipeline-probability-row">
                                    <span><i style="width: {{ $probability }}%"></i></span>
                                    <strong>{{ $probability }}%</strong>
                                </div>
                                <div class="crm-deal-card-meta">
                                    <span>{{ $opportunity->expected_close_date?->format('d M Y') ?: 'No close date' }}</span>
                                    <span>{{ $opportunity->assigned_to ?: 'Unassigned' }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="crm-pipeline-empty">Belum ada opportunity di stage ini.</div>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </section>

        <section class="crm-forecast-section pipeline-forecast-section">
            <div class="crm-content-heading"><div><h2>Forecast by Stage</h2><p>Pipeline value and weighted forecast summary.</p></div></div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead><tr><th>Stage</th><th>Opportunity Count</th><th>Total Value</th><th>Average Probability</th><th>Weighted Value</th></tr></thead>
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
        </section>
    </section>
@endsection
