@extends('admin.layouts.app')

@section('title', $automation->name.' - Automation - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="{{ $automation->name }} - Automation - Krakatau CRM" data-doc-title-id="{{ $automation->name }} - Otomasi - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'automation'])
            </div>
            <div>
                <h1 data-lang-en="Automation Detail" data-lang-id="Detail Otomasi">Automation Detail</h1>
                <p data-lang-en="View automation triggers, actions, conditions, payloads, and execution history." data-lang-id="Lihat trigger, action, conditions, payload, dan riwayat eksekusi otomasi.">Lihat trigger, action, conditions, payload, dan execution history automation.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $automation->name }}</h2>
                    <p data-lang-en="{{ number_format($automation->executed_count) }} executions" data-lang-id="{{ number_format($automation->executed_count) }} eksekusi">{{ number_format($automation->executed_count) }} executions</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge trigger-{{ $automation->trigger_type }}">{{ ucwords(str_replace('_', ' ', $automation->trigger_type)) }}</span>
                    <span class="status-badge action-{{ $automation->action_type }}">{{ ucwords(str_replace('_', ' ', $automation->action_type)) }}</span>
                    <span class="status-badge status-{{ $automation->status }}">{{ ucfirst($automation->status) }}</span>
                    <a href="{{ route('admin.marketing.automations.edit', $automation) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.automations.destroy', $automation) }}" data-confirm-en="Delete this automation?" data-confirm-id="Hapus otomasi ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this automation?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-relation-grid">
                <div class="customer-notes">
                    <h3 data-lang-en="Campaign" data-lang-id="Campaign">Campaign</h3>
                    @if ($automation->marketingCampaign)
                        <p><a href="{{ route('admin.marketing.campaigns.show', $automation->marketingCampaign) }}" class="btn btn-sm btn-muted">{{ $automation->marketingCampaign->name }}</a></p>
                    @else
                        <p>-</p>
                    @endif
                </div>

                <div class="customer-notes">
                    <h3 data-lang-en="Audience Segment" data-lang-id="Segmen Audiens">Audience Segment</h3>
                    @if ($automation->audienceSegment)
                        <p><a href="{{ route('admin.marketing.audiences.show', $automation->audienceSegment) }}" class="btn btn-sm btn-muted">{{ $automation->audienceSegment->name }}</a></p>
                    @else
                        <p>-</p>
                    @endif
                </div>
            </div>

            <div class="sales-detail-hero">
                <div><span data-lang-en="Delay" data-lang-id="Jeda">Delay</span><strong>{{ number_format($automation->delay_minutes) }} min</strong></div>
                <div><span data-lang-en="Executed" data-lang-id="Dieksekusi">Executed</span><strong>{{ number_format($automation->executed_count) }}</strong></div>
                <div><span data-lang-en="Last Executed" data-lang-id="Eksekusi Terakhir">Last Executed</span><strong>{{ $automation->last_executed_at?->format('d M Y H:i') ?: '-' }}</strong></div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Trigger" data-lang-id="Pemicu">Trigger</strong><span><span class="status-badge trigger-{{ $automation->trigger_type }}">{{ ucwords(str_replace('_', ' ', $automation->trigger_type)) }}</span></span></div>
                <div><strong data-lang-en="Action" data-lang-id="Aksi">Action</strong><span><span class="status-badge action-{{ $automation->action_type }}">{{ ucwords(str_replace('_', ' ', $automation->action_type)) }}</span></span></div>
                <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span><span class="status-badge status-{{ $automation->status }}">{{ ucfirst($automation->status) }}</span></span></div>
                <div><strong data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</strong><span>{{ $automation->created_by ?: '-' }}</span></div>
            </div>

            <div class="automation-json-grid">
                <div class="customer-notes">
                    <h3 data-lang-en="Conditions JSON" data-lang-id="JSON Kondisi">Conditions JSON</h3>
                    @if ($conditionsJson)
                        <pre class="automation-json">{{ $conditionsJson }}</pre>
                    @else
                        <p data-lang-en="No conditions available" data-lang-id="Belum ada kondisi">No conditions available</p>
                    @endif
                </div>

                <div class="customer-notes">
                    <h3 data-lang-en="Action Payload JSON" data-lang-id="JSON Payload Aksi">Action Payload JSON</h3>
                    @if ($actionPayloadJson)
                        <pre class="automation-json">{{ $actionPayloadJson }}</pre>
                    @else
                        <p data-lang-en="No action payload available" data-lang-id="Belum ada payload aksi">No action payload available</p>
                    @endif
                </div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Notes" data-lang-id="Catatan">Notes</h3>
                <p data-lang-en="{{ $automation->notes ?: 'No notes available' }}" data-lang-id="{{ $automation->notes ?: 'Belum ada catatan' }}">{{ $automation->notes ?: 'No notes available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.automations.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
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
