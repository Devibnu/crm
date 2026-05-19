@extends('admin.layouts.app')

@section('title', $user->name.' - User - Krakatau CRM')

@section('content')
    @php
        $primaryRole = $user->roles->first()?->name;
    @endphp
    <span hidden data-doc-title-en="{{ $user->name }} - User - Krakatau CRM" data-doc-title-id="{{ $user->name }} - Pengguna - Krakatau CRM"></span>

    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'user'])
            </div>
            <div>
                <h1>{{ $user->name }}</h1>
                <p data-lang-en="User account details, active role, and email verification status." data-lang-id="Detail akun user, role aktif, dan status verifikasi email.">Detail akun user, role aktif, dan status verifikasi email.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="card customer-alert danger">{{ session('error') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $user->name }}</h2>
                    <p>{{ $user->email }}</p>
                </div>
                <div class="table-actions">
                    @can('users.update')
                        <a href="{{ route('admin.system.users.edit', $user) }}" class="btn btn-primary" data-lang-en="Edit User" data-lang-id="Edit User">Edit User</a>
                    @endcan
                    <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted" data-lang-en="Back to List" data-lang-id="Kembali ke Daftar">Back to List</a>
                </div>
            </div>

            <div class="customer-show-grid">
                <div>
                    <strong data-lang-en="User ID" data-lang-id="ID User">User ID</strong>
                    <span>#{{ $user->id }}</span>
                </div>
                <div>
                    <strong data-lang-en="Email" data-lang-id="Email">Email</strong>
                    <span>{{ $user->email }}</span>
                </div>
                <div>
                    <strong data-lang-en="Active Role" data-lang-id="Role Aktif">Active Role</strong>
                    <span>
                        @if ($primaryRole)
                            <span class="role-badge {{ $roleBadgeClass[$primaryRole] ?? 'role-badge--none' }}">{{ $primaryRole }}</span>
                        @else
                            <span class="role-badge role-badge--none" data-lang-en="No Role" data-lang-id="Tanpa Role">No Role</span>
                        @endif
                    </span>
                </div>
                <div>
                    <strong data-lang-en="Email Status" data-lang-id="Status Email">Email Status</strong>
                    <span>
                        <span class="status-badge {{ $user->email_verified_at ? 'status-active' : 'status-pending' }}">
                            <span data-lang-en="{{ $user->email_verified_at ? 'Verified' : 'Pending' }}" data-lang-id="{{ $user->email_verified_at ? 'Terverifikasi' : 'Menunggu' }}">{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</span>
                        </span>
                    </span>
                </div>
                <div>
                    <strong data-lang-en="Email Verified At" data-lang-id="Email Diverifikasi Pada">Email Verified At</strong>
                    <span>{{ $user->email_verified_at?->format('d M Y H:i') ?? '-' }}</span>
                </div>
                <div>
                    <strong data-lang-en="Created At" data-lang-id="Dibuat Pada">Created At</strong>
                    <span>{{ $user->created_at?->format('d M Y H:i') ?? '-' }}</span>
                </div>
                <div>
                    <strong data-lang-en="Updated At" data-lang-id="Diperbarui Pada">Updated At</strong>
                    <span>{{ $user->updated_at?->format('d M Y H:i') ?? '-' }}</span>
                </div>
            </div>

            <div class="customer-notes">
                <h3 data-lang-en="Access & Credential" data-lang-id="Akses & Kredensial">Access & Credential</h3>
                <p data-lang-en="User passwords are not shown for security reasons. Password and role changes can be made from the edit user page." data-lang-id="Password user tidak ditampilkan demi keamanan. Perubahan password dan role dapat dilakukan dari halaman edit user.">Password user tidak ditampilkan demi keamanan. Perubahan password dan role dapat dilakukan dari halaman edit user.</p>
            </div>
        </article>
    </section>
@endsection
