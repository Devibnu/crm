@extends('admin.layouts.app')

@section('title', $campaign->name.' - Campaign - Krakatau CRM')

@section('content')
    @php
        $currency = fn ($value) => 'Rp '.number_format((float) $value, 2, ',', '.');
        $progressWidth = min(100, $progress);
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'campaign'])
            </div>
            <div>
                <h1>Campaign Detail</h1>
                <p>Lihat ringkasan channel, budget, leads, timeline, dan ownership campaign.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $campaign->name }}</h2>
                    <p>{{ $campaign->target_audience ?: 'No target audience' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge type-{{ $campaign->type }}">{{ ucwords(str_replace('_', ' ', $campaign->type)) }}</span>
                    <span class="status-badge status-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span>
                    <a href="{{ route('admin.marketing.campaigns.edit', $campaign) }}" class="btn btn-primary">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete campaign ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Budget</span>
                    <strong>{{ $currency($campaign->budget) }}</strong>
                </div>
                <div>
                    <span>Leads</span>
                    <strong>{{ number_format($campaign->actual_leads) }} / {{ number_format($campaign->expected_leads) }}</strong>
                </div>
                <div>
                    <span>Progress</span>
                    <strong>{{ number_format($progress, 2) }}%</strong>
                </div>
            </div>

            <div class="customer-notes">
                <h3>Lead Progress</h3>
                <div class="campaign-progress">
                    <span style="width: {{ $progressWidth }}%"></span>
                </div>
                <p>{{ number_format($campaign->actual_leads) }} actual leads from {{ number_format($campaign->expected_leads) }} expected leads.</p>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Campaign Name</strong><span>{{ $campaign->name }}</span></div>
                <div><strong>Type</strong><span><span class="status-badge type-{{ $campaign->type }}">{{ ucwords(str_replace('_', ' ', $campaign->type)) }}</span></span></div>
                <div><strong>Status</strong><span><span class="status-badge status-{{ $campaign->status }}">{{ ucfirst($campaign->status) }}</span></span></div>
                <div><strong>Target Audience</strong><span>{{ $campaign->target_audience ?: '-' }}</span></div>
                <div><strong>Budget</strong><span>{{ $currency($campaign->budget) }}</span></div>
                <div><strong>Expected vs Actual Leads</strong><span>{{ number_format($campaign->expected_leads) }} / {{ number_format($campaign->actual_leads) }}</span></div>
                <div><strong>Start Date</strong><span>{{ $campaign->start_date?->format('d M Y') ?: '-' }}</span></div>
                <div><strong>End Date</strong><span>{{ $campaign->end_date?->format('d M Y') ?: '-' }}</span></div>
                <div><strong>Created By</strong><span>{{ $campaign->created_by ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Description</h3>
                <p>{{ $campaign->description ?: 'No description available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.campaigns.index') }}" class="btn btn-muted">Back</a>
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
