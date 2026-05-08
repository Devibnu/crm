@extends('admin.layouts.app')

@section('title', $segment->name.' - Audience Segment - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'audience'])
            </div>
            <div>
                <h1>Audience Segment Detail</h1>
                <p>Lihat ringkasan segment, estimated audience, dan criteria targeting.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $segment->name }}</h2>
                    <p>{{ number_format($segment->estimated_audience) }} estimated audience</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge type-{{ $segment->type }}">{{ ucfirst($segment->type) }}</span>
                    <span class="status-badge status-{{ $segment->status }}">{{ ucfirst($segment->status) }}</span>
                    <a href="{{ route('admin.marketing.audiences.edit', $segment) }}" class="btn btn-primary">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.audiences.destroy', $segment) }}" onsubmit="return confirm('Delete segment ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Estimated Audience</span>
                    <strong>{{ number_format($segment->estimated_audience) }}</strong>
                </div>
                <div>
                    <span>Type</span>
                    <strong>{{ ucfirst($segment->type) }}</strong>
                </div>
                <div>
                    <span>Status</span>
                    <strong>{{ ucfirst($segment->status) }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Segment Name</strong><span>{{ $segment->name }}</span></div>
                <div><strong>Type</strong><span><span class="status-badge type-{{ $segment->type }}">{{ ucfirst($segment->type) }}</span></span></div>
                <div><strong>Status</strong><span><span class="status-badge status-{{ $segment->status }}">{{ ucfirst($segment->status) }}</span></span></div>
                <div><strong>Estimated Audience</strong><span>{{ number_format($segment->estimated_audience) }}</span></div>
                <div><strong>Created By</strong><span>{{ $segment->created_by ?: '-' }}</span></div>
                <div><strong>Created At</strong><span>{{ $segment->created_at?->format('d M Y H:i') }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Description</h3>
                <p>{{ $segment->description ?: 'No description available' }}</p>
            </div>

            <div class="customer-notes">
                <h3>Criteria JSON</h3>
                @if ($criteriaJson)
                    <pre class="audience-criteria-json">{{ $criteriaJson }}</pre>
                @else
                    <p>No criteria available</p>
                @endif
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.audiences.index') }}" class="btn btn-muted">Back</a>
            </div>
        </article>
    </section>

    <style>
        .audience-criteria-json {
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
