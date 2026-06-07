@php
    $whatsappProvider = $whatsappProvider ?? null;
    $selectedProvider = old('provider', $whatsappProvider->provider ?? 'fonnte');
    $selectedStatus = old('status', $whatsappProvider->status ?? 'inactive');
    $isDefault = (bool) old('is_default', $whatsappProvider->is_default ?? false);
    $apiUrl = old('api_url', $whatsappProvider->api_url ?? ($selectedProvider === 'meta' ? 'https://graph.facebook.com' : ''));
    $graphApiVersion = old('graph_api_version', $whatsappProvider->graph_api_version ?? ($selectedProvider === 'meta' ? 'v23.0' : ''));
    $metaTemplateLanguage = old('meta_template_language', $whatsappProvider->meta_template_language ?? ($selectedProvider === 'meta' ? 'id' : ''));
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Provider Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $whatsappProvider->name ?? '') }}" maxlength="255" placeholder="Fonnte Primary" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Provider <strong>*</strong></span>
                <select name="provider" id="whatsapp-provider-select" required>
                    @foreach ($providerOptions as $provider)
                        <option value="{{ $provider }}" @selected($selectedProvider === $provider)>{{ strtoupper($provider) }}</option>
                    @endforeach
                </select>
                @error('provider')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Status <strong>*</strong></span>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Default Provider</span>
                <select name="is_default">
                    <option value="0" @selected(! $isDefault)>No</option>
                    <option value="1" @selected($isDefault)>Yes</option>
                </select>
                @error('is_default')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>API Configuration</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-provider-label="api_url">API URL</span>
                <input type="text" name="api_url" value="{{ $apiUrl }}" maxlength="255" data-fonnte-placeholder="https://api.fonnte.com" data-wablas-placeholder="https://solo.wablas.com" data-meta-placeholder="https://graph.facebook.com" placeholder="https://api.fonnte.com">
                @error('api_url')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-provider-label="api_token">API Token</span>
                <input type="password" name="api_token" value="{{ old('api_token', $whatsappProvider->api_token ?? '') }}" data-fonnte-placeholder="Provider API token" data-wablas-placeholder="Provider API token" data-meta-placeholder="Permanent access token" placeholder="Provider API token">
                <small data-provider-help="api_token">Token disimpan terenkripsi untuk koneksi WhatsApp provider.</small>
                @error('api_token')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-provider-label="device_id">Device ID</span>
                <input type="text" name="device_id" value="{{ old('device_id', $whatsappProvider->device_id ?? '') }}" maxlength="255" data-fonnte-placeholder="device-001" data-wablas-placeholder="device-001" data-meta-placeholder="Phone Number ID" placeholder="device-001">
                @error('device_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-provider-label="webhook_secret">Webhook Secret</span>
                <input type="text" name="webhook_secret" value="{{ old('webhook_secret', $whatsappProvider->webhook_secret ?? '') }}" maxlength="255" data-fonnte-placeholder="Secret untuk validasi webhook" data-wablas-placeholder="Secret untuk validasi webhook" data-meta-placeholder="Webhook verify token" placeholder="Secret untuk validasi webhook">
                @error('webhook_secret')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field meta-provider-field">
                <span>Graph API Version</span>
                <input type="text" name="graph_api_version" value="{{ $graphApiVersion }}" maxlength="20" placeholder="v23.0">
                @error('graph_api_version')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field meta-provider-field">
                <span>WhatsApp Business Account ID</span>
                <input type="text" name="business_account_id" value="{{ old('business_account_id', $whatsappProvider->business_account_id ?? '') }}" maxlength="255" placeholder="WABA ID">
                @error('business_account_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field meta-provider-field">
                <span>Approved Template Name</span>
                <input type="text" name="meta_template_name" value="{{ old('meta_template_name', $whatsappProvider->meta_template_name ?? '') }}" maxlength="255" placeholder="approved_template_name">
                <small>Gunakan template approved dari WhatsApp Manager. Jangan gunakan hello_world kecuali Public Test Number Meta.</small>
                @error('meta_template_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field meta-provider-field">
                <span>Template Language</span>
                <input type="text" name="meta_template_language" value="{{ $metaTemplateLanguage }}" maxlength="20" placeholder="id">
                @error('meta_template_language')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Notes</h2>
        <label class="field">
            <span>Notes</span>
            <textarea name="notes" rows="4" placeholder="Catatan internal provider WhatsApp.">{{ old('notes', $whatsappProvider->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>

<script>
    (() => {
        const providerSelect = document.getElementById('whatsapp-provider-select');
        const labels = {
            fonnte: {
                api_url: 'API URL',
                api_token: 'API Token',
                device_id: 'Device ID',
                webhook_secret: 'Webhook Secret',
                api_token_help: 'Token disimpan terenkripsi untuk koneksi WhatsApp provider.',
            },
            wablas: {
                api_url: 'API URL',
                api_token: 'API Token',
                device_id: 'Device ID',
                webhook_secret: 'Webhook Secret',
                api_token_help: 'Token disimpan terenkripsi untuk koneksi WhatsApp provider.',
            },
            meta: {
                api_url: 'Graph API URL',
                api_token: 'Permanent Access Token',
                device_id: 'Phone Number ID',
                webhook_secret: 'Webhook Verify Token',
                api_token_help: 'Token permanen Meta disimpan terenkripsi untuk WhatsApp Cloud API.',
            },
        };

        const applyProviderState = () => {
            const provider = providerSelect?.value || 'fonnte';
            const copy = labels[provider] || labels.fonnte;

            document.querySelectorAll('[data-provider-label]').forEach((element) => {
                element.textContent = copy[element.dataset.providerLabel] || element.textContent;
            });

            document.querySelectorAll('[data-provider-help]').forEach((element) => {
                element.textContent = copy[`${element.dataset.providerHelp}_help`] || element.textContent;
            });

            document.querySelectorAll('input[data-fonnte-placeholder]').forEach((element) => {
                element.placeholder = element.dataset[`${provider}Placeholder`] || element.placeholder;
            });

            document.querySelectorAll('.meta-provider-field').forEach((element) => {
                element.style.display = provider === 'meta' ? '' : 'none';
            });

            const apiUrl = document.querySelector('input[name="api_url"]');
            const graphVersion = document.querySelector('input[name="graph_api_version"]');

            if (provider === 'meta') {
                if (apiUrl && apiUrl.value.trim() === '') {
                    apiUrl.value = 'https://graph.facebook.com';
                }
                if (graphVersion && graphVersion.value.trim() === '') {
                    graphVersion.value = 'v23.0';
                }
                const templateLanguage = document.querySelector('input[name="meta_template_language"]');
                if (templateLanguage && templateLanguage.value.trim() === '') {
                    templateLanguage.value = 'id';
                }
            }
        };

        providerSelect?.addEventListener('change', applyProviderState);
        applyProviderState();
    })();
</script>
