@extends('admin.layouts.app')

@section('title', 'Users - Krakatau CRM')

@section('content')
@php
    $totalUsers      = \App\Models\User::count();
    $superAdminCount = \App\Models\User::role('super_admin')->count();
    $adminMgrCount   = \App\Models\User::role(['admin', 'manager'])->count();
    $otherCount      = \App\Models\User::role(['sales', 'support', 'marketing'])->count();

    $roleBadgeClass = [
        'super_admin' => 'role-badge--super-admin',
        'admin'       => 'role-badge--admin',
        'manager'     => 'role-badge--manager',
        'sales'       => 'role-badge--sales',
        'marketing'   => 'role-badge--marketing',
        'support'     => 'role-badge--support',
    ];
@endphp

<section class="service-page customer-list-page users-management-page">

    {{-- Page header --}}
    <header class="lead-list-header users-page-header">
        <div>
            <span class="crm-record-kicker">System Management</span>
            <h1>Users</h1>
            <p>Kelola akun login dan role pengguna Krakatau CRM.</p>
        </div>
        <a href="{{ route('admin.system.users.create') }}" class="btn btn-primary">Create User</a>
    </header>

    @if (session('success'))
        <div class="card customer-alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="card customer-alert danger">{{ session('error') }}</div>
    @endif

    {{-- Summary cards --}}
    <div class="users-summary-grid">
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--total">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $totalUsers }}</strong>
                <span>Total Pengguna</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--super">
                <svg viewBox="0 0 24 24"><path d="M12 2l3.1 6.3L22 9.2l-5 4.9 1.2 6.9L12 18l-6.2 3 1.2-6.9L2 9.2l6.9-1z"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $superAdminCount }}</strong>
                <span>Super Admin</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--admin">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $adminMgrCount }}</strong>
                <span>Admin / Manager</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--other">
                <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $otherCount }}</strong>
                <span>Sales / Support / Marketing</span>
            </div>
        </div>
    </div>

    {{-- Table card --}}
    <article class="card users-table-card">
        <div class="users-table-toolbar">
            <form method="GET" action="{{ route('admin.system.users.index') }}" class="users-search-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau email..." aria-label="Cari nama atau email">
                <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                @if ($search)
                    <a href="{{ route('admin.system.users.index') }}" class="btn btn-sm btn-muted">Reset</a>
                @endif
            </form>
            <div class="users-table-meta">
                <span class="users-result-label">{{ $users->total() }} user</span>
                <span>Role menentukan akses menu dan fitur CRM.</span>
            </div>
        </div>

        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role Saat Ini</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        @php
                            $firstRole  = $user->roles->first()?->name;
                            $avatarSlug = str_replace('_', '-', $firstRole ?? 'none');
                            $initials   = collect(explode(' ', $user->name))
                                ->filter()
                                ->take(2)
                                ->map(fn($w) => strtoupper($w[0]))
                                ->join('');
                        @endphp
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <span class="user-avatar user-avatar--{{ $avatarSlug }}">{{ $initials }}</span>
                                    <div class="user-cell-info">
                                        <span class="user-name">{{ $user->name }}</span>
                                        <span class="user-id">User #{{ $user->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td><a href="mailto:{{ $user->email }}" class="user-email">{{ $user->email }}</a></td>
                            <td>
                                @if ($user->roles->isNotEmpty())
                                    @foreach ($user->roles->take(3) as $role)
                                        <span class="role-badge {{ $roleBadgeClass[$role->name] ?? 'role-badge--none' }}">{{ $role->name }}</span>
                                    @endforeach
                                @else
                                    <span class="role-badge role-badge--none">No Role</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions users-crud-actions">
                                    <a href="{{ route('admin.system.users.show', $user) }}" class="btn btn-sm btn-muted">View</a>
                                    <a href="{{ route('admin.system.users.edit', $user) }}" class="btn btn-sm btn-primary">Edit</a>
                                    <button type="button" class="btn btn-sm btn-danger js-open-user-delete-modal"
                                        data-delete-action="{{ route('admin.system.users.destroy', $user) }}"
                                        data-user-name="{{ $user->name }}"
                                        @disabled($user->hasRole('super_admin'))>Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="users-empty-state">
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                    <strong>{{ $search ? 'User tidak ditemukan' : 'Belum ada user' }}</strong>
                                    <p>{{ $search ? 'Tidak ada nama atau email yang cocok dengan pencarian ini.' : 'Buat akun login pertama dan tentukan role akses CRM-nya.' }}</p>
                                    @if ($search)
                                        <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted btn-sm">Hapus pencarian</a>
                                    @else
                                        <a href="{{ route('admin.system.users.create') }}" class="btn btn-primary btn-sm">Create User</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="customer-pagination">{{ $users->links() }}</div>
        @endif
    </article>

</section>

<div class="crm-modal-backdrop" data-user-delete-modal hidden>
    <div class="crm-confirm-modal opportunity-delete-modal" role="dialog" aria-modal="true" aria-labelledby="user-delete-title">
        <div class="crm-confirm-content">
            <h2 id="user-delete-title">Hapus User?</h2>
            <p>Akun login user akan dihapus permanen dari Krakatau CRM.</p>
            <div class="crm-confirm-target"><span>User</span><strong data-user-delete-name>-</strong></div>
        </div>
        <form method="POST" action="#" data-user-delete-form class="crm-confirm-actions">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-muted" data-user-delete-cancel>Batal</button>
            <button type="submit" class="btn btn-danger">Ya, Hapus User</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.querySelector('[data-user-delete-modal]');
        const form = document.querySelector('[data-user-delete-form]');
        const name = document.querySelector('[data-user-delete-name]');
        const cancel = document.querySelector('[data-user-delete-cancel]');

        document.querySelectorAll('.js-open-user-delete-modal').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.deleteAction;
                name.textContent = button.dataset.userName;
                modal.hidden = false;
                cancel.focus();
            });
        });

        const closeModal = () => { modal.hidden = true; };
        cancel.addEventListener('click', closeModal);
        modal.addEventListener('click', (event) => { if (event.target === modal) closeModal(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !modal.hidden) closeModal(); });
    });
</script>
@endsection
