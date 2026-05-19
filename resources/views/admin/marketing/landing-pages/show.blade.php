@extends('admin.layouts.app')

@section('title', $landingPage->title.' - Landing Page - Krakatau CRM')

@section('content')
    @php($conversionWidth = min(100, $conversionRate))

    <section class="service-page customer-list-page sales-workspace">
        <span data-doc-title-en="{{ $landingPage->title }} - Landing Page - Krakatau CRM" data-doc-title-id="{{ $landingPage->title }} - Landing Page - Krakatau CRM"></span>
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'landing'])
            </div>
            <div>
                <h1 data-lang-en="Landing Page Detail" data-lang-id="Detail Landing Page">Landing Page Detail</h1>
                <p data-lang-en="View the content, form preview, and landing page conversion performance." data-lang-id="Lihat konten, preview formulir, dan performa konversi landing page.">Lihat konten, form preview, dan conversion performance landing page.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $landingPage->title }}</h2>
                    <p>{{ $landingPage->slug }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $landingPage->status }}">{{ ucfirst($landingPage->status) }}</span>
                    <a href="{{ route('admin.marketing.landing-pages.edit', $landingPage) }}" class="btn btn-primary" data-lang-en="Edit" data-lang-id="Ubah">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.landing-pages.destroy', $landingPage) }}" data-confirm-en="Delete this landing page?" data-confirm-id="Hapus landing page ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Delete this landing page?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                    </form>
                </div>
            </div>

            @if ($landingPage->marketingCampaign)
                <div class="customer-notes">
                    <h3 data-lang-en="Campaign" data-lang-id="Campaign">Campaign</h3>
                    <p><a href="{{ route('admin.marketing.campaigns.show', $landingPage->marketingCampaign) }}" class="btn btn-sm btn-muted">{{ $landingPage->marketingCampaign->name }}</a></p>
                </div>
            @endif

            <div class="sales-detail-hero">
                <div><span data-lang-en="Views" data-lang-id="Dilihat">Views</span><strong>{{ number_format($landingPage->views_count) }}</strong></div>
                <div><span data-lang-en="Submissions" data-lang-id="Submission">Submissions</span><strong>{{ number_format($landingPage->submissions_count) }}</strong></div>
                <div><span data-lang-en="Conversion Rate" data-lang-id="Rasio Konversi">Conversion Rate</span><strong>{{ number_format($conversionRate, 2) }}%</strong></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Conversion Rate" data-lang-id="Rasio Konversi">Conversion Rate</h3>
                <div class="landing-conversion-track"><span style="width: {{ $conversionWidth }}%"></span></div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong data-lang-en="Headline" data-lang-id="Headline">Headline</strong><span>{{ $landingPage->headline ?: '-' }}</span></div>
                <div><strong data-lang-en="Status" data-lang-id="Status">Status</strong><span><span class="status-badge status-{{ $landingPage->status }}">{{ ucfirst($landingPage->status) }}</span></span></div>
                <div><strong data-lang-en="Published At" data-lang-id="Dipublikasikan Pada">Published At</strong><span>{{ $landingPage->published_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</strong><span>{{ $landingPage->created_by ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Subheadline" data-lang-id="Subheadline">Subheadline</h3>
                <p data-lang-en="{{ $landingPage->subheadline ?: 'No subheadline available' }}" data-lang-id="{{ $landingPage->subheadline ?: 'Belum ada subheadline' }}">{{ $landingPage->subheadline ?: 'No subheadline available' }}</p>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Form Fields Preview" data-lang-id="Preview Field Formulir">Form Fields Preview</h3>
                @if ($landingPage->form_fields)
                    <div class="landing-form-preview">
                        @foreach ($landingPage->form_fields as $field)
                            <label class="field">
                                <span>{{ ucwords(str_replace('_', ' ', $field['name'] ?? 'field')) }} @if ($field['required'] ?? false)<strong>*</strong>@endif</span>
                                <input type="{{ $field['type'] ?? 'text' }}" placeholder="{{ $field['name'] ?? 'field' }}" disabled>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p data-lang-en="No form fields available" data-lang-id="Belum ada field formulir">No form fields available</p>
                @endif
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Form Fields JSON" data-lang-id="JSON Field Formulir">Form Fields JSON</h3>
                @if ($formFieldsJson)
                    <pre class="landing-json">{{ $formFieldsJson }}</pre>
                @else
                    <p data-lang-en="No form fields JSON available" data-lang-id="Belum ada JSON field formulir">No form fields JSON available</p>
                @endif
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Thank You Message" data-lang-id="Pesan Terima Kasih">Thank You Message</h3>
                <p data-lang-en="{{ $landingPage->thank_you_message ?: 'No thank you message available' }}" data-lang-id="{{ $landingPage->thank_you_message ?: 'Belum ada pesan terima kasih' }}">{{ $landingPage->thank_you_message ?: 'No thank you message available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.marketing.landing-pages.index') }}" class="btn btn-muted" data-lang-en="Back" data-lang-id="Kembali">Back</a>
            </div>
        </article>
    </section>

    <style>
        .landing-conversion-track {
            height: 10px;
            border-radius: 999px;
            background: #ece9ff;
            overflow: hidden;
            max-width: 520px;
        }

        .landing-conversion-track span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg, #28c76f, #7367f0);
        }

        .landing-form-preview {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .landing-json {
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

        @media (max-width: 720px) {
            .landing-form-preview {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
