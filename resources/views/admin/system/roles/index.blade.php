@extends('admin.layouts.app')

@section('title', 'Roles & Permissions - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1>Roles & Permissions</h1>
                <p>Kelola role, permission, dan akses system Krakatau CRM.</p>
            </div>
        </article>

        @include('admin.system.roles.partials.alerts')

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <div>
                    <h2>Role List</h2>
                    <p>{{ $roles->count() }} roles configured</p>
                </div>
                <a href="{{ route('admin.system.roles.create') }}" class="btn btn-primary">Create Role</a>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Role</th>
                            <th>Permissions</th>
                            <th>Users</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($roles as $role)
                            <tr>
                                <td>
                                    <strong>{{ $role->name }}</strong>
                                    @if ($role->name === 'super_admin')
                                        <span class="status-badge status-qualified">Protected</span>
                                    @endif
                                </td>
                                <td>{{ $role->permissions_count }}</td>
                                <td>{{ $role->users_count }}</td>
                                <td>
                                    <div class="table-actions">
                                        <a href="{{ route('admin.system.roles.show', $role) }}" class="btn btn-sm btn-muted">View</a>
                                        <a href="{{ route('admin.system.roles.edit', $role) }}" class="btn btn-sm btn-primary">Edit</a>
                                        @if ($role->name !== 'super_admin')
                                            <form method="POST" action="{{ route('admin.system.roles.destroy', $role) }}" onsubmit="return confirm('Delete role ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="customer-empty">Belum ada role.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
