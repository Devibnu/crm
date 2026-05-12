@extends('admin.layouts.app')

@section('title', 'Role Detail - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'lock'])
            </div>
            <div>
                <h1>{{ $role->name }}</h1>
                <p>{{ $role->permissions->count() }} permissions, {{ $role->users->count() }} users.</p>
            </div>
        </article>

        @include('admin.system.roles.partials.alerts')

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <div>
                    <h2>Permissions</h2>
                    <p>Grouped by module</p>
                </div>
                <a href="{{ route('admin.system.roles.edit', $role) }}" class="btn btn-primary">Edit Role</a>
            </div>

            @foreach ($permissionGroups as $group => $permissions)
                <section class="permission-section">
                    <h3>{{ $group }}</h3>
                    <div class="permission-grid">
                        @foreach ($permissions as $permission)
                            @if ($role->hasPermissionTo($permission))
                                <span class="status-badge status-qualified">{{ $permission }}</span>
                            @endif
                        @endforeach
                    </div>
                </section>
            @endforeach
        </article>

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <div>
                    <h2>Users</h2>
                    <p>User yang memakai role ini</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($role->users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="customer-empty">Belum ada user memakai role ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
