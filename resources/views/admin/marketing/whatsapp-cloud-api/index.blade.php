@extends('admin.layouts.app')

@section('title', 'WhatsApp Cloud API - Krakatau CRM')

@section('content')
    @php($statusLabels = ['APPROVED' => 'Disetujui', 'PENDING' => 'Sedang ditinjau', 'REJECTED' => 'Ditolak'])
    @php($connectionStatus = $provider?->meta_connection_status ?: ($provider && $provider->status === 'active' && $provider->api_token && $provider->device_id && $provider->business_account_id ? 'connected' : 'not_connected'))
    @php($providerStatusLabels = ['connected' => 'Terhubung', 'token_invalid' => 'Token Invalid', 'token_expired' => 'Token Expired', 'connection_error' => 'Tidak Terhubung', 'not_connected' => 'Tidak Terhubung'])
    @php($activeTemplate = $provider?->meta_template_name ? $provider->meta_template_name.' / '.($provider->meta_template_language ?: '-') : '-')
    @php($statCards = [
        ['label' => 'Templates', 'value' => $stats['total_templates'], 'tone' => 'green', 'icon' => 'stack'],
        ['label' => 'Approved', 'value' => $stats['approved_templates'], 'tone' => 'green', 'icon' => 'check'],
        ['label' => 'Pending', 'value' => $stats['pending_templates'], 'tone' => 'amber', 'icon' => 'clock'],
        ['label' => 'Rejected', 'value' => $stats['rejected_templates'], 'tone' => 'red', 'icon' => 'x'],
        ['label' => 'Sent', 'value' => $stats['sent_messages'], 'tone' => 'blue', 'icon' => 'send'],
        ['label' => 'Delivered', 'value' => $stats['delivered_messages'], 'tone' => 'green', 'icon' => 'truck'],
        ['label' => 'Read', 'value' => $stats['read_messages'], 'tone' => 'violet', 'icon' => 'eye'],
        ['label' => 'Failed', 'value' => $stats['failed_messages'], 'tone' => 'red', 'icon' => 'alert'],
    ])

    <section class="wa-cloud-page">
        @if (session('success'))
            <div class="wa-notice wa-notice-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="wa-notice wa-notice-error">{{ session('error') }}</div>
        @endif

        <article class="wa-hero-card">
            <div class="wa-hero-main">
                <div class="wa-brand-row">
                    <div class="wa-brand-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M12.04 4.25a7.76 7.76 0 0 0-6.6 11.84l.18.29-.74 2.7 2.78-.73.28.16a7.75 7.75 0 1 0 4.1-14.26Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M9.2 8.35c-.17-.38-.34-.39-.5-.4h-.43c-.15 0-.39.06-.6.29-.2.23-.78.76-.78 1.85s.8 2.15.91 2.3c.12.15 1.55 2.48 3.84 3.38 1.9.75 2.3.6 2.71.56.42-.04 1.35-.55 1.54-1.09.2-.53.2-.99.14-1.09-.06-.1-.21-.15-.44-.27l-1.55-.76c-.23-.12-.4-.17-.57.11-.17.29-.66.76-.81.92-.15.15-.3.17-.55.06-.24-.12-1.02-.38-1.95-1.2-.72-.64-1.2-1.43-1.34-1.67-.14-.25-.01-.38.11-.5.11-.11.25-.3.36-.44.12-.15.15-.25.23-.42.08-.17.04-.32-.02-.44l-.7-1.69Z" fill="currentColor"/>
                        </svg>
                    </div>
                    <div>
                        <span class="wa-eyebrow">WhatsApp Cloud API</span>
                        <h1>{{ $provider?->verified_name ?: $provider?->name ?: 'Meta Primary' }}</h1>
                    </div>
                </div>

                <div class="wa-phone-block">
                    <span>Nomor WhatsApp Aktif</span>
                    <strong>{{ $provider?->display_phone_number ?: '-' }}</strong>
                    <div class="wa-hero-meta">
                        <span class="wa-connection-badge wa-connection-{{ $connectionStatus }}">
                            <i></i>{{ $providerStatusLabels[$connectionStatus] ?? 'Tidak Terhubung' }}
                        </span>
                        <span>Connected since {{ $provider?->last_connected_at?->format('d M Y H:i') ?: '-' }}</span>
                    </div>
                </div>

                @if ($connectionStatus === 'token_expired')
                    <div class="wa-inline-alert">
                        Token Meta telah kedaluwarsa. Silakan perbarui Access Token pada System &rarr; WhatsApp Providers.
                    </div>
                @elseif ($provider?->meta_connection_error)
                    <div class="wa-inline-alert">{{ $provider->meta_connection_error }}</div>
                @endif
            </div>

            <aside class="wa-hero-side">
                @if ($providers->count() > 1)
                    <form method="GET" action="{{ route('admin.marketing.whatsapp-cloud-api.index') }}" class="wa-provider-select">
                        <label for="wa-provider-id">Provider</label>
                        <select id="wa-provider-id" name="provider_id" onchange="this.form.submit()">
                            @foreach ($providers as $metaProvider)
                                <option value="{{ $metaProvider->id }}" @selected($provider?->id === $metaProvider->id)>
                                    {{ $metaProvider->name }}{{ $metaProvider->is_default ? ' - Default' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <div class="wa-id-grid">
                    <div>
                        <span>Template Aktif</span>
                        <strong>{{ $activeTemplate }}</strong>
                    </div>
                    <div>
                        <span>WABA ID</span>
                        <strong>{{ $provider?->business_account_id ?: '-' }}</strong>
                    </div>
                    <div>
                        <span>Phone Number ID</span>
                        <strong>{{ $provider?->device_id ?: '-' }}</strong>
                    </div>
                </div>

                <div class="wa-hero-actions">
                    <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.sync') }}">
                        @csrf
                        @if ($provider)
                            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                        @endif
                        <button type="submit" class="wa-btn wa-btn-primary" @disabled(! $provider)>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6v5h-5M4 18v-5h5M18.1 9A7 7 0 0 0 6.7 6.4L4 9m16 6-2.7 2.6A7 7 0 0 1 5.9 15" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Sync Templates
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.refresh-connection') }}">
                        @csrf
                        @if ($provider)
                            <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                        @endif
                        <button type="submit" class="wa-btn wa-btn-secondary" @disabled(! $provider)>
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M21 12a9 9 0 0 1-15.4 6.4L3 16m0 0v5h5M3 12A9 9 0 0 1 18.4 5.6L21 8m0 0V3h-5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            Refresh
                        </button>
                    </form>
                </div>
            </aside>
        </article>

        <section class="wa-stat-grid">
            @foreach ($statCards as $card)
                <article class="wa-stat-card wa-tone-{{ $card['tone'] }}">
                    <div class="wa-stat-icon" aria-hidden="true">
                        @switch($card['icon'])
                            @case('check')
                                <svg viewBox="0 0 24 24" fill="none"><path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @break
                            @case('clock')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 8v5l3 2M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                                @break
                            @case('x')
                                <svg viewBox="0 0 24 24" fill="none"><path d="m7 7 10 10M17 7 7 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                @break
                            @case('send')
                                <svg viewBox="0 0 24 24" fill="none"><path d="m22 2-7 20-4-9-9-4 20-7Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
                                @break
                            @case('truck')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M3 7h11v10H3V7Zm11 4h4l3 3v3h-7v-6ZM7 20a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm11 0a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                @break
                            @case('eye')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z" stroke="currentColor" stroke-width="1.8"/><path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="1.8"/></svg>
                                @break
                            @case('alert')
                                <svg viewBox="0 0 24 24" fill="none"><path d="M12 8v5m0 4h.01M10.3 4.4 2.8 17.5A2 2 0 0 0 4.5 20h15a2 2 0 0 0 1.7-2.5L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                @break
                            @default
                                <svg viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        @endswitch
                    </div>
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ number_format($card['value']) }}</strong>
                </article>
            @endforeach
        </section>

        <article class="wa-table-card">
            <div class="wa-table-head">
                <div>
                    <span class="wa-eyebrow">Message Templates</span>
                    <h2>Template Pesan</h2>
                    <p>Template aktif:
                        <span class="wa-active-template">{{ $activeTemplate }}</span>
                    </p>
                </div>
                <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.sync') }}">
                    @csrf
                    @if ($provider)
                        <input type="hidden" name="provider_id" value="{{ $provider->id }}">
                    @endif
                    <button type="submit" class="wa-btn wa-btn-primary" @disabled(! $provider)>Sync Templates</button>
                </form>
            </div>

            <div class="wa-table-wrap">
                <table class="wa-template-table">
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Category</th>
                            <th>Language</th>
                            <th>Status</th>
                            <th>Last Synced</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            @php($isActiveTemplate = $template->is_default || ($provider && $provider->meta_template_name === $template->name && $provider->meta_template_language === $template->language))
                            <tr @class(['is-active-template' => $isActiveTemplate])>
                                <td>
                                    <div class="wa-template-name">
                                        <a href="{{ route('admin.marketing.whatsapp-cloud-api.templates.show', $template) }}">{{ $template->name }}</a>
                                        @if ($isActiveTemplate)
                                            <span class="wa-pill wa-pill-active">Template Aktif</span>
                                        @endif
                                    </div>
                                </td>
                                <td>{{ $template->category ?: '-' }}</td>
                                <td><span class="wa-language-pill">{{ strtoupper($template->language) }}</span></td>
                                <td>
                                    <span class="wa-template-status wa-template-status-{{ strtolower((string) $template->status) }}">
                                        {{ $statusLabels[$template->status] ?? ($template->status ?: '-') }}
                                    </span>
                                </td>
                                <td>{{ $template->last_synced_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    <div class="wa-row-actions">
                                        <button type="button" class="wa-btn wa-btn-small wa-btn-secondary js-send-test-template" data-url="{{ route('admin.marketing.whatsapp-cloud-api.templates.send-test', $template) }}" @disabled($template->status !== 'APPROVED')>
                                            Test Template
                                        </button>
                                        <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.templates.default', $template) }}">
                                            @csrf
                                            <button type="submit" class="wa-btn wa-btn-small wa-btn-ghost">Set Default</button>
                                        </form>
                                        <a href="{{ route('admin.marketing.whatsapp-cloud-api.templates.show', $template) }}" class="wa-btn wa-btn-small wa-btn-ghost">View Detail</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="wa-empty-state">
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

        <pre id="whatsapp-template-test-result" class="wa-test-result" style="display:none; white-space:pre-wrap;"></pre>
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
        .wa-cloud-page {
            display: grid;
            gap: 1rem;
        }

        .wa-notice,
        .wa-test-result {
            padding: 0.9rem 1rem;
            border-radius: 8px;
            border: 1px solid rgba(47, 43, 61, 0.12);
            background: #fff;
            box-shadow: 0 3px 14px rgba(47, 43, 61, 0.06);
        }

        .wa-notice-success,
        .wa-test-result.success {
            border-color: rgba(40, 199, 111, 0.25);
            background: #f0fbf5;
            color: #168a49;
        }

        .wa-notice-error,
        .wa-inline-alert {
            border-color: rgba(234, 84, 85, 0.24);
            background: #fff5f5;
            color: #b42324;
        }

        .wa-hero-card,
        .wa-table-card,
        .wa-stat-card {
            border: 1px solid rgba(47, 43, 61, 0.08);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(47, 43, 61, 0.07);
        }

        .wa-hero-card {
            display: grid;
            grid-template-columns: minmax(0, 1.5fr) minmax(280px, 0.8fr);
            gap: 1.25rem;
            padding: 1.25rem;
            overflow: hidden;
        }

        .wa-hero-main,
        .wa-hero-side {
            min-width: 0;
        }

        .wa-brand-row {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .wa-brand-icon,
        .wa-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 auto;
        }

        .wa-brand-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 8px;
            background: #e8f8ef;
            color: #1aa85c;
        }

        .wa-brand-icon svg,
        .wa-stat-icon svg,
        .wa-btn svg {
            width: 1.2rem;
            height: 1.2rem;
        }

        .wa-eyebrow {
            display: block;
            color: #6f6b7d;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .wa-brand-row h1,
        .wa-table-head h2 {
            margin: 0.1rem 0 0;
            color: #2f2b3d;
            font-size: 1.35rem;
            line-height: 1.25;
        }

        .wa-phone-block {
            margin-top: 1.25rem;
        }

        .wa-phone-block > span,
        .wa-id-grid span,
        .wa-stat-card span {
            color: #6f6b7d;
            font-size: 0.82rem;
            font-weight: 600;
        }

        .wa-phone-block strong {
            display: block;
            margin: 0.15rem 0 0.55rem;
            color: #1f2f2a;
            font-size: 2.2rem;
            line-height: 1.12;
            overflow-wrap: anywhere;
        }

        .wa-hero-meta,
        .wa-hero-actions,
        .wa-row-actions,
        .wa-table-head {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .wa-hero-meta {
            color: #6f6b7d;
            font-size: 0.86rem;
        }

        .wa-connection-badge,
        .wa-template-status,
        .wa-pill,
        .wa-language-pill {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            min-height: 1.65rem;
            padding: 0.28rem 0.6rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .wa-connection-badge i {
            width: 0.55rem;
            height: 0.55rem;
            margin-right: 0.38rem;
            border-radius: 50%;
            background: currentColor;
        }

        .wa-connection-connected,
        .wa-template-status-approved,
        .wa-pill-active {
            background: #e8f8ef;
            color: #168a49;
        }

        .wa-connection-token_invalid,
        .wa-template-status-rejected {
            background: #fff0f0;
            color: #c23a3b;
        }

        .wa-connection-token_expired,
        .wa-template-status-pending {
            background: #fff6e8;
            color: #a35a00;
        }

        .wa-connection-connection_error,
        .wa-connection-not_connected {
            background: #f1f1f2;
            color: #6f6b7d;
        }

        .wa-inline-alert {
            margin-top: 0.9rem;
            padding: 0.8rem 0.9rem;
            border: 1px solid;
            border-radius: 8px;
        }

        .wa-hero-side {
            display: grid;
            align-content: start;
            gap: 0.9rem;
            padding: 1rem;
            border-radius: 8px;
            background: #f8f7fb;
        }

        .wa-provider-select label {
            display: block;
            margin-bottom: 0.35rem;
            color: #6f6b7d;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .wa-provider-select select {
            width: 100%;
            min-height: 2.45rem;
            padding: 0.45rem 0.65rem;
            border: 1px solid rgba(47, 43, 61, 0.16);
            border-radius: 8px;
            background: #fff;
            color: #2f2b3d;
        }

        .wa-id-grid {
            display: grid;
            gap: 0.65rem;
        }

        .wa-id-grid div {
            padding-bottom: 0.65rem;
            border-bottom: 1px solid rgba(47, 43, 61, 0.08);
        }

        .wa-id-grid div:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .wa-id-grid strong {
            display: block;
            margin-top: 0.18rem;
            color: #2f2b3d;
            font-size: 0.92rem;
            overflow-wrap: anywhere;
        }

        .wa-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            min-height: 2.35rem;
            padding: 0.48rem 0.85rem;
            border: 1px solid transparent;
            border-radius: 8px;
            font: inherit;
            font-size: 0.86rem;
            font-weight: 700;
            line-height: 1.2;
            text-decoration: none;
            cursor: pointer;
        }

        .wa-btn:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .wa-btn-primary {
            background: #28c76f;
            color: #fff;
            box-shadow: 0 6px 14px rgba(40, 199, 111, 0.22);
        }

        .wa-btn-secondary {
            border-color: rgba(47, 43, 61, 0.12);
            background: #fff;
            color: #2f2b3d;
        }

        .wa-btn-ghost {
            background: #f6f6f8;
            color: #4b465c;
        }

        .wa-btn-small {
            min-height: 2rem;
            padding: 0.38rem 0.62rem;
            font-size: 0.78rem;
        }

        .wa-stat-grid {
            display: grid;
            grid-template-columns: repeat(8, minmax(120px, 1fr));
            gap: 0.75rem;
        }

        .wa-stat-card {
            display: grid;
            gap: 0.3rem;
            padding: 0.85rem;
        }

        .wa-stat-icon {
            width: 2.1rem;
            height: 2.1rem;
            border-radius: 8px;
            background: #f2f2f3;
            color: #6f6b7d;
        }

        .wa-stat-card strong {
            color: #2f2b3d;
            font-size: 1.35rem;
            line-height: 1.1;
        }

        .wa-tone-green .wa-stat-icon { background: #e8f8ef; color: #168a49; }
        .wa-tone-amber .wa-stat-icon { background: #fff6e8; color: #a35a00; }
        .wa-tone-red .wa-stat-icon { background: #fff0f0; color: #c23a3b; }
        .wa-tone-blue .wa-stat-icon { background: #eef5ff; color: #246bca; }
        .wa-tone-violet .wa-stat-icon { background: #f1efff; color: #6554c0; }

        .wa-table-card {
            overflow: hidden;
        }

        .wa-table-head {
            justify-content: space-between;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid rgba(47, 43, 61, 0.08);
        }

        .wa-table-head p {
            margin: 0.2rem 0 0;
            color: #6f6b7d;
        }

        .wa-active-template {
            display: inline-flex;
            margin-left: 0.25rem;
            padding: 0.18rem 0.55rem;
            border-radius: 999px;
            background: #e8f8ef;
            color: #168a49;
            font-weight: 700;
        }

        .wa-table-wrap {
            overflow-x: auto;
        }

        .wa-template-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }

        .wa-template-table th,
        .wa-template-table td {
            padding: 0.85rem 1.1rem;
            border-bottom: 1px solid rgba(47, 43, 61, 0.08);
            color: #4b465c;
            text-align: left;
            vertical-align: middle;
        }

        .wa-template-table th {
            background: #f8f7fb;
            color: #6f6b7d;
            font-size: 0.76rem;
            font-weight: 800;
            text-transform: uppercase;
        }

        .wa-template-table tbody tr:hover {
            background: #fbfbfd;
        }

        .wa-template-table tbody tr.is-active-template {
            background: #f4fbf7;
        }

        .wa-template-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .wa-template-name a {
            color: #2f2b3d;
            font-weight: 800;
            text-decoration: none;
        }

        .wa-language-pill {
            background: #f1f1f2;
            color: #4b465c;
        }

        .wa-row-actions form {
            margin: 0;
        }

        .wa-empty-state {
            display: grid;
            gap: 0.25rem;
            padding: 2rem;
            color: #6f6b7d;
            text-align: center;
        }

        .wa-empty-state strong {
            color: #2f2b3d;
        }

        @media (max-width: 1200px) {
            .wa-stat-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        @media (max-width: 900px) {
            .wa-hero-card {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 640px) {
            .wa-hero-card,
            .wa-table-head {
                padding: 0.9rem;
            }

            .wa-phone-block strong {
                font-size: 1.6rem;
            }

            .wa-stat-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .wa-btn,
            .wa-hero-actions form,
            .wa-table-head form {
                width: 100%;
            }

            .wa-row-actions {
                align-items: stretch;
            }
        }
    </style>
@endsection
