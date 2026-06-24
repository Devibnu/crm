@extends('admin.layouts.app')

@section('title', $lead->name.' - Lead - Krakatau CRM')

@section('content')
    @php
        $leadAttributes = $lead->getAttributes();
        $hasLeadScore = array_key_exists('lead_score', $leadAttributes);
        $hasLeadTemperature = array_key_exists('lead_temperature', $leadAttributes) && filled($leadAttributes['lead_temperature']);
        $hasScoreBreakdown = array_key_exists('lead_score_breakdown', $leadAttributes) && filled($lead->lead_score_breakdown);
        $sourceCampaign = array_key_exists('source_campaign', $leadAttributes) ? $lead->source_campaign : null;
        $sourceConversation = $lead->conversation ?: $lead->sourceWhatsappConversation;
        $leadSource = $lead->lead_source ?: $lead->source;
        $contactSummary = $lead->company_name ?: 'No company';
        $contactSummary .= ' / '.($lead->phone ?: $lead->whatsapp ?: $lead->email ?: 'No contact information');
        $contactSummary .= ' / '.($leadSource ?: 'No source');
    @endphp

    <section class="crm-record-page lead-record-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif

        <header class="lead-detail-banner">
            <div class="crm-record-heading">
                <span class="crm-record-kicker">Sales Workspace</span>
                <h1>{{ $lead->name }}</h1>
                <p>{{ $contactSummary }}</p>
            </div>
            <div class="lead-detail-action-stack">
                <div class="crm-record-actions">
                    @if ($activeOpportunity)
                        <a href="{{ route('admin.sales.opportunities.show', $activeOpportunity) }}" class="btn btn-sm lead-banner-cta">Open Opportunity</a>
                    @else
                        <form method="POST" action="{{ route('admin.sales.leads.convert-to-opportunity', $lead) }}">@csrf<button type="submit" class="btn btn-sm lead-banner-cta">Convert To Opportunity</button></form>
                    @endif
                    <a href="{{ route('admin.sales.leads.edit', $lead) }}" class="btn btn-sm lead-banner-secondary">Edit</a>
                </div>
                <a href="{{ route('admin.sales.leads') }}" class="lead-detail-back lead-detail-back-secondary">Back to Lead Management</a>
            </div>
        </header>

        <div class="crm-metadata-row lead-detail-metadata">
            <div><span>Owner</span><strong>{{ $lead->assigned_to ?: '-' }}</strong></div>
            <div><span>Source</span><strong>{{ $leadSource ?: '-' }}</strong></div>
            <div><span>Campaign</span><strong>{{ $sourceCampaign ?: '-' }}</strong></div>
            <div><span>Updated At</span><strong>{{ $lead->updated_at?->format('d M Y H:i') }}</strong></div>
            <div><span>Status</span><strong><span class="status-badge status-{{ $lead->status }}">{{ ucfirst($lead->status) }}</span></strong></div>
            <div><span>Priority</span><strong><span class="status-badge priority-{{ $lead->priority }}">{{ ucfirst($lead->priority) }}</span></strong></div>
            @if ($hasLeadScore || $hasLeadTemperature)
                <div>
                    <span>Score / Temperature</span>
                    <strong class="lead-metadata-badges">
                        @if ($hasLeadScore)<span class="status-badge lead-score-badge">Score {{ (int) $lead->lead_score }}</span>@endif
                        @if ($hasLeadTemperature)<span class="status-badge lead-temperature-{{ $lead->lead_temperature }}">{{ ucfirst($lead->lead_temperature) }}</span>@endif
                    </strong>
                </div>
            @endif
        </div>

        <div class="crm-record-workspace lead-workspace">
            <aside class="crm-workspace-sidebar crm-details-sidebar">
                <section class="crm-workspace-section">
                    <h2>Contact Details</h2>
                    <dl class="crm-property-list">
                        <div><dt>Email</dt><dd>{{ $lead->email ?: '-' }}</dd></div>
                        <div><dt>Phone</dt><dd>{{ $lead->phone ?: '-' }}</dd></div>
                        <div><dt>WhatsApp</dt><dd>{{ $lead->whatsapp ?: '-' }}</dd></div>
                        <div><dt>Company</dt><dd>{{ $lead->company_name ?: '-' }}</dd></div>
                        <div><dt>Assigned To</dt><dd>{{ $lead->assigned_to ?: '-' }}</dd></div>
                        <div><dt>Created At</dt><dd>{{ $lead->created_at?->format('d M Y H:i') }}</dd></div>
                    </dl>
                </section>

                @if ($hasLeadScore || $hasScoreBreakdown)
                    <section class="crm-workspace-section">
                        <h2>Score Breakdown</h2>
                        @if ($hasScoreBreakdown)
                            <div class="crm-score-list">
                                @foreach ($lead->lead_score_breakdown as $item)
                                    <div><span>{{ $item['label'] ?? '-' }}</span><strong>+{{ (int) ($item['points'] ?? 0) }}</strong></div>
                                @endforeach
                            </div>
                        @else
                            <div class="crm-workspace-empty compact">No score activity yet.</div>
                        @endif
                    </section>
                @endif
            </aside>

            <main class="crm-workspace-main lead-activity-workspace">
                <section class="crm-tab-content">
                    <div class="crm-content-heading">
                        <div><h2>Recent Activities</h2><p>Chronological activity for this lead.</p></div>
                        <a href="{{ route('admin.sales.activities.create', ['related_type' => 'lead', 'related_id' => $lead->id]) }}" class="btn btn-sm btn-primary">Add Activity</a>
                    </div>
                    <div class="crm-activity-feed">
                        @forelse ($recentActivities as $activity)
                            <article class="crm-activity-entry">
                                <span class="crm-feed-marker activity-{{ $activity->type }}"></span>
                                <div class="crm-feed-body">
                                    <div class="crm-feed-title"><a href="{{ route('admin.sales.activities.show', $activity) }}">{{ $activity->subject }}</a><span>{{ ucwords(str_replace('_', ' ', $activity->type)) }}</span></div>
                                    <p>{{ $activity->description ?: 'No description' }}</p>
                                    <small>{{ $activity->activity_at?->format('d M Y H:i') ?: '-' }} · {{ $activity->assigned_to ?: 'Unassigned' }}</small>
                                </div>
                            </article>
                        @empty
                            <div class="crm-workspace-empty">No recent activities.</div>
                        @endforelse
                    </div>
                </section>
            </main>

            <aside class="crm-workspace-sidebar crm-related-sidebar">
                <section class="crm-workspace-section">
                    <h2>Related Records</h2>
                    <div class="crm-related-list">
                        <div><span>Customer</span>@if ($lead->customer)<a href="{{ route('admin.customers.show', $lead->customer) }}">{{ $lead->customer->name }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Opportunity</span>@if ($activeOpportunity)<a href="{{ route('admin.sales.opportunities.show', $activeOpportunity) }}">{{ $activeOpportunity->title }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Source</span><strong>{{ $leadSource ?: '-' }}</strong></div>
                        <div><span>Source Campaign</span><strong>{{ $sourceCampaign ?: '-' }}</strong></div>
                    </div>
                </section>

                @if ($sourceConversation)
                    <section class="crm-workspace-section">
                        <h2>Source Conversation</h2>
                        <a href="{{ route('admin.service.omnichannel.index', ['conversation' => $sourceConversation->id]) }}#contact" class="crm-related-record-link">
                            <strong>{{ $sourceConversation->contact_name ?: $sourceConversation->phone_number }}</strong>
                            <span>Open Conversation</span>
                        </a>
                    </section>
                @endif

                <section class="crm-workspace-section">
                    <h2>Notes</h2>
                    <div class="crm-notes-content compact">{{ $lead->notes ?: 'No notes available' }}</div>
                </section>
            </aside>
        </div>
    </section>
@endsection
