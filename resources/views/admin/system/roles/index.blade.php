@extends('admin.layouts.app')

@section('title', 'Roles & Permissions - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page roles-management-page">
        <header class="lead-list-header users-page-header">
            <div>
                <span class="crm-record-kicker">System Management</span>
                <h1>Roles &amp; Permissions</h1>
                <p>Kelola role, permission, dan akses sistem Krakatau CRM.</p>
            </div>
            <a href="{{ route('admin.system.roles.create') }}" class="btn btn-primary">Create Role</a>
        </header>

        @include('admin.system.roles.partials.alerts')

        <div class="roles-summary-grid" aria-label="Role summary">
            <article class="card roles-stat-card">
                <span class="roles-stat-icon is-primary">R</span>
                <div><strong>{{ $summary['roles'] }}</strong><span>Total Roles</span></div>
            </article>
            <article class="card roles-stat-card">
                <span class="roles-stat-icon is-info">P</span>
                <div><strong>{{ $summary['permissions'] }}</strong><span>Total Permissions</span></div>
            </article>
            <article class="card roles-stat-card">
                <span class="roles-stat-icon is-warning">S</span>
                <div><strong>{{ $summary['protected_roles'] }}</strong><span>Protected Roles</span></div>
            </article>
            <article class="card roles-stat-card">
                <span class="roles-stat-icon is-success">U</span>
                <div><strong>{{ $summary['assigned_users'] }}</strong><span>Assigned Users</span></div>
            </article>
        </div>

        <article class="card roles-list-card">
            <div class="roles-list-toolbar">
                <form method="GET" action="{{ route('admin.system.roles.index') }}" class="roles-search-form">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama role..." aria-label="Cari nama role">
                    <button type="submit" class="btn btn-sm btn-primary">Cari</button>
                    @if ($search !== '')
                        <a href="{{ route('admin.system.roles.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </form>
                <div class="roles-list-meta">
                    <strong>{{ $roles->count() }} role</strong>
                    <span>Role menentukan akses menu dan fitur CRM. Perubahan permission akan langsung memengaruhi user terkait.</span>
                </div>
            </div>

            <div class="roles-table-wrap">
                <table class="roles-table">
                    <thead>
                        <tr><th>Role</th><th>Permissions</th><th>Users</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>
                                    <div class="roles-name-cell">
                                        <span class="roles-avatar">{{ strtoupper(substr($role->name, 0, 1)) }}</span>
                                        <div>
                                            <strong>{{ $role->name }}</strong>
                                            @if ($role->name === 'super_admin')
                                                <span class="roles-protected-badge">Protected</span>
                                            @else
                                                <small>Custom access role</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td><span class="roles-count-badge is-permission">{{ $role->permissions_count }} permissions</span></td>
                                <td><span class="roles-count-badge is-user">{{ $role->users_count }} users</span></td>
                                <td>
                                    <div class="table-actions roles-row-actions">
                                        <a href="{{ route('admin.system.roles.show', $role) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.system.roles.edit', $role) }}" class="btn btn-sm btn-primary">Edit</a>
                                        <button type="button" class="btn btn-sm btn-danger js-open-role-delete-modal"
                                            data-delete-action="{{ route('admin.system.roles.destroy', $role) }}"
                                            data-role-name="{{ $role->name }}"
                                            @disabled($role->name === 'super_admin')>Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4">
                                    <div class="roles-empty-state">
                                        <strong>{{ $search !== '' ? 'Role tidak ditemukan' : 'Belum ada role' }}</strong>
                                        <p>{{ $search !== '' ? 'Coba gunakan nama role yang berbeda.' : 'Buat role untuk mengatur akses pengguna CRM.' }}</p>
                                        <a href="{{ $search !== '' ? route('admin.system.roles.index') : route('admin.system.roles.create') }}" class="btn btn-sm btn-primary">{{ $search !== '' ? 'Reset Pencarian' : 'Create Role' }}</a>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>

    <div class="crm-modal-backdrop" data-role-delete-modal hidden>
        <div class="crm-confirm-modal opportunity-delete-modal" role="dialog" aria-modal="true" aria-labelledby="role-delete-title">
            <div class="crm-confirm-content">
                <h2 id="role-delete-title">Hapus Role?</h2>
                <p>Role hanya dapat dihapus jika tidak sedang digunakan oleh user.</p>
                <div class="crm-confirm-target"><span>Role</span><strong data-role-delete-name>-</strong></div>
            </div>
            <form method="POST" action="#" data-role-delete-form class="crm-confirm-actions">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-muted" data-role-delete-cancel>Batal</button>
                <button type="submit" class="btn btn-danger">Ya, Hapus Role</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.querySelector('[data-role-delete-modal]');
            const form = document.querySelector('[data-role-delete-form]');
            const name = document.querySelector('[data-role-delete-name]');
            const cancel = document.querySelector('[data-role-delete-cancel]');

            document.querySelectorAll('.js-open-role-delete-modal').forEach((button) => {
                button.addEventListener('click', () => {
                    form.action = button.dataset.deleteAction;
                    name.textContent = button.dataset.roleName;
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
