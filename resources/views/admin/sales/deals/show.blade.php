@extends('admin.layouts.app')

@section('title', $quotation->title.' - Deal - Krakatau CRM')

@section('content')
    @php
        $opportunity = $quotation->opportunity;
        $customer = $quotation->customer;
        $lead = $opportunity?->lead;
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
        $coreMetadataLabels = ['source', 'opportunity', 'customer'];
        $dealMetadata = collect([
            ['label' => 'Source', 'value' => $sourceLabel, 'url' => null],
            ['label' => 'Opportunity', 'value' => $opportunity?->title ?: '-', 'url' => $opportunity ? route('admin.sales.opportunities.show', $opportunity) : null],
            ['label' => 'Customer', 'value' => $customer?->name ?: '-', 'url' => $customer ? route('admin.customers.show', $customer) : null],
        ])->concat($parsedNoteMetadata->reject(fn ($item) => in_array(strtolower($item['label']), $coreMetadataLabels, true)));
    @endphp

    <section class="crm-record-page quotation-record-page">
        @if (session('success'))
            <div class="customer-alert success">{{ session('success') }}</div>
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
            </main>

            <aside class="crm-workspace-sidebar crm-related-sidebar">
                <section class="crm-workspace-section">
                    <h2>Related Records</h2>
                    <div class="crm-related-list">
                        <div><span>Opportunity</span>@if ($opportunity)<a href="{{ route('admin.sales.opportunities.show', $opportunity) }}">{{ $opportunity->title }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Customer</span>@if ($customer)<a href="{{ route('admin.customers.show', $customer) }}">{{ $customer->name }}</a>@else<strong>-</strong>@endif</div>
                        <div><span>Lead</span>@if ($lead)<a href="{{ route('admin.sales.leads.show', $lead) }}">{{ $lead->name }}</a>@else<strong>-</strong>@endif</div>
                    </div>
                </section>

                @if ($quotation->status === 'accepted' && $opportunity)
                    <section class="crm-workspace-section">
                        <h2>Outcome</h2>
                        <div class="quotation-outcome-panel">
                            <span>Accepted Quotation</span>
                            <strong>{{ $opportunity->status === 'won' ? 'Opportunity Won' : ucfirst($opportunity->status) }}</strong>
                            <small>Value Rp {{ number_format((float) $opportunity->estimated_value, 0, ',', '.') }}</small>
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </section>
@endsection
