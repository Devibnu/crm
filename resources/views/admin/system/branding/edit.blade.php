@extends('admin.layouts.app')

@php
    $appNameValue = old('app_name', $branding->app_name);
    $displayName = filled($appNameValue) ? $appNameValue : $branding->display_app_name;
    $primaryColor = old('primary_color', $branding->primary_color) ?: '#7367f0';
@endphp

@section('title', 'Branding Settings - ' . $branding->display_app_name)

@section('content')
<section class="service-page customer-list-page branding-page">
    <article class="card service-card customer-list-card">
        <div class="service-card-icon">
            @include('admin.partials.sidebar-icon', ['icon' => 'brand'])
        </div>
        <div>
            <h1>Branding Settings</h1>
            <p>Atur nama aplikasi, logo sidebar, logo login, favicon, dan warna utama aplikasi.</p>
        </div>
    </article>

    @if (session('success'))
        <div class="card customer-alert success branding-alert">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="card customer-alert danger branding-alert">Periksa kembali input branding aplikasi.</div>
    @endif

    <div class="branding-preview-grid">
        <article class="card branding-preview-card">
            <span class="branding-preview-label">Preview Sidebar Brand</span>
            <div class="branding-sidebar-preview">
                <img src="{{ $branding->sidebar_logo_url }}" alt="Preview logo sidebar" class="branding-preview-sidebar-logo">
                <strong>{{ $displayName }}</strong>
            </div>
        </article>

        <article class="card branding-preview-card">
            <span class="branding-preview-label">Preview Login Brand</span>
            <div class="branding-login-preview">
                <img src="{{ $branding->login_logo_url }}" alt="Preview logo login" class="branding-preview-login-logo">
                <strong>{{ $displayName }}</strong>
            </div>
        </article>

        <article class="card branding-preview-card">
            <span class="branding-preview-label">Preview Favicon</span>
            <div class="branding-favicon-preview">
                @if ($branding->favicon_url)
                    <img src="{{ $branding->favicon_url }}" alt="Preview favicon" class="branding-preview-favicon">
                @else
                    <span class="branding-favicon-fallback">{{ strtoupper(substr($displayName, 0, 1)) }}</span>
                @endif
                <strong>{{ $displayName }}</strong>
            </div>
        </article>
    </div>

    <form method="POST" action="{{ route('admin.system.branding.update') }}" enctype="multipart/form-data" class="branding-form">
        @csrf
        @method('PUT')

        <div class="branding-settings-grid">
            <article class="card branding-settings-card">
                <div class="branding-card-head">
                    <span>Identity</span>
                    <h2>Nama dan warna aplikasi</h2>
                </div>

                <label class="field branding-field">
                    <span>Nama Aplikasi</span>
                    <input type="text" name="app_name" value="{{ $appNameValue }}" maxlength="100" placeholder="{{ \App\Models\BrandingSetting::FALLBACK_APP_NAME }}">
                    <small>Jika kosong, sistem memakai fallback {{ \App\Models\BrandingSetting::FALLBACK_APP_NAME }}.</small>
                    @error('app_name')<small class="error">{{ $message }}</small>@enderror
                </label>

                <div class="field branding-field">
                    <span>Warna Utama</span>
                    <div class="branding-color-row">
                        <input type="color" value="{{ $primaryColor }}" class="branding-color-picker" data-branding-color-picker aria-label="Pilih warna utama">
                        <input type="text" name="primary_color" value="{{ old('primary_color', $branding->primary_color) }}" placeholder="#7367f0" pattern="#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})" data-branding-color-text>
                    </div>
                    <small>Opsional. Format #RGB atau #RRGGBB.</small>
                    @error('primary_color')<small class="error">{{ $message }}</small>@enderror
                </div>
            </article>

            <article class="card branding-settings-card">
                <div class="branding-card-head">
                    <span>Assets</span>
                    <h2>Logo dan favicon</h2>
                </div>

                <div class="branding-upload-list">
                    <div class="field branding-upload-field">
                        <span>Logo Admin Sidebar</span>
                        <label class="branding-upload-card" for="sidebar_logo">
                            <img src="{{ $branding->sidebar_logo_url }}" alt="Logo sidebar saat ini" class="branding-upload-image branding-upload-image-sidebar">
                            <span class="branding-upload-body">
                                <strong>Pilih File</strong>
                                <small data-branding-file-name="sidebar_logo">{{ $branding->sidebar_logo_path ?: 'Default Vuexy logo' }}</small>
                                <em>JPG, PNG, atau WEBP. Maksimal 2MB.</em>
                            </span>
                        </label>
                        <input id="sidebar_logo" class="branding-file-input" type="file" name="sidebar_logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-branding-file-input>
                        @error('sidebar_logo')<small class="error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field branding-upload-field">
                        <span>Logo Login</span>
                        <label class="branding-upload-card" for="login_logo">
                            <img src="{{ $branding->login_logo_url }}" alt="Logo login saat ini" class="branding-upload-image branding-upload-image-login">
                            <span class="branding-upload-body">
                                <strong>Pilih File</strong>
                                <small data-branding-file-name="login_logo">{{ $branding->login_logo_path ?: 'Default Vuexy logo' }}</small>
                                <em>JPG, PNG, atau WEBP. Maksimal 2MB.</em>
                            </span>
                        </label>
                        <input id="login_logo" class="branding-file-input" type="file" name="login_logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" data-branding-file-input>
                        @error('login_logo')<small class="error">{{ $message }}</small>@enderror
                    </div>

                    <div class="field branding-upload-field">
                        <span>Favicon</span>
                        <label class="branding-upload-card" for="favicon">
                            @if ($branding->favicon_url)
                                <img src="{{ $branding->favicon_url }}" alt="Favicon saat ini" class="branding-upload-image branding-upload-image-favicon">
                            @else
                                <span class="branding-favicon-fallback branding-upload-fallback">{{ strtoupper(substr($displayName, 0, 1)) }}</span>
                            @endif
                            <span class="branding-upload-body">
                                <strong>Pilih File</strong>
                                <small data-branding-file-name="favicon">{{ $branding->favicon_path ?: 'Belum ada favicon custom' }}</small>
                                <em>ICO, JPG, PNG, atau WEBP. Maksimal 1MB.</em>
                            </span>
                        </label>
                        <input id="favicon" class="branding-file-input" type="file" name="favicon" accept=".ico,.jpg,.jpeg,.png,.webp,image/x-icon,image/vnd.microsoft.icon,image/jpeg,image/png,image/webp" data-branding-file-input>
                        @error('favicon')<small class="error">{{ $message }}</small>@enderror
                    </div>
                </div>
            </article>
        </div>

        <div class="card branding-action-bar">
            <div>
                <strong>{{ $displayName }}</strong>
                <span>Simpan perubahan branding untuk sidebar, login, dan favicon.</span>
            </div>
            <div class="branding-actions">
                <a href="{{ route('admin.dashboard') }}" class="btn btn-muted">Batal</a>
                <button type="submit" class="btn btn-primary branding-save-button">Simpan Branding</button>
            </div>
        </div>
    </form>
</section>

<script>
    document.querySelectorAll('[data-branding-file-input]').forEach((input) => {
        input.addEventListener('change', () => {
            const target = document.querySelector(`[data-branding-file-name="${input.name}"]`);
            if (target && input.files.length > 0) {
                target.textContent = input.files[0].name;
            }
        });
    });

    const colorPicker = document.querySelector('[data-branding-color-picker]');
    const colorText = document.querySelector('[data-branding-color-text]');

    if (colorPicker && colorText) {
        colorPicker.addEventListener('input', () => {
            colorText.value = colorPicker.value;
        });

        colorText.addEventListener('input', () => {
            if (/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(colorText.value)) {
                colorPicker.value = colorText.value;
            }
        });
    }
</script>
@endsection
