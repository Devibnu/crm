@extends('admin.layouts.app')

@section('title', $user->name.' - User - Krakatau CRM')

@section('content')
    @php
        $primaryRole = $user->roles->first()?->name ?? 'No Role';
        $roleBadge = $primaryRole === 'No Role' ? 'none' : str_replace('_', '-', $primaryRole);
        $initials = collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($word) => strtoupper($word[0]))->join('');
        $lastLoginValue = $user->getAttribute('last_login_at');
        $lastLogin = $lastLoginValue ? \Illuminate\Support\Carbon::parse($lastLoginValue)->format('d M Y H:i') : 'Tidak tersedia';
    @endphp
    <span hidden data-doc-title-en="{{ $user->name }} - User - Krakatau CRM" data-doc-title-id="{{ $user->name }} - Pengguna - Krakatau CRM"></span>

    <section class="service-page customer-list-page users-management-page users-show-page">
        <header class="lead-list-header users-page-header">
            <div>
                <span class="crm-record-kicker">System Management</span>
                <h1>User Detail</h1>
                <p>Lihat informasi akun, role, dan status pengguna CRM.</p>
            </div>
            <div class="users-detail-header-actions">
                @can('users.update')
                    <a href="{{ route('admin.system.users.edit', $user) }}" class="btn btn-primary">Edit User</a>
                @endcan
                <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted">Back to Users</a>
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">
                <div>{{ session('success') }}</div>
                @if (session('default_password'))
                    <div>Password default: {{ session('default_password') }}</div>
                @endif
            </div>
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
                <div><span>Status</span><strong class="status-badge {{ $user->email_verified_at ? 'status-active' : 'status-pending' }}">{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</strong></div>
                <div><span>Created At</span><strong>{{ $user->created_at?->format('d M Y') ?? '-' }}</strong></div>
                <div><span>Last Login</span><strong>{{ $lastLogin }}</strong></div>
            </div>
        </section>

        <div class="users-detail-information-grid">
            <section class="card users-information-card">
                <header><span>Account</span><h2>Account Information</h2></header>
                <div class="users-detail-list">
                    <div><span>User ID</span><strong>#{{ $user->id }}</strong></div>
                    <div><span>Email</span><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></div>
                    <div><span>Role Aktif</span><strong class="role-badge role-badge--{{ $roleBadge }}">{{ $primaryRole }}</strong></div>
                    <div><span>Email Status</span><strong>{{ $user->email_verified_at ? 'Verified' : 'Unverified' }}</strong></div>
                </div>
            </section>

            <section class="card users-information-card users-credential-card">
                <header><span>Security</span><h2>Security &amp; Credential</h2></header>
                <div class="users-credential-list">
                    <p><strong>Password aman</strong><span>Password tidak ditampilkan demi keamanan.</span></p>
                    <p><strong>Perubahan credential</strong><span>Perubahan password hanya melalui halaman Edit User.</span></p>
                    <p><strong>Default akun baru</strong><span>KrakatauCRM@123</span></p>
                </div>
            </section>
        </div>
    </section>
@endsection
