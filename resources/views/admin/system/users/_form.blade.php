@php
    $user = $user ?? null;
    $selectedRole = old('role', $selectedRole ?? $user?->roles->first()?->name ?? '');
    $isEdit = (bool) ($user?->exists ?? false);
@endphp

<div class="users-form-grid">
    <section class="card users-form-panel">
        <header class="users-form-panel-head">
            <span>01</span>
            <div><h2>Informasi Akun</h2><p>Identitas dan hak akses user di dalam CRM.</p></div>
        </header>
        <div class="users-form-fields">
            <div class="field users-form-field mb-4">
                <label for="user-name" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                <input id="user-name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" maxlength="255" placeholder="Contoh: Ibnu Qosim" required>
                <small class="users-field-help">Masukkan nama pengguna CRM.</small>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </div>

            <div class="field users-form-field mb-4">
                <label for="user-email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                <input id="user-email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" maxlength="255" placeholder="Contoh: ibnu@example.com" required>
                <small class="users-field-help">Digunakan untuk login ke sistem.</small>
                @error('email')<small class="error">{{ $message }}</small>@enderror
            </div>

            <div class="field users-form-field mb-4">
                <label for="user-role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                <select id="user-role" name="role" required>
                    <option value="">Pilih role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
                    @endforeach
                </select>
                <small class="users-field-help">Menentukan hak akses menu dan fitur CRM.</small>
                @error('role')<small class="error">{{ $message }}</small>@enderror
            </div>
        </div>
    </section>

    <section class="card users-form-panel users-security-panel">
        <header class="users-form-panel-head">
            <span>02</span>
            <div><h2>Keamanan Akun</h2><p>Atur credential untuk akses login user.</p></div>
        </header>
        <div class="users-form-fields">
            <div class="field users-form-field mb-4">
                <label for="user-password" class="form-label fw-semibold">Password</label>
                <input id="user-password" type="password" name="password" minlength="8" autocomplete="new-password" placeholder="Masukkan password baru">
                <small class="users-field-help">{{ $isEdit ? 'Kosongkan jika password lama tetap digunakan.' : 'Kosongkan untuk menggunakan password default.' }}</small>
                @error('password')<small class="error">{{ $message }}</small>@enderror
            </div>

            <div class="field users-form-field mb-4">
                <label for="user-password-confirmation" class="form-label fw-semibold">Konfirmasi Password</label>
                <input id="user-password-confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" placeholder="Ulangi password">
                @error('password_confirmation')<small class="error">{{ $message }}</small>@enderror
            </div>

            @if ($isEdit)
                <div class="users-security-note">
                    <strong>Password tidak berubah</strong>
                    <p>Kosongkan kedua field password jika credential lama tetap digunakan.</p>
                    <p>Untuk akun baru: Jika password dikosongkan, sistem menggunakan password default: KrakatauCRM@123</p>
                </div>
            @else
                <div class="users-default-password-box">
                    <span>Password Default</span>
                    <strong>KrakatauCRM@123</strong>
                    <p>Jika password dikosongkan, sistem menggunakan password default: KrakatauCRM@123</p>
                </div>
            @endif
        </div>
    </section>
</div>
