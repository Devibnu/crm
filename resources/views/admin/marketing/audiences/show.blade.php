@extends('admin.layouts.app')

@section('title', $segment->name.' - Audience Segment - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="{{ $segment->name }} - Audience Segment - Krakatau CRM" data-doc-title-id="{{ $segment->name }} - Segmen Audiens - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'audience'])
            </div>
            <div>
                <h1 data-lang-en="Audience Segment Detail" data-lang-id="Detail Segmen Audiens">Audience Segment Detail</h1>
                <p data-lang-en="View segment summary, estimated audience, and targeting criteria." data-lang-id="Lihat ringkasan segmen, estimasi audiens, dan kriteria penargetan.">Lihat ringkasan segment, estimated audience, dan criteria targeting.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $segment->name }}</h2>
                    <p data-lang-en="{{ number_format($segment->estimated_audience) }} estimated audience" data-lang-id="{{ number_format($segment->estimated_audience) }} estimasi audiens">{{ number_format($segment->estimated_audience) }} estimated audience</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge type-{{ $segment->type }}">{{ ucfirst($segment->type) }}</span>
                    <span class="status-badge status-{{ $segment->status }}">{{ ucfirst($segment->status) }}</span>
                    <a href="{{ route('admin.marketing.audiences.edit', $segment) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.audiences.destroy', $segment) }}" data-confirm-en="Delete this segment?" data-confirm-id="Hapus segmen ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this segment?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span data-lang-en="Estimated Audience" data-lang-id="Estimasi Audiens">Estimated Audience</span>
                    <strong>{{ number_format($segment->estimated_audience) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Type" data-lang-id="Tipe">Type</span>
                    <strong>{{ ucfirst($segment->type) }}</strong>
                </div>
                <div>
                    <span data-lang-en="Status" data-lang-id="Status">Status</span>
                    <strong>{{ ucfirst($segment->status) }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Segment Name" data-lang-id="Nama Segmen">Segment Name</strong><span>{{ $segment->name }}</span></div>
                <div><strong data-lang-en="Type" data-lang-id="Tipe">Type</strong><span><span class="status-badge type-{{ $segment->type }}">{{ ucfirst($segment->type) }}</span></span></div>
                <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span><span class="status-badge status-{{ $segment->status }}">{{ ucfirst($segment->status) }}</span></span></div>
                <div><strong data-lang-en="Estimated Audience" data-lang-id="Estimasi Audiens">Estimated Audience</strong><span>{{ number_format($segment->estimated_audience) }}</span></div>
                <div><strong data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</strong><span>{{ $segment->created_by ?: '-' }}</span></div>
                <div><strong data-lang-en="Created At" data-lang-id="Dibuat Pada">Created At</strong><span>{{ $segment->created_at?->format('d M Y H:i') }}</span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Description" data-lang-id="Deskripsi">Description</h3>
                <p data-lang-en="{{ $segment->description ?: 'No description available' }}" data-lang-id="{{ $segment->description ?: 'Belum ada deskripsi' }}">{{ $segment->description ?: 'No description available' }}</p>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Criteria JSON" data-lang-id="JSON Kriteria">Criteria JSON</h3>
                @if ($criteriaJson)
                    <pre class="audience-criteria-json">{{ $criteriaJson }}</pre>
                @else
                    <p data-lang-en="No criteria available" data-lang-id="Belum ada kriteria">No criteria available</p>
                @endif
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.audiences.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
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
