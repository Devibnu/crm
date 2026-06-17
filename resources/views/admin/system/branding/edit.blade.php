@extends('admin.layouts.app')

@section('title', 'Branding Settings - ' . $branding->display_app_name)

@section('content')
<section class="service-page customer-list-page">
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
        <div class="card customer-alert success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="card customer-alert danger">Periksa kembali input branding aplikasi.</div>
    @endif

    <form method="POST" action="{{ route('admin.system.branding.update') }}" enctype="multipart/form-data" class="card customer-form-card">
        @csrf
        @method('PUT')

        <div class="customer-form-grid">
            <label class="field">
                <span>Nama Aplikasi</span>
                <input type="text" name="app_name" value="{{ old('app_name', $branding->app_name) }}" maxlength="100" placeholder="{{ \App\Models\BrandingSetting::FALLBACK_APP_NAME }}">
                <small>Jika kosong, sistem memakai fallback {{ \App\Models\BrandingSetting::FALLBACK_APP_NAME }}.</small>
                @error('app_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Warna Utama</span>
                <input type="text" name="primary_color" value="{{ old('primary_color', $branding->primary_color) }}" placeholder="#7367f0" pattern="#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})">
                <small>Opsional. Format #RGB atau #RRGGBB.</small>
                @error('primary_color')<small class="error">{{ $message }}</small>@enderror
            </label>

            <div class="field">
                <span>Logo Admin Sidebar</span>
                <div class="branding-preview">
                    <img src="{{ $branding->sidebar_logo_url }}" alt="Logo sidebar saat ini">
                    <small>{{ $branding->sidebar_logo_path ?: 'Default Vuexy logo' }}</small>
                </div>
                <input type="file" name="sidebar_logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <small>JPG, PNG, atau WEBP. Maksimal 2MB.</small>
                @error('sidebar_logo')<small class="error">{{ $message }}</small>@enderror
            </div>

            <div class="field">
                <span>Logo Login</span>
                <div class="branding-preview">
                    <img src="{{ $branding->login_logo_url }}" alt="Logo login saat ini">
                    <small>{{ $branding->login_logo_path ?: 'Default Vuexy logo' }}</small>
                </div>
                <input type="file" name="login_logo" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                <small>JPG, PNG, atau WEBP. Maksimal 2MB.</small>
                @error('login_logo')<small class="error">{{ $message }}</small>@enderror
            </div>

            <div class="field field-full">
                <span>Favicon</span>
                <div class="branding-preview">
                    @if ($branding->favicon_url)
                        <img src="{{ $branding->favicon_url }}" alt="Favicon saat ini">
                        <small>{{ $branding->favicon_path }}</small>
                    @else
                        <span class="branding-empty-preview">Belum ada favicon custom</span>
                    @endif
                </div>
                <input type="file" name="favicon" accept=".ico,.jpg,.jpeg,.png,.webp,image/x-icon,image/vnd.microsoft.icon,image/jpeg,image/png,image/webp">
                <small>ICO, JPG, PNG, atau WEBP. Maksimal 1MB.</small>
                @error('favicon')<small class="error">{{ $message }}</small>@enderror
            </div>
        </div>

        <div class="customer-form-actions">
            <a href="{{ route('admin.dashboard') }}" class="btn btn-muted">Batal</a>
            <button type="submit" class="btn btn-primary">Simpan Branding</button>
        </div>
    </form>
</section>
@endsection
