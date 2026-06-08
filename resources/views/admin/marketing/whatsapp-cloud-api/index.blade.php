@extends('admin.layouts.app')

@section('title', 'WhatsApp Cloud API - Krakatau CRM')

@section('content')
    @php($statusLabels = ['APPROVED' => 'Disetujui', 'PENDING' => 'Sedang ditinjau', 'REJECTED' => 'Ditolak'])
    @php($statusClasses = ['APPROVED' => 'status-active', 'PENDING' => 'status-pending', 'REJECTED' => 'status-lost'])
    @php($connectionStatus = $provider?->meta_connection_status ?: ($provider && $provider->status === 'active' && $provider->api_token && $provider->device_id && $provider->business_account_id ? 'connected' : 'not_connected'))
    @php($providerStatusLabels = ['connected' => 'Terhubung', 'token_invalid' => 'Token Invalid', 'token_expired' => 'Token Expired', 'connection_error' => 'Tidak Terhubung', 'not_connected' => 'Tidak Terhubung'])
    @php($providerStatusClasses = ['connected' => 'status-active', 'token_invalid' => 'status-lost', 'token_expired' => 'status-pending', 'connection_error' => 'status-inactive', 'not_connected' => 'status-inactive'])
    @php($activeTemplate = $provider?->meta_template_name ? $provider->meta_template_name.' / '.($provider->meta_template_language ?: '-') : '-')

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

        <article class="card customer-show-card whatsapp-connection-card">
            <div class="customer-show-head">
                <div>
                    <h2>Status Koneksi</h2>
                    <p>Nomor WhatsApp bisnis aktif yang terhubung ke Meta Cloud API.</p>
                </div>
                <div class="table-actions">
                    @if ($providers->count() > 1)
                        <form method="GET" action="{{ route('admin.marketing.whatsapp-cloud-api.index') }}" class="table-actions">
                            <select name="provider_id" onchange="this.form.submit()" aria-label="Pilih Meta provider">
                                @foreach ($providers as $metaProvider)
                                    <option value="{{ $metaProvider->id }}" @selected($provider?->id === $metaProvider->id)>
                                        {{ $metaProvider->name }}{{ $metaProvider->is_default ? ' - Default' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    @endif
                    <span class="status-badge {{ $providerStatusClasses[$connectionStatus] ?? 'status-inactive' }}">
                        <span class="wa-status-dot wa-status-{{ $connectionStatus }}"></span>
                        {{ $providerStatusLabels[$connectionStatus] ?? 'Tidak Terhubung' }}
                    </span>
                </div>
            </div>

            @if ($connectionStatus === 'token_expired')
                <div class="customer-alert">
                    Token Meta telah kedaluwarsa. Silakan perbarui Access Token pada System &rarr; WhatsApp Providers.
                </div>
            @elseif ($provider?->meta_connection_error)
                <div class="customer-alert">{{ $provider->meta_connection_error }}</div>
            @endif

            <div class="wa-connection-hero">
                <div>
                    <span>Nomor WhatsApp</span>
                    <strong>{{ $provider?->display_phone_number ?: '-' }}</strong>
                    <small>{{ $provider?->verified_name ?: 'Nama bisnis belum tersinkron' }}</small>
                </div>
                <div>
                    <span>Template Aktif</span>
                    <strong>{{ $activeTemplate }}</strong>
                    <small>Dipakai untuk test template dan default Meta provider</small>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid wa-provider-grid">
                <div><strong>Nama Bisnis</strong><span>{{ $provider?->verified_name ?: '-' }}</span></div>
                <div><strong>Phone Number ID</strong><span>{{ $provider?->device_id ?: '-' }}</span></div>
                <div><strong>WABA ID</strong><span>{{ $provider?->business_account_id ?: '-' }}</span></div>
                <div><strong>Nama Provider</strong><span>{{ $provider?->name ?: '-' }}</span></div>
                <div><strong>Default Provider</strong><span>{{ $provider?->is_default ? 'Ya' : 'Tidak' }}</span></div>
                <div><strong>Terakhir Sinkron</strong><span>{{ $provider?->last_connected_at?->format('d M Y H:i') ?: '-' }}</span></div>
            </div>

            <div class="form-actions">
                <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.sync') }}">
                    @csrf
                    @if ($provider)
                        <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                    @endif
                    <button type="submit" class="btn btn-primary" @disabled(! $provider)>Sync Templates</button>
                </form>
                <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.refresh-connection') }}">
                    @csrf
                    @if ($provider)
                        <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                    @endif
                    <button type="submit" class="btn btn-muted" @disabled(! $provider)>Refresh Connection</button>
                </form>
                @if ($provider)
                    <a href="{{ route('admin.system.whatsapp-providers.show', $provider) }}" class="btn btn-muted">Provider Detail</a>
                @endif
            </div>
        </article>

        <section class="sales-summary-grid">
            <article class="card sales-summary-card">
                <span>Total Templates</span>
                <strong>{{ number_format($stats['total_templates']) }}</strong>
                <small>Template tersimpan di CRM</small>
            </article>
            <article class="card sales-summary-card">
                <span>Approved Templates</span>
                <strong>{{ number_format($stats['approved_templates']) }}</strong>
                <small>Siap dikirim dari Meta</small>
            </article>
            <article class="card sales-summary-card">
                <span>Pending Templates</span>
                <strong>{{ number_format($stats['pending_templates']) }}</strong>
                <small>Masih ditinjau Meta</small>
            </article>
            <article class="card sales-summary-card">
                <span>Rejected Templates</span>
                <strong>{{ number_format($stats['rejected_templates']) }}</strong>
                <small>Ditolak Meta</small>
            </article>
            <article class="card sales-summary-card">
                <span>Total Pesan Terkirim</span>
                <strong>{{ number_format($stats['sent_messages']) }}</strong>
                <small>Outbound Meta tercatat</small>
            </article>
            <article class="card sales-summary-card">
                <span>Delivered</span>
                <strong>{{ number_format($stats['delivered_messages']) }}</strong>
                <small>Pesan diterima perangkat</small>
            </article>
            <article class="card sales-summary-card">
                <span>Read</span>
                <strong>{{ number_format($stats['read_messages']) }}</strong>
                <small>Pesan sudah dibaca</small>
            </article>
            <article class="card sales-summary-card">
                <span>Failed</span>
                <strong>{{ number_format($stats['failed_messages']) }}</strong>
                <small>Pesan gagal dari Meta</small>
            </article>
        </section>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Template Pesan</h2>
                    <p>Template aktif: <strong>{{ $activeTemplate }}</strong></p>
                </div>
                <div class="table-actions">
                    <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.sync') }}">
                        @csrf
                        @if ($provider)
                            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                        @endif
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
                                            <button type="submit" class="btn btn-sm btn-primary">Set Default Template</button>
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

    <style>
        .whatsapp-connection-card {
            border: 1px solid rgba(40, 199, 111, 0.18);
        }

        .wa-status-dot {
            display: inline-block;
            width: 0.6rem;
            height: 0.6rem;
            margin-right: 0.35rem;
            border-radius: 50%;
            background: #a8aaae;
            vertical-align: middle;
        }

        .wa-status-connected {
            background: #28c76f;
            box-shadow: 0 0 0 4px rgba(40, 199, 111, 0.12);
        }

        .wa-status-token_invalid {
            background: #ea5455;
            box-shadow: 0 0 0 4px rgba(234, 84, 85, 0.12);
        }

        .wa-status-token_expired {
            background: #ff9f43;
            box-shadow: 0 0 0 4px rgba(255, 159, 67, 0.14);
        }

        .wa-connection-hero {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }

        .wa-connection-hero > div {
            padding: 1rem;
            border: 1px solid rgba(115, 103, 240, 0.16);
            border-radius: 8px;
            background: rgba(115, 103, 240, 0.04);
        }

        .wa-connection-hero span,
        .wa-connection-hero small {
            display: block;
            color: #6f6b7d;
        }

        .wa-connection-hero strong {
            display: block;
            margin: 0.25rem 0;
            color: #2f2b3d;
            font-size: 1.35rem;
            line-height: 1.2;
            overflow-wrap: anywhere;
        }

        .wa-provider-grid span {
            overflow-wrap: anywhere;
        }

        @media (max-width: 768px) {
            .wa-connection-hero {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endsection
