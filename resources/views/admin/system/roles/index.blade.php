@extends('admin.layouts.app')

@section('title', 'Roles & Permissions - Krakatau CRM')

@section('content')
    <span hidden data-doc-title-en="Roles & Permissions - Krakatau CRM" data-doc-title-id="Role & Permission - Krakatau CRM"></span>
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1 data-lang-en="Roles & Permissions" data-lang-id="Role & Permission">Roles & Permissions</h1>
                <p data-lang-en="Manage roles, permissions, and Krakatau CRM system access." data-lang-id="Kelola role, permission, dan akses sistem Krakatau CRM.">Kelola role, permission, dan akses sistem Krakatau CRM.</p>
            </div>
        </article>

        @include('admin.system.roles.partials.alerts')

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <div>
                    <h2 data-lang-en="Role List" data-lang-id="Daftar Role">Role List</h2>
                    <p>{{ $roles->count() }} <span data-lang-en="roles configured" data-lang-id="role terkonfigurasi">roles configured</span></p>
                </div>
                <a href="{{ route('admin.system.roles.create') }}" class="btn btn-primary" data-lang-en="Create Role" data-lang-id="Buat Role">Create Role</a>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Role" data-lang-id="Role">Role</th>
                            <th data-lang-en="Permissions" data-lang-id="Permission">Permissions</th>
                            <th data-lang-en="Users" data-lang-id="User">Users</th>
                            <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>
                                    <strong>{{ $role->name }}</strong>
                                    @if ($role->name === 'super_admin')
                                        <span class="status-badge status-qualified" data-lang-en="Protected" data-lang-id="Dilindungi">Protected</span>
                                    @endif
                                </td>
                                <td>{{ $role->permissions_count }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.system.roles.show', $role) }}" class="btn btn-sm btn-muted" data-lang-en="View" data-lang-id="Lihat">View</a>
                                        <a href="{{ route('admin.system.roles.edit', $role) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                        @if ($role->name !== 'super_admin')
                                            <form method="POST" action="{{ route('admin.system.roles.destroy', $role) }}" data-confirm-en="Delete this role?" data-confirm-id="Hapus role ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus role ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="customer-empty" data-lang-en="No roles yet." data-lang-id="Belum ada role.">Belum ada role.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
