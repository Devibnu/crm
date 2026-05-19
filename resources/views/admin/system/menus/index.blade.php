@extends('admin.layouts.app')

@section('title', 'Dynamic Menus - Krakatau CRM')

@section('content')
<span hidden data-doc-title-en="Dynamic Menus - Krakatau CRM" data-doc-title-id="Menu Dinamis - Krakatau CRM"></span>
<section class="service-page customer-list-page">
    <article class="card service-card customer-list-card">
        <div class="service-card-icon">
            @include('admin.partials.sidebar-icon', ['icon' => 'menu'])
        </div>
        <div>
            <h1 data-lang-en="Dynamic Main Menu" data-lang-id="Menu Utama Dinamis">Dynamic Main Menu</h1>
            <p data-lang-en="Manage CRM menu structure, nested submenus, ordering, routes, icons, and role visibility from the admin panel." data-lang-id="Kelola struktur menu CRM, nested submenu, urutan, route, icon, dan role visibility dari panel admin.">Kelola struktur menu CRM, nested submenu, urutan, route, icon, dan role visibility dari panel admin.</p>
        </div>
    </article>

    @if (session('success'))
        <div class="card customer-alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="card customer-alert danger">{{ session('error') }}</div>
    @endif

    @if (!($menuFeatureReady ?? true))
        <div class="card customer-alert danger" data-lang-en="Dynamic menu is not active because the menus and/or role_menu tables do not exist yet. Run migrations and the menu seeders." data-lang-id="Fitur dynamic menu belum aktif karena tabel menus dan/atau role_menu belum ada. Jalankan migrate dan menu seeder.">
            Fitur dynamic menu belum aktif karena tabel <code>menus</code> dan/atau <code>role_menu</code> belum ada.
            Jalankan <code>php artisan migrate</code> lalu <code>php artisan db:seed --class=MenuSeeder</code> dan <code>php artisan db:seed --class=RoleMenuSeeder</code>.
        </div>
    @endif

    <div class="users-summary-grid">
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--total">
                <svg viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['total'] }}</strong>
                <span data-lang-en="Total Menus" data-lang-id="Total Menu">Total Menus</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--admin">
                <svg viewBox="0 0 24 24"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['root'] }}</strong>
                <span data-lang-en="Root Menus" data-lang-id="Menu Root">Root Menus</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--super">
                <svg viewBox="0 0 24 24"><path d="m5 13 4 4L19 7"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['active'] }}</strong>
                <span data-lang-en="Active" data-lang-id="Aktif">Active</span>
            </div>
        </div>
        <div class="card users-stat-card">
            <span class="users-stat-icon users-stat-icon--other">
                <svg viewBox="0 0 24 24"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['inactive'] }}</strong>
                <span data-lang-en="Inactive" data-lang-id="Nonaktif">Inactive</span>
            </div>
        </div>
    </div>

    <article class="card users-table-card">
        <div class="customer-alert info users-role-info" data-lang-en="Changes on this page immediately affect the Vuexy dynamic menu endpoint and role-based route access." data-lang-id="Perubahan di halaman ini langsung memengaruhi endpoint menu dinamis Vuexy dan route access per role.">
            Perubahan di halaman ini langsung memengaruhi endpoint menu dinamis Vuexy dan route access per role.
        </div>

        <div class="users-table-toolbar">
            <form method="GET" action="{{ route('admin.system.menus.index') }}" class="users-search-form">
                <input type="search" name="q" value="{{ $search }}" placeholder="Cari title, section, route, atau icon..." aria-label="Cari title, section, route, atau icon" data-placeholder-en="Search title, section, route, or icon..." data-placeholder-id="Cari title, section, route, atau icon..." data-title-en="Search title, section, route, or icon" data-title-id="Cari title, section, route, atau icon">
                <button type="submit" class="btn btn-primary" data-lang-en="Search" data-lang-id="Cari">Cari</button>
                @if ($search)
                    <a href="{{ route('admin.system.menus.index') }}" class="btn btn-muted" data-lang-en="Reset" data-lang-id="Reset">Reset</a>
                @endif
            </form>
            <div class="table-actions">
                <span class="users-result-label">{{ $menus->count() }} <span data-lang-en="Menus Shown" data-lang-id="Menu Ditampilkan">Menus Shown</span></span>
                @if ($menuFeatureReady ?? true)
                    <a href="{{ route('admin.system.menus.preview') }}" class="btn btn-muted" data-lang-en="Preview & Reorder" data-lang-id="Preview & Ubah Urutan">Preview & Reorder</a>
                    <a href="{{ route('admin.system.menus.create') }}" class="btn btn-primary" data-lang-en="Add Menu" data-lang-id="Tambah Menu">Add Menu</a>
                @else
                    <span class="btn btn-muted disabled" aria-disabled="true" data-lang-en="Preview & Reorder" data-lang-id="Preview & Ubah Urutan">Preview & Reorder</span>
                    <span class="btn btn-primary disabled" aria-disabled="true" data-lang-en="Add Menu" data-lang-id="Tambah Menu">Add Menu</span>
                @endif
            </div>
        </div>

        @if ($isFiltered)
            <div class="customer-alert info users-role-info" data-lang-en="Search mode is active. For drag-and-drop reorder, use Preview & Reorder so sibling ordering is not cut by filters." data-lang-id="Mode pencarian aktif. Untuk reorder drag and drop, gunakan halaman Preview & Reorder agar urutan sibling tidak terpotong filter.">
                Mode pencarian aktif. Untuk reorder drag and drop, gunakan halaman Preview & Reorder agar urutan sibling tidak terpotong filter.
            </div>
        @endif

        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th data-lang-en="Menu" data-lang-id="Menu">Menu</th>
                        <th data-lang-en="Section" data-lang-id="Section">Section</th>
                        <th data-lang-en="Route" data-lang-id="Route">Route</th>
                        <th data-lang-en="Role Visibility" data-lang-id="Role Visibility">Role Visibility</th>
                        <th data-lang-en="Status" data-lang-id="Status">Status</th>
                        <th data-lang-en="Sort" data-lang-id="Urutan">Sort</th>
                        <th data-lang-en="Actions" data-lang-id="Aksi">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($menus as $menu)
                        <tr>
                            <td>
                                <div class="menu-title-stack">
                                    <strong style="padding-left: {{ $menu->depth * 18 }}px;">
                                        @if ($menu->depth > 0)
                                            <span class="menu-depth-indicator">&#8627;</span>
                                        @endif
                                        {{ $menu->title }}
                                    </strong>
                                    <small>
                                        @if ($menu->parent?->title)
                                            <span data-lang-en="Parent" data-lang-id="Parent">Parent</span>: {{ $menu->parent->title }}
                                        @else
                                            <span data-lang-en="Root menu" data-lang-id="Menu root">Root menu</span>
                                        @endif
                                        • <span data-lang-en="Icon" data-lang-id="Icon">Icon</span>: {{ $menu->icon ?: '-' }}
                                        @if ($menu->children->isNotEmpty())
                                            • {{ $menu->children->count() }} <span data-lang-en="submenu" data-lang-id="submenu">submenu</span>
                                        @endif
                                    </small>
                                </div>
                            </td>
                            <td><span class="status-badge status-qualified">{{ $menu->section }}</span></td>
                            <td>
                                @if ($menu->route)
                                    <code>{{ $menu->route }}</code>
                                @else
                                    <span class="status-badge status-pending" data-lang-en="Group only" data-lang-id="Hanya grup">Group only</span>
                                @endif
                            </td>
                            <td>
                                @if ($menu->roles->isNotEmpty())
                                    <div class="menu-role-chip-list">
                                        @foreach ($menu->roles as $role)
                                            <span class="role-badge role-badge--admin">{{ $role->name }}</span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="status-badge status-active" data-lang-en="All roles" data-lang-id="Semua role">All roles</span>
                                @endif
                            </td>
                            <td>
                                <span class="status-badge {{ $menu->is_active ? 'status-active' : 'status-pending' }}">
                                    <span data-lang-en="{{ $menu->is_active ? 'Active' : 'Inactive' }}" data-lang-id="{{ $menu->is_active ? 'Aktif' : 'Nonaktif' }}">{{ $menu->is_active ? 'Active' : 'Inactive' }}</span>
                                </span>
                            </td>
                            <td>{{ $menu->sort_order }}</td>
                            <td>
                                <div class="table-actions">
                                    <a href="{{ route('admin.system.menus.edit', $menu) }}" class="btn btn-sm btn-primary" data-lang-en="Edit" data-lang-id="Edit">Edit</a>
                                    <form method="POST" action="{{ route('admin.system.menus.destroy', $menu) }}" data-confirm-en="Delete this menu?" data-confirm-id="Hapus menu ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus menu ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" data-lang-en="Delete" data-lang-id="Hapus">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="users-empty-state">
                                    <svg viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
                                    <p><span data-lang-en="No menus found" data-lang-id="Tidak ada menu ditemukan">Tidak ada menu ditemukan</span>{{ $search ? ' "'.e($search).'"' : '' }}</p>
                                    <div class="table-actions">
                                        @if ($search)
                                            <a href="{{ route('admin.system.menus.index') }}" class="btn btn-muted btn-sm" data-lang-en="Clear search" data-lang-id="Hapus pencarian">Clear search</a>
                                        @endif
                                        <a href="{{ route('admin.system.menus.create') }}" class="btn btn-primary btn-sm" data-lang-en="Add Menu" data-lang-id="Tambah Menu">Add Menu</a>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </article>
</section>
@endsection
