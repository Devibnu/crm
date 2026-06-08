@extends('admin.layouts.app')

@section('title', $template->name.' - WhatsApp Template - Krakatau CRM')

@section('content')
    @php($statusLabels = ['APPROVED' => 'Disetujui', 'PENDING' => 'Sedang ditinjau', 'REJECTED' => 'Ditolak'])
    @php($statusClass = strtolower((string) ($template->status ?: 'unknown')))
    @php($buttons = is_array($template->buttons) ? $template->buttons : [])
    @php($isDefault = $template->provider->meta_template_name === $template->name && $template->provider->meta_template_language === $template->language)

    <section class="wa-template-detail-page">
        @if (session('success'))
            <div class="wa-detail-notice wa-detail-notice-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="wa-detail-notice wa-detail-notice-error">{{ session('error') }}</div>
        @endif

        <article class="wa-template-header-card">
            <div class="wa-template-title-block">
                <span class="wa-detail-eyebrow">WhatsApp Template</span>
                <h1>{{ $template->name }}</h1>
                <div class="wa-header-badges">
                    <span class="wa-template-status wa-template-status-{{ $statusClass }}">
                        {{ $statusLabels[$template->status] ?? ($template->status ?: '-') }}
                    </span>
                    <span class="wa-chip">{{ $template->category ?: '-' }}</span>
                    <span class="wa-chip">{{ strtoupper($template->language ?: '-') }}</span>
                    <span class="wa-chip">{{ $template->provider->name }}</span>
                    @if ($isDefault)
                        <span class="wa-chip wa-chip-active">Template Aktif</span>
                    @endif
                </div>
            </div>

            <div class="wa-header-actions">
                <a href="{{ route('admin.marketing.whatsapp-cloud-api.index', ['provider_id' => $template->provider_id]) }}" class="wa-detail-btn wa-detail-btn-secondary">Back</a>
                @if ($template->status === 'APPROVED')
                    <form method="POST" action="{{ route('admin.marketing.whatsapp-cloud-api.templates.default', $template) }}">
                        @csrf
                        <button type="submit" class="wa-detail-btn wa-detail-btn-ghost">Set as Default</button>
                    </form>
                    <button type="button" class="wa-detail-btn wa-detail-btn-primary js-send-test-template" data-url="{{ route('admin.marketing.whatsapp-cloud-api.templates.send-test', $template) }}">
                        Send Test Template
                    </button>
                @endif
            </div>
        </article>

        <section class="wa-detail-grid">
            <article class="wa-detail-card">
                <div class="wa-card-head">
                    <span class="wa-detail-eyebrow">Template Information</span>
                    <h2>Informasi Template</h2>
                </div>

                <div class="wa-info-list">
                    <div><span>Template ID</span><strong>{{ $template->template_id ?: '-' }}</strong></div>
                    <div><span>Category</span><strong>{{ $template->category ?: '-' }}</strong></div>
                    <div><span>Language</span><strong>{{ strtoupper($template->language ?: '-') }}</strong></div>
                    <div><span>Status</span><strong>{{ $statusLabels[$template->status] ?? ($template->status ?: '-') }}</strong></div>
                    <div><span>Last Synced</span><strong>{{ $template->last_synced_at?->format('d M Y H:i') ?: '-' }}</strong></div>
                    <div><span>Provider</span><strong>{{ $template->provider->name }}</strong></div>
                    <div><span>WABA ID</span><strong>{{ $template->provider->business_account_id ?: '-' }}</strong></div>
                </div>
            </article>

            <article class="wa-preview-card">
                <div class="wa-card-head">
                    <span class="wa-detail-eyebrow">Preview</span>
                    <h2>WhatsApp Preview</h2>
                </div>

                <div class="wa-phone-preview">
                    <div class="wa-phone-topbar">
                        <div class="wa-avatar">WA</div>
                        <div>
                            <strong>{{ $template->provider->verified_name ?: $template->provider->name }}</strong>
                            <span>{{ $template->provider->display_phone_number ?: 'WhatsApp Business' }}</span>
                        </div>
                    </div>
                    <div class="wa-chat-area">
                        <div class="wa-message-bubble">
                            @if ($template->header)
                                <div class="wa-message-header">{{ $template->header }}</div>
                            @endif
                            <div class="wa-message-body">{{ $template->body ?: '-' }}</div>
                            @if ($template->footer)
                                <div class="wa-message-footer">{{ $template->footer }}</div>
                            @endif
                            <div class="wa-message-time">10:24</div>
                        </div>

                        @if ($buttons !== [])
                            <div class="wa-button-stack">
                                @foreach ($buttons as $button)
                                    <div class="wa-preview-button">
                                        {{ data_get($button, 'text') ?: data_get($button, 'title') ?: data_get($button, 'type') ?: '-' }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </article>
        </section>

        <article class="wa-detail-card">
            <div class="wa-card-head">
                <span class="wa-detail-eyebrow">Components</span>
                <h2>Struktur Template</h2>
            </div>

            <div class="wa-component-grid">
                <section>
                    <span>Header</span>
                    <p>{{ $template->header ?: '-' }}</p>
                </section>
                <section>
                    <span>Body</span>
                    <p>{{ $template->body ?: '-' }}</p>
                </section>
                <section>
                    <span>Footer</span>
                    <p>{{ $template->footer ?: '-' }}</p>
                </section>
                <section>
                    <span>Buttons</span>
                    @if ($buttons === [])
                        <p>-</p>
                    @else
                        <div class="wa-component-buttons">
                            @foreach ($buttons as $button)
                                <span>{{ data_get($button, 'text') ?: data_get($button, 'title') ?: data_get($button, 'type') ?: '-' }}</span>
                            @endforeach
                        </div>
                    @endif
                </section>
            </div>
        </article>

        <details class="wa-detail-card wa-dev-payload">
            <summary>
                <span>
                    <span class="wa-detail-eyebrow">Developer Payload</span>
                    <strong>Raw JSON</strong>
                </span>
                <i>Show</i>
            </summary>
            <pre>{{ json_encode($template->raw, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '-' }}</pre>
        </details>

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
        .wa-template-detail-page {
            display: grid;
            gap: 1rem;
        }

        .wa-template-header-card,
        .wa-detail-card,
        .wa-preview-card,
        .wa-detail-notice,
        .wa-test-result {
            border: 1px solid rgba(47, 43, 61, 0.08);
            border-radius: 8px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(47, 43, 61, 0.07);
        }

        .wa-template-header-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.2rem;
        }

        .wa-detail-eyebrow {
            display: block;
            color: #6f6b7d;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0;
            text-transform: uppercase;
        }

        .wa-template-title-block h1,
        .wa-card-head h2 {
            margin: 0.15rem 0 0;
            color: #2f2b3d;
            line-height: 1.2;
        }

        .wa-template-title-block h1 {
            font-size: 1.75rem;
            overflow-wrap: anywhere;
        }

        .wa-header-badges,
        .wa-header-actions,
        .wa-component-buttons {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .wa-header-badges {
            margin-top: 0.65rem;
        }

        .wa-header-actions form {
            margin: 0;
        }

        .wa-chip,
        .wa-template-status {
            display: inline-flex;
            align-items: center;
            min-height: 1.65rem;
            padding: 0.28rem 0.62rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 800;
            white-space: nowrap;
        }

        .wa-chip {
            background: #f1f1f2;
            color: #4b465c;
        }

        .wa-chip-active,
        .wa-template-status-approved {
            background: #e8f8ef;
            color: #168a49;
        }

        .wa-template-status-pending {
            background: #fff6e8;
            color: #a35a00;
        }

        .wa-template-status-rejected {
            background: #fff0f0;
            color: #c23a3b;
        }

        .wa-detail-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.35rem;
            padding: 0.48rem 0.85rem;
            border: 1px solid transparent;
            border-radius: 8px;
            font: inherit;
            font-size: 0.86rem;
            font-weight: 800;
            line-height: 1.2;
            text-decoration: none;
            cursor: pointer;
        }

        .wa-detail-btn-primary {
            background: #28c76f;
            color: #fff;
            box-shadow: 0 6px 14px rgba(40, 199, 111, 0.22);
        }

        .wa-detail-btn-secondary {
            border-color: rgba(47, 43, 61, 0.12);
            background: #fff;
            color: #2f2b3d;
        }

        .wa-detail-btn-ghost {
            background: #f6f6f8;
            color: #4b465c;
        }

        .wa-detail-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(320px, 0.9fr);
            gap: 1rem;
        }

        .wa-detail-card,
        .wa-preview-card {
            padding: 1rem;
        }

        .wa-info-list {
            display: grid;
            gap: 0.7rem;
            margin-top: 1rem;
        }

        .wa-info-list div {
            display: grid;
            grid-template-columns: minmax(130px, 0.45fr) minmax(0, 1fr);
            gap: 0.75rem;
            padding-bottom: 0.7rem;
            border-bottom: 1px solid rgba(47, 43, 61, 0.08);
        }

        .wa-info-list div:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .wa-info-list span,
        .wa-component-grid span,
        .wa-phone-topbar span {
            color: #6f6b7d;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .wa-info-list strong {
            color: #2f2b3d;
            overflow-wrap: anywhere;
        }

        .wa-phone-preview {
            margin-top: 1rem;
            overflow: hidden;
            border: 1px solid rgba(47, 43, 61, 0.1);
            border-radius: 8px;
            background: #efe7dc;
        }

        .wa-phone-topbar {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.8rem;
            background: #075e54;
            color: #fff;
        }

        .wa-phone-topbar span {
            display: block;
            margin-top: 0.1rem;
            color: rgba(255, 255, 255, 0.75);
        }

        .wa-avatar {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 50%;
            background: #25d366;
            color: #fff;
            font-size: 0.72rem;
            font-weight: 900;
        }

        .wa-chat-area {
            min-height: 320px;
            padding: 1rem;
        }

        .wa-message-bubble {
            position: relative;
            max-width: 86%;
            padding: 0.7rem 0.75rem 1.2rem;
            border-radius: 8px;
            background: #fff;
            color: #2f2b3d;
            box-shadow: 0 2px 8px rgba(47, 43, 61, 0.08);
        }

        .wa-message-header {
            margin-bottom: 0.45rem;
            font-weight: 900;
            overflow-wrap: anywhere;
        }

        .wa-message-body {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .wa-message-footer {
            margin-top: 0.5rem;
            color: #8b8794;
            font-size: 0.82rem;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .wa-message-time {
            position: absolute;
            right: 0.55rem;
            bottom: 0.28rem;
            color: #8b8794;
            font-size: 0.68rem;
        }

        .wa-button-stack {
            display: grid;
            gap: 0.35rem;
            max-width: 86%;
            margin-top: 0.4rem;
        }

        .wa-preview-button {
            padding: 0.55rem 0.7rem;
            border-radius: 8px;
            background: #fff;
            color: #128c7e;
            font-weight: 800;
            text-align: center;
            box-shadow: 0 2px 8px rgba(47, 43, 61, 0.06);
            overflow-wrap: anywhere;
        }

        .wa-component-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .wa-component-grid section {
            padding: 0.85rem;
            border: 1px solid rgba(47, 43, 61, 0.08);
            border-radius: 8px;
            background: #fbfbfd;
        }

        .wa-component-grid p {
            margin: 0.35rem 0 0;
            color: #2f2b3d;
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .wa-component-buttons {
            margin-top: 0.45rem;
        }

        .wa-component-buttons span {
            padding: 0.28rem 0.55rem;
            border-radius: 999px;
            background: #e8f8ef;
            color: #168a49;
        }

        .wa-dev-payload {
            padding: 0;
        }

        .wa-dev-payload summary {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem;
            cursor: pointer;
            list-style: none;
        }

        .wa-dev-payload summary::-webkit-details-marker {
            display: none;
        }

        .wa-dev-payload summary strong {
            display: block;
            margin-top: 0.15rem;
            color: #2f2b3d;
        }

        .wa-dev-payload summary i {
            color: #6f6b7d;
            font-style: normal;
            font-weight: 800;
        }

        .wa-dev-payload pre,
        .wa-test-result {
            margin: 0;
            padding: 1rem;
            overflow-x: auto;
            border-top: 1px solid rgba(47, 43, 61, 0.08);
            color: #2f2b3d;
        }

        .wa-detail-notice {
            padding: 0.9rem 1rem;
        }

        .wa-detail-notice-success,
        .wa-test-result.success {
            border-color: rgba(40, 199, 111, 0.25);
            background: #f0fbf5;
            color: #168a49;
        }

        .wa-detail-notice-error {
            border-color: rgba(234, 84, 85, 0.24);
            background: #fff5f5;
            color: #b42324;
        }

        @media (max-width: 960px) {
            .wa-template-header-card,
            .wa-detail-grid {
                grid-template-columns: 1fr;
            }

            .wa-template-header-card {
                display: grid;
            }
        }

        @media (max-width: 640px) {
            .wa-template-header-card,
            .wa-detail-card,
            .wa-preview-card {
                padding: 0.9rem;
            }

            .wa-header-actions,
            .wa-header-actions form,
            .wa-detail-btn {
                width: 100%;
            }

            .wa-info-list div,
            .wa-component-grid {
                grid-template-columns: 1fr;
            }

            .wa-template-title-block h1 {
                font-size: 1.35rem;
            }
        }
    </style>
@endsection
