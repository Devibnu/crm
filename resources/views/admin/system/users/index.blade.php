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

<section class="service-page customer-list-page">

    {{-- Page header --}}
    <article class="card service-card customer-list-card">
        <div class="service-card-icon">
            @include('admin.partials.sidebar-icon', ['icon' => 'user'])
        </div>
        <div>
            <h1>Users</h1>
            <p>Kelola role pengguna Krakatau CRM.</p>
        </div>
    </article>

    @if (session('success'))
        <div class="card customer-alert success">{{ session('success') }}</div>
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
        <div class="customer-alert info users-role-info">
            Perubahan role akan langsung memengaruhi akses user ke menu dan fitur CRM.
        </div>

        <div class="users-table-toolbar">
            <form method="GET" action="{{ route('admin.system.users.index') }}" class="users-search-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama atau email..." aria-label="Cari nama atau email">
                <button type="submit" class="btn btn-primary">Cari</button>
                @if ($search)
                    <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted">Reset</a>
                @endif
            </form>
            <span class="users-result-label">{{ $users->total() }} Total Pengguna</span>
        </div>

        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role Saat Ini</th>
                        <th>Ubah Role</th>
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
                                        <span class="user-email">{{ $user->email }}</span>
                                    </div>
                                </div>
                            </td>
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
                                <form method="POST" action="{{ route('admin.system.users.update', $user) }}" class="user-role-action-panel" data-role-change-panel>
                                    @csrf
                                    @method('PUT')
                                    <div class="user-role-action-head">
                                        <span class="user-role-action-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M12 3 4 7v6c0 5 3.5 8 8 8s8-3 8-8V7z"/><path d="m9 12 2 2 4-4"/></svg>
                                        </span>
                                        <span>Role Baru</span>
                                    </div>
                                    <div class="user-role-action-controls">
                                        <select
                                            name="role"
                                            class="form-select user-role-select"
                                            aria-label="Ubah role untuk {{ $user->name }}"
                                            data-current-role="{{ $firstRole }}"
                                        >
                                            @foreach ($roles as $role)
                                                <option value="{{ $role }}" @selected($user->hasRole($role))>{{ $role }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary user-role-save">Simpan Perubahan</button>
                                    </div>
                                    <small class="assign-role-help" data-role-helper>
                                        {{ $firstRole ? 'Role saat ini sudah aktif.' : 'Perubahan role langsung memengaruhi akses user.' }}
                                    </small>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">
                                <div class="users-empty-state">
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                    <p>Tidak ada pengguna ditemukan{{ $search ? ' untuk "'.e($search).'"' : '' }}</p>
                                    @if ($search)
                                        <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted btn-sm">Hapus pencarian</a>
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

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-role-change-panel]').forEach((panel) => {
            const select = panel.querySelector('[data-current-role]');
            const helper = panel.querySelector('[data-role-helper]');

            if (!select || !helper) return;

            const updateHelper = () => {
                helper.textContent = select.value === select.dataset.currentRole
                    ? 'Role saat ini sudah aktif.'
                    : 'Perubahan role langsung memengaruhi akses user.';
            };

            select.addEventListener('change', updateHelper);
            updateHelper();
        });
    });
</script>
@endsection
