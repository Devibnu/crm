@extends('admin.layouts.app')

@section('title', 'WhatsApp Cloud API - Krakatau CRM')

@section('content')
    @php($statusLabels = ['APPROVED' => 'Disetujui', 'PENDING' => 'Sedang ditinjau', 'REJECTED' => 'Ditolak'])
    @php($statusClasses = ['APPROVED' => 'status-active', 'PENDING' => 'status-pending', 'REJECTED' => 'status-lost'])
    @php($connected = $provider && $provider->status === 'active' && $provider->api_token && $provider->device_id && $provider->business_account_id)

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>WhatsApp Cloud API</h1>
                <p>Kelola koneksi Meta, sinkronisasi template, dan test pengiriman approved template.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="card customer-alert">{{ session('error') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>Status Koneksi</h2>
                    <p>Meta Primary provider untuk WhatsApp Cloud API.</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge {{ $connected ? 'status-active' : 'status-inactive' }}">
                        {{ $connected ? 'Terhubung' : 'Tidak Terhubung' }}
                    </span>
                    @if ($provider)
                        <a href="{{ route('admin.system.whatsapp-providers.show', $provider) }}" class="btn btn-muted">Provider Detail</a>
                    @endif
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Nama Provider</strong><span>{{ $provider?->name ?: '-' }}</span></div>
                <div><strong>Nomor WhatsApp / Phone Number ID</strong><span>{{ $provider?->device_id ?: '-' }}</span></div>
                <div><strong>WABA ID</strong><span>{{ $provider?->business_account_id ?: '-' }}</span></div>
                <div><strong>Last Connected</strong><span>{{ $provider?->last_connected_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Default Provider</strong><span>{{ $provider?->is_default ? 'Ya' : 'Tidak' }}</span></div>
                <div><strong>Default Template</strong><span>{{ $provider?->meta_template_name ? $provider->meta_template_name.' / '.$provider->meta_template_language : '-' }}</span></div>
            </div>
        </article>

        <section class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Templates</span>
                <strong>{{ number_format($stats['total_templates']) }}</strong>
                <small>Template tersimpan di CRM</small>
            </article>
            <article class="card sales-summary-card">
                <span>Template Approved</span>
                <strong>{{ number_format($stats['approved_templates']) }}</strong>
                <small>Siap dikirim dari Meta</small>
            </article>
            <article class="card sales-summary-card">
                <span>Template Pending</span>
                <strong>{{ number_format($stats['pending_templates']) }}</strong>
                <small>Masih ditinjau Meta</small>
            </article>
            <article class="card sales-summary-card">
                <span>Template Rejected</span>
                <strong>{{ number_format($stats['rejected_templates']) }}</strong>
                <small>Ditolak Meta</small>
            </article>
            <article class="card sales-summary-card">
                <span>Pesan Terkirim</span>
                <strong>{{ number_format($stats['sent_messages']) }}</strong>
                <small>Outbound Meta tercatat</small>
            </article>
            <article class="card sales-summary-card">
                <span>Kontak Opt-in</span>
                <strong>{{ number_format($stats['opt_in_contacts']) }}</strong>
                <small>Consent WhatsApp aktif</small>
            </article>
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Template Pesan</h2>
                    <p>Template diambil dari endpoint Meta Graph API WABA message_templates.</p>
                </div>
                <div class="table-actions">
                    <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.sync') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary" @disabled(! $provider)>Sync Templates</button>
                    </form>
                </div>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Template Name</th>
                            <th>Category</th>
                            <th>Language</th>
                            <th>Status</th>
                            <th>Last Synced</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.marketing.whatsapp-cloud-api.templates.show', $template) }}" class="sales-title-link">{{ $template->name }}</a>
                                    @if ($provider && $provider->meta_template_name === $template->name && $provider->meta_template_language === $template->language)
                                        <span class="status-badge status-active">Default</span>
                                    @endif
                                </td>
                                <td>{{ $template->category ?: '-' }}</td>
                                <td>{{ $template->language }}</td>
                                <td>
                                    <span class="status-badge {{ $statusClasses[$template->status] ?? 'status-inactive' }}">
                                        {{ $statusLabels[$template->status] ?? ($template->status ?: '-') }}
                                    </span>
                                </td>
                                <td>{{ $template->last_synced_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="table-actions sales-row-actions">
                                        <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.templates.default', $template) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">Set as Default Template</button>
                                        </form>
                                        <button type="button" class="btn btn-sm btn-muted js-send-test-template" data-url="{{ route('admin.marketing.whatsapp-cloud-api.templates.send-test', $template) }}" @disabled($template->status !== 'APPROVED')>
                                            Send Test Template
                                        </button>
                                        <a href="{{ route('admin.marketing.whatsapp-cloud-api.templates.show', $template) }}" class="btn btn-sm btn-muted">View Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="customer-empty">
                                    <div class="sales-empty-state">
                                        <strong>Belum ada template tersinkron</strong>
                                        <span>Klik Sync Templates untuk mengambil template dari Meta WhatsApp Manager.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($templates->hasPages())
                <div class="customer-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $templates->firstItem() }}-{{ $templates->lastItem() }} dari {{ $templates->total() }} template
                    </div>
                    <div class="pagination-links">{{ $templates->links() }}</div>
                </div>
            @endif
        </article>

        <pre id="whatsapp-template-test-result" class="customer-alert" style="display:none; white-space:pre-wrap;"></pre>
    </section>

    <script>
        document.querySelectorAll('.js-send-test-template').forEach((button) => {
            button.addEventListener('click', async () => {
                const phone = window.prompt('Nomor tujuan test', '6281234567890');
                const resultBox = document.getElementById('whatsapp-template-test-result');

                if (!phone || !resultBox) {
                    return;
                }

                resultBox.style.display = 'block';
                resultBox.classList.remove('success');
                resultBox.textContent = 'Sending test template...';

                try {
                    const response = await fetch(button.dataset.url, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                        body: JSON.stringify({ phone }),
                    });
                    const json = await response.json();

                    resultBox.classList.toggle('success', Boolean(json.success));
                    resultBox.textContent = JSON.stringify(json, null, 2);
                } catch (error) {
                    resultBox.textContent = JSON.stringify({ success: false, error: error.message }, null, 2);
                }
            });
        });
    </script>
@endsection
