@extends('admin.layouts.app')

@section('title', 'Menu Management - Krakatau CRM')

@section('content')
@php
    $menusCollection = collect($menus);
    $sectionLabels = [
        'dashboard' => 'Dashboard',
        'customer-profile-360' => 'Customer Profile 360',
        'sales-enablement' => 'Sales Enablement',
        'marketing-automation' => 'Marketing Automation',
        'whatsapp-marketing' => 'WhatsApp Marketing',
        'service-management' => 'Service Management',
        'system' => 'System',
    ];
    $sectionOrder = array_flip(array_keys($sectionLabels));
    $sectionFilter = (string) request('section', '');
    $statusFilter = (string) request('status', '');
    $roleFilter = (string) request('role_visibility', '');
    $quickFilter = (string) request('quick', '');
    $quickFilters = [
        '' => 'All',
        'active' => 'Active',
        'hidden' => 'Hidden',
        'public' => 'Public',
        'restricted' => 'Restricted',
    ];
    $roleOptions = $menusCollection
        ->flatMap(fn ($menu) => $menu->roles->pluck('name'))
        ->filter()
        ->unique()
        ->sort()
        ->values();
    $sectionOptions = $menusCollection
        ->pluck('section')
        ->filter()
        ->unique()
        ->sortBy(fn ($section) => $sectionOrder[$section] ?? 999)
        ->values();
    $sectionCounts = $menusCollection
        ->groupBy(fn ($menu) => $menu->section ?: 'uncategorized')
        ->map(fn ($items) => $items->count());
    $visibleMenus = $menusCollection->filter(function ($menu) use ($sectionFilter, $statusFilter, $roleFilter, $quickFilter) {
        $matchesSection = $sectionFilter === '' || (string) $menu->section === $sectionFilter;
        $matchesStatus = $statusFilter === ''
            || ($statusFilter === 'active' && (bool) $menu->is_active)
            || ($statusFilter === 'inactive' && ! (bool) $menu->is_active);
        $matchesRole = $roleFilter === ''
            || ($roleFilter === '__all_roles' && $menu->roles->isEmpty())
            || $menu->roles->pluck('name')->contains($roleFilter);
        $matchesQuick = $quickFilter === ''
            || ($quickFilter === 'active' && (bool) $menu->is_active)
            || ($quickFilter === 'hidden' && ! (bool) $menu->is_active)
            || ($quickFilter === 'public' && blank($menu->permission_name) && $menu->roles->isEmpty())
            || ($quickFilter === 'restricted' && (filled($menu->permission_name) || $menu->roles->isNotEmpty()));

        return $matchesSection && $matchesStatus && $matchesRole && $matchesQuick;
    })->values();
    $menusBySection = $visibleMenus
        ->groupBy(fn ($menu) => $menu->section ?: 'uncategorized')
        ->sortKeysUsing(function ($first, $second) use ($sectionOrder) {
            $firstOrder = $sectionOrder[$first] ?? 999;
            $secondOrder = $sectionOrder[$second] ?? 999;

            return $firstOrder === $secondOrder
                ? strcasecmp((string) $first, (string) $second)
                : $firstOrder <=> $secondOrder;
        });
    $totalSections = $menusCollection->pluck('section')->filter()->unique()->count();
    $isMenuFilterActive = $isFiltered || $sectionFilter !== '' || $statusFilter !== '' || $roleFilter !== '' || $quickFilter !== '';
@endphp

<span hidden data-doc-title-en="Menu Management - Krakatau CRM" data-doc-title-id="Menu Management - Krakatau CRM"></span>
<section class="service-page customer-list-page menu-management-page">
    <article class="lead-list-header menu-management-hero">
        <div>
            <span class="crm-record-kicker">SYSTEM MANAGEMENT</span>
            <h1>Menu Management</h1>
            <p>Kelola menu sidebar, permission menu, urutan navigasi, dan visibilitas role dari satu tempat.</p>
        </div>
        <div class="menu-management-hero-actions">
            @if ($menuFeatureReady ?? true)
                <a href="{{ route('admin.system.menus.preview') }}" class="btn lead-banner-cta">Preview & Reorder</a>
                <a href="{{ route('admin.system.menus.create') }}" class="btn lead-banner-cta">Add Menu</a>
            @else
                <span class="btn lead-banner-cta disabled" aria-disabled="true">Preview & Reorder</span>
                <span class="btn lead-banner-cta disabled" aria-disabled="true">Add Menu</span>
            @endif
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

    <div class="users-summary-grid menu-kpi-grid">
        <div class="card users-stat-card menu-kpi-card">
            <span class="users-stat-icon users-stat-icon--total">
                <svg viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['total'] }}</strong>
                <span>Total Menu</span>
            </div>
        </div>
        <div class="card users-stat-card menu-kpi-card">
            <span class="users-stat-icon users-stat-icon--super">
                <svg viewBox="0 0 24 24"><path d="m5 13 4 4L19 7"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['active'] }}</strong>
                <span>Active Menu</span>
            </div>
        </div>
        <div class="card users-stat-card menu-kpi-card">
            <span class="users-stat-icon users-stat-icon--other">
                <svg viewBox="0 0 24 24"><path d="M6 6l12 12"/><path d="M18 6 6 18"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $summary['inactive'] }}</strong>
                <span>Hidden Menu</span>
            </div>
        </div>
        <div class="card users-stat-card menu-kpi-card">
            <span class="users-stat-icon users-stat-icon--admin">
                <svg viewBox="0 0 24 24"><path d="M3 7h18"/><path d="M6 12h12"/><path d="M9 17h6"/></svg>
            </span>
            <div class="users-stat-body">
                <strong>{{ $totalSections }}</strong>
                <span>Total Sections</span>
            </div>
        </div>
    </div>

    <article class="lead-list-workspace menu-management-workspace">
        <div class="customer-alert info users-role-info menu-management-note">
            Perubahan di halaman ini langsung memengaruhi endpoint menu dinamis Vuexy dan route access per role.
        </div>

        <form method="GET" action="{{ route('admin.system.menus.index') }}" class="menu-filter-panel">
            <input type="hidden" name="quick" value="{{ $quickFilter }}">
            <div class="menu-filter-search">
                <input type="search" name="q" value="{{ $search }}" placeholder="Search menu, route, section, atau icon..." aria-label="Search Menu">
                <div class="menu-filter-search-actions">
                    <button type="submit" class="btn btn-sm btn-primary">Apply</button>
                    @if ($isMenuFilterActive)
                        <a href="{{ route('admin.system.menus.index') }}" class="btn btn-sm btn-muted">Reset</a>
                    @endif
                </div>
            </div>

            <div class="menu-filter-main-row">
                <nav class="lead-filter-chips menu-quick-chips" aria-label="Quick menu filters">
                    @foreach ($quickFilters as $quickValue => $quickLabel)
                        @php
                            $quickQuery = request()->query();
                            if ($quickValue === '') {
                                unset($quickQuery['quick']);
                            } else {
                                $quickQuery['quick'] = $quickValue;
                            }
                        @endphp
                        <a href="{{ route('admin.system.menus.index', $quickQuery) }}" @class(['active' => $quickFilter === $quickValue])>{{ $quickLabel }}</a>
                    @endforeach
                </nav>

                <div class="menu-filter-compact-row">
                    <select name="section" aria-label="Filter Section">
                        <option value="">All Sections</option>
                        @foreach ($sectionOptions as $section)
                            <option value="{{ $section }}" @selected($sectionFilter === $section)>{{ $sectionLabels[$section] ?? str($section)->headline() }}</option>
                        @endforeach
                    </select>
                    <select name="status" aria-label="Filter Status">
                        <option value="">All Status</option>
                        <option value="active" @selected($statusFilter === 'active')>Active</option>
                        <option value="inactive" @selected($statusFilter === 'inactive')>Hidden</option>
                    </select>
                    <select name="role_visibility" aria-label="Filter Role Visibility">
                        <option value="">All Visibility</option>
                        <option value="__all_roles" @selected($roleFilter === '__all_roles')>All Roles</option>
                        @foreach ($roleOptions as $roleName)
                            <option value="{{ $roleName }}" @selected($roleFilter === $roleName)>{{ $roleName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <nav class="menu-section-counter-chips" aria-label="Section counters">
                @foreach ($sectionOptions as $section)
                    @php
                        $sectionQuery = request()->query();
                        if ($sectionFilter === $section) {
                            unset($sectionQuery['section']);
                        } else {
                            $sectionQuery['section'] = $section;
                        }
                    @endphp
                    <a href="{{ route('admin.system.menus.index', $sectionQuery) }}" @class(['active' => $sectionFilter === $section])>
                        {{ $sectionLabels[$section] ?? str($section)->headline() }} <span>({{ $sectionCounts[$section] ?? 0 }})</span>
                    </a>
                @endforeach
            </nav>
        </form>

        @if ($isMenuFilterActive)
            <div class="customer-alert info users-role-info menu-management-note">
                Filter aktif. Untuk reorder drag and drop, gunakan halaman Preview & Reorder agar urutan sibling tidak terpotong filter.
            </div>
        @endif

        <div class="menu-tree-toolbar">
            <div>
                <span class="crm-record-kicker">TREE NAVIGATION</span>
                <h2>Sidebar Menu Structure</h2>
                <p>Menu ditampilkan berdasarkan section dan hierarchy parent-child yang tersimpan di database.</p>
            </div>
            <span class="users-result-label">{{ $visibleMenus->count() }} Menus Shown</span>
        </div>

        @forelse ($menusBySection as $section => $sectionMenus)
            @php
                $sectionLabel = $sectionLabels[$section] ?? str($section)->headline();
                $activeCount = $sectionMenus->where('is_active', true)->count();
            @endphp
            <details class="menu-tree-section" open>
                <summary>
                    <span class="menu-tree-section-icon">
                        @include('admin.partials.sidebar-icon', ['icon' => $section === 'dashboard' ? 'dashboard' : 'menu'])
                    </span>
                    <span>
                        <strong>{{ $sectionLabel }} ({{ $sectionMenus->count() }})</strong>
                        <small>{{ $activeCount }} active / {{ $sectionMenus->count() }} menu</small>
                    </span>
                    <span class="menu-badge menu-badge--section">Section</span>
                </summary>

                <div class="menu-tree-list">
                    @foreach ($sectionMenus->sortBy([['parent_id', 'asc'], ['sort_order', 'asc'], ['title', 'asc']])->values() as $menu)
                        <div class="menu-tree-row" id="menu-{{ $menu->id }}" style="--menu-depth: {{ (int) $menu->depth }};">
                            <div class="menu-tree-main">
                                <span class="menu-tree-branch" aria-hidden="true">{{ $menu->depth > 0 ? '└─' : '•' }}</span>
                                <span class="menu-tree-icon">
                                    @include('admin.partials.sidebar-icon', ['icon' => $menu->icon ?: 'menu'])
                                </span>
                                <div class="menu-tree-title">
                                    <strong>{{ $menu->title }}</strong>
                                    <small>
                                        @if ($menu->parent?->title)
                                            Parent: {{ $menu->parent->title }}
                                        @else
                                            Root menu
                                        @endif
                                        • Sort {{ $menu->sort_order }}
                                        @if ($menu->children->isNotEmpty())
                                            • {{ $menu->children->count() }} submenu
                                        @endif
                                    </small>
                                </div>
                            </div>

                            <div class="menu-tree-badges">
                                @if ($menu->route)
                                    <span class="menu-badge menu-badge--route">{{ $menu->route }}</span>
                                @else
                                    <span class="menu-badge menu-badge--muted">Group only</span>
                                @endif

                                @if ($menu->permission_name)
                                    <span class="menu-badge menu-badge--permission">{{ $menu->permission_name }}</span>
                                @else
                                    <span class="menu-badge menu-badge--active">Public</span>
                                @endif

                                <span class="menu-badge {{ $menu->is_active ? 'menu-badge--active' : 'menu-badge--inactive' }}">
                                    {{ $menu->is_active ? 'Active' : 'Inactive' }}
                                </span>

                                @if ($menu->roles->isNotEmpty())
                                    @foreach ($menu->roles as $role)
                                        <span class="menu-badge menu-badge--role">{{ $role->name }}</span>
                                    @endforeach
                                @else
                                    <span class="menu-badge menu-badge--role">All Roles</span>
                                @endif
                            </div>

                            <details class="lead-row-menu menu-action-menu">
                                <summary aria-label="Menu actions">⋯</summary>
                                <div>
                                    <a href="{{ route('admin.system.menus.preview') }}#menu-{{ $menu->id }}">View</a>
                                    <a href="{{ route('admin.system.menus.edit', $menu) }}">Edit</a>
                                    <a href="{{ route('admin.system.menus.create', ['duplicate' => $menu->id]) }}">Duplicate</a>
                                    <form method="POST" action="{{ route('admin.system.menus.destroy', $menu) }}" data-confirm-en="Delete this menu?" data-confirm-id="Hapus menu ini?" onsubmit="return confirm(this.dataset.confirmCurrent || 'Hapus menu ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    @endforeach
                </div>
            </details>
        @empty
            <div class="lead-empty-state menu-empty-state">
                <svg viewBox="0 0 24 24"><path d="M4 6h16"/><path d="M4 12h16"/><path d="M4 18h16"/></svg>
                <strong>Tidak ada menu ditemukan</strong>
                <p>
                    @if ($isMenuFilterActive)
                        Tidak ada menu yang cocok dengan filter saat ini. Coba reset filter atau tambahkan menu baru.
                    @else
                        Struktur menu belum tersedia. Tambahkan menu pertama untuk membangun sidebar CRM.
                    @endif
                </p>
                <div class="table-actions">
                    @if ($isMenuFilterActive)
                        <a href="{{ route('admin.system.menus.index') }}" class="btn btn-muted btn-sm">Clear Filter</a>
                    @endif
                    @if ($menuFeatureReady ?? true)
                        <a href="{{ route('admin.system.menus.create') }}" class="btn btn-primary btn-sm">Add Menu</a>
                    @endif
                </div>
            </div>
        @endforelse
    </article>
</section>
@endsection
