@extends('admin.layouts.app')

@section('title', 'Users - Krakatau CRM')

@section('content')
<span hidden data-doc-title-en="Users - Krakatau CRM" data-doc-title-id="Pengguna - Krakatau CRM"></span>
<section class="service-page customer-list-page">
    <article class="card service-card customer-list-card">
        <div class="service-card-icon">
            @include('admin.partials.sidebar-icon', ['icon' => 'user'])
        </div>
        <div>
            <h1 data-lang-en="Users" data-lang-id="Pengguna">Users</h1>
            <p data-lang-en="Manage account data, access roles, and email verification status for Krakatau CRM users." data-lang-id="Kelola data akun, role akses, dan status verifikasi user Krakatau CRM.">Kelola data akun, role akses, dan status verifikasi user Krakatau CRM.</p>
        </div>
    </article>

    @if (session('success'))
        <div class="card customer-alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="card customer-alert danger">{{ session('error') }}</div>
    @endif

    <div class="users-summary-grid">
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--total">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['total'] }}</strong>
                <span data-lang-en="Total Users" data-lang-id="Total Pengguna">Total Users</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--super">
                <svg viewBox="0 0 24 24"><path d="M12 2l3.1 6.3L22 9.2l-5 4.9 1.2 6.9L12 18l-6.2 3 1.2-6.9L2 9.2l6.9-1z"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['superAdmin'] }}</strong>
                <span data-lang-en="Super Admin" data-lang-id="Super Admin">Super Admin</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--admin">
                <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['adminManager'] }}</strong>
                <span data-lang-en="Admin / Manager" data-lang-id="Admin / Manager">Admin / Manager</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--other">
                <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['others'] }}</strong>
                <span data-lang-en="Sales / Support / Others" data-lang-id="Sales / Support / Lainnya">Sales / Support / Others</span>
            </div>
        </div>
    </div>

    <article class="card users-table-card">
        <div class="customer-alert info users-role-info" data-lang-en="Role changes immediately affect user access to CRM menus and features. Use edit user to update email, password, and verification status." data-lang-id="Perubahan role akan langsung memengaruhi akses user ke menu dan fitur CRM. Gunakan edit user untuk memperbarui email, password, dan status verifikasi.">
            Perubahan role akan langsung memengaruhi akses user ke menu dan fitur CRM. Gunakan edit user untuk memperbarui email, password, dan status verifikasi.
        </div>

        <div class="users-table-toolbar">
            <form method="GET" action="{{ route('admin.system.users.index') }}" class="users-search-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama, email, atau role..." aria-label="Cari nama, email, atau role" data-placeholder-en="Search name, email, or role..." data-placeholder-id="Cari nama, email, atau role..." data-title-en="Search name, email, or role" data-title-id="Cari nama, email, atau role">
                <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Cari</button>
                @if ($search)
                    <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                @endif
            </form>
            <div class="table-actions">
                <span class="users-result-label">{{ $users->total() }} <span data-lang-en="Total Users" data-lang-id="Total Pengguna">Total Users</span></span>
                @can('users.create')
                    <a href="{{ route('admin.system.users.create') }}" class="btn btn-primary" data-lang-en="Add User" data-lang-id="Tambah User">Add User</a>
                @endcan
            </div>
        </div>

        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th data-lang-en="User" data-lang-id="User">User</th>
                        <th data-lang-en="Current Role" data-lang-id="Role Saat Ini">Current Role</th>
                        <th data-lang-en="Email Status" data-lang-id="Status Email">Email Status</th>
                        <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        <th data-lang-en="Change Role" data-lang-id="Ubah Role">Change Role</th>
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
                                        <small><span data-lang-en="Created" data-lang-id="Dibuat">Created</span> {{ $user->created_at?->format('d M Y') ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if ($user->roles->isNotEmpty())
                                    @foreach ($user->roles->take(3) as $role)
                                        <span class="role-badge {{ $roleBadgeClass[$role->name] ?? 'role-badge--none' }}">{{ $role->name }}</span>
                                    @endforeach
                                @else
                                    <span class="role-badge role-badge--none" data-lang-en="No Role" data-lang-id="Tanpa Role">No Role</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-badge {{ $user->email_verified_at ? 'status-active' : 'status-pending' }}">
                                    <span data-lang-en="{{ $user->email_verified_at ? 'Verified' : 'Pending' }}" data-lang-id="{{ $user->email_verified_at ? 'Terverifikasi' : 'Menunggu' }}">{{ $user->email_verified_at ? 'Verified' : 'Pending' }}</span>
                                </span>
                                <br>
                                <small>{{ $user->email_verified_at?->format('d M Y H:i') ?? '' }}@unless($user->email_verified_at)<span data-lang-en="Not verified yet" data-lang-id="Belum diverifikasi">Belum diverifikasi</span>@endunless</small>
                            </td>
                            <td>
                                <div class="table-actions">
                                    @can('users.view')
                                        <a href="{{ route('admin.system.users.show', $user) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a>
                                    @endcan
                                    @can('users.update')
                                        <a href="{{ route('admin.system.users.edit', $user) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                    @endcan
                                    @can('users.delete')
                                        <form method="POST" action="{{ route('admin.system.users.destroy', $user) }}" data-confirm-en="Delete this user?" data-confirm-id="Hapus user ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus user ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                            <td>
                                <form method="POST" action="{{ route('admin.system.users.role.update', $user) }}" class="user-role-action-panel" data-role-change-panel>
                                    @csrf
                                    @method('PUT')
                                    <div class="user-role-action-head">
                                        <span class="user-role-action-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M12 3 4 7v6c0 5 3.5 8 8 8s8-3 8-8V7z"/><path d="m9 12 2 2 4-4"/></svg>
                                        </span>
                                        <span data-lang-en="New Role" data-lang-id="Role Baru">New Role</span>
                                    </div>
                                    <div class="user-role-action-controls">
                                        <select
                                            name="role"
                                            class="form-select user-role-select"
                                            aria-label="Ubah role untuk {{ $user->name }}"
                                            data-title-en="Change role for {{ $user->name }}"
                                            data-title-id="Ubah role untuk {{ $user->name }}"
                                            data-current-role="{{ $firstRole }}"
                                        >
                                            @foreach ($roles as $role)
                                                <option value="{{ $role }}" @selected($user->hasRole($role))>{{ $role }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-primary user-role-save" data-lang-en="Save Changes" data-lang-id="Simpan Perubahan">Save Changes</button>
                                    </div>
                                    <small class="assign-role-help" data-role-helper data-helper-current-en="Current role is already active." data-helper-current-id="Role saat ini sudah aktif." data-helper-change-en="Role changes immediately affect user access." data-helper-change-id="Perubahan role langsung memengaruhi akses user.">
                                        {{ $firstRole ? 'Role saat ini sudah aktif.' : 'Perubahan role langsung memengaruhi akses user.' }}
                                    </small>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="users-empty-state">
                                    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 3.6-7 8-7s8 3 8 7"/></svg>
                                    <p><span data-lang-en="No users found" data-lang-id="Tidak ada pengguna ditemukan">Tidak ada pengguna ditemukan</span>{{ $search ? ' "'.e($search).'"' : '' }}</p>
                                    <div class="table-actions">
                                        @if ($search)
                                            <a href="{{ route('admin.system.users.index') }}" class="btn btn-muted btn-sm" data-lang-en="Clear search" data-lang-id="Hapus pencarian">Clear search</a>
                                        @endif
                                        @can('users.create')
                                            <a href="{{ route('admin.system.users.create') }}" class="btn btn-primary btn-sm" data-lang-en="Add User" data-lang-id="Tambah User">Add User</a>
                                        @endcan
                                    </div>
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
        const getLanguage = () => document.documentElement.lang === 'en' ? 'en' : 'id';

        document.querySelectorAll('[data-role-change-panel]').forEach((panel) => {
            const select = panel.querySelector('[data-current-role]');
            const helper = panel.querySelector('[data-role-helper]');

            if (!select || !helper) return;

            const updateHelper = () => {
                const language = getLanguage();
                helper.textContent = select.value === select.dataset.currentRole
                    ? (language === 'en' ? helper.dataset.helperCurrentEn : helper.dataset.helperCurrentId)
                    : (language === 'en' ? helper.dataset.helperChangeEn : helper.dataset.helperChangeId);
            };

            select.addEventListener('change', updateHelper);
            document.addEventListener('crm:language-changed', updateHelper);
            updateHelper();
        });
    });
</script>
@endsection
