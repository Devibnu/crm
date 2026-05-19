@extends('admin.layouts.app')

@section('title', $execution->execution_name.' - Campaign Execution - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="{{ $execution->execution_name }} - Campaign Execution - Krakatau CRM" data-doc-title-id="{{ $execution->execution_name }} - Eksekusi Campaign - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'execution'])
            </div>
            <div>
                <h1 data-lang-en="Campaign Execution Detail" data-lang-id="Detail Eksekusi Campaign">Campaign Execution Detail</h1>
                <p data-lang-en="View the timeline, metrics, and performance rates of the campaign execution." data-lang-id="Lihat timeline, metrik, dan rasio performa eksekusi campaign.">Lihat timeline, metrics, dan performance rate eksekusi campaign.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $execution->execution_name }}</h2>
                    <p>{{ ucwords(str_replace('_', ' ', $execution->channel)) }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge channel-{{ $execution->channel }}">{{ ucwords(str_replace('_', ' ', $execution->channel)) }}</span>
                    <span class="status-badge status-{{ $execution->status }}">{{ ucfirst($execution->status) }}</span>
                    <a href="{{ route('admin.marketing.executions.edit', $execution) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.executions.destroy', $execution) }}" data-confirm-en="Delete this execution?" data-confirm-id="Hapus eksekusi ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this execution?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-relation-grid">
                <div class="customer-notes">
                    <h3 data-lang-en="Campaign" data-lang-id="Campaign">Campaign</h3>
                    @if ($execution->marketingCampaign)
                        <p><a href="{{ route('admin.marketing.campaigns.show', $execution->marketingCampaign) }}" class="btn btn-sm btn-muted">{{ $execution->marketingCampaign->name }}</a></p>
                    @else
                        <p>-</p>
                    @endif
                </div>

                <div class="customer-notes">
                    <h3 data-lang-en="Audience Segment" data-lang-id="Segmen Audiens">Audience Segment</h3>
                    @if ($execution->audienceSegment)
                        <p><a href="{{ route('admin.marketing.audiences.show', $execution->audienceSegment) }}" class="btn btn-sm btn-muted">{{ $execution->audienceSegment->name }}</a></p>
                    @else
                        <p>-</p>
                    @endif
                </div>
            </div>

            <div class="sales-detail-hero">
                <div><span data-lang-en="Sent" data-lang-id="Terkirim">Sent</span><strong>{{ number_format($execution->sent_count) }}</strong></div>
                <div><span data-lang-en="Delivered" data-lang-id="Sampai">Delivered</span><strong>{{ number_format($execution->delivered_count) }}</strong></div>
                <div><span data-lang-en="Opened" data-lang-id="Dibuka">Opened</span><strong>{{ number_format($execution->opened_count) }}</strong></div>
                <div><span data-lang-en="Clicked" data-lang-id="Diklik">Clicked</span><strong>{{ number_format($execution->clicked_count) }}</strong></div>
                <div><span data-lang-en="Responses" data-lang-id="Respons">Responses</span><strong>{{ number_format($execution->response_count) }}</strong></div>
            </div>

            <div class="campaign-rate-grid">
                @foreach ([
                    'delivered_rate' => ['en' => 'Delivered Rate', 'id' => 'Rasio Sampai'],
                    'open_rate' => ['en' => 'Open Rate', 'id' => 'Rasio Buka'],
                    'click_rate' => ['en' => 'Click Rate', 'id' => 'Rasio Klik'],
                    'response_rate' => ['en' => 'Response Rate', 'id' => 'Rasio Respons'],
                ] as $key => $label)
                    <div class="customer-notes">
                        <h3 data-lang-en="{{ $label['en'] }}" data-lang-id="{{ $label['id'] }}">{{ $label['en'] }}</h3>
                        <strong>{{ number_format($rates[$key], 2) }}%</strong>
                        <div class="campaign-rate-track"><span style="width: {{ min(100, $rates[$key]) }}%"></span></div>
                    </div>
                @endforeach
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Scheduled At" data-lang-id="Dijadwalkan Pada">Scheduled At</strong><span>{{ $execution->scheduled_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Started At" data-lang-id="Dimulai Pada">Started At</strong><span>{{ $execution->started_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Completed At" data-lang-id="Selesai Pada">Completed At</strong><span>{{ $execution->completed_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span><span class="status-badge status-{{ $execution->status }}">{{ ucfirst($execution->status) }}</span></span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Notes" data-lang-id="Catatan">Notes</h3>
                <p data-lang-en="{{ $execution->notes ?: 'No notes available' }}" data-lang-id="{{ $execution->notes ?: 'Belum ada catatan' }}">{{ $execution->notes ?: 'No notes available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.executions.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
            </div>
        </article>
    </section>

    <style>
        .campaign-rate-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 14px;
            margin-top: 16px;
        }

        .campaign-rate-grid strong {
            display: block;
            margin-bottom: 8px;
            color: #3b384c;
            font-size: 22px;
        }

        .campaign-rate-track {
            height: 8px;
            border-radius: 999px;
            background: #ece9ff;
            overflow: hidden;
        }

        .campaign-rate-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #28c76f, #7367f0);
        }

        @media (max-width: 920px) {
            .campaign-rate-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 620px) {
            .campaign-rate-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
