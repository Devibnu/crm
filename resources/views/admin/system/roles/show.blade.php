@extends('admin.layouts.app')

@section('title', 'Role Detail - Krakatau CRM')

@php
    $selectedMenuIds = collect($selectedMenuIds ?? [])->map(fn ($menuId) => (int) $menuId)->all();

    $flattenMenuNodes = function (array $nodes) use (&$flattenMenuNodes) {
        return collect($nodes)->flatMap(function (array $node) use (&$flattenMenuNodes) {
            return collect([$node])->merge($flattenMenuNodes($node['children'] ?? []));
        });
    };
@endphp

@section('content')
    <span hidden data-doc-title-en="Role Detail - Krakatau CRM" data-doc-title-id="Detail Role - Krakatau CRM"></span>
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
                    <h2 data-lang-en="Permissions" data-lang-id="Permission">Permissions</h2>
                    <p data-lang-en="Grouped by module" data-lang-id="Dikelompokkan per modul">Grouped by module</p>
                </div>
                <a href="{{ route('admin.system.roles.edit', $role) }}" class="btn btn-primary" data-lang-en="Edit Role" data-lang-id="Edit Role">Edit Role</a>
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

        @if (($menuGroups ?? []) !== [])
            <article class="card customer-table-card">
                <div class="customer-table-toolbar">
                    <div>
                        <h2 data-lang-en="Menu Access" data-lang-id="Akses Menu">Menu Access</h2>
                        <p data-lang-en="Menus that will appear for this role." data-lang-id="Menu yang akan muncul untuk role ini.">Menu yang akan muncul untuk role ini.</p>
                    </div>
                </div>

                @foreach ($menuGroups as $group => $menus)
                    @php
                        $visibleMenus = $flattenMenuNodes($menus)
                            ->filter(fn (array $item) => in_array((int) $item['id'], $selectedMenuIds ?? [], true))
                            ->values();
                    @endphp

                    @if ($visibleMenus->isNotEmpty())
                        <section class="permission-section">
                            <h3>{{ $group }}</h3>
                            <div class="permission-grid">
                                @foreach ($visibleMenus as $menu)
                                    <span class="status-badge status-qualified">{{ $menu['title'] }}</span>
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </article>
        @endif

        <article class="card customer-table-card">
            <div class="customer-table-toolbar">
                <div>
                    <h2 data-lang-en="Users" data-lang-id="User">Users</h2>
                    <p data-lang-en="Users assigned to this role" data-lang-id="User yang memakai role ini">User yang memakai role ini</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th data-lang-en="Name" data-lang-id="Nama">Name</th>
                            <th data-lang-en="Email" data-lang-id="Email">Email</th>
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
                                <td colspan="2" class="customer-empty" data-lang-en="No users are using this role yet." data-lang-id="Belum ada user memakai role ini.">Belum ada user memakai role ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
