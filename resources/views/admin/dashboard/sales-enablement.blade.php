@extends('admin.layouts.app')

@section('title', 'Sales Enablement Dashboard - Krakatau CRM')

@section('content')
    @php
        $tx = static fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
        $titleTranslation = $tx('Sales Enablement Dashboard', 'Dashboard Sales Enablement');
        $descriptionTranslation = $tx(
            'Sales summary for leads, opportunities, forecast, sales activities, and quotations.',
            'Ringkasan performa lead, opportunity, forecast, aktivitas sales, dan quotation.'
        );
        $summaryCardItems = collect($summaryCards)->map(function (array $card) use ($tx) {
            $labelTranslation = match ($card['label']) {
                'Total Leads' => $tx('Total Leads', 'Total Lead'),
                'Total Opportunities' => $tx('Total Opportunities', 'Total Opportunity'),
                'Pipeline Value' => $tx('Pipeline Value', 'Nilai Pipeline'),
                'Win Rate' => $tx('Win Rate', 'Win Rate'),
                'Sales Activities' => $tx('Sales Activities', 'Aktivitas Sales'),
                'Quotation Value' => $tx('Quotation Value', 'Nilai Quotation'),
                default => $tx($card['label'], $card['label']),
            };

            return $card + ['label_translation' => $labelTranslation];
        });
        $statusTranslation = static function (?string $value) use ($tx): array {
            return match ((string) $value) {
                'new' => $tx('New', 'Baru'),
                'contacted' => $tx('Contacted', 'Dihubungi'),
                'qualified' => $tx('Qualified', 'Qualified'),
                'converted' => $tx('Converted', 'Terkonversi'),
                'unqualified' => $tx('Unqualified', 'Tidak Qualified'),
                'open' => $tx('Open', 'Open'),
                'proposal' => $tx('Proposal', 'Proposal'),
                'negotiation' => $tx('Negotiation', 'Negosiasi'),
                'won' => $tx('Won', 'Won'),
                'lost' => $tx('Lost', 'Lost'),
                'draft' => $tx('Draft', 'Draft'),
                'sent' => $tx('Sent', 'Terkirim'),
                'accepted' => $tx('Accepted', 'Diterima'),
                'rejected' => $tx('Rejected', 'Ditolak'),
                'expired' => $tx('Expired', 'Kedaluwarsa'),
                'high' => $tx('High', 'Tinggi'),
                'medium' => $tx('Medium', 'Sedang'),
                'low' => $tx('Low', 'Rendah'),
                'lead' => $tx('Lead', 'Lead'),
                'opportunity' => $tx('Opportunity', 'Opportunity'),
                default => $tx(str((string) $value)->headline()->toString(), str((string) $value)->headline()->toString()),
            };
        };
        $badgeClass = static function (?string $status): string {
            return match ((string) $status) {
                'new', 'open', 'qualified', 'high', 'accepted' => 'status-active',
                'contacted', 'proposal', 'negotiation', 'medium', 'sent', 'draft' => 'status-pending',
                'won', 'converted' => 'status-won',
                'lost', 'unqualified', 'rejected', 'expired', 'low' => 'status-lost',
                default => 'status-inactive',
            };
        };

        $chartPalette = [
            'new' => '#4361ee',
            'contacted' => '#00bad1',
            'qualified' => '#28c76f',
            'converted' => '#16a34a',
            'unqualified' => '#ff4c51',
            'open' => '#4361ee',
            'proposal' => '#ff9f43',
            'negotiation' => '#4cc9f0',
            'won' => '#28c76f',
            'lost' => '#ff4c51',
            'draft' => '#8b879a',
            'sent' => '#00bad1',
            'accepted' => '#28c76f',
            'rejected' => '#ff4c51',
            'expired' => '#ff9f43',
        ];

        $leadItems = $leadStatusOverview->values();
        $leadTotal = max((int) $leadItems->sum('total'), 1);
        $leadSegments = [];
        $leadOffset = 0;

        foreach ($leadItems as $item) {
            $share = round(((int) $item->total / $leadTotal) * 100, 2);
            $color = $chartPalette[$item->status] ?? '#8b879a';
            $leadSegments[] = "{$color} {$leadOffset}% ".($leadOffset + $share).'%';
            $leadOffset += $share;
        }

        $leadChart = $leadSegments !== []
            ? 'conic-gradient('.implode(', ', $leadSegments).')'
            : 'conic-gradient(#eef2ff 0% 100%)';

        $pipelineItems = $opportunityPipelineOverview->values();
        $pipelineTotalCount = max((int) $pipelineItems->sum('total'), 1);
        $pipelineMaxCount = max((int) $pipelineItems->max('total'), 1);
        $pipelineGaugeRate = $metrics['pipeline_value'] > 0
            ? min(100, round(($metrics['weighted_forecast'] / max((float) $metrics['pipeline_value'], 1)) * 100, 1))
            : 0;
        $pipelineGauge = 'conic-gradient(#7367f0 0% '.$pipelineGaugeRate.'%, #dfe4fb '.$pipelineGaugeRate.'% 100%)';
        $pipelineOpenRate = $metrics['total_opportunities'] > 0
            ? round(($metrics['open_opportunities'] / max((int) $metrics['total_opportunities'], 1)) * 100, 1)
            : 0;
        $pipelineWonTotal = (int) optional($pipelineItems->firstWhere('status', 'won'))->total;
        $pipelineWonRate = $pipelineTotalCount > 0
            ? round(($pipelineWonTotal / $pipelineTotalCount) * 100, 1)
            : 0;
        $pipelineAverageValue = $pipelineTotalCount > 0
            ? ((float) $metrics['pipeline_value'] / $pipelineTotalCount)
            : 0;

        $quotationItems = $quotationStatusOverview->values();
        $quotationMaxCount = max((int) $quotationItems->max('total'), 1);
        $quotationAcceptanceRate = $metrics['total_quotations'] > 0
            ? round(($metrics['accepted_quotations'] / $metrics['total_quotations']) * 100, 1)
            : 0;
        $quotationRing = 'conic-gradient(#28c76f 0% '.$quotationAcceptanceRate.'%, #e9edf7 '.$quotationAcceptanceRate.'% 100%)';
    @endphp

    <section class="service-page customer-list-page sales-workspace sales-dashboard-page sales-enablement-dashboard-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'pipeline'])</div>
            <div>
                <span data-doc-title-en="Sales Enablement Dashboard - Krakatau CRM" data-doc-title-id="Dashboard Sales Enablement - Krakatau CRM" hidden></span>
                <span class="service-badge dashboard-hero-badge" data-lang-en="Revenue Engine" data-lang-id="Mesin Pendapatan">Revenue Engine</span>
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
                        <h2 data-lang-en="Lead Status Overview" data-lang-id="Ringkasan Status Lead">Lead Status Overview</h2>
                        <p data-lang-en="Lead status distribution from acquisition funnel to conversion." data-lang-id="Distribusi status lead dari funnel akuisisi hingga konversi.">Lead status distribution from acquisition funnel to conversion.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--lead">
                    <div class="sales-donut-panel">
                        <div class="sales-donut-chart" style="--chart-fill: {{ $leadChart }};">
                            <div class="sales-donut-center">
                                <span data-lang-en="Total Leads" data-lang-id="Total Lead">Total Leads</span>
                                <strong>{{ number_format($metrics['total_leads']) }}</strong>
                                <small>{{ number_format($metrics['qualified_leads']) }} qualified • {{ number_format($metrics['converted_leads']) }} converted</small>
                            </div>
                        </div>
                    </div>

                    <div class="sales-legend-list">
                        @forelse ($leadItems as $status)
                            @php
                                $share = round(((int) $status->total / $leadTotal) * 100, 1);
                                $color = $chartPalette[$status->status] ?? '#8b879a';
                            @endphp
                            <div class="sales-legend-item">
                                <span class="sales-legend-dot" style="--legend-color: {{ $color }};"></span>
                                <div class="sales-legend-copy">
                                    <strong>{{ str($status->status)->headline() }}</strong>
                                    <small>{{ number_format($status->total) }} leads • {{ $share }}%</small>
                                </div>
                                <div class="sales-legend-metric">{{ number_format($status->total) }}</div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No lead status data yet." data-lang-id="Belum ada data status lead.">No lead status data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card sales-chart-card--pipeline">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Opportunity Pipeline Overview" data-lang-id="Ringkasan Pipeline Opportunity">Opportunity Pipeline Overview</h2>
                        <p data-lang-en="Opportunity status and value across each pipeline stage." data-lang-id="Status opportunity dan nilai tiap stage pipeline.">Opportunity status and value across each pipeline stage.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--pipeline-premium">
                    <div class="sales-radial-panel">
                        <div class="sales-radial-chart" style="--gauge-fill: {{ $pipelineGauge }};">
                            <div class="sales-radial-core">
                                <span data-lang-en="Forecast Coverage" data-lang-id="Cakupan Forecast">Forecast Coverage</span>
                                <strong>{{ number_format($pipelineGaugeRate, 1, ',', '.') }}%</strong>
                                <small>{{ 'Rp '.number_format((float) $metrics['weighted_forecast'], 0, ',', '.') }} dari {{ 'Rp '.number_format((float) $metrics['pipeline_value'], 0, ',', '.') }}</small>
                            </div>
                        </div>

                        <div class="sales-radial-metrics">
                            <div class="sales-radial-metric">
                                <span data-lang-en="Open Opportunities" data-lang-id="Opportunity Terbuka">Open Opportunities</span>
                                <strong>{{ number_format($metrics['open_opportunities']) }}</strong>
                                <small>{{ number_format($pipelineOpenRate, 1, ',', '.') }}% dari total opportunity</small>
                            </div>
                            <div class="sales-radial-metric">
                                <span data-lang-en="Avg Pipeline Value" data-lang-id="Rata-rata Nilai Pipeline">Avg Pipeline Value</span>
                                <strong>{{ 'Rp '.number_format($pipelineAverageValue, 0, ',', '.') }}</strong>
                                <small>Rata-rata per stage opportunity</small>
                            </div>
                            <div class="sales-radial-metric">
                                <span data-lang-en="Won Share" data-lang-id="Porsi Won">Won Share</span>
                                <strong>{{ number_format($pipelineWonRate, 1, ',', '.') }}%</strong>
                                <small>{{ number_format($pipelineWonTotal) }} opportunity won</small>
                            </div>
                        </div>
                    </div>

                    <div class="sales-stage-list">
                        @forelse ($pipelineItems as $opportunity)
                            @php
                                $countWidth = max(12, round(((int) $opportunity->total / $pipelineMaxCount) * 100, 1));
                                $valueShare = $metrics['pipeline_value'] > 0
                                    ? round(((float) $opportunity->value_total / max((float) $metrics['pipeline_value'], 1)) * 100, 1)
                                    : 0;
                                $color = $chartPalette[$opportunity->status] ?? '#8b879a';
                            @endphp
                            <div class="sales-stage-item">
                                <div class="sales-stage-head">
                                    <div class="sales-stage-title-row">
                                        @php($opportunityStatus = $statusTranslation($opportunity->status))
                                        <strong data-lang-en="{{ $opportunityStatus['en'] }}" data-lang-id="{{ $opportunityStatus['id'] }}">{{ $opportunityStatus['en'] }}</strong>
                                        <span class="sales-stage-value">{{ 'Rp '.number_format((float) $opportunity->value_total, 0, ',', '.') }}</span>
                                    </div>
                                    <small class="sales-stage-count" data-lang-en="{{ number_format($opportunity->total) }} opps" data-lang-id="{{ number_format($opportunity->total) }} opp">{{ number_format($opportunity->total) }} opps</small>
                                </div>
                                <div class="sales-stage-track">
                                    <div class="sales-stage-bar" style="--bar-width: {{ $countWidth }}%; --bar-color: {{ $color }};"></div>
                                </div>
                                <div class="sales-stage-foot">
                                    <small data-lang-en="{{ number_format($valueShare, 1, ',', '.') }}% pipeline value contribution" data-lang-id="{{ number_format($valueShare, 1, ',', '.') }}% kontribusi pipeline value">{{ number_format($valueShare, 1, ',', '.') }}% pipeline value contribution</small>
                                </div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No opportunity stage data yet." data-lang-id="Belum ada stage opportunity.">No opportunity stage data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>

        <article class="card customer-table-card sales-chart-card sales-chart-card--quotation">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Quotation Status Overview" data-lang-id="Ringkasan Status Quotation">Quotation Status Overview</h2>
                    <p data-lang-en="Quotation distribution by status and total value." data-lang-id="Distribusi quotation berdasarkan status dan total nilai.">Quotation distribution by status and total value.</p>
                </div>
            </div>
            <div class="sales-chart-body sales-chart-body--quotation">
                <div class="sales-quotation-ring-panel">
                    <div class="sales-quotation-ring" style="--ring-fill: {{ $quotationRing }};">
                        <div class="sales-quotation-ring-center">
                            <span data-lang-en="Accepted Rate" data-lang-id="Accepted Rate">Accepted Rate</span>
                            <strong>{{ number_format($quotationAcceptanceRate, 1, ',', '.') }}%</strong>
                            <small>{{ number_format($metrics['accepted_quotations']) }} dari {{ number_format($metrics['total_quotations']) }} quotation</small>
                        </div>
                    </div>
                    <div class="sales-quotation-summary">
                        <div>
                            <span data-lang-en="Total Value" data-lang-id="Total Nilai">Total Value</span>
                            <strong>{{ 'Rp '.number_format((float) $metrics['quotation_value'], 0, ',', '.') }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Total Quotations" data-lang-id="Total Quotation">Total Quotations</span>
                            <strong>{{ number_format($metrics['total_quotations']) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="sales-quotation-bars">
                    @forelse ($quotationItems as $quotation)
                        @php($countWidth = max(10, round(((int) $quotation->total / $quotationMaxCount) * 100, 1)))
                        @php($color = $chartPalette[$quotation->status] ?? '#8b879a')
                        <div class="sales-quotation-item">
                            <div class="sales-quotation-head">
                                @php($quotationStatus = $statusTranslation($quotation->status))
                                <strong data-lang-en="{{ $quotationStatus['en'] }}" data-lang-id="{{ $quotationStatus['id'] }}">{{ $quotationStatus['en'] }}</strong>
                                <span>{{ number_format($quotation->total) }}</span>
                            </div>
                            <div class="sales-quotation-track">
                                <div class="sales-quotation-bar" style="--bar-width: {{ $countWidth }}%; --bar-color: {{ $color }};"></div>
                            </div>
                            <small>{{ 'Rp '.number_format((float) $quotation->value_total, 0, ',', '.') }}</small>
                        </div>
                    @empty
                        <div class="sales-empty-chart-state" data-lang-en="No quotation status data yet." data-lang-id="Belum ada status quotation.">No quotation status data yet.</div>
                    @endforelse
                </div>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Leads" data-lang-id="Lead Terbaru">Recent Leads</h2>
                    <p data-lang-en="5 latest leads." data-lang-id="5 lead terbaru.">5 latest leads.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Name" data-lang-id="Nama">Name</th>
                            <th data-lang-en="Company" data-lang-id="Perusahaan">Company</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Priority" data-lang-id="Prioritas">Priority</th>
                            <th data-lang-en="Source" data-lang-id="Sumber">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLeads as $lead)
                            <tr>
                                <td>{{ $lead->name }}</td>
                                <td>{{ $lead->company_name ?: '-' }}</td>
                                @php($leadStatus = $statusTranslation($lead->status))
                                @php($leadPriority = $statusTranslation($lead->priority))
                                <td><span class="status-badge {{ $badgeClass($lead->status) }}" data-lang-en="{{ $leadStatus['en'] }}" data-lang-id="{{ $leadStatus['id'] }}">{{ $leadStatus['en'] }}</span></td>
                                <td><span class="status-badge {{ $badgeClass($lead->priority) }}" data-lang-en="{{ $leadPriority['en'] }}" data-lang-id="{{ $leadPriority['id'] }}">{{ $leadPriority['en'] }}</span></td>
                                <td>{{ $lead->source ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty" data-lang-en="No leads found." data-lang-id="Tidak ada lead.">No leads found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Opportunities" data-lang-id="Opportunity Terbaru">Recent Opportunities</h2>
                    <p data-lang-en="5 latest opportunities." data-lang-id="5 opportunity terbaru.">5 latest opportunities.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Opportunity" data-lang-id="Opportunity">Opportunity</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Probability" data-lang-id="Probabilitas">Probability</th>
                            <th data-lang-en="Value" data-lang-id="Nilai">Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentOpportunities as $opportunity)
                            <tr>
                                <td>{{ $opportunity->title }}</td>
                                @php($recentOpportunityStatus = $statusTranslation($opportunity->status))
                                <td><span class="status-badge {{ $badgeClass($opportunity->status) }}" data-lang-en="{{ $recentOpportunityStatus['en'] }}" data-lang-id="{{ $recentOpportunityStatus['id'] }}">{{ $recentOpportunityStatus['en'] }}</span></td>
                                <td>{{ number_format((float) $opportunity->probability, 0, ',', '.') }}%</td>
                                <td>{{ 'Rp '.number_format((float) $opportunity->estimated_value, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="customer-empty" data-lang-en="No opportunities found." data-lang-id="Tidak ada opportunity.">No opportunities found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Sales Activities" data-lang-id="Aktivitas Sales Terbaru">Recent Sales Activities</h2>
                    <p data-lang-en="5 latest sales activities." data-lang-id="5 aktivitas sales terbaru.">5 latest sales activities.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Type" data-lang-id="Tipe">Type</th>
                            <th data-lang-en="Subject" data-lang-id="Subjek">Subject</th>
                            <th data-lang-en="Related To" data-lang-id="Terkait Ke">Related To</th>
                            <th data-lang-en="Assigned To" data-lang-id="Ditugaskan Ke">Assigned To</th>
                            <th data-lang-en="Activity At" data-lang-id="Waktu Aktivitas">Activity At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSalesActivities as $activity)
                            <tr>
                                @php($activityType = $statusTranslation($activity->type))
                                @php($relatedType = $statusTranslation($activity->related_type))
                                <td><span class="status-badge status-pending" data-lang-en="{{ $activityType['en'] }}" data-lang-id="{{ $activityType['id'] }}">{{ $activityType['en'] }}</span></td>
                                <td>{{ $activity->subject }}</td>
                                <td data-lang-en="{{ $relatedType['en'] }}" data-lang-id="{{ $relatedType['id'] }}">{{ $relatedType['en'] }}</td>
                                <td>{{ $activity->assigned_to ?: '-' }}</td>
                                <td>{{ optional($activity->activity_at)->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty" data-lang-en="No sales activities found." data-lang-id="Tidak ada aktivitas sales.">No sales activities found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Quotations" data-lang-id="Quotation Terbaru">Recent Quotations</h2>
                    <p data-lang-en="5 latest quotations." data-lang-id="5 quotation terbaru.">5 latest quotations.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Quote Number" data-lang-id="Nomor Quotation">Quote Number</th>
                            <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                            <th data-lang-en="Status" data-lang-id="Status">Status</th>
                            <th data-lang-en="Amount" data-lang-id="Jumlah">Amount</th>
                            <th data-lang-en="Issued At" data-lang-id="Tanggal Terbit">Issued At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentQuotations as $quotation)
                            <tr>
                                <td>{{ $quotation->quote_number }}</td>
                                <td>{{ $quotation->title }}</td>
                                @php($recentQuotationStatus = $statusTranslation($quotation->status))
                                <td><span class="status-badge {{ $badgeClass($quotation->status) }}" data-lang-en="{{ $recentQuotationStatus['en'] }}" data-lang-id="{{ $recentQuotationStatus['id'] }}">{{ $recentQuotationStatus['en'] }}</span></td>
                                <td>{{ 'Rp '.number_format((float) $quotation->amount, 0, ',', '.') }}</td>
                                <td>{{ optional($quotation->issued_at)->format('d M Y') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty" data-lang-en="No quotations found." data-lang-id="Tidak ada quotation.">No quotations found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
