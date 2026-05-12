@extends('admin.layouts.app')

@section('title', $rule->name.' - Lead Scoring - Krakatau CRM')

@section('content')
    @php
        $scorePercent = min(100, max(0, (int) $rule->score_value));
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'scoring'])
            </div>
            <div>
                <h1>Lead Scoring Detail</h1>
                <p>Lihat scoring, routing destination, conditions, dan execution history rule.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $rule->name }}</h2>
                    <p>{{ number_format($rule->execution_count) }} executions</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge trigger-{{ $rule->trigger_source }}">{{ ucwords(str_replace('_', ' ', $rule->trigger_source)) }}</span>
                    <span class="status-badge priority-{{ $rule->priority }}">{{ ucfirst($rule->priority) }}</span>
                    <span class="status-badge status-{{ $rule->status }}">{{ ucfirst($rule->status) }}</span>
                    <a href="{{ route('admin.marketing.lead-scoring.edit', $rule) }}" class="btn btn-primary">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.lead-scoring.destroy', $rule) }}" onsubmit="return confirm('Delete lead scoring rule ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div><span>Score Value</span><strong>{{ number_format($rule->score_value) }}/100</strong></div>
                <div><span>Auto Assign</span><strong>{{ $rule->auto_assign ? 'Yes' : 'No' }}</strong></div>
                <div><span>Last Executed</span><strong>{{ $rule->last_executed_at?->format('d M Y H:i') ?: '-' }}</strong></div>
            </div>

            <div class="lead-score-panel">
                <div class="lead-score-head">
                    <span>Score Visualization</span>
                    <strong>{{ $scorePercent }}%</strong>
                </div>
                <div class="lead-score-track" aria-label="Score visualization">
                    <span style="width: {{ $scorePercent }}%"></span>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Trigger Source</strong><span><span class="status-badge trigger-{{ $rule->trigger_source }}">{{ ucwords(str_replace('_', ' ', $rule->trigger_source)) }}</span></span></div>
                <div><strong>Score Value</strong><span>{{ number_format($rule->score_value) }}</span></div>
                <div><strong>Routing Team</strong><span>{{ $rule->routing_team ?: '-' }}</span></div>
                <div><strong>Routing User</strong><span>{{ $rule->routing_user ?: '-' }}</span></div>
                <div><strong>Priority</strong><span><span class="status-badge priority-{{ $rule->priority }}">{{ ucfirst($rule->priority) }}</span></span></div>
                <div><strong>Status</strong><span><span class="status-badge status-{{ $rule->status }}">{{ ucfirst($rule->status) }}</span></span></div>
                <div><strong>Auto Assign</strong><span><span class="status-badge status-{{ $rule->auto_assign ? 'active' : 'inactive' }}">{{ $rule->auto_assign ? 'Yes' : 'No' }}</span></span></div>
                <div><strong>Execution Count</strong><span>{{ number_format($rule->execution_count) }}</span></div>
                <div><strong>Created By</strong><span>{{ $rule->created_by ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Conditions JSON</h3>
                @if ($conditionsJson)
                    <pre class="lead-scoring-json">{{ $conditionsJson }}</pre>
                @else
                    <p>No conditions available</p>
                @endif
            </div>

            <div class="customer-notes">
                <h3>Notes</h3>
                <p>{{ $rule->notes ?: 'No notes available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.lead-scoring.index') }}" class="btn btn-muted">Back</a>
            </div>
        </article>
    </section>

    <style>
        .lead-score-panel {
            margin: 16px 0;
            padding: 16px;
            border: 1px solid #e7e5ef;
            border-radius: 6px;
            background: #fff;
        }

        .lead-score-head {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
            color: #5d5870;
            font-weight: 700;
        }

        .lead-score-track {
            height: 10px;
            border-radius: 999px;
            background: #edeaf7;
            overflow: hidden;
        }

        .lead-score-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: #7367f0;
        }

        .lead-scoring-json {
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
    </style>
@endsection
