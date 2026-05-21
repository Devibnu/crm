@php
    $whatsappProvider = $whatsappProvider ?? null;
    $selectedProvider = old('provider', $whatsappProvider->provider ?? 'fonnte');
    $selectedStatus = old('status', $whatsappProvider->status ?? 'inactive');
    $isDefault = (bool) old('is_default', $whatsappProvider->is_default ?? false);
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
                <select name="provider" required>
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
                <span>API URL</span>
                <input type="text" name="api_url" value="{{ old('api_url', $whatsappProvider->api_url ?? '') }}" maxlength="255" placeholder="https://api.fonnte.com">
                @error('api_url')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>API Token</span>
                <input type="password" name="api_token" value="{{ old('api_token', $whatsappProvider->api_token ?? '') }}" placeholder="Provider API token">
                <small>Token disimpan terenkripsi untuk koneksi WhatsApp provider.</small>
                @error('api_token')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Device ID</span>
                <input type="text" name="device_id" value="{{ old('device_id', $whatsappProvider->device_id ?? '') }}" maxlength="255" placeholder="device-001">
                @error('device_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Webhook Secret</span>
                <input type="text" name="webhook_secret" value="{{ old('webhook_secret', $whatsappProvider->webhook_secret ?? '') }}" maxlength="255" placeholder="Secret untuk validasi webhook">
                @error('webhook_secret')<small class="error">{{ $message }}</small>@enderror
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
