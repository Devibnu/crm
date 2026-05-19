@php
    $groupDescriptions = [
        'Customer Profile 360' => [
            'en' => 'Access customer data, profiles, and interaction history.',
            'id' => 'Akses data pelanggan, profil, dan riwayat interaksi.',
        ],
        'Sales Enablement' => [
            'en' => 'Access leads, opportunities, pipeline, activities, quotations, and win/loss.',
            'id' => 'Akses lead, opportunity, pipeline, activity, quotation, dan win/loss.',
        ],
        'Service Management' => [
            'en' => 'Access ticketing, omnichannel, SLA, cases, CSAT, and knowledge base.',
            'id' => 'Akses ticketing, omnichannel, SLA, case, CSAT, dan knowledge base.',
        ],
        'Marketing Automation' => [
            'en' => 'Access campaigns, audiences, executions, landing pages, social, automation, and lead scoring.',
            'id' => 'Akses campaign, audience, execution, landing page, social, automation, dan lead scoring.',
        ],
        'System' => [
            'en' => 'Access user, role, and permission administration.',
            'id' => 'Akses pengelolaan user, role, dan permission.',
        ],
    ];

    $isSuperAdmin = $role->name === 'super_admin';
    $selectedPermissions = $selectedPermissions ?? [];
    $selectedMenuIds = collect($selectedMenuIds ?? [])->map(fn ($menuId) => (int) $menuId)->all();

    $resourceLabels = [
        'customers' => 'Customer',
        'interactions' => 'Interactions',
        'leads' => 'Leads',
        'opportunities' => 'Opportunities',
        'pipeline' => 'Pipeline',
        'activities' => 'Activities',
        'quotations' => 'Quotations',
        'winloss' => 'Win/Loss',
        'tickets' => 'Tickets',
        'omnichannel' => 'Omnichannel',
        'sla' => 'SLA',
        'cases' => 'Cases',
        'csat' => 'CSAT',
        'knowledge' => 'Knowledge',
        'campaigns' => 'Campaigns',
        'audiences' => 'Audiences',
        'executions' => 'Executions',
        'landing_pages' => 'Landing Pages',
        'social' => 'Social',
        'automations' => 'Automations',
        'lead_scoring' => 'Lead Scoring',
        'users' => 'Users',
        'roles' => 'Roles',
    ];

    $actions = [
        'view' => ['en' => 'View', 'id' => 'Lihat'],
        'create' => ['en' => 'Create', 'id' => 'Buat'],
        'update' => ['en' => 'Update', 'id' => 'Ubah'],
        'delete' => ['en' => 'Delete', 'id' => 'Hapus'],
    ];
    $totalPermissionCount = collect($permissionGroups)->flatten()->count();

    $flattenMenuNodes = function (array $nodes) use (&$flattenMenuNodes) {
        return collect($nodes)->flatMap(function (array $node) use (&$flattenMenuNodes) {
            return collect([$node])->merge($flattenMenuNodes($node['children'] ?? []));
        });
    };

    $matrixGroups = collect($permissionGroups)
        ->map(function ($permissions) use ($resourceLabels) {
            return collect($permissions)
                ->mapToGroups(function ($permission) {
                    [$resource, $action] = array_pad(explode('.', $permission, 2), 2, 'other');

                    return [$resource => [
                        'name' => $permission,
                        'action' => $action,
                    ]];
                })
                ->map(function ($resourcePermissions, $resource) use ($resourceLabels) {
                    return [
                        'resource' => $resource,
                        'label' => $resourceLabels[$resource] ?? str($resource)->replace('_', ' ')->title()->toString(),
                        'permissions' => $resourcePermissions->keyBy('action')->all(),
                    ];
                })
                ->values()
                ->all();
        })
        ->all();

    $menuGroupsMeta = collect($menuGroups ?? [])
        ->map(function (array $menus, string $groupLabel) use ($flattenMenuNodes, $selectedMenuIds) {
            $flatMenus = $flattenMenuNodes($menus)->values();

            return [
                'key' => str($groupLabel)->slug()->toString(),
                'label' => $groupLabel,
                'menus' => $menus,
                'root_count' => count($menus),
                'total_count' => $flatMenus->count(),
                'selected_count' => $flatMenus->filter(fn (array $item) => in_array((int) $item['id'], $selectedMenuIds, true))->count(),
            ];
        })
        ->values()
        ->all();

    $dashboardGroupMeta = collect($menuGroupsMeta)->firstWhere('label', 'Dashboard');
    $navigationMenuGroupsMeta = collect($menuGroupsMeta)
        ->reject(fn (array $group) => $group['label'] === 'Dashboard')
        ->values()
        ->all();

    $dashboardMatrixNodes = collect(data_get($dashboardGroupMeta, 'menus', []))
        ->flatMap(function (array $node) {
            if (($node['title'] ?? null) === 'Dashboard' && ! empty($node['children'])) {
                return collect($node['children']);
            }

            return collect([$node]);
        })
        ->values()
        ->all();

    $totalMenuCount = collect($menuGroupsMeta)->sum('total_count');
    $selectedMenuCount = collect($menuGroupsMeta)->sum('selected_count');
    $selectedPermissionCount = $isSuperAdmin ? $totalPermissionCount : count($selectedPermissions);

    $renderMenuNodes = function (array $nodes) use (&$renderMenuNodes, $selectedMenuIds, $isSuperAdmin): string {
        $html = '';

        foreach ($nodes as $node) {
            $depth = (int) ($node['depth'] ?? 0);
            $title = e((string) ($node['title'] ?? ''));
            $route = (string) ($node['route'] ?? '');
            $checked = in_array((int) $node['id'], $selectedMenuIds, true) || $isSuperAdmin ? 'checked' : '';
            $disabled = $isSuperAdmin ? 'disabled' : '';
            $indent = min($depth, 4) * 20;
            $isParent = ! empty($node['children']);
            $badge = $isParent ? 'Parent' : 'Link';
            $badgeId = $isParent ? 'Induk' : 'Link';
            $keywords = e(str(implode(' ', [$node['title'] ?? '', $route, $badge]))->lower()->toString());

            $html .= '<label class="menu-access-item" data-menu-item data-search-keywords="'.$keywords.'" style="--menu-depth: '.$indent.'px">';
            $html .= '<input type="checkbox" name="menu_ids[]" value="'.(int) $node['id'].'" '.$checked.' '.$disabled.'>';
            $html .= '<span class="menu-access-copy">';
            $html .= '<span class="menu-access-copy-head"><strong>'.$title.'</strong><span class="menu-access-badge" data-lang-en="'.$badge.'" data-lang-id="'.$badgeId.'">'.$badge.'</span></span>';
            if ($route !== '') {
                $html .= '<small>'.e($route).'</small>';
            } else {
                $html .= '<small data-lang-en="Parent container" data-lang-id="Kontainer induk">Parent container</small>';
            }
            $html .= '</span>';
            $html .= '</label>';

            if (! empty($node['children'])) {
                $html .= $renderMenuNodes($node['children']);
            }
        }

        return $html;
    };
@endphp

<div class="role-form-shell" data-role-permission-form>
    <div class="role-permission-layout">
        <aside class="role-permission-left">
            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2 data-lang-en="Role Info" data-lang-id="Info Role">Role Info</h2>
                        <p data-lang-en="Role identity and its primary access context." data-lang-id="Identitas role dan konteks akses utamanya.">Identitas role dan konteks akses utamanya.</p>
                    </div>
                </div>

                @if ($isSuperAdmin)
                    <div class="role-protected-alert" data-lang-en="Super admin role is protected" data-lang-id="Role super admin dilindungi">Super admin role is protected</div>
                @endif

                <label class="role-name-field">
                    <span data-lang-en="Role Name" data-lang-id="Nama Role">Role Name</span>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $role->name) }}"
                        placeholder="sales_manager"
                        data-placeholder-en="sales_manager"
                        data-placeholder-id="sales_manager"
                        required
                        @readonly($isSuperAdmin)
                    >
                    <small data-lang-en="Use lowercase letters and underscores. Example: sales_manager" data-lang-id="Gunakan huruf kecil dan underscore. Contoh: sales_manager">Gunakan huruf kecil dan underscore. Contoh: sales_manager</small>
                    @error('name')<small class="error">{{ $message }}</small>@enderror
                </label>
            </section>

            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2 data-lang-en="Access Summary" data-lang-id="Ringkasan Akses">Access Summary</h2>
                        <p data-lang-en="Summary of selected permissions and active menus." data-lang-id="Ringkasan pilihan permission dan menu aktif.">Ringkasan pilihan permission dan menu aktif.</p>
                    </div>
                </div>

                <div class="role-summary-grid">
                    <article class="role-summary-tile">
                        <span data-lang-en="Permissions" data-lang-id="Permission">Permissions</span>
                        <strong><span data-selected-permission-count>{{ $selectedPermissionCount }}</span> / {{ $totalPermissionCount }}</strong>
                        <small data-lang-en="Selected from the permission matrix." data-lang-id="Dipilih dari permission matrix.">Dipilih dari permission matrix.</small>
                    </article>
                    <article class="role-summary-tile">
                        <span data-lang-en="Menus" data-lang-id="Menu">Menus</span>
                        <strong><span data-selected-menu-count>{{ $selectedMenuCount }}</span> / <span data-total-menu-count>{{ $totalMenuCount }}</span></strong>
                        <small data-lang-en="Menus that will appear in the sidebar." data-lang-id="Menu yang akan tampil di sidebar.">Menu yang akan tampil di sidebar.</small>
                    </article>
                </div>
            </section>

            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2 data-lang-en="Quick Actions" data-lang-id="Aksi Cepat">Quick Actions</h2>
                        <p data-lang-en="Bulk selection for menus or permissions." data-lang-id="Pilih massal untuk menu atau permission.">Pilih massal untuk menu atau permission.</p>
                    </div>
                </div>

                <div class="role-bulk-actions">
                    <div class="role-bulk-action-group">
                        <span class="role-bulk-action-label" data-lang-en="Menu Access" data-lang-id="Akses Menu">Menu Access</span>
                        <div class="role-quick-actions">
                            <button type="button" class="btn btn-primary" data-select-all-menus @disabled($isSuperAdmin) data-lang-en="Select All Menus" data-lang-id="Pilih Semua Menu">Select All Menus</button>
                            <button type="button" class="btn btn-muted" data-clear-all-menus @disabled($isSuperAdmin) data-lang-en="Clear All Menus" data-lang-id="Kosongkan Semua Menu">Clear All Menus</button>
                        </div>
                    </div>

                    <div class="role-bulk-action-group">
                        <span class="role-bulk-action-label" data-lang-en="Permission Matrix" data-lang-id="Matriks Permission">Permission Matrix</span>
                        <div class="role-quick-actions">
                            <button type="button" class="btn btn-primary" data-select-all-permissions @disabled($isSuperAdmin) data-lang-en="Select All Permissions" data-lang-id="Pilih Semua Permission">Select All Permissions</button>
                            <button type="button" class="btn btn-muted" data-clear-all-permissions @disabled($isSuperAdmin) data-lang-en="Clear All Permissions" data-lang-id="Kosongkan Semua Permission">Clear All Permissions</button>
                        </div>
                    </div>
                </div>
            </section>
        </aside>

        <section class="role-permission-right">
            @if ($navigationMenuGroupsMeta !== [])
                <article class="permission-matrix-card menu-access-card">
                    <div class="permission-matrix-head menu-access-head">
                        <div>
                            <h2 data-lang-en="Menu Access" data-lang-id="Akses Menu">Menu Access</h2>
                            <p data-lang-en="Dashboard and module menus are grouped by section so parent-child structure and routes are easier to read." data-lang-id="Dashboard dan menu modul disusun per section agar parent-child dan route lebih mudah dibaca.">Dashboard dan menu modul disusun per section agar parent-child dan route lebih mudah dibaca.</p>
                        </div>
                        <div class="menu-access-head-meta">
                            <span class="menu-access-overall-badge">
                                <span data-selected-menu-count>{{ $selectedMenuCount }}</span>
                                <span data-lang-en="of" data-lang-id="dari">of</span>
                                <span data-total-menu-count>{{ $totalMenuCount }}</span>
                                <span data-lang-en="selected" data-lang-id="dipilih">selected</span>
                            </span>
                        </div>
                    </div>

                    <div class="role-panel-toolbar">
                        <label class="role-panel-search">
                            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                            <input type="search" placeholder="Cari menu, dashboard, route..." data-placeholder-en="Search menu, dashboard, route..." data-placeholder-id="Cari menu, dashboard, route..." data-menu-search>
                        </label>
                        <div class="role-panel-toolbar-actions">
                            <button type="button" class="btn btn-muted" data-expand-all-menus data-lang-en="Expand All" data-lang-id="Buka Semua">Expand All</button>
                            <button type="button" class="btn btn-muted" data-collapse-all-menus data-lang-en="Collapse All" data-lang-id="Tutup Semua">Collapse All</button>
                        </div>
                    </div>

                    <div class="menu-access-grid">
                        @foreach ($navigationMenuGroupsMeta as $group)
                            <section class="menu-access-group is-open" data-menu-section>
                                <div class="menu-access-group-head" data-menu-group-toggle role="button" tabindex="0" aria-expanded="true">
                                    <span class="menu-access-group-title">
                                        <strong>{{ $group['label'] }}</strong>
                                        <small>
                                            <span>{{ $group['root_count'] }}</span>
                                            <span data-lang-en="root items" data-lang-id="menu utama">root items</span>,
                                            <span>{{ $group['total_count'] }}</span>
                                            <span data-lang-en="total entries" data-lang-id="total entri">total entries</span>
                                        </small>
                                    </span>
                                    <span class="menu-access-group-meta">
                                        <span class="menu-access-count-badge" data-menu-group-counter="{{ $group['key'] }}">
                                            <span data-menu-group-selected>{{ $group['selected_count'] }}</span>
                                            <span data-lang-en="of" data-lang-id="dari">of</span>
                                            <span data-menu-group-total>{{ $group['total_count'] }}</span>
                                            <span data-lang-en="selected" data-lang-id="dipilih">selected</span>
                                        </span>
                                        <label class="menu-access-select-all" onclick="event.stopPropagation(); event.preventDefault(); this.querySelector('input').click();">
                                            <input type="checkbox" data-select-menu-group="{{ $group['key'] }}" @disabled($isSuperAdmin)>
                                            <span data-lang-en="Select All" data-lang-id="Pilih Semua">Select All</span>
                                        </label>
                                        <span class="permission-group-chevron" aria-hidden="true">&#9662;</span>
                                    </span>
                                </div>
                                <div class="menu-access-group-body" data-menu-group-items="{{ $group['key'] }}">
                                    {!! $renderMenuNodes($group['menus']) !!}
                                </div>
                            </section>
                        @endforeach
                    </div>
                    @error('menu_ids')<small class="error role-form-inline-error">{{ $message }}</small>@enderror
                    @error('menu_ids.*')<small class="error role-form-inline-error">{{ $message }}</small>@enderror
                </article>
            @endif

            <article class="permission-matrix-card">
                <div class="permission-matrix-head">
                    <div>
                        <h2 data-lang-en="Permission Matrix" data-lang-id="Matriks Permission">Permission Matrix</h2>
                        <p data-lang-en="Choose access by resource and action." data-lang-id="Pilih akses berdasarkan resource dan action.">Pilih akses berdasarkan resource dan action.</p>
                    </div>
                </div>

                @if ($dashboardMatrixNodes !== [])
                    <div class="dashboard-matrix-shell">
                        <div class="dashboard-matrix-head">
                            <div>
                                <h3 data-lang-en="Dashboard Access" data-lang-id="Akses Dashboard">Dashboard Access</h3>
                                <p data-lang-en="Dashboard access is managed from role menus and shown here to stay consistent with the permission matrix." data-lang-id="Akses dashboard dikelola dari role menu dan ditampilkan di sini agar konsisten dengan permission matrix.">Akses dashboard dikelola dari role menu dan ditampilkan di sini agar konsisten dengan permission matrix.</p>
                            </div>
                            <div class="dashboard-matrix-meta">
                                <span class="menu-access-count-badge" data-dashboard-counter>
                                    <span data-dashboard-selected>0</span>
                                    <span data-lang-en="of" data-lang-id="dari">of</span>
                                    <span data-dashboard-total>{{ count($dashboardMatrixNodes) }}</span>
                                    <span data-lang-en="selected" data-lang-id="dipilih">selected</span>
                                </span>
                                <label class="menu-access-select-all">
                                    <input type="checkbox" data-select-dashboard-group @disabled($isSuperAdmin)>
                                    <span data-lang-en="Select All" data-lang-id="Pilih Semua">Select All</span>
                                </label>
                            </div>
                        </div>
                        <div class="dashboard-matrix-grid" data-dashboard-group-items>
                            @foreach ($dashboardMatrixNodes as $node)
                                @php
                                    $route = (string) ($node['route'] ?? '');
                                    $checked = in_array((int) $node['id'], $selectedMenuIds, true) || $isSuperAdmin;
                                @endphp
                                <label class="dashboard-matrix-item" data-dashboard-item>
                                    <input
                                        type="checkbox"
                                        name="menu_ids[]"
                                        value="{{ (int) $node['id'] }}"
                                        @checked($checked)
                                        @disabled($isSuperAdmin)
                                    >
                                    <span class="dashboard-matrix-copy">
                                        <strong>{{ $node['title'] }}</strong>
                                        @if ($route !== '')
                                            <small>{{ $route }}</small>
                                        @else
                                            <small data-lang-en="Dashboard route" data-lang-id="Route dashboard">Dashboard route</small>
                                        @endif
                                    </span>
                                    <span class="dashboard-matrix-pill" data-lang-en="Visible" data-lang-id="Tampil">Visible</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="role-panel-toolbar">
                    <label class="role-panel-search">
                        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                        <input type="search" placeholder="Cari module, resource, permission..." data-placeholder-en="Search module, resource, permission..." data-placeholder-id="Cari module, resource, permission..." data-permission-search>
                    </label>
                    <div class="role-panel-toolbar-actions">
                        <button type="button" class="btn btn-muted" data-expand-all-permissions data-lang-en="Expand All" data-lang-id="Buka Semua">Expand All</button>
                        <button type="button" class="btn btn-muted" data-collapse-all-permissions data-lang-en="Collapse All" data-lang-id="Tutup Semua">Collapse All</button>
                    </div>
                </div>

                <div class="permission-accordion-stack">
                    @foreach ($matrixGroups as $group => $resources)
                        @php $groupKey = 'permission-group-'.$loop->index; @endphp
                        <div class="permission-group-card is-open" data-permission-group>
                            <div class="permission-group-header" data-group-toggle role="button" tabindex="0" aria-expanded="true">
                                <span class="permission-group-title">
                                    <strong class="permission-group-name">{{ $group }}</strong>
                                    <span class="permission-group-desc" data-lang-en="{{ data_get($groupDescriptions, $group.'.en', 'Permissions for this module.') }}" data-lang-id="{{ data_get($groupDescriptions, $group.'.id', 'Permission untuk modul ini.') }}">{{ data_get($groupDescriptions, $group.'.id', 'Permission untuk modul ini.') }}</span>
                                </span>
                                <span class="permission-group-meta">
                                    <span class="permission-count-badge">
                                        <span>{{ count($permissionGroups[$group]) }}</span>
                                        <span data-lang-en="permissions" data-lang-id="permission">permissions</span>
                                    </span>
                                    <span class="permission-selected-counter" data-group-counter="{{ $groupKey }}">
                                        <span data-permission-group-selected>0</span>
                                        <span data-lang-en="of" data-lang-id="dari">of</span>
                                        <span data-permission-group-total>{{ count($permissionGroups[$group]) }}</span>
                                        <span data-lang-en="selected" data-lang-id="dipilih">selected</span>
                                    </span>
                                    <label class="permission-select-all" onclick="event.stopPropagation(); event.preventDefault(); this.querySelector('input').click();">
                                        <input type="checkbox" data-select-group="{{ $groupKey }}" @disabled($isSuperAdmin) onclick="event.stopPropagation()">
                                        <span data-lang-en="Select All" data-lang-id="Pilih Semua">Select All</span>
                                    </label>
                                    <span class="permission-group-chevron" aria-hidden="true">&#9662;</span>
                                </span>
                            </div>

                            <div class="permission-matrix-table-wrap" data-permission-group-items="{{ $groupKey }}">
                                <table class="permission-matrix-table">
                                    <thead>
                                        <tr>
                                            <th data-lang-en="Module / Resource" data-lang-id="Modul / Resource">Module / Resource</th>
                                            @foreach ($actions as $actionLabel)
                                                <th data-lang-en="{{ $actionLabel['en'] }}" data-lang-id="{{ $actionLabel['id'] }}">{{ $actionLabel['en'] }}</th>
                                            @endforeach
                                            <th data-lang-en="Other" data-lang-id="Lainnya">Other</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($resources as $resource)
                                            @php
                                                $otherPermissions = collect($resource['permissions'])
                                                    ->reject(fn ($permission, $action) => array_key_exists($action, $actions));
                                            @endphp
                                            <tr data-permission-row data-search-keywords="{{ str(implode(' ', array_merge([$resource['label']], collect($resource['permissions'])->pluck('name')->all())))->lower()->toString() }}">
                                                <td class="permission-resource-cell">{{ $resource['label'] }}</td>
                                                @foreach ($actions as $action => $actionLabel)
                                                    @php $permission = $resource['permissions'][$action] ?? null; @endphp
                                                    <td class="permission-action-cell">
                                                        @if ($permission)
                                                            <label class="permission-cell-check">
                                                                <input
                                                                    type="checkbox"
                                                                    name="permissions[]"
                                                                    value="{{ $permission['name'] }}"
                                                                    @checked(in_array($permission['name'], $selectedPermissions, true) || $isSuperAdmin)
                                                                    @disabled($isSuperAdmin)
                                                                >
                                                                <span data-lang-en="{{ $actionLabel['en'] }}" data-lang-id="{{ $actionLabel['id'] }}">{{ $actionLabel['en'] }}</span>
                                                            </label>
                                                        @else
                                                            <span class="permission-empty">-</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="permission-action-cell">
                                                    @forelse ($otherPermissions as $permission)
                                                        <label class="permission-cell-check">
                                                            <input
                                                                type="checkbox"
                                                                name="permissions[]"
                                                                value="{{ $permission['name'] }}"
                                                                @checked(in_array($permission['name'], $selectedPermissions, true) || $isSuperAdmin)
                                                                @disabled($isSuperAdmin)
                                                            >
                                                            <span>{{ str($permission['action'])->headline() }}</span>
                                                        </label>
                                                    @empty
                                                        <span class="permission-empty">-</span>
                                                    @endforelse
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            </article>
        </section>
    </div>

    <div class="role-form-footer">
        <a href="{{ route('admin.system.roles.index') }}" class="btn btn-muted" data-lang-en="Cancel" data-lang-id="Batal">Cancel</a>
        <button
            type="submit"
            class="btn btn-primary"
            data-lang-en="{{ $mode === 'create' ? 'Create Role' : 'Update Role' }}"
            data-lang-id="{{ $mode === 'create' ? 'Buat Role' : 'Ubah Role' }}"
        >{{ $mode === 'create' ? 'Create Role' : 'Update Role' }}</button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-role-permission-form]');

        if (!form) return;

        const permissionCheckboxes = () => Array.from(form.querySelectorAll('input[name="permissions[]"]:not(:disabled)'));
        const menuCheckboxes = () => Array.from(form.querySelectorAll('input[name="menu_ids[]"]:not(:disabled)'));
        const dashboardCheckboxes = () => Array.from(form.querySelectorAll('[data-dashboard-group-items] input[name="menu_ids[]"]:not(:disabled)'));
        const groupToggles = () => Array.from(form.querySelectorAll('[data-select-group]:not(:disabled)'));
        const menuGroupToggles = () => Array.from(form.querySelectorAll('[data-select-menu-group]:not(:disabled)'));
        const permissionSearch = form.querySelector('[data-permission-search]');
        const menuSearch = form.querySelector('[data-menu-search]');
        const menuSections = () => Array.from(form.querySelectorAll('[data-menu-section]'));
        const permissionGroups = () => Array.from(form.querySelectorAll('[data-permission-group]'));

        const syncPermissionSummary = () => {
            const selectedCount = permissionCheckboxes().filter((checkbox) => checkbox.checked).length;

            form.querySelectorAll('[data-selected-permission-count]').forEach((node) => {
                node.textContent = selectedCount;
            });
        };

        const syncMenuSummary = () => {
            const selectedCount = menuCheckboxes().filter((checkbox) => checkbox.checked).length;
            const totalCount = menuCheckboxes().length;

            form.querySelectorAll('[data-selected-menu-count]').forEach((node) => {
                node.textContent = selectedCount;
            });

            form.querySelectorAll('[data-total-menu-count]').forEach((node) => {
                node.textContent = totalCount;
            });
        };

        const syncDashboardGroupToggle = () => {
            const toggle = form.querySelector('[data-select-dashboard-group]');
            const counter = form.querySelector('[data-dashboard-counter]');
            const checkboxes = dashboardCheckboxes();
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

            if (toggle) {
                toggle.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                toggle.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            }

            if (counter) {
                const selectedNode = counter.querySelector('[data-dashboard-selected]');
                const totalNode = counter.querySelector('[data-dashboard-total]');

                if (selectedNode) {
                    selectedNode.textContent = checkedCount;
                }

                if (totalNode) {
                    totalNode.textContent = checkboxes.length;
                }
            }
        };

        const syncGroupToggle = (toggle) => {
            const group = form.querySelector(`[data-permission-group-items="${toggle.dataset.selectGroup}"]`);
            const checkboxes = Array.from(group.querySelectorAll('input[name="permissions[]"]:not(:disabled)'));
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const counter = form.querySelector(`[data-group-counter="${toggle.dataset.selectGroup}"]`);

            toggle.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            toggle.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;

            if (counter) {
                const selectedNode = counter.querySelector('[data-permission-group-selected]');
                const totalNode = counter.querySelector('[data-permission-group-total]');

                if (selectedNode) {
                    selectedNode.textContent = checkedCount;
                }

                if (totalNode) {
                    totalNode.textContent = checkboxes.length;
                }
            }
        };

        const syncMenuGroupToggle = (toggle) => {
            const group = form.querySelector(`[data-menu-group-items="${toggle.dataset.selectMenuGroup}"]`);
            const checkboxes = Array.from(group.querySelectorAll('input[name="menu_ids[]"]:not(:disabled)'));
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const counter = form.querySelector(`[data-menu-group-counter="${toggle.dataset.selectMenuGroup}"]`);

            toggle.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            toggle.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;

            if (counter) {
                const selectedNode = counter.querySelector('[data-menu-group-selected]');
                const totalNode = counter.querySelector('[data-menu-group-total]');

                if (selectedNode) {
                    selectedNode.textContent = checkedCount;
                }

                if (totalNode) {
                    totalNode.textContent = checkboxes.length;
                }
            }
        };

        const syncCellStates = () => {
            form.querySelectorAll('.permission-cell-check').forEach((cell) => {
                cell.classList.toggle('is-checked', cell.querySelector('input')?.checked);
            });

            form.querySelectorAll('.permission-matrix-table tbody tr').forEach((row) => {
                row.classList.toggle('has-permission', row.querySelector('input[name="permissions[]"]:checked'));
            });
        };

        const syncMenuStates = () => {
            form.querySelectorAll('.menu-access-item').forEach((item) => {
                item.classList.toggle('is-checked', item.querySelector('input[name="menu_ids[]"]')?.checked);
            });

            form.querySelectorAll('.dashboard-matrix-item').forEach((item) => {
                item.classList.toggle('is-checked', item.querySelector('input[name="menu_ids[]"]')?.checked);
            });
        };

        const syncAllGroupToggles = () => {
            groupToggles().forEach(syncGroupToggle);
            syncCellStates();
            syncPermissionSummary();
        };

        const syncAllMenuGroupToggles = () => {
            menuGroupToggles().forEach(syncMenuGroupToggle);
            syncDashboardGroupToggle();
            syncMenuStates();
            syncMenuSummary();
        };

        const setPermissionGroupOpen = (card, isOpen) => {
            card.classList.toggle('is-open', isOpen);
            card.querySelector('[data-group-toggle]')?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        const setMenuSectionOpen = (card, isOpen) => {
            card.classList.toggle('is-open', isOpen);
            card.querySelector('[data-menu-group-toggle]')?.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        };

        const applyPermissionSearch = () => {
            const keyword = (permissionSearch?.value || '').trim().toLowerCase();

            permissionGroups().forEach((group) => {
                const rows = Array.from(group.querySelectorAll('[data-permission-row]'));
                let visibleCount = 0;

                rows.forEach((row) => {
                    const haystack = row.dataset.searchKeywords || row.textContent.toLowerCase();
                    const visible = keyword === '' || haystack.includes(keyword);
                    row.hidden = !visible;

                    if (visible) visibleCount += 1;
                });

                group.hidden = visibleCount === 0;

                if (keyword !== '' && visibleCount > 0) {
                    setPermissionGroupOpen(group, true);
                }
            });
        };

        const applyMenuSearch = () => {
            const keyword = (menuSearch?.value || '').trim().toLowerCase();

            menuSections().forEach((section) => {
                const items = Array.from(section.querySelectorAll('[data-menu-item]'));
                let visibleCount = 0;

                items.forEach((item) => {
                    const haystack = item.dataset.searchKeywords || item.textContent.toLowerCase();
                    const visible = keyword === '' || haystack.includes(keyword);
                    item.hidden = !visible;

                    if (visible) visibleCount += 1;
                });

                section.hidden = visibleCount === 0;

                if (keyword !== '' && visibleCount > 0) {
                    setMenuSectionOpen(section, true);
                }
            });
        };

        groupToggles().forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const group = form.querySelector(`[data-permission-group-items="${toggle.dataset.selectGroup}"]`);
                group.querySelectorAll('input[name="permissions[]"]:not(:disabled)').forEach((checkbox) => {
                    checkbox.checked = toggle.checked;
                });
                syncAllGroupToggles();
            });
        });

        menuGroupToggles().forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const group = form.querySelector(`[data-menu-group-items="${toggle.dataset.selectMenuGroup}"]`);
                group.querySelectorAll('input[name="menu_ids[]"]:not(:disabled)').forEach((checkbox) => {
                    checkbox.checked = toggle.checked;
                });
                syncAllMenuGroupToggles();
            });
        });

        form.querySelector('[data-select-all-permissions]')?.addEventListener('click', () => {
            permissionCheckboxes().forEach((checkbox) => {
                checkbox.checked = true;
            });
            syncAllGroupToggles();
        });

        form.querySelector('[data-clear-all-permissions]')?.addEventListener('click', () => {
            permissionCheckboxes().forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncAllGroupToggles();
        });

        form.querySelector('[data-select-all-menus]')?.addEventListener('click', () => {
            menuCheckboxes().forEach((checkbox) => {
                checkbox.checked = true;
            });
            syncAllMenuGroupToggles();
        });

        form.querySelector('[data-clear-all-menus]')?.addEventListener('click', () => {
            menuCheckboxes().forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncAllMenuGroupToggles();
        });

        form.querySelector('[data-select-dashboard-group]')?.addEventListener('change', (event) => {
            dashboardCheckboxes().forEach((checkbox) => {
                checkbox.checked = event.currentTarget.checked;
            });
            syncAllMenuGroupToggles();
        });

        form.querySelector('[data-expand-all-permissions]')?.addEventListener('click', () => {
            permissionGroups().forEach((group) => setPermissionGroupOpen(group, true));
        });

        form.querySelector('[data-collapse-all-permissions]')?.addEventListener('click', () => {
            permissionGroups().forEach((group) => setPermissionGroupOpen(group, false));
        });

        form.querySelector('[data-expand-all-menus]')?.addEventListener('click', () => {
            menuSections().forEach((section) => setMenuSectionOpen(section, true));
        });

        form.querySelector('[data-collapse-all-menus]')?.addEventListener('click', () => {
            menuSections().forEach((section) => setMenuSectionOpen(section, false));
        });

        permissionSearch?.addEventListener('input', applyPermissionSearch);
        menuSearch?.addEventListener('input', applyMenuSearch);

        permissionCheckboxes().forEach((checkbox) => checkbox.addEventListener('change', syncAllGroupToggles));
        menuCheckboxes().forEach((checkbox) => checkbox.addEventListener('change', syncAllMenuGroupToggles));
        syncAllGroupToggles();
        syncAllMenuGroupToggles();
        applyPermissionSearch();
        applyMenuSearch();

        form.querySelectorAll('[data-group-toggle]').forEach((header) => {
            header.addEventListener('click', (e) => {
                if (e.target.closest('.permission-select-all')) return;
                const card = header.closest('[data-permission-group]');
                setPermissionGroupOpen(card, !card.classList.contains('is-open'));
            });
            header.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (!e.target.closest('.permission-select-all')) header.click();
                }
            });
        });

        form.querySelectorAll('[data-menu-group-toggle]').forEach((header) => {
            header.addEventListener('click', (e) => {
                if (e.target.closest('.menu-access-select-all')) return;
                const card = header.closest('[data-menu-section]');
                setMenuSectionOpen(card, !card.classList.contains('is-open'));
            });
            header.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (!e.target.closest('.menu-access-select-all')) header.click();
                }
            });
        });
    });
</script>
