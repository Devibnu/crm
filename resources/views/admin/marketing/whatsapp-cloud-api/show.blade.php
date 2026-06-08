@extends('admin.layouts.app')

@section('title', $template->name.' - WhatsApp Template - Krakatau CRM')

@section('content')
    @php($statusLabels = ['APPROVED' => 'Disetujui', 'PENDING' => 'Sedang ditinjau', 'REJECTED' => 'Ditolak'])

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>{{ $template->name }}</h1>
                <p>Detail template WhatsApp Cloud API dari Meta.</p>
            </div>
        </article>

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>Template Detail</h2>
                    <p>{{ $template->provider->name }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $template->status === 'APPROVED' ? 'active' : 'pending' }}">
                        {{ $statusLabels[$template->status] ?? ($template->status ?: '-') }}
                    </span>
                    <a href="{{ route('admin.marketing.whatsapp-cloud-api.index') }}" class="btn btn-muted">Back</a>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Template ID</strong><span>{{ $template->template_id ?: '-' }}</span></div>
                <div><strong>Name</strong><span>{{ $template->name }}</span></div>
                <div><strong>Category</strong><span>{{ $template->category ?: '-' }}</span></div>
                <div><strong>Language</strong><span>{{ $template->language }}</span></div>
                <div><strong>Status</strong><span>{{ $statusLabels[$template->status] ?? ($template->status ?: '-') }}</span></div>
                <div><strong>Last Synced</strong><span>{{ $template->last_synced_at?->format('d M Y H:i') ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Header</h3>
                <p>{{ $template->header ?: '-' }}</p>
                <h3>Body</h3>
                <p>{{ $template->body ?: '-' }}</p>
                <h3>Footer</h3>
                <p>{{ $template->footer ?: '-' }}</p>
                <h3>Buttons</h3>
                <pre class="customer-alert" style="white-space:pre-wrap;">{{ json_encode($template->buttons, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
                <h3>Raw</h3>
                <pre class="customer-alert" style="white-space:pre-wrap;">{{ json_encode($template->raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
            </div>
        </article>
    </section>
@endsection
