@extends('admin.layouts.app')

@section('title', $rule->name.' - Lead Scoring - Krakatau CRM')

@section('content')
    @php
        $scorePercent = min(100, max(0, (int) $rule->score_value));
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="{{ $rule->name }} - Lead Scoring - Krakatau CRM" data-doc-title-id="{{ $rule->name }} - Lead Scoring - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'scoring'])
            </div>
            <div>
                <h1 data-lang-en="Lead Scoring Detail" data-lang-id="Detail Lead Scoring">Lead Scoring Detail</h1>
                <p data-lang-en="View the scoring, routing destination, conditions, and rule execution history." data-lang-id="Lihat scoring, tujuan routing, kondisi, dan riwayat eksekusi aturan.">Lihat scoring, routing destination, conditions, dan execution history rule.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $rule->name }}</h2>
                    <p data-lang-en="{{ number_format($rule->execution_count) }} executions" data-lang-id="{{ number_format($rule->execution_count) }} eksekusi">{{ number_format($rule->execution_count) }} executions</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge trigger-{{ $rule->trigger_source }}">{{ ucwords(str_replace('_', ' ', $rule->trigger_source)) }}</span>
                    <span class="status-badge priority-{{ $rule->priority }}">{{ ucfirst($rule->priority) }}</span>
                    <span class="status-badge status-{{ $rule->status }}">{{ ucfirst($rule->status) }}</span>
                    <a href="{{ route('admin.marketing.lead-scoring.edit', $rule) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.lead-scoring.destroy', $rule) }}" data-confirm-en="Delete this lead scoring rule?" data-confirm-id="Hapus aturan lead scoring ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this lead scoring rule?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div><span data-lang-en="Score Value" data-lang-id="Nilai Skor">Score Value</span><strong>{{ number_format($rule->score_value) }}/100</strong></div>
                <div><span data-lang-en="Auto Assign" data-lang-id="Auto Assign">Auto Assign</span><strong data-lang-en="{{ $rule->auto_assign ? 'Yes' : 'No' }}" data-lang-id="{{ $rule->auto_assign ? 'Ya' : 'Tidak' }}">{{ $rule->auto_assign ? 'Yes' : 'No' }}</strong></div>
                <div><span data-lang-en="Last Executed" data-lang-id="Eksekusi Terakhir">Last Executed</span><strong>{{ $rule->last_executed_at?->format('d M Y H:i') ?: '-' }}</strong></div>
            </div>

            <div class="lead-score-panel">
                <div class="lead-score-head">
                    <span data-lang-en="Score Visualization" data-lang-id="Visualisasi Skor">Score Visualization</span>
                    <strong>{{ $scorePercent }}%</strong>
                </div>
                <div class="lead-score-track" aria-label="Score visualization" data-title-en="Score visualization" data-title-id="Visualisasi skor">
                    <span style="width: {{ $scorePercent }}%"></span>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Trigger Source" data-lang-id="Sumber Trigger">Trigger Source</strong><span><span class="status-badge trigger-{{ $rule->trigger_source }}">{{ ucwords(str_replace('_', ' ', $rule->trigger_source)) }}</span></span></div>
                <div><strong data-lang-en="Score Value" data-lang-id="Nilai Skor">Score Value</strong><span>{{ number_format($rule->score_value) }}</span></div>
                <div><strong data-lang-en="Routing Team" data-lang-id="Tim Routing">Routing Team</strong><span>{{ $rule->routing_team ?: '-' }}</span></div>
                <div><strong data-lang-en="Routing User" data-lang-id="User Routing">Routing User</strong><span>{{ $rule->routing_user ?: '-' }}</span></div>
                <div><strong data-lang-en="Priority" data-lang-id="Prioritas">Priority</strong><span><span class="status-badge priority-{{ $rule->priority }}">{{ ucfirst($rule->priority) }}</span></span></div>
                <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span><span class="status-badge status-{{ $rule->status }}">{{ ucfirst($rule->status) }}</span></span></div>
                <div><strong data-lang-en="Auto Assign" data-lang-id="Auto Assign">Auto Assign</strong><span><span class="status-badge status-{{ $rule->auto_assign ? 'active' : 'inactive' }}" data-lang-en="{{ $rule->auto_assign ? 'Yes' : 'No' }}" data-lang-id="{{ $rule->auto_assign ? 'Ya' : 'Tidak' }}">{{ $rule->auto_assign ? 'Yes' : 'No' }}</span></span></div>
                <div><strong data-lang-en="Execution Count" data-lang-id="Jumlah Eksekusi">Execution Count</strong><span>{{ number_format($rule->execution_count) }}</span></div>
                <div><strong data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</strong><span>{{ $rule->created_by ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Conditions JSON" data-lang-id="JSON Kondisi">Conditions JSON</h3>
                @if ($conditionsJson)
                    <pre class="lead-scoring-json">{{ $conditionsJson }}</pre>
                @else
                    <p data-lang-en="No conditions available" data-lang-id="Belum ada kondisi">No conditions available</p>
                @endif
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Notes" data-lang-id="Catatan">Notes</h3>
                <p data-lang-en="{{ $rule->notes ?: 'No notes available' }}" data-lang-id="{{ $rule->notes ?: 'Belum ada catatan' }}">{{ $rule->notes ?: 'No notes available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.lead-scoring.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
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
