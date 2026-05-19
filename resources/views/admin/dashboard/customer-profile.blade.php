@extends('admin.layouts.app')

@section('title', 'Customer Profile 360 Dashboard - Krakatau CRM')

@section('content')
    @php
        $tx = static fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
        $titleTranslation = $tx('Customer Profile 360 Dashboard', 'Dashboard Customer Profile 360');
        $descriptionTranslation = $tx(
            'Customer profile summary across interactions, preferences, transactions, and lifecycle behavior.',
            'Ringkasan profil customer, interaction, preference, transaction, dan behavior lifecycle.'
        );
        $summaryCardItems = collect($summaryCards)->map(function (array $card) use ($tx) {
            $labelTranslation = match ($card['label']) {
                'Total Customers' => $tx('Total Customers', 'Total Customer'),
                'Total Interactions' => $tx('Total Interactions', 'Total Interaction'),
                'Total Preferences' => $tx('Total Preferences', 'Total Preference'),
                'Total Transactions' => $tx('Total Transactions', 'Total Transaksi'),
                'Won Transaction Value' => $tx('Won Transaction Value', 'Nilai Transaksi Won'),
                'Average Engagement Score' => $tx('Average Engagement Score', 'Rata-rata Skor Engagement'),
                default => $tx($card['label'], $card['label']),
            };

            return $card + ['label_translation' => $labelTranslation];
        });
        $customerLabel = static function (?string $value) use ($tx): array {
            return match ((string) $value) {
                'email' => $tx('Email', 'Email'),
                'whatsapp' => $tx('WhatsApp', 'WhatsApp'),
                'call' => $tx('Call', 'Telepon'),
                'meeting' => $tx('Meeting', 'Meeting'),
                'follow_up' => $tx('Follow Up', 'Follow Up'),
                'sms' => $tx('SMS', 'SMS'),
                'active' => $tx('Active', 'Aktif'),
                'inactive' => $tx('Inactive', 'Tidak Aktif'),
                'blacklist' => $tx('Blacklist', 'Blacklist'),
                'positive' => $tx('Positive', 'Positif'),
                'neutral' => $tx('Neutral', 'Netral'),
                'negative' => $tx('Negative', 'Negatif'),
                default => $tx(str((string) $value)->headline()->toString(), str((string) $value)->headline()->toString()),
            };
        };
        $chartPalette = [
            'email' => '#7c3aed',
            'whatsapp' => '#ec4899',
            'call' => '#00bad1',
            'meeting' => '#f97316',
            'follow_up' => '#8b5cf6',
            'sms' => '#16a34a',
            'active' => '#7c3aed',
            'inactive' => '#8b879a',
            'blacklist' => '#ff4c51',
            'positive' => '#00bad1',
            'neutral' => '#f97316',
            'negative' => '#ff4c51',
        ];

        $channelItems = $interactionChannelOverview->values();
        $channelSegments = [];
        $channelOffset = 0;

        foreach ($channelItems as $item) {
            $share = round(((int) $item->total / max($channelTotal, 1)) * 100, 2);
            $color = $chartPalette[$item->channel] ?? '#7367f0';
            $channelSegments[] = "{$color} {$channelOffset}% ".($channelOffset + $share).'%';
            $channelOffset += $share;
        }

        $channelChart = $channelSegments !== []
            ? 'conic-gradient('.implode(', ', $channelSegments).')'
            : 'conic-gradient(#eef2ff 0% 100%)';

        $trendLabels = $activityTrend['labels'] ?? [];
        $trendSeries = collect($activityTrend['series'] ?? []);
        $trendChartWidth = 660;
        $trendChartHeight = 280;
        $trendPaddingX = 34;
        $trendPaddingY = 24;
        $trendInnerWidth = $trendChartWidth - ($trendPaddingX * 2);
        $trendInnerHeight = $trendChartHeight - ($trendPaddingY * 2);
        $trendBaselineY = $trendPaddingY + $trendInnerHeight;
        $trendPointCount = max(count($trendLabels), 2);
        $trendMax = max(1, (int) $trendSeries->flatMap(fn (array $series) => $series['values'] ?? [0])->max());

        $linePoints = static function (
            array $values,
            int $pointCount,
            int $chartWidth,
            int $paddingX,
            int $paddingY,
            int $innerHeight,
            int $maxValue
        ): array {
            $stepX = ($chartWidth - ($paddingX * 2)) / max($pointCount - 1, 1);

            return collect($values)
                ->values()
                ->map(function ($value, $index) use ($stepX, $paddingX, $paddingY, $innerHeight, $maxValue) {
                    return [
                        'x' => $paddingX + ($stepX * $index),
                        'y' => $paddingY + $innerHeight - (($value / max($maxValue, 1)) * $innerHeight),
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

        $lineAreaPath = static function (array $points, float $baselineY) use ($smoothLinePath): string {
            if ($points === []) {
                return '';
            }

            $linePath = $smoothLinePath($points);
            $firstPoint = $points[0];
            $lastPoint = $points[count($points) - 1];

            return $linePath
                .' L '.number_format($lastPoint['x'], 2, '.', '').' '.number_format($baselineY, 2, '.', '')
                .' L '.number_format($firstPoint['x'], 2, '.', '').' '.number_format($baselineY, 2, '.', '')
                .' Z';
        };

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
        ])->map(function (array $position, string $key) use ($journeyNodes, $journeyNodeMax, $journeyNodeWidth) {
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
                'color' => (string) ($node['color'] ?? '#7367f0'),
            ];
        });
        $journeyLinkMax = max(1, (int) $journeyLinks->max('value'));

        $stackedItems = collect($interactionTypeStacked ?? []);
        $heatmapRows = collect($engagementHeatmap ?? []);
        $heatmapBands = $engagementHeatmapBands ?? [];
        $heatmapMax = max(1, (int) $heatmapRows->flatMap(fn (array $row) => collect($row['cells'] ?? [])->pluck('value'))->max());

        $sentimentItems = $sentimentOverview->values();
        $sentimentTotal = max((int) $sentimentItems->sum('total'), 1);
        $sentimentGauge = 'conic-gradient(#28c76f 0% '.max(0, min(100, (float) $metrics['sentiment_score'])).'%, #e9edf7 '.max(0, min(100, (float) $metrics['sentiment_score'])).'% 100%)';
    @endphp

    <section class="service-page customer-list-page sales-workspace sales-dashboard-page customer-dashboard-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'user'])</div>
            <div>
                <span data-doc-title-en="Customer Profile 360 Dashboard - Krakatau CRM" data-doc-title-id="Dashboard Customer Profile 360 - Krakatau CRM" hidden></span>
                <span class="service-badge dashboard-hero-badge" data-lang-en="Relationship Intelligence" data-lang-id="Intelijen Relasi">Relationship Intelligence</span>
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

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card sales-chart-card--lead">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Interaction Channel" data-lang-id="Channel Interaksi">Interaction Channel</h2>
                        <p data-lang-en="Distribution of the preferred channels most often selected by customers." data-lang-id="Distribusi preferred channel yang paling sering dipilih customer.">Distribution of the preferred channels most often selected by customers.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--lead">
                    <div class="sales-donut-panel">
                        <div class="sales-donut-chart" style="--chart-fill: {{ $channelChart }};">
                            <div class="sales-donut-center">
                                <span data-lang-en="Channel Records" data-lang-id="Record Channel">Channel Records</span>
                                <strong>{{ number_format($channelTotal) }}</strong>
                                <small data-lang-en="{{ number_format($metrics['total_preferences']) }} preference logs / {{ number_format($metrics['total_interactions']) }} interactions" data-lang-id="{{ number_format($metrics['total_preferences']) }} log preferensi / {{ number_format($metrics['total_interactions']) }} interaksi">{{ number_format($metrics['total_preferences']) }} preference logs / {{ number_format($metrics['total_interactions']) }} interactions</small>
                            </div>
                        </div>
                    </div>

                    <div class="sales-legend-list">
                        @forelse ($channelItems as $channel)
                            @php($share = round(((int) $channel->total / max($channelTotal, 1)) * 100, 1))
                            @php($color = $chartPalette[$channel->channel] ?? '#7367f0')
                            <div class="sales-legend-item">
                                <span class="sales-legend-dot" style="--legend-color: {{ $color }};"></span>
                                <div class="sales-legend-copy">
                                    @php($channelLabel = $customerLabel($channel->channel))
                                    <strong data-lang-en="{{ $channelLabel['en'] }}" data-lang-id="{{ $channelLabel['id'] }}">{{ $channelLabel['en'] }}</strong>
                                    <small>{{ number_format($channel->total) }} records / {{ number_format($share, 1, ',', '.') }}%</small>
                                </div>
                                <div class="sales-legend-metric">{{ number_format($channel->total) }}</div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No interaction channel data yet." data-lang-id="Belum ada data interaction channel.">No interaction channel data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card sales-chart-card--trend">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Trend Activity" data-lang-id="Tren Aktivitas">Trend Activity</h2>
                        <p data-lang-en="Interaction, transaction, and behavior update movement over the last 6 months." data-lang-id="Pergerakan interaction, transaction, dan behavior update dalam 6 bulan terakhir.">Interaction, transaction, and behavior update movement over the last 6 months.</p>
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
                        <svg viewBox="0 0 {{ $trendChartWidth }} {{ $trendChartHeight }}" class="sales-line-chart" aria-label="Customer profile activity trend line chart" role="img">
                            <rect x="{{ $trendPaddingX }}" y="{{ $trendPaddingY }}" width="{{ $trendInnerWidth }}" height="{{ $trendInnerHeight }}" rx="20" class="sales-line-plot-shell" />

                            @for ($grid = 0; $grid < 5; $grid++)
                                @php($y = $trendPaddingY + (($trendInnerHeight / 4) * $grid))
                                @php($labelValue = round($trendMax - (($trendMax / 4) * $grid)))
                                <line x1="{{ $trendPaddingX }}" y1="{{ $y }}" x2="{{ $trendChartWidth - $trendPaddingX }}" y2="{{ $y }}" class="sales-line-grid" />
                                <text x="6" y="{{ $y + 4 }}" class="sales-line-axis-label">{{ $labelValue }}</text>
                            @endfor

                            @foreach ($trendLabels as $index => $label)
                                @php($stepX = $trendInnerWidth / max($trendPointCount - 1, 1))
                                @php($x = $trendPaddingX + ($stepX * $index))
                                <line x1="{{ $x }}" y1="{{ $trendPaddingY }}" x2="{{ $x }}" y2="{{ $trendBaselineY }}" class="sales-line-grid sales-line-grid--vertical" />
                            @endforeach

                            <line x1="{{ $trendPaddingX }}" y1="{{ $trendBaselineY }}" x2="{{ $trendChartWidth - $trendPaddingX }}" y2="{{ $trendBaselineY }}" class="sales-line-baseline" />

                            @foreach ($trendSeries as $series)
                                @php($points = $linePoints($series['values'], $trendPointCount, $trendChartWidth, $trendPaddingX, $trendPaddingY, $trendInnerHeight, $trendMax))
                                @php($path = $smoothLinePath($points))
                                @php($areaPath = $lineAreaPath($points, $trendBaselineY))
                                @php($lastPoint = $points[count($points) - 1] ?? ['x' => 0, 'y' => 0])
                                @php($labelX = max($trendPaddingX + 48, $lastPoint['x'] - 14))
                                @php($labelY = $lastPoint['y'] < ($trendPaddingY + 18) ? $lastPoint['y'] + 18 : $lastPoint['y'] - 12)
                                <path d="{{ $areaPath }}" class="sales-line-area" style="--series-fill: {{ $series['color'] }}22;" />
                                <path d="{{ $path }}" class="sales-line-series customer-smooth-line" style="--series-color: {{ $series['color'] }};" />
                                @foreach ($points as $point)
                                    <circle cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4" class="sales-line-point" style="--series-color: {{ $series['color'] }};" />
                                @endforeach
                                <text x="{{ $labelX }}" y="{{ $labelY }}" text-anchor="end" class="sales-line-series-label" style="--series-color: {{ $series['color'] }};">{{ $series['name'] }}</text>
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
        </div>

        <article class="card customer-table-card sales-chart-card customer-journey-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Customer Journey" data-lang-id="Customer Journey">Customer Journey</h2>
                    <p data-lang-en="Primary customer flow from onboarding to loyal or churned." data-lang-id="Flow utama customer dari onboarding sampai loyal atau churned.">Primary customer flow from onboarding to loyal or churned.</p>
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
                                    <text x="18" y="{{ $titleY }}" class="customer-sankey-title" dominant-baseline="hanging" data-lang-en="{{ $node['label_translation']['en'] ?? $node['label'] }}" data-lang-id="{{ $node['label_translation']['id'] ?? $node['label'] }}">{{ $node['label_translation']['en'] ?? $node['label'] }}</text>
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
            <article class="card customer-table-card sales-chart-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Interaction Type" data-lang-id="Tipe Interaksi">Interaction Type</h2>
                        <p data-lang-en="Stacked bar by type based on the status of interacting customers." data-lang-id="Stacked bar per type berdasarkan status customer yang berinteraksi.">Stacked bar by type based on the status of interacting customers.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="customer-stack-legend">
                        <span data-lang-en="Active" data-lang-id="Aktif"><i style="--legend-color:#28c76f;"></i>Active</span>
                        <span data-lang-en="Inactive" data-lang-id="Tidak Aktif"><i style="--legend-color:#8b879a;"></i>Inactive</span>
                        <span data-lang-en="Blacklist" data-lang-id="Blacklist"><i style="--legend-color:#ff4c51;"></i>Blacklist</span>
                    </div>

                    <div class="customer-stack-list">
                        @forelse ($stackedItems as $item)
                            <div class="customer-stack-item">
                                <div class="customer-stack-head">
                                    @php($interactionType = $customerLabel($item['type']))
                                    <strong data-lang-en="{{ $interactionType['en'] }}" data-lang-id="{{ $interactionType['id'] }}">{{ $interactionType['en'] }}</strong>
                                    <span data-lang-en="{{ number_format($item['total']) }} logs" data-lang-id="{{ number_format($item['total']) }} log">{{ number_format($item['total']) }} logs</span>
                                </div>
                                <div class="customer-stack-bar">
                                    @foreach ($item['segments'] as $segment)
                                        @php($width = $item['total'] > 0 ? (($segment['total'] / $item['total']) * 100) : 0)
                                        @php($color = $chartPalette[$segment['status']] ?? '#8b879a')
                                        @if ($segment['total'] > 0)
                                            <div class="customer-stack-segment" style="--segment-width: {{ max(6, $width) }}%; --segment-color: {{ $color }};"></div>
                                        @endif
                                    @endforeach
                                </div>
                                <div class="customer-stack-meta">
                                    @foreach ($item['segments'] as $segment)
                                        @php($segmentLabel = $customerLabel($segment['status']))
                                        <small data-lang-en="{{ $segmentLabel['en'] }} {{ number_format($segment['total']) }}" data-lang-id="{{ $segmentLabel['id'] }} {{ number_format($segment['total']) }}">{{ $segmentLabel['en'] }} {{ number_format($segment['total']) }}</small>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No interaction type data yet." data-lang-id="Belum ada data interaction type.">No interaction type data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Engagement Time" data-lang-id="Waktu Engagement">Engagement Time</h2>
                        <p data-lang-en="Interaction activity heatmap by the most active day and hour." data-lang-id="Heatmap aktivitas interaction berdasarkan hari dan jam yang paling aktif.">Interaction activity heatmap by the most active day and hour.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="customer-heatmap">
                        <div class="customer-heatmap-head">
                            <span></span>
                            @foreach ($heatmapBands as $band)
                                <span>{{ $band }}</span>
                            @endforeach
                        </div>

                        @forelse ($heatmapRows as $row)
                            <div class="customer-heatmap-row">
                                <strong>{{ $row['day'] }}</strong>
                                @foreach ($row['cells'] as $cell)
                                    @php($intensity = $heatmapMax > 0 ? ($cell['value'] / $heatmapMax) : 0)
                                    <div class="customer-heatmap-cell" style="--cell-alpha: {{ max(0.08, min(0.92, $intensity)) }};">
                                        <span>{{ $cell['value'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No engagement time data yet." data-lang-id="Belum ada data engagement time.">No engagement time data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card sales-chart-card--quotation">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Sentiment" data-lang-id="Sentimen">Sentiment</h2>
                        <p data-lang-en="Customer sentiment overview from incoming satisfaction surveys and ratings." data-lang-id="Gambaran sentimen customer dari survey satisfaction dan rating yang masuk.">Customer sentiment overview from incoming satisfaction surveys and ratings.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--quotation">
                    <div class="sales-quotation-ring-panel">
                        <div class="sales-quotation-ring" style="--ring-fill: {{ $sentimentGauge }};">
                            <div class="sales-quotation-ring-center">
                                <span data-lang-en="Sentiment Score" data-lang-id="Skor Sentimen">Sentiment Score</span>
                                <strong>{{ number_format((float) $metrics['sentiment_score'], 1, ',', '.') }}</strong>
                                <small data-lang-en="Avg rating {{ number_format((float) $metrics['average_sentiment_rating'], 1, ',', '.') }} / 5" data-lang-id="Rata-rata rating {{ number_format((float) $metrics['average_sentiment_rating'], 1, ',', '.') }} / 5">Avg rating {{ number_format((float) $metrics['average_sentiment_rating'], 1, ',', '.') }} / 5</small>
                            </div>
                        </div>
                    </div>

                    <div class="sales-quotation-bars">
                        @forelse ($sentimentItems as $sentiment)
                            @php($share = round(((int) $sentiment->total / max($sentimentTotal, 1)) * 100, 1))
                            @php($color = $chartPalette[$sentiment->sentiment] ?? '#8b879a')
                            <div class="sales-quotation-item">
                                <div class="sales-quotation-head">
                                    @php($sentimentLabel = $customerLabel($sentiment->sentiment))
                                    <strong data-lang-en="{{ $sentimentLabel['en'] }}" data-lang-id="{{ $sentimentLabel['id'] }}">{{ $sentimentLabel['en'] }}</strong>
                                    <span>{{ number_format($sentiment->total) }}</span>
                                </div>
                                <div class="sales-quotation-track">
                                    <div class="sales-quotation-bar" style="--bar-width: {{ max(10, $share) }}%; --bar-color: {{ $color }};"></div>
                                </div>
                                <small data-lang-en="{{ number_format($share, 1, ',', '.') }}% of total sentiment responses" data-lang-id="{{ number_format($share, 1, ',', '.') }}% dari total respons sentimen">{{ number_format($share, 1, ',', '.') }}% of total sentiment responses</small>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No sentiment data yet." data-lang-id="Belum ada data sentiment.">No sentiment data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Customer History" data-lang-id="Riwayat Customer">Customer History</h2>
                        <p data-lang-en="Latest customer activity timeline across customer, interaction, transaction, and behavior." data-lang-id="Timeline aktivitas customer terbaru dari customer, interaction, transaction, dan behavior.">Latest customer activity timeline across customer, interaction, transaction, and behavior.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="customer-history-list">
                        @forelse ($customerHistory as $event)
                            <div class="customer-history-item">
                                <div class="customer-history-dot" style="--history-color: {{ $event['accent'] }};"></div>
                                <div class="customer-history-content">
                                    <div class="customer-history-meta">
                                        <span>{{ $event['label'] }}</span>
                                        <small>{{ optional($event['timestamp'])->format('d M Y H:i') ?: '-' }}</small>
                                    </div>
                                    <strong>{{ $event['title'] }}</strong>
                                    <p>{{ $event['meta'] }}</p>
                                </div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No customer history yet." data-lang-id="Belum ada riwayat customer.">No customer history yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>
    </section>
@endsection
