@extends('admin.layouts.app')

@section('title', $whatsappProvider->name.' - WhatsApp Provider - Krakatau CRM')

@section('content')
    @php($maskedSecret = $whatsappProvider->webhook_secret ? str_repeat('*', min(12, strlen($whatsappProvider->webhook_secret))) : '-')
    @php($isMeta = $whatsappProvider->provider === 'meta')
    @php($webhookUrl = $isMeta ? route('webhooks.whatsapp.meta') : route('webhooks.whatsapp.fonnte'))
    @php($appHost = parse_url(config('app.url'), PHP_URL_HOST))

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>WhatsApp Provider Detail</h1>
                <p>Lihat konfigurasi provider WhatsApp untuk fondasi integrasi CRM.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $whatsappProvider->name }}</h2>
                    <p>{{ strtoupper($whatsappProvider->provider) }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge type-{{ $whatsappProvider->provider }}">{{ strtoupper($whatsappProvider->provider) }}</span>
                    <span class="status-badge status-{{ $whatsappProvider->status }}">{{ ucfirst($whatsappProvider->status) }}</span>
                    <span class="status-badge status-{{ $whatsappProvider->is_default ? 'active' : 'inactive' }}">{{ $whatsappProvider->is_default ? 'Default' : 'Not Default' }}</span>
                    <a href="{{ route('admin.system.whatsapp-providers.edit', $whatsappProvider) }}" class="btn btn-primary">Edit</a>
                    <form method="POST" action="{{ route('admin.system.whatsapp-providers.destroy', $whatsappProvider) }}" onsubmit="return confirm('Delete provider ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                    <a href="{{ route('admin.system.whatsapp-providers.index') }}" class="btn btn-muted">Back</a>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Provider Name</strong><span>{{ $whatsappProvider->name }}</span></div>
                <div><strong>Provider Type</strong><span>{{ strtoupper($whatsappProvider->provider) }}</span></div>
                <div><strong>Status</strong><span>{{ ucfirst($whatsappProvider->status) }}</span></div>
                <div><strong>Default</strong><span>{{ $whatsappProvider->is_default ? 'Yes' : 'No' }}</span></div>
                <div><strong>{{ $isMeta ? 'Graph API URL' : 'API URL' }}</strong><span>{{ $whatsappProvider->api_url ?: '-' }}</span></div>
                @if ($isMeta)
                    <div><strong>Graph API Version</strong><span>{{ $whatsappProvider->graph_api_version ?: 'v23.0' }}</span></div>
                    <div><strong>Phone Number ID</strong><span>{{ $whatsappProvider->device_id ?: '-' }}</span></div>
                    <div><strong>WhatsApp Business Account ID</strong><span>{{ $whatsappProvider->business_account_id ?: '-' }}</span></div>
                    <div><strong>Approved Template Name</strong><span>{{ $whatsappProvider->meta_template_name ?: '-' }}</span></div>
                    <div><strong>Template Language</strong><span>{{ $whatsappProvider->meta_template_language ?: 'id' }}</span></div>
                    <div><strong>Webhook Verify Token</strong><span>{{ $maskedSecret }}</span></div>
                @else
                    <div><strong>Device ID</strong><span>{{ $whatsappProvider->device_id ?: '-' }}</span></div>
                    <div><strong>Webhook Secret</strong><span>{{ $maskedSecret }}</span></div>
                @endif
                <div><strong>Last Connected</strong><span>{{ $whatsappProvider->last_connected_at?->format('d M Y H:i') ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Notes</h3>
                <p>{{ $whatsappProvider->notes ?: 'No notes available' }}</p>
            </div>
        </article>

        <article class="card customer-show-card">
            <div class="sales-section-head">
                <div>
                    <h2>{{ $isMeta ? 'Webhook Meta' : 'Webhook Fonnte' }}</h2>
                    <p>{{ $isMeta ? 'Gunakan URL ini sebagai callback WhatsApp Cloud API di dashboard Meta.' : 'Gunakan URL ini di dashboard Fonnte agar pesan inbound masuk ke Omnichannel WhatsApp Inbox.' }}</p>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>{{ $isMeta ? 'Webhook Callback URL' : 'Webhook URL' }}</strong><span id="whatsapp-webhook-url">{{ $webhookUrl }}</span></div>
                <div><strong>Method</strong><span>{{ $isMeta ? 'GET verification, POST inbound' : 'POST' }}</span></div>
                <div><strong>{{ $isMeta ? 'Verify Token' : 'Secret Header' }}</strong><span>{{ $isMeta ? ($whatsappProvider->webhook_secret ?: '-') : 'X-Webhook-Secret atau X-Fonnte-Secret' }}</span></div>
                <div><strong>APP_URL</strong><span>{{ config('app.url') }}</span></div>
            </div>

            @if (str_ends_with((string) $appHost, '.test') || in_array($appHost, ['localhost', '127.0.0.1'], true))
                <div class="customer-alert">
                    Domain lokal seperti <strong>{{ $appHost }}</strong> tidak bisa diakses dari internet. Untuk testing webhook {{ $isMeta ? 'Meta' : 'Fonnte' }} di local development, gunakan ngrok, expose, atau tunnel publik lain lalu update APP_URL/webhook URL.
                </div>
            @endif

            <div class="form-actions">
                <button type="button" class="btn btn-muted" id="copy-webhook-url">Copy Webhook URL</button>
                @unless ($isMeta)
                    <button type="button" class="btn btn-primary" id="test-webhook-payload">Test Webhook Payload</button>
                @endunless
            </div>
            <pre id="webhook-test-result" class="customer-alert" style="display: none; white-space: pre-wrap;"></pre>
        </article>

        <article class="card customer-form-card">
            <div class="sales-section-head">
                <div>
                    <h2>Test WhatsApp Connection</h2>
                    <p>Kirim pesan uji memakai default active provider saat ini.</p>
                </div>
            </div>

            @if ($isMeta)
                <div class="customer-alert">
                    Meta menerima pesan bukan berarti pesan langsung delivered. Delivered/read dikirim melalui webhook.
                    Jika aplikasi Meta masih development mode, hanya nomor yang sudah ditambahkan sebagai tester/recipient yang dapat menerima pesan.
                    Template test dikirim dari konfigurasi provider atau template approved yang tersimpan di CRM.
                </div>
            @endif

            <form id="whatsapp-test-send-form" class="sales-form-sections">
                @csrf

                <div class="sales-form-section">
                    <div class="customer-form-grid">
                        <label class="field">
                            <span>Send Mode</span>
                            <select name="send_mode" id="whatsapp-test-send-mode">
                                <option value="text">Send Free Text</option>
                                <option value="template">Send Approved Template</option>
                            </select>
                        </label>

                        <label class="field">
                            <span>Phone</span>
                            <input type="text" name="phone" placeholder="6281234567890" required>
                        </label>

                        @if ($isMeta)
                            <label class="field" id="whatsapp-test-template-field">
                                <span>Template</span>
                                <select name="template_id">
                                    <option value="">Auto: Default approved template</option>
                                    @foreach ($approvedTemplates as $template)
                                        <option value="{{ $template->id }}" @selected($template->is_default)>
                                            {{ $template->name }} / {{ $template->language }}{{ $template->is_default ? ' - Default' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <small>Template diambil otomatis dari hasil sync WhatsApp Cloud API.</small>
                            </label>
                        @endif

                        <label class="field">
                            <span>Message</span>
                            <textarea name="message" rows="4" placeholder="Halo, ini pesan test dari CRM."></textarea>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Send Test Message</button>
                </div>

                <pre id="whatsapp-test-send-result" class="customer-alert" style="display: none; white-space: pre-wrap;"></pre>
            </form>
        </article>
    </section>

    <script>
        const sendModeSelect = document.getElementById('whatsapp-test-send-mode');
        const testMessageInput = document.querySelector('#whatsapp-test-send-form textarea[name="message"]');
        const templateField = document.getElementById('whatsapp-test-template-field');

        const syncMessageRequirement = () => {
            if (!sendModeSelect || !testMessageInput) {
                return;
            }

            const isTemplate = sendModeSelect.value === 'template';
            testMessageInput.required = !isTemplate;
            testMessageInput.closest('.field').style.display = isTemplate ? 'none' : '';
            if (templateField) {
                templateField.style.display = isTemplate ? '' : 'none';
            }
        };

        sendModeSelect?.addEventListener('change', syncMessageRequirement);
        syncMessageRequirement();

        document.getElementById('whatsapp-test-send-form')?.addEventListener('submit', async (event) => {
            event.preventDefault();

            const form = event.currentTarget;
            const resultBox = document.getElementById('whatsapp-test-send-result');
            const formData = new FormData(form);

            resultBox.style.display = 'block';
            resultBox.classList.remove('success');
            resultBox.textContent = 'Sending...';

            try {
                const response = await fetch('{{ route('admin.system.whatsapp-providers.test-send') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': formData.get('_token'),
                    },
                    body: formData,
                });
                const json = await response.json();
                const summary = [
                    `status: ${json.delivery_status || (json.success ? 'accepted' : 'failed')}`,
                    `provider: ${json.provider || '-'}`,
                    `message_id: ${json.message_id || '-'}`,
                    `template: ${json.template_name || '-'}`,
                    `reason: ${json.reason || '-'}`,
                ].join('\n');

                resultBox.classList.toggle('success', Boolean(json.success));
                resultBox.textContent = `${summary}\n\n${JSON.stringify(json, null, 2)}`;
            } catch (error) {
                resultBox.textContent = JSON.stringify({
                    success: false,
                    raw: {
                        error: error.message,
                    },
                }, null, 2);
            }
        });

        document.getElementById('copy-webhook-url')?.addEventListener('click', async () => {
            const url = document.getElementById('whatsapp-webhook-url')?.textContent?.trim() || '';
            await navigator.clipboard?.writeText(url);
        });

        document.getElementById('test-webhook-payload')?.addEventListener('click', async () => {
            const resultBox = document.getElementById('webhook-test-result');

            resultBox.style.display = 'block';
            resultBox.textContent = 'Sending webhook test...';

            try {
                const response = await fetch('{{ route('webhooks.whatsapp.fonnte') }}', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({
                        sender: '081200000001',
                        name: 'Webhook Test',
                        message: 'Test inbound dari halaman provider.',
                        webhook_secret: '{{ $whatsappProvider->webhook_secret }}',
                    }),
                });
                const json = await response.json();

                resultBox.classList.toggle('success', response.ok);
                resultBox.textContent = JSON.stringify(json, null, 2);
            } catch (error) {
                resultBox.textContent = JSON.stringify({
                    success: false,
                    error: error.message,
                }, null, 2);
            }
        });
    </script>
@endsection
