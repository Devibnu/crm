@extends('admin.layouts.app')

@section('title', $quotation->title.' - Deal - Krakatau CRM')

@section('content')
    @php
        $opportunity = $quotation->opportunity;
        $customer = $quotation->customer;
        $lead = $quotation->lead ?: $opportunity?->lead;
        $sourceConversation = $quotation->conversation
            ?: ($opportunity?->conversation
                ?: ($lead?->conversation ?: $lead?->sourceWhatsappConversation));
        $sourceLabel = $opportunity ? 'Opportunity' : ($customer ? 'Customer' : 'Direct Quotation');
        $backUrl = $opportunity
            ? route('admin.sales.opportunities.show', $opportunity)
            : route('admin.sales.opportunities');
        $subtitleParts = collect([
            $quotation->quote_number,
            $opportunity?->title ? 'Opportunity: '.$opportunity->title : null,
            $customer?->name ? 'Customer: '.$customer->name : null,
            'Rp '.number_format((float) $quotation->amount, 0, ',', '.'),
        ])->filter();
        $parsedNoteMetadata = collect(preg_split('/\R/u', trim((string) $quotation->notes)) ?: [])
            ->filter(fn ($line) => filled(trim($line)))
            ->map(function ($line) {
                $parts = explode(':', $line, 2);

                return [
                    'label' => count($parts) === 2 ? trim($parts[0]) : 'Notes',
                    'value' => trim(count($parts) === 2 ? $parts[1] : $line) ?: '-',
                    'url' => null,
                ];
            });
        $coreMetadataLabels = ['source', 'opportunity', 'customer', 'lead', 'owner'];
        $dealMetadata = collect([
            ['label' => 'Source', 'value' => $sourceLabel, 'url' => null],
            ['label' => 'Opportunity', 'value' => $opportunity?->title ?: '-', 'url' => $opportunity ? route('admin.sales.opportunities.show', $opportunity) : null],
            ['label' => 'Lead', 'value' => $lead?->name ?: '-', 'url' => $lead ? route('admin.sales.leads.show', $lead) : null],
            ['label' => 'Customer', 'value' => $customer?->name ?: '-', 'url' => $customer ? route('admin.customers.show', $customer) : null],
        ])->concat($parsedNoteMetadata->reject(fn ($item) => in_array(strtolower($item['label']), $coreMetadataLabels, true)));
        $timelineEvents = collect([
            $sourceConversation ? [
                'label' => 'Conversation',
                'title' => 'Conversation Started',
                'description' => trim(($sourceConversation->contact_name ?: 'WhatsApp Contact').' '.$sourceConversation->phone_number),
                'date' => $sourceConversation->created_at,
            ] : null,
            $lead ? [
                'label' => 'Lead',
                'title' => 'Lead Created',
                'description' => $lead->name,
                'date' => $lead->created_at,
            ] : null,
            $opportunity ? [
                'label' => 'Opportunity',
                'title' => 'Opportunity Created',
                'description' => $opportunity->title,
                'date' => $opportunity->created_at,
            ] : null,
            [
                'label' => 'Quotation',
                'title' => 'Quotation Created',
                'description' => $quotation->quote_number,
                'date' => $quotation->created_at,
            ],
            ($quotation->status === 'accepted' || $opportunity?->status === 'won') ? [
                'label' => 'Won',
                'title' => 'Deal Won',
                'description' => 'Quotation accepted and opportunity marked as won.',
                'date' => $opportunity?->won_at ?: $quotation->updated_at,
            ] : null,
            ($quotation->status === 'rejected' || $opportunity?->status === 'lost') ? [
                'label' => 'Lost',
                'title' => 'Deal Lost',
                'description' => $opportunity?->lost_reason ? 'Reason: '.$opportunity->lost_reason : 'Quotation rejected.',
                'date' => $opportunity?->lost_at ?: $quotation->updated_at,
            ] : null,
        ])->filter()->sortBy(fn ($event) => $event['date']?->timestamp ?? 0)->values();
        $isWon = $quotation->status === 'accepted' && $opportunity?->status === 'won';
        $isLost = $quotation->status === 'rejected' && $opportunity?->status === 'lost';
    @endphp

    <section class="crm-record-page quotation-record-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="customer-alert error">{{ $errors->first() }}</div>
        @endif

        <header class="lead-detail-banner quotation-detail-banner">
            <div class="crm-record-heading">
                <a href="{{ $backUrl }}" class="lead-detail-back quotation-back-link">{{ $opportunity ? 'Back to Opportunity' : 'Opportunity Management' }}</a>
                <span class="crm-record-kicker">Sales Workspace</span>
                <div class="crm-record-title-row">
                    <h1>{{ $quotation->title }}</h1>
                    <span class="status-badge quotation-banner-status status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span>
                </div>
                <p>{{ $subtitleParts->implode(' · ') }}</p>
            </div>
            <div class="crm-record-actions quotation-banner-actions">
                @if (! $isWon)
                    <form method="POST" action="{{ route('admin.sales.deals.mark-won', $quotation) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm lead-banner-cta">Mark as Won</button>
                    </form>
                @endif
                @if ($isWon)
                    <a href="#related-project" class="btn btn-sm lead-banner-cta">Create Project</a>
                @endif
                <a href="{{ route('admin.sales.deals.edit', $quotation) }}" class="btn btn-sm lead-banner-cta">Edit</a>
                <form method="POST" action="{{ route('admin.sales.deals.destroy', $quotation) }}" onsubmit="return confirm('Delete quotation ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm quotation-banner-delete">Delete</button>
                </form>
            </div>
        </header>

        <div class="crm-metadata-row quotation-metadata-row">
            <div><span>Amount</span><strong>Rp {{ number_format((float) $quotation->amount, 0, ',', '.') }}</strong></div>
            <div><span>Status</span><strong><span class="status-badge status-{{ $quotation->status }}">{{ ucfirst($quotation->status) }}</span></strong></div>
            <div><span>Issued At</span><strong>{{ $quotation->issued_at?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Valid Until</span><strong>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</strong></div>
            <div><span>Quote Number</span><strong>{{ $quotation->quote_number }}</strong></div>
            <div><span>Opportunity</span><strong>{{ $opportunity?->title ?: '-' }}</strong></div>
        </div>

        <div class="crm-record-workspace quotation-detail-workspace">
            <aside class="crm-workspace-sidebar crm-details-sidebar">
                <section class="crm-workspace-section">
                    <h2>Quote Details</h2>
                    <dl class="crm-property-list">
                        <div><dt>Quote Number</dt><dd>{{ $quotation->quote_number }}</dd></div>
                        <div><dt>Amount</dt><dd>Rp {{ number_format((float) $quotation->amount, 2, ',', '.') }}</dd></div>
                        <div><dt>Status</dt><dd>{{ ucfirst($quotation->status) }}</dd></div>
                        <div><dt>Issued At</dt><dd>{{ $quotation->issued_at?->format('d M Y') ?: '-' }}</dd></div>
                        <div><dt>Valid Until</dt><dd>{{ $quotation->valid_until?->format('d M Y') ?: '-' }}</dd></div>
                    </dl>
                </section>
            </aside>

            <main class="crm-workspace-main quotation-context-workspace">
                <section class="crm-tab-content">
                    <div class="crm-content-heading">
                        <div><h2>Deal Metadata</h2><p>Commercial context supporting this quotation.</p></div>
                    </div>

                    <div class="deal-meta-grid">
                        @foreach ($dealMetadata as $metadata)
                            <div class="deal-meta-label">{{ $metadata['label'] }}</div>
                            <div class="deal-meta-value">
                                @if ($metadata['url'])
                                    <a href="{{ $metadata['url'] }}">{{ $metadata['value'] }}</a>
                                @else
                                    {{ $metadata['value'] }}
                                @endif
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="crm-tab-content">
                    <div class="crm-content-heading">
                        <div><h2>CRM Timeline</h2><p>Conversation → Lead → Opportunity → Quotation → Won/Lost.</p></div>
                    </div>

                    <div class="crm-activity-list">
                        @foreach ($timelineEvents as $event)
                            <article class="crm-activity-item">
                                <div>
                                    <span class="status-badge status-{{ strtolower(str_replace(' ', '-', $event['label'])) }}">{{ $event['label'] }}</span>
                                    <strong>{{ $event['title'] }}</strong>
                                    <p>{{ $event['description'] ?: '-' }}</p>
                                </div>
                                <time>{{ $event['date']?->format('d M Y H:i') ?: '-' }}</time>
                            </article>
                        @endforeach
                    </div>
                </section>
            </main>

            <aside class="crm-workspace-sidebar crm-related-sidebar">
                <section class="crm-workspace-section">
                    <h2>Related Records</h2>
                    <div class="crm-related-list">
                        <div><span>Source Opportunity</span>@if ($opportunity)<a href="{{ route('admin.sales.opportunities.show', $opportunity) }}">Open Opportunity</a>@else<strong>-</strong>@endif</div>
                        <div><span>Source Lead</span>@if ($lead)<a href="{{ route('admin.sales.leads.show', $lead) }}">Open Lead</a>@else<strong>-</strong>@endif</div>
                        <div><span>Source Conversation</span>@if ($sourceConversation)<a href="{{ route('admin.service.omnichannel.index', ['conversation' => $sourceConversation->id]) }}#contact">Open Conversation</a>@else<strong>-</strong>@endif</div>
                        <div><span>Customer</span>@if ($customer)<a href="{{ route('admin.customers.show', $customer) }}">{{ $customer->name }}</a>@else<strong>-</strong>@endif</div>
                    </div>
                </section>

                <section class="crm-workspace-section">
                    <h2>Outcome</h2>
                    <div class="quotation-outcome-panel">
                        <span>{{ $isWon ? 'Accepted Quotation' : ($isLost ? 'Rejected Quotation' : 'Deal Outcome') }}</span>
                        <strong>{{ $opportunity ? ucfirst($opportunity->status) : ucfirst($quotation->status) }}</strong>
                        @if ($opportunity)
                            <small>Value Rp {{ number_format((float) $opportunity->estimated_value, 0, ',', '.') }}</small>
                        @endif
                        @if ($opportunity?->lost_reason)
                            <small>Lost Reason: {{ $opportunity->lost_reason }}</small>
                        @endif
                    </div>

                    @if (! $isLost)
                        <form method="POST" action="{{ route('admin.sales.deals.mark-lost', $quotation) }}" class="quotation-outcome-form">
                            @csrf
                            <label for="lost_reason">Lost Reason</label>
                            <select id="lost_reason" name="lost_reason" required>
                                <option value="">Select reason</option>
                                @foreach ($lostReasonOptions as $reason)
                                    <option value="{{ $reason }}" @selected(old('lost_reason') === $reason)>{{ $reason }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm quotation-banner-delete">Mark as Lost</button>
                        </form>
                    @endif
                </section>

                <section id="related-project" class="crm-workspace-section">
                    <h2>Related Project</h2>
                    @if ($isWon)
                        <div class="quotation-outcome-panel">
                            <span>Project Placeholder</span>
                            <strong>No project linked yet.</strong>
                            <small>Create Project action is ready for the next project module.</small>
                        </div>
                    @else
                        <div class="quotation-outcome-panel">
                            <span>Project Placeholder</span>
                            <strong>Available after Won</strong>
                            <small>Project linkage will appear after this quotation is won.</small>
                        </div>
                    @endif
                </section>
            </aside>
        </div>
    </section>
@endsection
