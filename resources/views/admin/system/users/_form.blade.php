@php
    $user = $user ?? null;
    $selectedRole = old('role', $selectedRole ?? $user?->roles->first()?->name ?? '');
    $isVerified = (bool) old('is_verified', $user?->email_verified_at !== null);
    $isEdit = (bool) ($user?->exists ?? false);
@endphp

<div class="customer-form-grid">
    <label class="field">
        <span data-lang-en="User Name" data-lang-id="Nama User">User Name</span> <strong>*</strong>
        <input type="text" name="name" value="{{ old('name', $user->name ?? '') }}" maxlength="255" required>
        @error('name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Email" data-lang-id="Email">Email</span> <strong>*</strong>
        <input type="email" name="email" value="{{ old('email', $user->email ?? '') }}" maxlength="255" required>
        @error('email')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Role" data-lang-id="Role">Role</span> <strong>*</strong>
        <select name="role" required>
            <option value="" data-lang-en="Select role" data-lang-id="Pilih role">Pilih role</option>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected($selectedRole === $role)>{{ $role }}</option>
            @endforeach
        </select>
        @error('role')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full preference-consent-field">
        <span data-lang-en="Email Status" data-lang-id="Status Email">Email Status</span>
        <input type="hidden" name="is_verified" value="0">
        <label class="preference-checkbox">
            <input type="checkbox" name="is_verified" value="1" @checked($isVerified)>
            <span data-lang-en="Mark the user's email as verified" data-lang-id="Tandai email user sudah terverifikasi">Mark the user's email as verified</span>
        </label>
        @if ($isEdit && $user?->email_verified_at)
            <small><span data-lang-en="Email verified at" data-lang-id="Email terverifikasi pada">Email verified at</span> {{ $user->email_verified_at->format('d M Y H:i') }}</small>
        @endif
        @error('is_verified')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Password" data-lang-id="Password">Password</span> {{ $isEdit ? '' : '*' }}
        <input type="password" name="password" minlength="8" {{ $isEdit ? '' : 'required' }}>
        <small data-lang-en="{{ $isEdit ? 'Leave blank if the password is not changing.' : 'Minimum 8 characters.' }}" data-lang-id="{{ $isEdit ? 'Kosongkan jika password tidak diubah.' : 'Minimal 8 karakter.' }}">{{ $isEdit ? 'Kosongkan jika password tidak diubah.' : 'Minimal 8 karakter.' }}</small>
        @error('password')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span data-lang-en="Confirm Password" data-lang-id="Konfirmasi Password">Confirm Password</span> {{ $isEdit ? '' : '*' }}
        <input type="password" name="password_confirmation" minlength="8" {{ $isEdit ? '' : 'required' }}>
        @error('password_confirmation')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
