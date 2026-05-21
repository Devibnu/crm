@extends('admin.layouts.app')

@section('title', $whatsappProvider->name.' - WhatsApp Provider - Krakatau CRM')

@section('content')
    @php($maskedSecret = $whatsappProvider->webhook_secret ? str_repeat('*', min(12, strlen($whatsappProvider->webhook_secret))) : '-')
    @php($webhookUrl = route('webhooks.whatsapp.fonnte'))
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
                <div><strong>API URL</strong><span>{{ $whatsappProvider->api_url ?: '-' }}</span></div>
                <div><strong>Device ID</strong><span>{{ $whatsappProvider->device_id ?: '-' }}</span></div>
                <div><strong>Webhook Secret</strong><span>{{ $maskedSecret }}</span></div>
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
                    <h2>Webhook Fonnte</h2>
                    <p>Gunakan URL ini di dashboard Fonnte agar pesan inbound masuk ke Omnichannel WhatsApp Inbox.</p>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Webhook URL</strong><span id="whatsapp-webhook-url">{{ $webhookUrl }}</span></div>
                <div><strong>Method</strong><span>POST</span></div>
                <div><strong>Secret Header</strong><span>X-Webhook-Secret atau X-Fonnte-Secret</span></div>
                <div><strong>APP_URL</strong><span>{{ config('app.url') }}</span></div>
            </div>

            @if (str_ends_with((string) $appHost, '.test') || in_array($appHost, ['localhost', '127.0.0.1'], true))
                <div class="customer-alert">
                    Domain lokal seperti <strong>{{ $appHost }}</strong> tidak bisa diakses dari internet. Untuk testing webhook Fonnte di local development, gunakan ngrok, expose, atau tunnel publik lain lalu update APP_URL/webhook URL.
                </div>
            @endif

            <div class="form-actions">
                <button type="button" class="btn btn-muted" id="copy-webhook-url">Copy Webhook</button>
                <button type="button" class="btn btn-primary" id="test-webhook-payload">Test Webhook Payload</button>
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

            <form id="whatsapp-test-send-form" class="sales-form-sections">
                @csrf

                <div class="sales-form-section">
                    <div class="customer-form-grid">
                        <label class="field">
                            <span>Phone</span>
                            <input type="text" name="phone" placeholder="6281234567890" required>
                        </label>

                        <label class="field">
                            <span>Message</span>
                            <textarea name="message" rows="4" placeholder="Halo, ini pesan test dari CRM." required></textarea>
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

                resultBox.classList.toggle('success', Boolean(json.success));
                resultBox.textContent = JSON.stringify(json, null, 2);
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
