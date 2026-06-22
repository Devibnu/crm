@extends('admin.layouts.app')

@section('title', 'Edit User - Krakatau CRM')

@section('content')
    @php
        $primaryRole = $user->roles->first()?->name ?? 'No Role';
        $roleBadge = $primaryRole === 'No Role' ? 'none' : str_replace('_', '-', $primaryRole);
        $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($word) => strtoupper($word[0]))->join('');
        $lastLoginValue = $user->getAttribute('last_login_at');
        $lastLogin = $lastLoginValue ? \Illuminate\Support\Carbon::parse($lastLoginValue)->format('d M Y H:i') : 'Tidak tersedia';
        $totalPermissions = $user->getAllPermissions()->count();
    @endphp
    <span hidden data-doc-title-en="Edit User - Krakatau CRM" data-doc-title-id="Edit User - Krakatau CRM"></span>
    <section class="users-form-page users-edit-page">
        <header class="lead-list-header users-page-header">
            <div>
                <span class="crm-record-kicker">System Management</span>
                <h1>Edit User</h1>
                <p>Perbarui akun login dan role pengguna Krakatau CRM.</p>
            </div>
            <a href="{{ route('admin.system.users.show', $user) }}" class="btn btn-primary">View Profile</a>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="card customer-alert danger">{{ session('error') }}</div>
        @endif

        <section class="card users-profile-summary-card" aria-label="User profile summary">
            <div class="users-profile-identity">
                <span class="users-profile-avatar">{{ $initials }}</span>
                <div>
                    <span class="users-profile-kicker">User Profile</span>
                    <h2>{{ $user->name }}</h2>
                    <a href="mailto:{{ $user->email }}">{{ $user->email }}</a>
                </div>
            </div>
            <div class="users-profile-facts">
                <div><span>Role</span><strong class="role-badge role-badge--{{ $roleBadge }}">{{ $primaryRole }}</strong></div>
                <div><span>Status</span><strong class="status-badge {{ $user->email_verified_at ? 'status-active' : 'status-pending' }}">{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</strong></div>
                <div><span>Created At</span><strong>{{ $user->created_at?->format('d M Y') ?? '-' }}</strong></div>
                <div><span>Last Login</span><strong>{{ $lastLogin }}</strong></div>
            </div>
        </section>

        <div class="users-edit-information-grid">
            <section class="card users-information-card">
                <header><span>Account</span><h2>Account Information</h2></header>
                <div class="users-information-list">
                    <div><span>User ID</span><strong>#{{ $user->id }}</strong></div>
                    <div><span>Role</span><strong>{{ $primaryRole }}</strong></div>
                    <div><span>Total Permissions</span><strong>{{ $totalPermissions }}</strong></div>
                    <div><span>Last Login</span><strong>{{ $lastLogin }}</strong></div>
                </div>
            </section>

            <section class="card users-information-card users-security-information-card">
                <header><span>Security</span><h2>Security Information</h2></header>
                <div class="users-security-guidance">
                    <div><span>Password Default</span><strong>KrakatauCRM@123</strong></div>
                    <p>Password user tidak berubah jika kedua field password dikosongkan.</p>
                    <p>Untuk akun baru: Jika password dikosongkan, sistem menggunakan password default: KrakatauCRM@123</p>
                    <p>Gunakan password unik dengan kombinasi huruf besar, huruf kecil, angka, dan simbol.</p>
                </div>
            </section>
        </div>

        <form method="POST" action="{{ route('admin.system.users.update', $user) }}" class="users-form-shell">
            @csrf
            @method('PUT')

            @include('admin.system.users._form', ['user' => $user])

            <div class="users-form-actions">
                <a href="{{ route('admin.system.users.show', $user) }}" class="btn btn-muted">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </section>
@endsection
