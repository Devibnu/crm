@extends('admin.layouts.app')

@section('title', 'CRM Overview - Krakatau CRM')

@section('content')
    @php
        $tx = static fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
        $pageTitleTranslation = $tx('CRM Overview', 'CRM Overview');
        $pageDescriptionTranslation = $tx(
            'Cross-functional CRM summary for revenue, leads, sales, service, marketing, and customer journey in one executive workspace.',
            'Ringkasan CRM lintas revenue, lead, sales, service, marketing, dan customer journey dalam satu executive workspace.'
        );
        $summaryCardItems = collect($summaryCards)->map(function (array $card) use ($tx, $metrics) {
            return match ($card['label']) {
                'Revenue' => $card + [
                    'label_translation' => $tx('Revenue', 'Pendapatan'),
                    'hint_translation' => $tx('Won Rp '.number_format((float) $metrics['won_value'], 0, ',', '.'), 'Won Rp '.number_format((float) $metrics['won_value'], 0, ',', '.')),
                ],
                'Lead Growth' => $card + [
                    'label_translation' => $tx('Lead Growth', 'Pertumbuhan Lead'),
                    'hint_translation' => $tx(number_format($metrics['qualified_leads']).' qualified', number_format($metrics['qualified_leads']).' qualified'),
                ],
                'Pipeline' => $card + [
                    'label_translation' => $tx('Pipeline', 'Pipeline'),
                    'hint_translation' => $tx(number_format($metrics['total_opportunities']).' opportunities', number_format($metrics['total_opportunities']).' opportunity'),
                ],
                'SLA Rate' => $card + [
                    'label_translation' => $tx('SLA Rate', 'Rasio SLA'),
                    'hint_translation' => $tx(number_format($metrics['met_sla_tickets']).' met / '.number_format($metrics['tracked_sla_tickets']).' tracked', number_format($metrics['met_sla_tickets']).' tercapai / '.number_format($metrics['tracked_sla_tickets']).' terlacak'),
                ],
                'Customers' => $card + [
                    'label_translation' => $tx('Customers', 'Customer'),
                    'hint_translation' => $tx(number_format($metrics['active_customers']).' active', number_format($metrics['active_customers']).' aktif'),
                ],
                'Campaigns' => $card + [
                    'label_translation' => $tx('Campaigns', 'Campaign'),
                    'hint_translation' => $tx($card['hint'], $card['hint']),
                ],
                default => $card + [
                    'label_translation' => $tx($card['label'], $card['label']),
                    'hint_translation' => $tx($card['hint'], $card['hint']),
                ],
            };
        });
        $chartPalette = [
            'new' => '#7367f0',
            'qualified' => '#28c76f',
            'open' => '#7367f0',
            'proposal' => '#00bad1',
            'negotiation' => '#ff9f43',
            'won' => '#16a34a',
            'lost' => '#ff4c51',
            'direct' => '#7367f0',
        ];

        $linePoints = static function (
            array $values,
            int $pointCount,
            int $chartWidth,
            int $paddingX,
            int $paddingY,
            int $innerHeight,
            float|int $maxValue
        ): array {
            $stepX = ($chartWidth - ($paddingX * 2)) / max($pointCount - 1, 1);

            return collect($values)
                ->values()
                ->map(function ($value, $index) use ($stepX, $paddingX, $paddingY, $innerHeight, $maxValue) {
                    return [
                        'x' => $paddingX + ($stepX * $index),
                        'y' => $paddingY + $innerHeight - ((((float) $value) / max((float) $maxValue, 1)) * $innerHeight),
                    ];
                })
                ->all();
        };

        $smoothLinePath = static function (array $points): string {
            if ($points === []) {
                return '';
            }

            if (count($points) === 1) {
                return 'M '.number_format($points[0]['x'], 2, '.', '').' '.number_format($points[0]['y'], 2, '.', '');
            }

            $path = 'M '.number_format($points[0]['x'], 2, '.', '').' '.number_format($points[0]['y'], 2, '.', '');

            for ($index = 1; $index < count($points); $index++) {
                $previous = $points[$index - 1];
                $current = $points[$index];
                $controlX = ($previous['x'] + $current['x']) / 2;

                $path .= ' C '
                    .number_format($controlX, 2, '.', '').' '.number_format($previous['y'], 2, '.', '').', '
                    .number_format($controlX, 2, '.', '').' '.number_format($current['y'], 2, '.', '').', '
                    .number_format($current['x'], 2, '.', '').' '.number_format($current['y'], 2, '.', '');
            }

            return $path;
        };

        $revenueLabels = $revenueTrend['labels'] ?? [];
        $revenueValues = $revenueTrend['values'] ?? [];
        $revenueChartWidth = 640;
        $revenueChartHeight = 250;
        $revenuePaddingX = 24;
        $revenuePaddingY = 20;
        $revenueInnerHeight = $revenueChartHeight - ($revenuePaddingY * 2);
        $revenueInnerWidth = $revenueChartWidth - ($revenuePaddingX * 2);
        $revenuePointCount = max(count($revenueLabels), 2);
        $revenueMax = max(1, (float) collect($revenueValues)->max());

        $leadLabels = $leadGrowth['labels'] ?? [];
        $leadValues = $leadGrowth['values'] ?? [];
        $qualifiedLeadValues = $leadGrowth['qualified_values'] ?? [];
        $leadChartWidth = 640;
        $leadChartHeight = 250;
        $leadPaddingX = 24;
        $leadPaddingY = 20;
        $leadInnerHeight = $leadChartHeight - ($leadPaddingY * 2);
        $leadPointCount = max(count($leadLabels), 2);
        $leadMax = max(1, (int) collect($leadValues)->merge($qualifiedLeadValues)->max());

        $leadSourceItems = $leadSourceOverview->values();
        $leadSourceTotal = max((int) $leadSourceItems->sum('total'), 1);
        $leadSourceSegments = [];
        $leadSourceOffset = 0;

        foreach ($leadSourceItems as $item) {
            $key = strtolower((string) $item->source_label);
            $share = round(((int) $item->total / $leadSourceTotal) * 100, 2);
            $color = $chartPalette[$key] ?? ['#7367f0', '#00bad1', '#28c76f', '#ff9f43', '#ff4c51'][count($leadSourceSegments) % 5];
            $leadSourceSegments[] = "{$color} {$leadSourceOffset}% ".($leadSourceOffset + $share).'%';
            $leadSourceOffset += $share;
        }

        $leadSourceChart = $leadSourceSegments !== []
            ? 'conic-gradient('.implode(', ', $leadSourceSegments).')'
            : 'conic-gradient(#eef2ff 0% 100%)';

        $salesFunnelItems = collect($salesFunnel)->map(function (array $stage) use ($tx) {
            $translation = match ($stage['label']) {
                'Leads' => $tx('Leads', 'Lead'),
                'Qualified' => $tx('Qualified', 'Qualified'),
                'Opportunities' => $tx('Opportunities', 'Opportunity'),
                'Quotations' => $tx('Quotations', 'Quotation'),
                'Won' => $tx('Won', 'Won'),
                default => $tx($stage['label'], $stage['label']),
            };

            return $stage + ['label_translation' => $translation];
        });
        $salesFunnelMax = max(1, (int) $salesFunnelItems->max('value'));

        $teamItems = collect($teamPerformance);
        $teamMaxScore = max(1, (float) $teamItems->max('score'));

        $pipelineItems = $pipelineOverview->values();
        $pipelineTotalValueAll = max(1, (float) $pipelineItems->sum('value_total'));
        $pipelineStageMax = max(1, (float) $pipelineItems->max('value_total'));

        $journeyNodes = collect($customerJourney['nodes'] ?? []);
        $journeyLinks = collect($customerJourney['links'] ?? [])->filter(fn (array $link) => (int) $link['value'] > 0)->values();
        $journeyChartWidth = 920;
        $journeyChartHeight = 270;
        $journeyNodeWidth = 132;
        $journeyNodeMax = max(1, (int) $journeyNodes->max('count'));
        $journeyLayout = collect([
            'customers' => ['x' => 24, 'y' => 92],
            'engaged' => ['x' => 212, 'y' => 92],
            'interested' => ['x' => 400, 'y' => 92],
            'transacting' => ['x' => 588, 'y' => 92],
            'loyal' => ['x' => 776, 'y' => 48],
            'churned' => ['x' => 776, 'y' => 156],
        ])->map(function (array $position, string $key) use ($journeyNodes, $journeyNodeMax, $journeyNodeWidth, $tx) {
            $node = $journeyNodes->firstWhere('key', $key);
            $height = $node !== null
                ? max(52, min(92, (int) round((((int) $node['count']) / $journeyNodeMax) * 82)))
                : 52;

            return [
                'x' => $position['x'],
                'y' => $position['y'],
                'width' => $journeyNodeWidth,
                'height' => $height,
                'count' => (int) ($node['count'] ?? 0),
                'label' => (string) ($node['label'] ?? str($key)->headline()->toString()),
                'label_translation' => match ((string) ($node['label'] ?? $key)) {
                    'Customers' => $tx('Customers', 'Customer'),
                    'Engaged' => $tx('Engaged', 'Engaged'),
                    'Interested' => $tx('Interested', 'Interested'),
                    'Transacting' => $tx('Transacting', 'Bertransaksi'),
                    'Loyal' => $tx('Loyal', 'Loyal'),
                    'Churned' => $tx('Churned', 'Churned'),
                    default => $tx((string) ($node['label'] ?? str($key)->headline()->toString()), (string) ($node['label'] ?? str($key)->headline()->toString())),
                },
                'color' => (string) ($node['color'] ?? '#7367f0'),
            ];
        });
        $journeyLinkMax = max(1, (int) $journeyLinks->max('value'));

        $slaGaugeRate = max(0, min(100, (float) $metrics['sla_rate']));
        $slaGauge = 'conic-gradient(#28c76f 0% '.$slaGaugeRate.'%, #e9edf7 '.$slaGaugeRate.'% 100%)';

        $geoItems = collect($regionalCoverage);
        $geoMax = max(1, (int) $geoItems->max('total'));
        $activityTimeline = collect($activityTimeline)->map(function (array $event) use ($tx) {
            $labelTranslation = match ($event['label']) {
                'Customer Added' => $tx('Customer Added', 'Customer Ditambahkan'),
                'Lead Captured' => $tx('Lead Captured', 'Lead Tertangkap'),
                'Sales Activity' => $tx('Sales Activity', 'Aktivitas Sales'),
                'Service Ticket' => $tx('Service Ticket', 'Tiket Service'),
                'Campaign Updated' => $tx('Campaign Updated', 'Campaign Diperbarui'),
                default => $tx($event['label'], $event['label']),
            };

            return $event + ['label_translation' => $labelTranslation];
        });
    @endphp

    <section class="service-page customer-list-page sales-workspace sales-dashboard-page overview-dashboard-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'dashboard'])</div>
            <div>
                <span data-doc-title-en="CRM Overview - Krakatau CRM" data-doc-title-id="CRM Overview - Krakatau CRM" hidden></span>
                <span class="service-badge overview-hero-badge" data-lang-en="Executive Workspace" data-lang-id="Executive Workspace">Executive Workspace</span>
                <h1 data-lang-en="{{ $pageTitleTranslation['en'] }}" data-lang-id="{{ $pageTitleTranslation['id'] }}">{{ $pageTitleTranslation['en'] }}</h1>
                <p data-lang-en="{{ $pageDescriptionTranslation['en'] }}" data-lang-id="{{ $pageDescriptionTranslation['id'] }}">{{ $pageDescriptionTranslation['en'] }}</p>
            </div>
        </article>

        <section class="sales-summary-grid">
            @foreach ($summaryCardItems as $card)
                <article class="card sales-summary-card">
                    <span data-lang-en="{{ $card['label_translation']['en'] }}" data-lang-id="{{ $card['label_translation']['id'] }}">{{ $card['label_translation']['en'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small data-lang-en="{{ $card['hint_translation']['en'] }}" data-lang-id="{{ $card['hint_translation']['id'] }}">{{ $card['hint_translation']['en'] }}</small>
                </article>
            @endforeach
        </section>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card sales-chart-card--trend">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Revenue Trend" data-lang-id="Tren Pendapatan">Revenue Trend</h2>
                        <p data-lang-en="Smoothed revenue line from closed transactions over the last 6 months." data-lang-id="Garis halus revenue dari closing transaction selama 6 bulan terakhir.">Smoothed revenue line from closed transactions over the last 6 months.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="sales-chart-hero">
                        <div>
                            <span data-lang-en="Total Revenue" data-lang-id="Total Pendapatan">Total Revenue</span>
                            <strong>{{ 'Rp '.number_format((float) $metrics['total_transaction_value'], 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Won Revenue" data-lang-id="Pendapatan Won">Won Revenue</span>
                            <strong>{{ 'Rp '.number_format((float) $metrics['won_value'], 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Total Transactions" data-lang-id="Total Transaksi">Total Transactions</span>
                            <strong>{{ number_format($metrics['total_transactions']) }}</strong>
                        </div>
                    </div>

                    <div class="sales-line-chart-card">
                        <svg viewBox="0 0 {{ $revenueChartWidth }} {{ $revenueChartHeight }}" class="sales-line-chart" aria-label="Revenue trend line chart" role="img">
                            @for ($grid = 0; $grid < 5; $grid++)
                                @php
                                    $y = $revenuePaddingY + (($revenueInnerHeight / 4) * $grid);
                                    $labelValue = round($revenueMax - (($revenueMax / 4) * $grid));
                                @endphp
                                <line x1="{{ $revenuePaddingX }}" y1="{{ $y }}" x2="{{ $revenueChartWidth - $revenuePaddingX }}" y2="{{ $y }}" class="sales-line-grid" />
                                <text x="6" y="{{ $y + 4 }}" class="sales-line-axis-label">{{ number_format($labelValue / 1000000, 0, ',', '.') }}M</text>
                            @endfor
                            @php
                                $revenuePoints = $linePoints($revenueValues, $revenuePointCount, $revenueChartWidth, $revenuePaddingX, $revenuePaddingY, $revenueInnerHeight, $revenueMax);
                                $revenuePath = $smoothLinePath($revenuePoints);
                                $revenueLastPoint = $revenuePoints[count($revenuePoints) - 1] ?? ['x' => 0, 'y' => 0];
                            @endphp
                            <path d="{{ $revenuePath }}" class="sales-line-series customer-smooth-line" style="--series-color: #7367f0;" />
                            @foreach ($revenuePoints as $point)
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" class="sales-line-point" style="--series-color: #7367f0;" />
                            @endforeach
                            <text x="{{ $revenueLastPoint['x'] + 10 }}" y="{{ $revenueLastPoint['y'] + 4 }}" class="sales-line-series-label" style="--series-color: #7367f0;">Revenue</text>
                        </svg>

                        <div class="sales-line-labels">
                            @foreach ($revenueLabels as $label)
                                <span>{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card sales-chart-card--trend">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Lead Growth" data-lang-id="Pertumbuhan Lead">Lead Growth</h2>
                        <p data-lang-en="New lead growth with qualified lead overlay by month." data-lang-id="Pertumbuhan lead baru dengan overlay qualified lead per bulan.">New lead growth with qualified lead overlay by month.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="sales-chart-hero">
                        <div>
                            <span data-lang-en="Total Leads" data-lang-id="Total Lead">Total Leads</span>
                            <strong>{{ number_format($metrics['total_leads']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Qualified Leads" data-lang-id="Qualified Lead">Qualified Leads</span>
                            <strong>{{ number_format($metrics['qualified_leads']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Win Rate" data-lang-id="Win Rate">Win Rate</span>
                            <strong>{{ number_format((float) $metrics['win_rate'], 1, ',', '.') }}%</strong>
                        </div>
                    </div>

                    <div class="sales-line-chart-card">
                        <svg viewBox="0 0 {{ $leadChartWidth }} {{ $leadChartHeight }}" class="sales-line-chart" aria-label="Lead growth area chart" role="img">
                            @for ($grid = 0; $grid < 5; $grid++)
                                @php
                                    $y = $leadPaddingY + (($leadInnerHeight / 4) * $grid);
                                    $labelValue = round($leadMax - (($leadMax / 4) * $grid));
                                @endphp
                                <line x1="{{ $leadPaddingX }}" y1="{{ $y }}" x2="{{ $leadChartWidth - $leadPaddingX }}" y2="{{ $y }}" class="sales-line-grid" />
                                <text x="6" y="{{ $y + 4 }}" class="sales-line-axis-label">{{ $labelValue }}</text>
                            @endfor
                            @php
                                $leadPoints = $linePoints($leadValues, $leadPointCount, $leadChartWidth, $leadPaddingX, $leadPaddingY, $leadInnerHeight, $leadMax);
                                $leadPath = $smoothLinePath($leadPoints);
                                $leadArea = $leadPath !== ''
                                    ? $leadPath.' L '.number_format($leadPoints[count($leadPoints) - 1]['x'] ?? 0, 2, '.', '').' '.number_format($leadChartHeight - $leadPaddingY, 2, '.', '').' L '.number_format($leadPoints[0]['x'] ?? 0, 2, '.', '').' '.number_format($leadChartHeight - $leadPaddingY, 2, '.', '').' Z'
                                    : '';
                                $qualifiedPoints = $linePoints($qualifiedLeadValues, $leadPointCount, $leadChartWidth, $leadPaddingX, $leadPaddingY, $leadInnerHeight, $leadMax);
                                $qualifiedPath = $smoothLinePath($qualifiedPoints);
                            @endphp
                            @if ($leadArea !== '')
                                <path d="{{ $leadArea }}" class="overview-area-fill" />
                            @endif
                            <path d="{{ $leadPath }}" class="sales-line-series customer-smooth-line" style="--series-color: #7367f0;" />
                            <path d="{{ $qualifiedPath }}" class="sales-line-series" style="--series-color: #28c76f; stroke-width: 2.4;" />
                            @foreach ($qualifiedPoints as $point)
                                <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="3.6" class="sales-line-point" style="--series-color: #28c76f;" />
                            @endforeach
                        </svg>

                        <div class="sales-trend-legend">
                            <div class="sales-trend-legend-item">
                                <span class="sales-legend-dot" style="--legend-color:#7367f0;"></span>
                                <strong data-lang-en="All Leads" data-lang-id="Semua Lead">All Leads</strong>
                                <small data-lang-en="{{ number_format(array_sum($leadValues)) }} total" data-lang-id="{{ number_format(array_sum($leadValues)) }} total">{{ number_format(array_sum($leadValues)) }} total</small>
                            </div>
                            <div class="sales-trend-legend-item">
                                <span class="sales-legend-dot" style="--legend-color:#28c76f;"></span>
                                <strong data-lang-en="Qualified" data-lang-id="Qualified">Qualified</strong>
                                <small data-lang-en="{{ number_format(array_sum($qualifiedLeadValues)) }} total" data-lang-id="{{ number_format(array_sum($qualifiedLeadValues)) }} total">{{ number_format(array_sum($qualifiedLeadValues)) }} total</small>
                            </div>
                        </div>

                        <div class="sales-line-labels">
                            @foreach ($leadLabels as $label)
                                <span>{{ $label }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Sales Funnel" data-lang-id="Sales Funnel">Sales Funnel</h2>
                        <p data-lang-en="Progress from acquisition to closed won in a tiered funnel." data-lang-id="Perjalanan dari acquisition sampai closed won dalam funnel bertingkat.">Progress from acquisition to closed won in a tiered funnel.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="overview-funnel-list">
                        @foreach ($salesFunnelItems as $index => $stage)
                            @php($width = 100 - ($index * 12))
                            @php($opacity = 1 - ($index * .08))
                            <div class="overview-funnel-stage" style="--stage-width: {{ max(32, $width) }}%; --stage-color: {{ $stage['color'] }}; --stage-opacity: {{ max(.58, $opacity) }};">
                                <span data-lang-en="{{ $stage['label_translation']['en'] }}" data-lang-id="{{ $stage['label_translation']['id'] }}">{{ $stage['label_translation']['en'] }}</span>
                                <strong>{{ number_format($stage['value']) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card sales-chart-card--lead">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Lead Source" data-lang-id="Sumber Lead">Lead Source</h2>
                        <p data-lang-en="Distribution of lead sources with the biggest contribution." data-lang-id="Distribusi sumber lead yang sedang memberi kontribusi terbesar.">Distribution of lead sources with the biggest contribution.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--lead">
                    <div class="sales-donut-panel">
                        <div class="sales-donut-chart" style="--chart-fill: {{ $leadSourceChart }};">
                            <div class="sales-donut-center">
                                <span data-lang-en="Total Sources" data-lang-id="Total Sumber">Total Sources</span>
                                <strong>{{ number_format($leadSourceTotal) }}</strong>
                                <small data-lang-en="{{ number_format($metrics['qualified_leads']) }} qualified from all channels" data-lang-id="{{ number_format($metrics['qualified_leads']) }} qualified dari semua channel">{{ number_format($metrics['qualified_leads']) }} qualified from all channels</small>
                            </div>
                        </div>
                    </div>

                    <div class="sales-legend-list">
                        @forelse ($leadSourceItems as $index => $source)
                            @php($share = round(((int) $source->total / $leadSourceTotal) * 100, 1))
                            @php($color = $chartPalette[strtolower((string) $source->source_label)] ?? ['#7367f0', '#00bad1', '#28c76f', '#ff9f43', '#ff4c51'][$index % 5])
                            <div class="sales-legend-item">
                                <span class="sales-legend-dot" style="--legend-color: {{ $color }};"></span>
                                <div class="sales-legend-copy">
                                    @php($sourceLabel = $tx(str($source->source_label)->headline()->toString(), str($source->source_label)->headline()->toString()))
                                    <strong data-lang-en="{{ $sourceLabel['en'] }}" data-lang-id="{{ $sourceLabel['id'] }}">{{ $sourceLabel['en'] }}</strong>
                                    <small>{{ number_format($source->total) }} leads / {{ number_format($share, 1, ',', '.') }}%</small>
                                </div>
                                <div class="sales-legend-metric">{{ number_format($source->total) }}</div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No lead source data yet." data-lang-id="Belum ada data source lead.">No lead source data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Team Performance" data-lang-id="Performa Tim">Team Performance</h2>
                        <p data-lang-en="Combined horizontal load bar for leads, opportunities, tickets, and activities per owner." data-lang-id="Horizontal bar gabungan beban lead, opportunity, ticket, dan activity per team owner.">Combined horizontal load bar for leads, opportunities, tickets, and activities per owner.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="overview-team-list">
                        @forelse ($teamItems as $member)
                            @php($width = round((((float) $member['score']) / $teamMaxScore) * 100, 1))
                            <div class="overview-team-item">
                                <div class="overview-team-head">
                                    <strong>{{ $member['name'] }}</strong>
                                    <span data-lang-en="{{ number_format((float) $member['score'], 1, ',', '.') }} score" data-lang-id="{{ number_format((float) $member['score'], 1, ',', '.') }} skor">{{ number_format((float) $member['score'], 1, ',', '.') }} score</span>
                                </div>
                                <div class="overview-team-track">
                                    <div class="overview-team-bar" style="--bar-width: {{ max(12, $width) }}%;"></div>
                                </div>
                                <small data-lang-en="{{ number_format($member['leads']) }} leads / {{ number_format($member['opportunities']) }} opps / {{ number_format($member['tickets']) }} tickets / {{ number_format($member['activities']) }} activities" data-lang-id="{{ number_format($member['leads']) }} lead / {{ number_format($member['opportunities']) }} opp / {{ number_format($member['tickets']) }} tiket / {{ number_format($member['activities']) }} aktivitas">{{ number_format($member['leads']) }} leads / {{ number_format($member['opportunities']) }} opps / {{ number_format($member['tickets']) }} tickets / {{ number_format($member['activities']) }} activities</small>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No team performance data yet." data-lang-id="Belum ada data team performance.">No team performance data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Pipeline" data-lang-id="Pipeline">Pipeline</h2>
                        <p data-lang-en="Stacked pipeline value by active opportunity status." data-lang-id="Stacked bar value pipeline berdasarkan status opportunity yang sedang berjalan.">Stacked pipeline value by active opportunity status.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="overview-pipeline-stack">
                        @forelse ($pipelineItems as $index => $item)
                            @php($share = ((float) $item->value_total / $pipelineTotalValueAll) * 100)
                            @php($color = $chartPalette[(string) $item->status] ?? ['#7367f0', '#00bad1', '#28c76f', '#ff9f43', '#ff4c51'][$index % 5])
                            @if ((float) $item->value_total > 0)
                                <div class="overview-pipeline-segment" style="--segment-width: {{ max(8, $share) }}%; --segment-color: {{ $color }};"></div>
                            @endif
                        @empty
                            <div class="overview-pipeline-segment" style="--segment-width: 100%; --segment-color: #eef2ff;"></div>
                        @endforelse
                    </div>

                    <div class="sales-quotation-bars">
                        @forelse ($pipelineItems as $index => $item)
                            @php($barWidth = round((((float) $item->value_total) / $pipelineStageMax) * 100, 1))
                            @php($color = $chartPalette[(string) $item->status] ?? ['#7367f0', '#00bad1', '#28c76f', '#ff9f43', '#ff4c51'][$index % 5])
                            @php($share = round((((float) $item->value_total) / $pipelineTotalValueAll) * 100, 1))
                            <div class="sales-quotation-item">
                                <div class="sales-quotation-head">
                                    @php($pipelineStatus = $tx(str($item->status)->headline()->toString(), str($item->status)->headline()->toString()))
                                    <strong data-lang-en="{{ $pipelineStatus['en'] }}" data-lang-id="{{ $pipelineStatus['id'] }}">{{ $pipelineStatus['en'] }}</strong>
                                    <span>{{ 'Rp '.number_format((float) $item->value_total, 0, ',', '.') }}</span>
                                </div>
                                <div class="sales-quotation-track">
                                    <div class="sales-quotation-bar" style="--bar-width: {{ max(10, $barWidth) }}%; --bar-color: {{ $color }};"></div>
                                </div>
                                <small data-lang-en="{{ number_format($item->total) }} opps / {{ number_format($share, 1, ',', '.') }}% of total pipeline value" data-lang-id="{{ number_format($item->total) }} opp / {{ number_format($share, 1, ',', '.') }}% dari total pipeline value">{{ number_format($item->total) }} opps / {{ number_format($share, 1, ',', '.') }}% of total pipeline value</small>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No pipeline data yet." data-lang-id="Belum ada data pipeline.">No pipeline data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>

        <article class="card customer-table-card sales-chart-card customer-journey-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Customer Journey" data-lang-id="Customer Journey">Customer Journey</h2>
                        <p data-lang-en="Cross-module flow from incoming customer to engaged, interested, transacting, loyal, or churned." data-lang-id="Flow cross-module dari customer masuk, engage, tertarik, transaksi, hingga loyal atau churned.">Cross-module flow from incoming customer to engaged, interested, transacting, loyal, or churned.</p>
                    </div>
                </div>
            <div class="sales-chart-body">
                @if ($journeyLinks->isNotEmpty())
                    <div class="customer-sankey-card">
                        <svg viewBox="0 0 {{ $journeyChartWidth }} {{ $journeyChartHeight }}" class="customer-sankey-svg" aria-label="Customer journey sankey" role="img">
                            @foreach ($journeyLinks as $link)
                                @php($from = $journeyLayout[$link['from']] ?? null)
                                @php($to = $journeyLayout[$link['to']] ?? null)
                                @if ($from !== null && $to !== null)
                                    @php($fromX = $from['x'] + $from['width'])
                                    @php($fromY = $from['y'] + ($from['height'] / 2))
                                    @php($toX = $to['x'])
                                    @php($toY = $to['y'] + ($to['height'] / 2))
                                    @php($curve = ($toX - $fromX) / 2)
                                    @php($thickness = max(10, round((((int) $link['value']) / $journeyLinkMax) * 26)))
                                    <path
                                        d="M {{ $fromX }} {{ $fromY }} C {{ $fromX + $curve }} {{ $fromY }}, {{ $toX - $curve }} {{ $toY }}, {{ $toX }} {{ $toY }}"
                                        class="customer-sankey-link"
                                        style="--link-color: {{ $link['color'] }}; --link-width: {{ $thickness }};"
                                    />
                                @endif
                            @endforeach

                            @foreach ($journeyLayout as $node)
                                @php($titleY = 16)
                                @php($valueY = $node['height'] >= 72 ? 54 : ($node['height'] - 14))
                                <g transform="translate({{ $node['x'] }}, {{ $node['y'] }})">
                                    <rect width="{{ $node['width'] }}" height="{{ $node['height'] }}" rx="18" class="customer-sankey-node" style="--node-color: {{ $node['color'] }};" />
                                    <text x="18" y="{{ $titleY }}" class="customer-sankey-title" dominant-baseline="hanging">{{ $node['label_translation']['en'] }}</text>
                                    <text x="18" y="{{ $valueY }}" class="customer-sankey-value">{{ number_format($node['count']) }}</text>
                                </g>
                            @endforeach
                        </svg>
                    </div>
                @else
                    <div class="sales-empty-chart-state" data-lang-en="No customer journey data yet." data-lang-id="Belum ada data journey customer.">No customer journey data yet.</div>
                @endif
            </div>
        </article>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card sales-chart-card--quotation">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="SLA" data-lang-id="SLA">SLA</h2>
                        <p data-lang-en="SLA compliance gauge based on tracked tickets that have been resolved." data-lang-id="Gauge kepatuhan SLA berdasarkan ticket bertarget yang sudah terselesaikan.">SLA compliance gauge based on tracked tickets that have been resolved.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--quotation">
                    <div class="sales-quotation-ring-panel">
                        <div class="sales-quotation-ring" style="--ring-fill: {{ $slaGauge }};">
                            <div class="sales-quotation-ring-center">
                                <span data-lang-en="SLA Compliance" data-lang-id="Kepatuhan SLA">SLA Compliance</span>
                                <strong>{{ number_format($slaGaugeRate, 1, ',', '.') }}%</strong>
                                <small data-lang-en="{{ number_format($metrics['met_sla_tickets']) }} met / {{ number_format($metrics['tracked_sla_tickets']) }} tracked" data-lang-id="{{ number_format($metrics['met_sla_tickets']) }} tercapai / {{ number_format($metrics['tracked_sla_tickets']) }} terlacak">{{ number_format($metrics['met_sla_tickets']) }} met / {{ number_format($metrics['tracked_sla_tickets']) }} tracked</small>
                            </div>
                        </div>
                    </div>

                    <div class="sales-quotation-bars">
                        <div class="sales-quotation-item">
                            <div class="sales-quotation-head">
                                <strong data-lang-en="Met SLA" data-lang-id="SLA Tercapai">Met SLA</strong>
                                <span>{{ number_format($metrics['met_sla_tickets']) }}</span>
                            </div>
                            <div class="sales-quotation-track">
                                <div class="sales-quotation-bar" style="--bar-width: {{ max(10, $slaGaugeRate) }}%; --bar-color: #28c76f;"></div>
                            </div>
                            <small data-lang-en="Tickets completed before or on time" data-lang-id="Tiket selesai sebelum atau tepat waktu">Tickets completed before or on time</small>
                        </div>
                        <div class="sales-quotation-item">
                            <div class="sales-quotation-head">
                                <strong data-lang-en="Breached" data-lang-id="Melewati SLA">Breached</strong>
                                <span>{{ number_format($metrics['breached_sla_tickets']) }}</span>
                            </div>
                            <div class="sales-quotation-track">
                                <div class="sales-quotation-bar" style="--bar-width: {{ max(10, 100 - $slaGaugeRate) }}%; --bar-color: #ff4c51;"></div>
                            </div>
                            <small data-lang-en="Tickets completed past due time" data-lang-id="Tiket selesai melewati due time">Tickets completed past due time</small>
                        </div>
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Activity" data-lang-id="Aktivitas">Activity</h2>
                        <p data-lang-en="Latest activity timeline from sales, service, marketing, leads, and customers." data-lang-id="Timeline aktivitas terbaru dari sales, service, marketing, lead, dan customer.">Latest activity timeline from sales, service, marketing, leads, and customers.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="customer-history-list">
                        @forelse ($activityTimeline as $event)
                            <div class="customer-history-item">
                                <div class="customer-history-dot" style="--history-color: {{ $event['accent'] }};"></div>
                                <div class="customer-history-content">
                                    <div class="customer-history-meta">
                                        <span data-lang-en="{{ $event['label_translation']['en'] }}" data-lang-id="{{ $event['label_translation']['id'] }}">{{ $event['label_translation']['en'] }}</span>
                                        <small>{{ optional($event['timestamp'])->format('d M Y H:i') ?: '-' }}</small>
                                    </div>
                                    <strong>{{ $event['title'] }}</strong>
                                    <p>{{ $event['meta'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No activity timeline yet." data-lang-id="Belum ada activity timeline.">No activity timeline yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>

        <article class="card customer-table-card sales-chart-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Regional" data-lang-id="Regional">Regional</h2>
                        <p data-lang-en="Geo-style operational map based on the distribution of recorded customer and lead sources." data-lang-id="Geo-style operational map berdasarkan sebaran source customer dan lead yang sedang tercatat.">Geo-style operational map based on the distribution of recorded customer and lead sources.</p>
                    </div>
                </div>
            <div class="sales-chart-body">
                <div class="overview-geo-card">
                    <svg viewBox="0 0 760 280" class="overview-geo-svg" aria-label="Regional geo style map" role="img">
                        <path class="overview-geo-island" d="M62 140 C104 112, 142 104, 190 120 C212 128, 228 146, 250 154 C270 162, 292 158, 312 166 C332 174, 340 194, 358 198 C392 206, 430 190, 456 176 C486 160, 518 144, 546 142 C568 140, 588 146, 608 142 C634 136, 654 122, 690 122 C704 122, 716 126, 730 132" />
                        <path class="overview-geo-island overview-geo-island--secondary" d="M258 210 C282 196, 308 194, 332 202 C346 208, 358 220, 380 224 C404 228, 432 222, 454 230" />
                        <path class="overview-geo-island overview-geo-island--tertiary" d="M520 206 C540 194, 558 190, 582 194 C604 198, 622 210, 646 212" />
                        @foreach ($geoItems as $item)
                            @php($size = 20 + round((((int) $item['total']) / $geoMax) * 16))
                            <g transform="translate({{ $item['x'] }}, {{ $item['y'] }})">
                                <circle r="{{ $size }}" class="overview-geo-marker"></circle>
                                <text y="-2" class="overview-geo-value">{{ number_format($item['total']) }}</text>
                                <text y="32" class="overview-geo-label">{{ $item['zone'] }}</text>
                                @php($geoLabel = $tx(str($item['label'])->headline()->toString(), str($item['label'])->headline()->toString()))
                                <text y="46" class="overview-geo-caption" data-lang-en="{{ $geoLabel['en'] }}" data-lang-id="{{ $geoLabel['id'] }}">{{ $geoLabel['en'] }}</text>
                            </g>
                        @endforeach
                    </svg>
                </div>
            </div>
        </article>
    </section>
@endsection
