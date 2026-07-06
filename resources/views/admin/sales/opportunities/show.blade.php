@extends('admin.layouts.app')

@section('title', $opportunity->title.' - Opportunity - Krakatau CRM')

@section('content')
    @php
        $probability = min(max((int) $opportunity->probability, 0), 100);
        $activeTab = request('tab', 'overview');
        $allowedTabs = ['overview', 'activities', 'quotations', 'timeline', 'notes', 'documents'];
        $activeTab = in_array($activeTab, $allowedTabs, true) ? $activeTab : 'overview';
        $stages = [
            'open' => 'Prospecting',
            'qualified' => 'Qualified',
            'proposal' => 'Proposal',
            'negotiation' => 'Negotiation',
            'won' => 'Won',
            'lost' => 'Lost',
        ];
        $stageKeys = array_keys($stages);
        $currentStageIndex = array_search($opportunity->status, $stageKeys, true);
        $currentStageIndex = $currentStageIndex === false ? 0 : $currentStageIndex;
        $sourceConversation = $opportunity->conversation ?: ($opportunity->lead?->conversation ?: $opportunity->lead?->sourceWhatsappConversation);
        $displayQuotation = $activeQuotation ?: (($quotations ?? collect())->first());
        $relatedProject = $opportunity->project;
        $quotationCreateUrl = route('admin.sales.quotations.create', ['opportunity_id' => $opportunity->id]);
        $timelineEvents = collect()
            ->push(['at' => $opportunity->lead?->created_at, 'label' => 'Lead Created', 'description' => $opportunity->lead?->name, 'type' => 'lead'])
            ->push(['at' => $opportunity->created_at, 'label' => 'Opportunity Created', 'description' => $opportunity->title, 'type' => 'opportunity'])
            ->merge(($activities ?? collect())->map(fn ($activity) => [
                'at' => $activity->activity_at ?: $activity->created_at,
                'label' => ucwords(str_replace('_', ' ', $activity->type)).' Logged',
                'description' => $activity->subject,
                'type' => 'activity',
            ]))
            ->merge(($quotations ?? collect())->flatMap(function ($quotation) {
                return collect([
                    ['at' => $quotation->created_at, 'label' => 'Quotation Created', 'description' => $quotation->quote_number, 'type' => 'quotation'],
                    ['at' => $quotation->issued_at, 'label' => 'Quotation Sent', 'description' => $quotation->quote_number, 'type' => 'quotation'],
                    $quotation->status === 'accepted'
                        ? ['at' => $quotation->updated_at, 'label' => 'Quotation Accepted', 'description' => $quotation->quote_number, 'type' => 'won']
                        : null,
                    in_array($quotation->status, ['rejected', 'expired'], true)
                        ? ['at' => $quotation->updated_at, 'label' => 'Quotation '.ucfirst($quotation->status), 'description' => $quotation->quote_number, 'type' => 'lost']
                        : null,
                ])->filter();
            }))
            ->when(in_array($opportunity->status, ['won', 'lost'], true), fn ($events) => $events->push([
                'at' => $opportunity->updated_at,
                'label' => 'Opportunity '.ucfirst($opportunity->status),
                'description' => $statusLabels[$opportunity->status] ?? ucfirst($opportunity->status),
                'type' => $opportunity->status,
            ]))
            ->filter(fn ($event) => filled($event['at']))
            ->sortByDesc('at')
            ->values();
    @endphp

    <section class="crm-record-page opportunity-record-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-detail-banner opportunity-detail-banner">
            <div class="crm-record-heading">
                <span class="crm-record-kicker">Sales Workspace</span>
                <div class="crm-record-title-row">
                    <h1>{{ $opportunity->title }}</h1>
                    <span class="status-badge opportunity-banner-stage status-{{ $opportunity->status }}">{{ $statusLabels[$opportunity->status] ?? ucfirst($opportunity->status) }}</span>
                </div>
                <p>{{ $opportunity->company_name ?: 'No company' }} · {{ $opportunity->contact_name ?: 'No contact' }}</p>
            </div>
            <div class="lead-detail-action-stack">
                <div class="crm-record-actions">
                    @if ($displayQuotation)
                        <a href="{{ route('admin.sales.deals.show', $displayQuotation) }}" class="btn btn-sm lead-banner-cta">Open Quotation</a>
                    @else
                        <a href="{{ $quotationCreateUrl }}" class="btn btn-sm lead-banner-cta">Create Quotation</a>
                    @endif
                    <a href="{{ route('admin.sales.opportunities.edit', $opportunity) }}" class="btn btn-sm lead-banner-cta">Edit</a>
                </div>
                <a href="{{ route('admin.sales.opportunities') }}" class="lead-detail-back lead-detail-back-secondary">Back to Opportunity Management</a>
            </div>
        </header>

        <div class="crm-metadata-row opportunity-detail-metadata">
            <div><span>Value</span><strong>Rp {{ number_format((float) $opportunity->estimated_value, 2, ',', '.') }}</strong></div>
            <div><span>Progress</span><strong>{{ $probability }}%</strong></div>
            <div><span>Expected Close</span><strong>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Owner</span><strong>{{ $opportunity->assigned_to ?: '-' }}</strong></div>
        </div>

        <div class="crm-stage-rail opportunity-stage-rail" aria-label="Opportunity stage">
            @foreach ($stages as $stageKey => $stageLabel)
                @php
                    $stageIndex = array_search($stageKey, $stageKeys, true);
                    $stageState = $stageIndex < $currentStageIndex ? 'done' : ($stageIndex === $currentStageIndex ? 'current' : 'upcoming');
                @endphp
                <div class="crm-stage-node {{ $stageState }}">
                    <span></span>
                    <strong>{{ $stageLabel }}</strong>
                </div>
            @endforeach
        </div>

        <div class="crm-record-workspace opportunity-detail-workspace">
            <aside class="crm-workspace-sidebar crm-details-sidebar">
                <section class="crm-workspace-section">
                    <h2>Details</h2>
                    <dl class="crm-property-list">
                        <div><dt>Company</dt><dd>{{ $opportunity->company_name ?: '-' }}</dd></div>
                        <div><dt>Contact</dt><dd>{{ $opportunity->contact_name ?: '-' }}</dd></div>
                        <div><dt>Current Stage</dt><dd>{{ $statusLabels[$opportunity->status] ?? ucfirst($opportunity->status) }}</dd></div>
                        <div><dt>Probability</dt><dd>{{ $probability }}%</dd></div>
                        <div><dt>Estimated Value</dt><dd>Rp {{ number_format((float) $opportunity->estimated_value, 2, ',', '.') }}</dd></div>
                        <div><dt>Expected Close</dt><dd>{{ $opportunity->expected_close_date?->format('d M Y') ?: '-' }}</dd></div>
                        <div><dt>Assigned To</dt><dd>{{ $opportunity->assigned_to ?: '-' }}</dd></div>
                        <div><dt>Created At</dt><dd>{{ $opportunity->created_at?->format('d M Y H:i') }}</dd></div>
                    </dl>
                </section>
            </aside>

            <main class="crm-workspace-main">
                <nav class="crm-record-tabs" aria-label="Opportunity detail sections">
                    @foreach (['overview' => 'Overview', 'activities' => 'Activities', 'quotations' => 'Quotations', 'timeline' => 'Timeline', 'notes' => 'Notes', 'documents' => 'Documents'] as $tabKey => $tabLabel)
                        <a href="{{ route('admin.sales.opportunities.show', ['opportunity' => $opportunity, 'tab' => $tabKey]) }}" @class(['active' => $activeTab === $tabKey])>{{ $tabLabel }}</a>
                    @endforeach
                </nav>

                @if ($activeTab === 'overview')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading">
                            <div><h2>Recent Activities</h2><p>Aktivitas terbaru pada opportunity ini.</p></div>
                            <a href="{{ route('admin.sales.opportunities.show', ['opportunity' => $opportunity, 'tab' => 'activities']) }}" class="btn btn-sm btn-muted">View All</a>
                        </div>
                        <div class="crm-activity-feed compact">
                            @forelse ($recentActivities as $activity)
                                <article class="crm-activity-entry">
                                    <span class="crm-feed-marker activity-{{ $activity->type }}"></span>
                                    <div class="crm-feed-body">
                                        <div class="crm-feed-title"><strong>{{ $activity->subject }}</strong><span>{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></div>
                                        <p>{{ $activity->description ?: 'No description' }}</p>
                                        <small>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }} · {{ $activity->assigned_to ?: 'Unassigned' }}</small>
                                    </div>
                                </article>
                            @empty
                                <div class="crm-workspace-empty">No recent activities.</div>
                            @endforelse
                        </div>

                        <div class="crm-content-heading crm-section-divider">
                            <div><h2>Recent Quotations</h2><p>Quotation terbaru pada opportunity ini.</p></div>
                            @if ($displayQuotation)
                                <a href="{{ route('admin.sales.deals.show', $displayQuotation) }}" class="btn btn-sm btn-primary">Open Quotation</a>
                            @else
                                <a href="{{ $quotationCreateUrl }}" class="btn btn-sm btn-primary">Create Quotation</a>
                            @endif
                        </div>
                        <div class="crm-quotation-grid compact">
                            @forelse ($recentQuotations as $quotation)
                                <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="crm-quotation-card">
                                    <div><span class="sales-code">{{ $quotation->quote_number }}</span><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></div>
                                    <strong>{{ $quotation->title }}</strong>
                                    <b>Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</b>
                                    <small>Valid until {{ $quotation->valid_until?->format('d M Y') ?: '-' }}</small>
                                </a>
                            @empty
                                <div class="crm-workspace-empty">No recent quotations.</div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'activities')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading">
                            <div><h2>Activities</h2><p>Chronological sales activity for this opportunity.</p></div>
                            <a href="{{ route('admin.sales.activities.create', ['related_type' => 'opportunity', 'related_id' => $opportunity->id]) }}" class="btn btn-sm btn-primary">Add Activity</a>
                        </div>
                        <div class="crm-activity-feed">
                            @forelse ($activities as $activity)
                                <article class="crm-activity-entry">
                                    <span class="crm-feed-marker activity-{{ $activity->type }}"></span>
                                    <div class="crm-feed-body">
                                        <div class="crm-feed-title"><a href="{{ route('admin.sales.activities.show', $activity) }}">{{ $activity->subject }}</a><span>{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></div>
                                        <p>{{ $activity->description ?: 'No description' }}</p>
                                        <div class="crm-feed-meta"><small>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }}</small><small>{{ $activity->assigned_to ?: 'Unassigned' }}</small>@if ($activity->outcome)<small>Outcome: {{ $activity->outcome }}</small>@endif</div>
                                    </div>
                                </article>
                            @empty
                                <div class="crm-workspace-empty">No activities for this opportunity.</div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'quotations')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading">
                            <div><h2>Quotations</h2><p>All quotations connected to this opportunity.</p></div>
                            @if ($displayQuotation)
                                <a href="{{ route('admin.sales.deals.show', $displayQuotation) }}" class="btn btn-sm btn-primary">Open Quotation</a>
                            @else
                                <a href="{{ $quotationCreateUrl }}" class="btn btn-sm btn-primary">Create Quotation</a>
                            @endif
                        </div>
                        <div class="crm-quotation-grid">
                            @forelse ($quotations as $quotation)
                                <a href="{{ route('admin.sales.deals.show', $quotation) }}" class="crm-quotation-card">
                                    <div><span class="sales-code">{{ $quotation->quote_number }}</span><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></div>
                                    <strong>{{ $quotation->title }}</strong>
                                    <b>Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</b>
                                    <dl><div><dt>Issued</dt><dd>{{ $quotation->issued_at?->format('d M Y') ?: '-' }}</dd></div><div><dt>Valid Until</dt><dd>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</dd></div></dl>
                                </a>
                            @empty
                                <div class="crm-workspace-empty">No quotations for this opportunity.</div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'timeline')
                    <section class="crm-tab-content">
                        <div class="crm-content-heading"><div><h2>Timeline</h2><p>Recorded opportunity history.</p></div></div>
                        <div class="crm-activity-feed">
                            @forelse ($timelineEvents as $event)
                                <article class="crm-activity-entry">
                                    <span class="crm-feed-marker {{ $event['type'] }}"></span>
                                    <div class="crm-feed-body"><div class="crm-feed-title"><strong>{{ $event['label'] }}</strong></div><p>{{ $event['description'] ?: '-' }}</p><small>{{ $event['at']?->format('d M Y H:i') }}</small></div>
                                </article>
                            @empty
                                <div class="crm-workspace-empty">No timeline events available.</div>
                            @endforelse
                        </div>
                    </section>
                @endif

                @if ($activeTab === 'notes')
                    <section class="crm-tab-content"><div class="crm-content-heading"><div><h2>Notes</h2><p>Internal sales notes.</p></div></div><div class="crm-notes-content">{{ $opportunity->notes ?: 'No notes available' }}</div></section>
                @endif

                @if ($activeTab === 'documents')
                    <section class="crm-tab-content"><div class="crm-content-heading"><div><h2>Documents</h2><p>Proposal, contract, and attachment context.</p></div></div><div class="crm-workspace-empty">No documents available.</div></section>
                @endif
            </main>

            <aside class="crm-workspace-sidebar crm-related-sidebar">
                <section class="crm-workspace-section">
                    <h2>Related Records</h2>
                    <div class="crm-related-list">
                        <div><span>Source Lead</span>@if ($opportunity->lead)<a href="{{ route('admin.sales.leads.show', $opportunity->lead) }}">Open Lead</a>@else<strong>-</strong>@endif</div>
                        <div><span>Customer</span>@if ($opportunity->customer)<a href="{{ route('admin.customers.show', $opportunity->customer) }}">{{ $opportunity->customer->name }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Active Quotation</span>@if ($activeQuotation)<a href="{{ route('admin.sales.deals.show', $activeQuotation) }}">{{ $activeQuotation->quote_number }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Related Project</span>@if ($relatedProject)<a href="{{ route('admin.projects.show', $relatedProject) }}">Open Project</a>@else<strong>-</strong>@endif</div>
                        <div><span>Source Conversation</span>@if ($sourceConversation)<a href="{{ route('admin.service.omnichannel.index', ['conversation' => $sourceConversation->id]) }}#contact">Open Conversation</a>@else<strong>-</strong>@endif</div>
                    </div>
                </section>
                <section class="crm-workspace-section">
                    <h2>Related Quotations</h2>
                    <div class="crm-related-list">
                        @forelse ($quotations as $quotation)
                            <div>
                                <span>{{ ucfirst($quotation->status) }}</span>
                                <a href="{{ route('admin.sales.deals.show', $quotation) }}">{{ $quotation->quote_number }}</a>
                            </div>
                        @empty
                            <div><span>Quotation</span><strong>-</strong></div>
                        @endforelse
                    </div>
                </section>
                <section class="crm-workspace-section">
                    <h2>Latest Activity</h2>
                    @if ($recentActivities->first())
                        <div class="crm-related-highlight"><strong>{{ $recentActivities->first()->subject }}</strong><span>{{ ucwords(str_replace('_', ' ', $recentActivities->first()->type)) }}</span><small>{{ $recentActivities->first()->activity_at?->format('d M Y H:i') ?: '-' }}</small></div>
                    @else
                        <div class="crm-workspace-empty compact">No activity.</div>
                    @endif
                </section>
            </aside>
        </div>
    </section>
@endsection
