@extends('admin.layouts.app')

@section('title', $campaign->name.' - Campaign - Krakatau CRM')

@section('content')
    @php
        $currency = fn ($value) => 'Rp '.number_format((float) $value, 2, ',', '.');
        $progressWidth = min(100, $progress);
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="{{ $campaign->name }} - Campaign - Krakatau CRM" data-doc-title-id="{{ $campaign->name }} - Campaign - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'campaign'])
            </div>
            <div>
                <h1 data-lang-en="Campaign Detail" data-lang-id="Detail Campaign">Campaign Detail</h1>
                <p data-lang-en="View the campaign channel summary, budget, leads, timeline, and ownership." data-lang-id="Lihat ringkasan channel, anggaran, lead, timeline, dan ownership campaign.">Lihat ringkasan channel, budget, leads, timeline, dan ownership campaign.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $campaign->name }}</h2>
                    <p data-lang-en="{{ $campaign->target_audience ?: 'No target audience' }}" data-lang-id="{{ $campaign->target_audience ?: 'Belum ada target audiens' }}">{{ $campaign->target_audience ?: 'No target audience' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge type-{{ $campaign->type }}">{{ ucwords(str_replace('_', ' ', $campaign->type)) }}</span>
                    <span class="status-badge status-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span>
                    <a href="{{ route('admin.marketing.campaigns.edit', $campaign) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.campaigns.destroy', $campaign) }}" data-confirm-en="Delete this campaign?" data-confirm-id="Hapus campaign ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this campaign?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Budget" data-lang-id="Anggaran">Budget</span>
                    <strong>{{ $currency($campaign->budget) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Leads" data-lang-id="Lead">Leads</span>
                    <strong>{{ number_format($campaign->actual_leads) }} / {{ number_format($campaign->expected_leads) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Progress" data-lang-id="Progres">Progress</span>
                    <strong>{{ number_format($progress, 2) }}%</strong>
                </div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Lead Progress" data-lang-id="Progres Lead">Lead Progress</h3>
                <div class="campaign-progress">
                    <span style="width: {{ $progressWidth }}%"></span>
                </div>
                <p data-lang-en="{{ number_format($campaign->actual_leads) }} actual leads from {{ number_format($campaign->expected_leads) }} expected leads." data-lang-id="{{ number_format($campaign->actual_leads) }} lead aktual dari {{ number_format($campaign->expected_leads) }} target lead.">{{ number_format($campaign->actual_leads) }} actual leads from {{ number_format($campaign->expected_leads) }} expected leads.</p>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Campaign Name" data-lang-id="Nama Campaign">Campaign Name</strong><span>{{ $campaign->name }}</span></div>
                <div><strong data-lang-en="Type" data-lang-id="Tipe">Type</strong><span><span class="status-badge type-{{ $campaign->type }}">{{ ucwords(str_replace('_', ' ', $campaign->type)) }}</span></span></div>
                <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span><span class="status-badge status-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span></span></div>
                <div><strong data-lang-en="Target Audience" data-lang-id="Target Audiens">Target Audience</strong><span>{{ $campaign->target_audience ?: '-' }}</span></div>
                <div><strong data-lang-en="Budget" data-lang-id="Anggaran">Budget</strong><span>{{ $currency($campaign->budget) }}</span></div>
                <div><strong data-lang-en="Expected vs Actual Leads" data-lang-id="Target vs Lead Aktual">Expected vs Actual Leads</strong><span>{{ number_format($campaign->expected_leads) }} / {{ number_format($campaign->actual_leads) }}</span></div>
                <div><strong data-lang-en="Start Date" data-lang-id="Tanggal Mulai">Start Date</strong><span>{{ $campaign->start_date?->format('d M Y') ?: '-' }}</span></div>
                <div><strong data-lang-en="End Date" data-lang-id="Tanggal Selesai">End Date</strong><span>{{ $campaign->end_date?->format('d M Y') ?: '-' }}</span></div>
                <div><strong data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</strong><span>{{ $campaign->created_by ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Description" data-lang-id="Deskripsi">Description</h3>
                <p data-lang-en="{{ $campaign->description ?: 'No description available' }}" data-lang-id="{{ $campaign->description ?: 'Belum ada deskripsi' }}">{{ $campaign->description ?: 'No description available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
            </div>
        </article>
    </section>

    <style>
        .campaign-progress {
            height: 10px;
            border-radius: 999px;
            background: #ece9ff;
            overflow: hidden;
            max-width: 520px;
            margin-bottom: 8px;
        }

        .campaign-progress span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #28c76f, #7367f0);
        }
    </style>
@endsection
