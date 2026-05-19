@extends('admin.layouts.app')

@section('title', 'Marketing Automation Dashboard - Krakatau CRM')

@section('content')
    @php
        $tx = static fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
        $titleTranslation = $tx('Marketing Automation Dashboard', 'Dashboard Marketing Automation');
        $descriptionTranslation = $tx(
            'Marketing summary for campaigns, executions, landing pages, social engagement, and automation.',
            'Ringkasan performa campaign, execution, landing page, social engagement, dan automation.'
        );
        $summaryCardItems = collect($summaryCards)->map(function (array $card) use ($tx) {
            $labelTranslation = match ($card['label']) {
                'Total Campaigns' => $tx('Total Campaigns', 'Total Campaign'),
                'Total Executions' => $tx('Total Executions', 'Total Execution'),
                'Total Sent' => $tx('Total Sent', 'Total Terkirim'),
                'Landing Pages' => $tx('Landing Pages', 'Landing Page'),
                'Social Posts' => $tx('Social Posts', 'Post Sosial'),
                'Automations' => $tx('Automations', 'Automation'),
                default => $tx($card['label'], $card['label']),
            };

            return $card + ['label_translation' => $labelTranslation];
        });
        $marketingLabel = static function (?string $value) use ($tx): array {
            return match ((string) $value) {
                'running' => $tx('Running', 'Berjalan'),
                'active' => $tx('Active', 'Aktif'),
                'completed' => $tx('Completed', 'Selesai'),
                'scheduled' => $tx('Scheduled', 'Terjadwal'),
                'draft' => $tx('Draft', 'Draft'),
                'failed' => $tx('Failed', 'Gagal'),
                'cancelled' => $tx('Cancelled', 'Dibatalkan'),
                'paused' => $tx('Paused', 'Dijeda'),
                'inactive' => $tx('Inactive', 'Tidak Aktif'),
                'published' => $tx('Published', 'Dipublikasikan'),
                'email' => $tx('Email', 'Email'),
                'whatsapp' => $tx('WhatsApp', 'WhatsApp'),
                'social_media' => $tx('Social Media', 'Media Sosial'),
                'sms' => $tx('SMS', 'SMS'),
                default => $tx(str((string) $value)->headline()->toString(), str((string) $value)->headline()->toString()),
            };
        };
        $badgeClass = static function (?string $status): string {
            return match ((string) $status) {
                'running', 'active', 'completed', 'published' => 'status-active',
                'scheduled', 'draft' => 'status-pending',
                'failed', 'cancelled', 'archived', 'inactive', 'paused' => 'status-lost',
                default => 'status-inactive',
            };
        };

        $chartPalette = [
            'running' => '#22c55e',
            'active' => '#28c76f',
            'completed' => '#00bad1',
            'scheduled' => '#ff9f43',
            'draft' => '#8b879a',
            'failed' => '#ff4c51',
            'cancelled' => '#e11d48',
            'paused' => '#f59e0b',
            'inactive' => '#6f6b7d',
            'published' => '#00bad1',
            'email' => '#22c55e',
            'whatsapp' => '#28c76f',
            'social_media' => '#00bad1',
            'sms' => '#ff9f43',
        ];

        $campaignItems = $campaignStatusOverview->values();
        $campaignTotal = max((int) $campaignItems->sum('total'), 1);
        $campaignSegments = [];
        $campaignOffset = 0;

        foreach ($campaignItems as $item) {
            $share = round(((int) $item->total / $campaignTotal) * 100, 2);
            $color = $chartPalette[$item->status] ?? '#8b879a';
            $campaignSegments[] = "{$color} {$campaignOffset}% ".($campaignOffset + $share).'%';
            $campaignOffset += $share;
        }

        $campaignChart = $campaignSegments !== []
            ? 'conic-gradient('.implode(', ', $campaignSegments).')'
            : 'conic-gradient(#eef2ff 0% 100%)';

        $executionItems = $executionPerformanceOverview->values();
        $executionMaxCount = max((int) $executionItems->max('total'), 1);
        $executionMaxSent = max((int) $executionItems->max('sent_total'), 1);
        $executionLabels = $executionItems
            ->map(fn ($item) => str($item->status)->headline()->toString())
            ->values()
            ->all();
        $executionSeries = collect([
            [
                'name' => 'Executions',
                'color' => '#7367f0',
                'values' => $executionItems->pluck('total')->map(fn ($value) => (int) $value)->values()->all(),
            ],
            [
                'name' => 'Messages Sent',
                'color' => '#28c76f',
                'values' => $executionItems->pluck('sent_total')->map(fn ($value) => (int) $value)->values()->all(),
            ],
        ])->filter(fn (array $series) => $series['values'] !== [])->values();

        $socialItems = $socialByPlatform->values();
        $socialMaxCount = max((int) $socialItems->max('total'), 1);
        $socialMaxImpressions = max((int) $socialItems->max('impressions_total'), 1);

        $automationItems = $automationStatusOverview->values();
        $automationMax = max((int) $automationItems->max('total'), 1);

        $leadScoringItems = $leadScoringStatusOverview->values();
        $leadScoringMax = max((int) $leadScoringItems->max('total'), 1);

        $trendLabels = $trendPerformance['labels'] ?? [];
        $trendSeries = collect($trendPerformance['series'] ?? []);
        $trendChartWidth = 640;
        $trendChartHeight = 260;
        $trendPaddingX = 24;
        $trendPaddingY = 20;
        $trendInnerWidth = $trendChartWidth - ($trendPaddingX * 2);
        $trendInnerHeight = $trendChartHeight - ($trendPaddingY * 2);
        $trendPointCount = max(count($trendLabels), 2);
        $trendMax = max(1, (int) $trendSeries->flatMap(fn (array $series) => $series['values'] ?? [0])->max());

        $executionChartWidth = 640;
        $executionChartHeight = 220;
        $executionPaddingX = 24;
        $executionPaddingY = 18;
        $executionInnerWidth = $executionChartWidth - ($executionPaddingX * 2);
        $executionInnerHeight = $executionChartHeight - ($executionPaddingY * 2);
        $executionPointCount = max(count($executionLabels), 2);
        $executionMax = max(1, (int) $executionSeries->flatMap(fn (array $series) => $series['values'] ?? [0])->max());

        $buildLinePoints = static function (
            array $values,
            int $pointCount,
            int $chartWidth,
            int $paddingX,
            int $paddingY,
            int $innerHeight,
            int $maxValue
        ): string {
            $stepX = ($chartWidth - ($paddingX * 2)) / max($pointCount - 1, 1);

            return collect($values)
                ->values()
                ->map(function ($value, $index) use ($stepX, $paddingX, $paddingY, $innerHeight, $maxValue) {
                    $x = $paddingX + ($stepX * $index);
                    $y = $paddingY + $innerHeight - (($value / max($maxValue, 1)) * $innerHeight);

                    return number_format($x, 2, '.', '').','.number_format($y, 2, '.', '');
                })
                ->implode(' ');
        };
    @endphp

    <section class="service-page customer-list-page sales-workspace sales-dashboard-page marketing-dashboard-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'campaign'])</div>
            <div>
                <span data-doc-title-en="Marketing Automation Dashboard - Krakatau CRM" data-doc-title-id="Dashboard Marketing Automation - Krakatau CRM" hidden></span>
                <span class="service-badge dashboard-hero-badge" data-lang-en="Growth Orchestration" data-lang-id="Orkestrasi Pertumbuhan">Growth Orchestration</span>
                <h1 data-lang-en="{{ $titleTranslation['en'] }}" data-lang-id="{{ $titleTranslation['id'] }}">{{ $titleTranslation['en'] }}</h1>
                <p data-lang-en="{{ $descriptionTranslation['en'] }}" data-lang-id="{{ $descriptionTranslation['id'] }}">{{ $descriptionTranslation['en'] }}</p>
            </div>
        </article>

        <section class="sales-summary-grid">
            @foreach ($summaryCardItems as $card)
                <article class="card sales-summary-card">
                    <span data-lang-en="{{ $card['label_translation']['en'] }}" data-lang-id="{{ $card['label_translation']['id'] }}">{{ $card['label_translation']['en'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small>{{ $card['hint'] }}</small>
                </article>
            @endforeach
        </section>

        <article class="card customer-table-card sales-chart-card sales-chart-card--trend">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Trend Performance" data-lang-id="Performa Tren">Trend Performance</h2>
                    <p data-lang-en="Campaign, execution, and social post movement over the last 6 months." data-lang-id="Pergerakan campaign, execution, dan social posts dalam 6 bulan terakhir.">Campaign, execution, and social post movement over the last 6 months.</p>
                </div>
            </div>
            <div class="sales-chart-body">
                <div class="sales-trend-legend">
                    @foreach ($trendSeries as $series)
                        <div class="sales-trend-legend-item">
                            <span class="sales-legend-dot" style="--legend-color: {{ $series['color'] }};"></span>
                            <strong>{{ $series['name'] }}</strong>
                            <small>{{ number_format(array_sum($series['values'])) }} total</small>
                        </div>
                    @endforeach
                </div>

                <div class="sales-line-chart-card">
                    <svg viewBox="0 0 {{ $trendChartWidth }} {{ $trendChartHeight }}" class="sales-line-chart" aria-label="Marketing trend performance line chart" role="img">
                        @for ($grid = 0; $grid < 5; $grid++)
                            @php($y = $trendPaddingY + (($trendInnerHeight / 4) * $grid))
                            @php($labelValue = round($trendMax - (($trendMax / 4) * $grid)))
                            <line x1="{{ $trendPaddingX }}" y1="{{ $y }}" x2="{{ $trendChartWidth - $trendPaddingX }}" y2="{{ $y }}" class="sales-line-grid" />
                            <text x="6" y="{{ $y + 4 }}" class="sales-line-axis-label">{{ $labelValue }}</text>
                        @endfor

                        @foreach ($trendSeries as $series)
                            @php($points = $buildLinePoints($series['values'], $trendPointCount, $trendChartWidth, $trendPaddingX, $trendPaddingY, $trendInnerHeight, $trendMax))
                            @php($lastValue = collect($series['values'])->last())
                            @php($lastIndex = max(count($series['values']) - 1, 0))
                            @php($stepX = $trendInnerWidth / max($trendPointCount - 1, 1))
                            @php($lastX = $trendPaddingX + ($stepX * $lastIndex))
                            @php($lastY = $trendPaddingY + $trendInnerHeight - (($lastValue / $trendMax) * $trendInnerHeight))
                            <polyline points="{{ $points }}" class="sales-line-series" style="--series-color: {{ $series['color'] }};" />
                            @foreach ($series['values'] as $index => $value)
                                @php($x = $trendPaddingX + ($stepX * $index))
                                @php($y = $trendPaddingY + $trendInnerHeight - (($value / $trendMax) * $trendInnerHeight))
                                <circle cx="{{ $x }}" cy="{{ $y }}" r="4" class="sales-line-point" style="--series-color: {{ $series['color'] }};" />
                            @endforeach
                            <text x="{{ $lastX + 10 }}" y="{{ $lastY + 4 }}" class="sales-line-series-label" style="--series-color: {{ $series['color'] }};">{{ $series['name'] }}</text>
                        @endforeach
                    </svg>

                    <div class="sales-line-labels">
                        @foreach ($trendLabels as $label)
                            <span>{{ $label }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
        </article>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card sales-chart-card--lead">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Campaign Status Overview" data-lang-id="Ringkasan Status Campaign">Campaign Status Overview</h2>
                        <p data-lang-en="Campaign distribution from draft to completed." data-lang-id="Distribusi campaign dari draft hingga completed.">Campaign distribution from draft to completed.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--lead">
                    <div class="sales-donut-panel">
                        <div class="sales-donut-chart" style="--chart-fill: {{ $campaignChart }};">
                            <div class="sales-donut-center">
                                <span data-lang-en="Total Campaigns" data-lang-id="Total Campaign">Total Campaigns</span>
                                <strong>{{ number_format($metrics['total_campaigns']) }}</strong>
                                <small>{{ number_format($metrics['running_campaigns']) }} running • {{ number_format($metrics['completed_campaigns']) }} completed</small>
                            </div>
                        </div>
                    </div>

                    <div class="sales-legend-list">
                        @forelse ($campaignItems as $status)
                            @php($share = round(((int) $status->total / $campaignTotal) * 100, 1))
                            @php($color = $chartPalette[$status->status] ?? '#8b879a')
                            <div class="sales-legend-item">
                                <span class="sales-legend-dot" style="--legend-color: {{ $color }};"></span>
                                <div class="sales-legend-copy">
                                    @php($campaignStatus = $marketingLabel($status->status))
                                    <strong data-lang-en="{{ $campaignStatus['en'] }}" data-lang-id="{{ $campaignStatus['id'] }}">{{ $campaignStatus['en'] }}</strong>
                                    <small>{{ number_format($status->total) }} campaigns • {{ $share }}%</small>
                                </div>
                                <div class="sales-legend-metric">{{ number_format($status->total) }}</div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No campaign status data yet." data-lang-id="Belum ada data status campaign.">No campaign status data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card sales-chart-card--pipeline">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Execution Performance Overview" data-lang-id="Ringkasan Performa Execution">Execution Performance Overview</h2>
                        <p data-lang-en="Execution volume, completion rate, and total sent by status." data-lang-id="Volume execution, completed rate, dan total sent per status.">Execution volume, completion rate, and total sent by status.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="sales-chart-hero">
                        <div>
                            <span data-lang-en="Total Executions" data-lang-id="Total Execution">Total Executions</span>
                            <strong>{{ number_format($metrics['total_executions']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Completed Executions" data-lang-id="Execution Selesai">Completed Executions</span>
                            <strong>{{ number_format($metrics['completed_executions']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Total Sent" data-lang-id="Total Terkirim">Total Sent</span>
                            <strong>{{ number_format($metrics['total_sent']) }}</strong>
                        </div>
                    </div>

                    @if ($executionItems->isNotEmpty())
                        <div class="sales-trend-legend">
                            @foreach ($executionSeries as $series)
                                <div class="sales-trend-legend-item">
                                    <span class="sales-legend-dot" style="--legend-color: {{ $series['color'] }};"></span>
                                    <strong>{{ $series['name'] }}</strong>
                                    <small>{{ number_format(array_sum($series['values'])) }} total</small>
                                </div>
                            @endforeach
                        </div>

                        <div class="sales-line-chart-card">
                            <svg viewBox="0 0 {{ $executionChartWidth }} {{ $executionChartHeight }}" class="sales-line-chart" aria-label="Execution performance overview line chart" role="img">
                                @for ($grid = 0; $grid < 5; $grid++)
                                    @php($y = $executionPaddingY + (($executionInnerHeight / 4) * $grid))
                                    @php($labelValue = round($executionMax - (($executionMax / 4) * $grid)))
                                    <line x1="{{ $executionPaddingX }}" y1="{{ $y }}" x2="{{ $executionChartWidth - $executionPaddingX }}" y2="{{ $y }}" class="sales-line-grid" />
                                    <text x="6" y="{{ $y + 4 }}" class="sales-line-axis-label">{{ $labelValue }}</text>
                                @endfor

                                @foreach ($executionSeries as $series)
                                    @php($points = $buildLinePoints($series['values'], $executionPointCount, $executionChartWidth, $executionPaddingX, $executionPaddingY, $executionInnerHeight, $executionMax))
                                    @php($lastValue = collect($series['values'])->last())
                                    @php($lastIndex = max(count($series['values']) - 1, 0))
                                    @php($stepX = $executionInnerWidth / max($executionPointCount - 1, 1))
                                    @php($lastX = $executionPaddingX + ($stepX * $lastIndex))
                                    @php($lastY = $executionPaddingY + $executionInnerHeight - (($lastValue / $executionMax) * $executionInnerHeight))
                                    <polyline points="{{ $points }}" class="sales-line-series" style="--series-color: {{ $series['color'] }};" />
                                    @foreach ($series['values'] as $index => $value)
                                        @php($x = $executionPaddingX + ($stepX * $index))
                                        @php($y = $executionPaddingY + $executionInnerHeight - (($value / $executionMax) * $executionInnerHeight))
                                        <circle cx="{{ $x }}" cy="{{ $y }}" r="4" class="sales-line-point" style="--series-color: {{ $series['color'] }};" />
                                    @endforeach
                                    <text x="{{ $lastX + 10 }}" y="{{ $lastY + 4 }}" class="sales-line-series-label" style="--series-color: {{ $series['color'] }};">{{ $series['name'] }}</text>
                                @endforeach
                            </svg>

                            <div class="sales-line-labels">
                                @foreach ($executionLabels as $label)
                                    <span>{{ $label }}</span>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <div class="sales-empty-chart-state" data-lang-en="No execution data yet." data-lang-id="Belum ada data execution.">No execution data yet.</div>
                    @endif
                </div>
            </article>
        </div>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card sales-chart-card--quotation">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Landing Page Performance" data-lang-id="Performa Landing Page">Landing Page Performance</h2>
                        <p data-lang-en="Landing page asset tracking and incoming submission volume." data-lang-id="Tracking asset landing page dan volume submission yang masuk.">Landing page asset tracking and incoming submission volume.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--quotation">
                    <div class="sales-quotation-ring-panel">
                        <div class="sales-quotation-summary">
                            <div>
                                <span data-lang-en="Total Landing Pages" data-lang-id="Total Landing Page">Total Landing Pages</span>
                                <strong>{{ number_format($metrics['total_landing_pages']) }}</strong>
                            </div>
                            <div>
                                <span data-lang-en="Total Submissions" data-lang-id="Total Submission">Total Submissions</span>
                                <strong>{{ number_format($metrics['total_submissions']) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="sales-quotation-bars">
                        <div class="sales-quotation-item">
                            <div class="sales-quotation-head">
                                <strong data-lang-en="Conversion Pulse" data-lang-id="Pulse Konversi">Conversion Pulse</strong>
                                <span>{{ $metrics['total_landing_pages'] > 0 ? number_format($metrics['total_submissions'] / max($metrics['total_landing_pages'], 1), 1, ',', '.') : '0,0' }}</span>
                            </div>
                            <div class="sales-quotation-track">
                                <div class="sales-quotation-bar" style="--bar-width: {{ min(100, max(10, $metrics['total_landing_pages'] > 0 ? round(($metrics['total_submissions'] / max($metrics['total_landing_pages'], 1)) * 10, 1) : 10)) }}%; --bar-color: #7367f0;"></div>
                            </div>
                            <small data-lang-en="Average submissions per landing page" data-lang-id="Rata-rata submission per landing page">Average submissions per landing page</small>
                        </div>
                        <div class="sales-quotation-item">
                            <div class="sales-quotation-head">
                                <strong data-lang-en="Audience Segments" data-lang-id="Segmen Audiens">Audience Segments</strong>
                                <span>{{ number_format($totalAudienceSegments) }}</span>
                            </div>
                            <div class="sales-quotation-track">
                                <div class="sales-quotation-bar" style="--bar-width: {{ min(100, max(10, $totalAudienceSegments * 8)) }}%; --bar-color: #28c76f;"></div>
                            </div>
                            <small data-lang-en="Targeting groups ready for campaign use" data-lang-id="Targeting groups yang siap dipakai campaign">Targeting groups ready for campaign use</small>
                        </div>
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card sales-chart-card--pipeline">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Social Engagement Overview" data-lang-id="Ringkasan Social Engagement">Social Engagement Overview</h2>
                        <p data-lang-en="Post performance, impressions, and engagement rate by platform." data-lang-id="Performa post, impressions, dan engagement rate per platform.">Post performance, impressions, and engagement rate by platform.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="sales-chart-hero">
                        <div>
                            <span data-lang-en="Total Social Posts" data-lang-id="Total Post Sosial">Total Social Posts</span>
                            <strong>{{ number_format($metrics['total_social_posts']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Total Impressions" data-lang-id="Total Impression">Total Impressions</span>
                            <strong>{{ number_format($metrics['total_impressions']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Average Engagement Rate" data-lang-id="Rata-rata Engagement Rate">Average Engagement Rate</span>
                            <strong>{{ number_format((float) $metrics['average_engagement_rate'], 2, ',', '.') }}%</strong>
                        </div>
                    </div>

                    <div class="sales-funnel-list">
                        @forelse ($socialItems as $platform)
                            @php($countWidth = max(12, round(((int) $platform->total / $socialMaxCount) * 100, 1)))
                            @php($impressionShare = round(((int) $platform->impressions_total / $socialMaxImpressions) * 100, 1))
                            @php($color = $chartPalette[$platform->platform] ?? '#00bad1')
                            <div class="sales-funnel-item">
                                <div class="sales-funnel-head">
                                    <div>
                                        @php($platformLabel = $marketingLabel($platform->platform))
                                        <strong data-lang-en="{{ $platformLabel['en'] }}" data-lang-id="{{ $platformLabel['id'] }}">{{ $platformLabel['en'] }}</strong>
                                        <small>{{ number_format($platform->impressions_total) }} impressions</small>
                                    </div>
                                    <span>{{ number_format($platform->total) }} posts</span>
                                </div>
                                <div class="sales-funnel-track">
                                    <div class="sales-funnel-bar" style="--bar-width: {{ $countWidth }}%; --bar-color: {{ $color }};"></div>
                                </div>
                                <div class="sales-funnel-foot">
                                    <small>{{ $impressionShare }}% dari platform exposure tertinggi</small>
                                </div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No social engagement data yet." data-lang-id="Belum ada data social engagement.">No social engagement data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>

        <article class="card customer-table-card sales-chart-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Automation Overview" data-lang-id="Ringkasan Automation">Automation Overview</h2>
                    <p data-lang-en="Active automation and lead scoring rules in one workspace." data-lang-id="Status automation dan lead scoring rules yang aktif dalam satu workspace.">Active automation and lead scoring rules in one workspace.</p>
                </div>
            </div>
            <div class="dashboard-panel-grid sales-chart-grid" style="margin-bottom:0;">
                <div class="sales-chart-body">
                    <div class="sales-chart-hero">
                        <div>
                            <span data-lang-en="Total Automations" data-lang-id="Total Automation">Total Automations</span>
                            <strong>{{ number_format($metrics['total_automations']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Active Automations" data-lang-id="Automation Aktif">Active Automations</span>
                            <strong>{{ number_format($metrics['active_automations']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Lead Scoring Rules" data-lang-id="Rules Lead Scoring">Lead Scoring Rules</span>
                            <strong>{{ number_format($metrics['total_lead_scoring_rules']) }}</strong>
                        </div>
                    </div>

                    <div class="sales-quotation-bars">
                        @forelse ($automationItems as $automation)
                            @php($barWidth = max(10, round(((int) $automation->total / $automationMax) * 100, 1)))
                            @php($color = $chartPalette[$automation->status] ?? '#8b879a')
                            <div class="sales-quotation-item">
                                <div class="sales-quotation-head">
                                    @php($automationStatus = $marketingLabel($automation->status))
                                    <strong data-lang-en="{{ $automationStatus['en'] }}" data-lang-id="{{ $automationStatus['id'] }}">{{ $automationStatus['en'] }}</strong>
                                    <span>{{ number_format($automation->total) }}</span>
                                </div>
                                <div class="sales-quotation-track">
                                    <div class="sales-quotation-bar" style="--bar-width: {{ $barWidth }}%; --bar-color: {{ $color }};"></div>
                                </div>
                                <small data-lang-en="Automation status distribution" data-lang-id="Distribusi status automation">Automation status distribution</small>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No automation data yet." data-lang-id="Belum ada data automation.">No automation data yet.</div>
                        @endforelse
                    </div>
                </div>

                <div class="sales-chart-body">
                    <div class="sales-chart-hero">
                        <div>
                            <span data-lang-en="Total Lead Scoring Rules" data-lang-id="Total Rules Lead Scoring">Total Lead Scoring Rules</span>
                            <strong>{{ number_format($metrics['total_lead_scoring_rules']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Active Rules" data-lang-id="Rules Aktif">Active Rules</span>
                            <strong>{{ number_format($metrics['active_lead_scoring_rules']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Audience Segments" data-lang-id="Segmen Audiens">Audience Segments</span>
                            <strong>{{ number_format($totalAudienceSegments) }}</strong>
                        </div>
                    </div>

                    <div class="sales-quotation-bars">
                        @forelse ($leadScoringItems as $rule)
                            @php($barWidth = max(10, round(((int) $rule->total / $leadScoringMax) * 100, 1)))
                            @php($color = $chartPalette[$rule->status] ?? '#8b879a')
                            <div class="sales-quotation-item">
                                <div class="sales-quotation-head">
                                    @php($ruleStatus = $marketingLabel($rule->status))
                                    <strong data-lang-en="{{ $ruleStatus['en'] }}" data-lang-id="{{ $ruleStatus['id'] }}">{{ $ruleStatus['en'] }}</strong>
                                    <span>{{ number_format($rule->total) }}</span>
                                </div>
                                <div class="sales-quotation-track">
                                    <div class="sales-quotation-bar" style="--bar-width: {{ $barWidth }}%; --bar-color: {{ $color }};"></div>
                                </div>
                                <small data-lang-en="Lead scoring rules by status" data-lang-id="Rules lead scoring berdasarkan status">Lead scoring rules by status</small>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No lead scoring rule data yet." data-lang-id="Belum ada data lead scoring rules.">No lead scoring rule data yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Campaigns" data-lang-id="Campaign Terbaru">Recent Campaigns</h2>
                    <p data-lang-en="5 latest campaigns." data-lang-id="5 campaign terbaru.">5 latest campaigns.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Campaign" data-lang-id="Campaign">Campaign</th>
                            <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Actual Leads" data-lang-id="Lead Aktual">Actual Leads</th>
                            <th data-lang-en="Start Date" data-lang-id="Tanggal Mulai">Start Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentCampaigns as $campaign)
                            <tr>
                                <td>{{ $campaign->name }}</td>
                                @php($campaignType = $marketingLabel($campaign->type))
                                @php($recentCampaignStatus = $marketingLabel($campaign->status))
                                <td data-lang-en="{{ $campaignType['en'] }}" data-lang-id="{{ $campaignType['id'] }}">{{ $campaignType['en'] }}</td>
                                <td><span class="status-badge {{ $badgeClass($campaign->status) }}" data-lang-en="{{ $recentCampaignStatus['en'] }}" data-lang-id="{{ $recentCampaignStatus['id'] }}">{{ $recentCampaignStatus['en'] }}</span></td>
                                <td>{{ number_format((int) $campaign->actual_leads) }}</td>
                                <td>{{ optional($campaign->start_date)->format('d M Y') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty" data-lang-en="No campaigns found." data-lang-id="Tidak ada campaign.">No campaigns found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Executions" data-lang-id="Execution Terbaru">Recent Executions</h2>
                    <p data-lang-en="5 latest executions." data-lang-id="5 execution terbaru.">5 latest executions.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Execution" data-lang-id="Execution">Execution</th>
                            <th data-lang-en="Campaign" data-lang-id="Campaign">Campaign</th>
                            <th data-lang-en="Channel" data-lang-id="Channel">Channel</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Sent" data-lang-id="Terkirim">Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentExecutions as $execution)
                            <tr>
                                <td>{{ $execution->execution_name }}</td>
                                <td>{{ $execution->marketingCampaign?->name ?: '-' }}</td>
                                @php($executionChannel = $marketingLabel($execution->channel))
                                @php($executionStatus = $marketingLabel($execution->status))
                                <td data-lang-en="{{ $executionChannel['en'] }}" data-lang-id="{{ $executionChannel['id'] }}">{{ $executionChannel['en'] }}</td>
                                <td><span class="status-badge {{ $badgeClass($execution->status) }}" data-lang-en="{{ $executionStatus['en'] }}" data-lang-id="{{ $executionStatus['id'] }}">{{ $executionStatus['en'] }}</span></td>
                                <td>{{ number_format((int) $execution->sent_count) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty" data-lang-en="No executions found." data-lang-id="Tidak ada execution.">No executions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Landing Pages" data-lang-id="Landing Page Terbaru">Recent Landing Pages</h2>
                    <p data-lang-en="5 latest landing pages." data-lang-id="5 landing page terbaru.">5 latest landing pages.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Views" data-lang-id="Views">Views</th>
                            <th data-lang-en="Submissions" data-lang-id="Submission">Submissions</th>
                            <th data-lang-en="Published At" data-lang-id="Dipublikasikan Pada">Published At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLandingPages as $landingPage)
                            <tr>
                                <td>{{ $landingPage->title }}</td>
                                @php($landingStatus = $marketingLabel($landingPage->status))
                                <td><span class="status-badge {{ $badgeClass($landingPage->status) }}" data-lang-en="{{ $landingStatus['en'] }}" data-lang-id="{{ $landingStatus['id'] }}">{{ $landingStatus['en'] }}</span></td>
                                <td>{{ number_format((int) $landingPage->views_count) }}</td>
                                <td>{{ number_format((int) $landingPage->submissions_count) }}</td>
                                <td>{{ optional($landingPage->published_at)->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty" data-lang-en="No landing pages found." data-lang-id="Tidak ada landing page.">No landing pages found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Social Posts" data-lang-id="Post Sosial Terbaru">Recent Social Posts</h2>
                    <p data-lang-en="5 latest social posts." data-lang-id="5 social post terbaru.">5 latest social posts.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Platform" data-lang-id="Platform">Platform</th>
                            <th data-lang-en="Post Title" data-lang-id="Judul Post">Post Title</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Impressions" data-lang-id="Impression">Impressions</th>
                            <th data-lang-en="Engagement Rate" data-lang-id="Engagement Rate">Engagement Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSocialPosts as $socialPost)
                            <tr>
                                @php($socialPlatform = $marketingLabel($socialPost->platform))
                                @php($socialStatus = $marketingLabel($socialPost->status))
                                <td data-lang-en="{{ $socialPlatform['en'] }}" data-lang-id="{{ $socialPlatform['id'] }}">{{ $socialPlatform['en'] }}</td>
                                <td>{{ $socialPost->post_title }}</td>
                                <td><span class="status-badge {{ $badgeClass($socialPost->status) }}" data-lang-en="{{ $socialStatus['en'] }}" data-lang-id="{{ $socialStatus['id'] }}">{{ $socialStatus['en'] }}</span></td>
                                <td>{{ number_format((int) $socialPost->impressions_count) }}</td>
                                <td>{{ number_format((float) $socialPost->engagement_rate, 2, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty" data-lang-en="No social posts found." data-lang-id="Tidak ada post sosial.">No social posts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
