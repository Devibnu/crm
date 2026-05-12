@extends('admin.layouts.app')

@section('title', $automation->name.' - Automation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'automation'])
            </div>
            <div>
                <h1>Automation Detail</h1>
                <p>Lihat trigger, action, conditions, payload, dan execution history automation.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $automation->name }}</h2>
                    <p>{{ number_format($automation->executed_count) }} executions</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge trigger-{{ $automation->trigger_type }}">{{ ucwords(str_replace('_', ' ', $automation->trigger_type)) }}</span>
                    <span class="status-badge action-{{ $automation->action_type }}">{{ ucwords(str_replace('_', ' ', $automation->action_type)) }}</span>
                    <span class="status-badge status-{{ $automation->status }}">{{ ucfirst($automation->status) }}</span>
                    <a href="{{ route('admin.marketing.automations.edit', $automation) }}" class="btn btn-primary">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.automations.destroy', $automation) }}" onsubmit="return confirm('Delete automation ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-relation-grid">
                <div class="customer-notes">
                    <h3>Campaign</h3>
                    @if ($automation->marketingCampaign)
                        <p><a href="{{ route('admin.marketing.campaigns.show', $automation->marketingCampaign) }}" class="btn btn-sm btn-muted">{{ $automation->marketingCampaign->name }}</a></p>
                    @else
                        <p>-</p>
                    @endif
                </div>

                <div class="customer-notes">
                    <h3>Audience Segment</h3>
                    @if ($automation->audienceSegment)
                        <p><a href="{{ route('admin.marketing.audiences.show', $automation->audienceSegment) }}" class="btn btn-sm btn-muted">{{ $automation->audienceSegment->name }}</a></p>
                    @else
                        <p>-</p>
                    @endif
                </div>
            </div>

            <div class="sales-detail-hero">
                <div><span>Delay</span><strong>{{ number_format($automation->delay_minutes) }} min</strong></div>
                <div><span>Executed</span><strong>{{ number_format($automation->executed_count) }}</strong></div>
                <div><span>Last Executed</span><strong>{{ $automation->last_executed_at?->format('d M Y H:i') ?: '-' }}</strong></div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Trigger</strong><span><span class="status-badge trigger-{{ $automation->trigger_type }}">{{ ucwords(str_replace('_', ' ', $automation->trigger_type)) }}</span></span></div>
                <div><strong>Action</strong><span><span class="status-badge action-{{ $automation->action_type }}">{{ ucwords(str_replace('_', ' ', $automation->action_type)) }}</span></span></div>
                <div><strong>Status</strong><span><span class="status-badge status-{{ $automation->status }}">{{ ucfirst($automation->status) }}</span></span></div>
                <div><strong>Created By</strong><span>{{ $automation->created_by ?: '-' }}</span></div>
            </div>

            <div class="automation-json-grid">
                <div class="customer-notes">
                    <h3>Conditions JSON</h3>
                    @if ($conditionsJson)
                        <pre class="automation-json">{{ $conditionsJson }}</pre>
                    @else
                        <p>No conditions available</p>
                    @endif
                </div>

                <div class="customer-notes">
                    <h3>Action Payload JSON</h3>
                    @if ($actionPayloadJson)
                        <pre class="automation-json">{{ $actionPayloadJson }}</pre>
                    @else
                        <p>No action payload available</p>
                    @endif
                </div>
            </div>

            <div class="customer-notes">
                <h3>Notes</h3>
                <p>{{ $automation->notes ?: 'No notes available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.automations.index') }}" class="btn btn-muted">Back</a>
            </div>
        </article>
    </section>

    <style>
        .automation-json-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            margin-top: 16px;
        }

        .automation-json {
            margin: 0;
            padding: 14px;
            border: 1px solid #e7e5ef;
            border-radius: 6px;
            background: #f8f7fa;
            color: #3b384c;
            font-size: 13px;
            line-height: 1.6;
            overflow-x: auto;
        }

        @media (max-width: 920px) {
            .automation-json-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
