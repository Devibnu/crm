@php
    $groupDescriptions = [
        'Customer Profile 360' => 'Akses data pelanggan, profil, dan interaction history.',
        'Sales Enablement' => 'Akses lead, opportunity, pipeline, activity, quotation, dan win/loss.',
        'Service Management' => 'Akses ticketing, omnichannel, SLA, case, CSAT, dan knowledge base.',
        'Marketing Automation' => 'Akses campaign, audience, execution, landing page, social, automation, dan lead scoring.',
        'System' => 'Akses pengelolaan user, role, dan permission.',
    ];

    $isSuperAdmin = $role->name === 'super_admin';

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

    $actions = ['view' => 'View', 'create' => 'Create', 'update' => 'Update', 'delete' => 'Delete'];

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
@endphp

<div class="role-form-shell" data-role-permission-form>
    <div class="role-permission-layout">
        <aside class="role-permission-left">
            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2>Role Info</h2>
                        <p>Identitas role dan kontrol cepat permission.</p>
                    </div>
                </div>

                @if ($isSuperAdmin)
                    <div class="role-protected-alert">Super admin role is protected</div>
                @endif

                <label class="role-name-field">
                    <span>Role Name</span>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $role->name) }}"
                        placeholder="sales_manager"
                        required
                        @readonly($isSuperAdmin)
                    >
                    <small>Gunakan lowercase dan underscore. Contoh: sales_manager</small>
                    @error('name')<small class="error">{{ $message }}</small>@enderror
                </label>
            </section>

            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2>Quick Actions</h2>
                        <p>Terapkan pilihan permission secara cepat.</p>
                    </div>
                </div>
                <div class="role-quick-actions">
                    <button type="button" class="btn btn-primary" data-select-all-permissions @disabled($isSuperAdmin)>Select All Permissions</button>
                    <button type="button" class="btn btn-muted" data-clear-all-permissions @disabled($isSuperAdmin)>Clear All Permissions</button>
                </div>
            </section>
        </aside>

        <section class="role-permission-right">
            <article class="permission-matrix-card">
                <div class="permission-matrix-head">
                    <div>
                        <h2>Permission Matrix</h2>
                        <p>Pilih akses berdasarkan resource dan action.</p>
                    </div>
                </div>

                <div class="permission-accordion-stack">
        @foreach ($matrixGroups as $group => $resources)
            @php $groupKey = 'permission-group-'.$loop->index; @endphp
            <div class="permission-group-card is-open" data-permission-group>
                <div class="permission-group-header" data-group-toggle role="button" tabindex="0" aria-expanded="true">
                    <span class="permission-group-title">
                        <strong class="permission-group-name">{{ $group }}</strong>
                        <span class="permission-group-desc">{{ $groupDescriptions[$group] ?? 'Permission untuk module ini.' }}</span>
                    </span>
                    <span class="permission-group-meta">
                        <span class="permission-count-badge">{{ count($permissionGroups[$group]) }} permissions</span>
                        <span class="permission-selected-counter" data-group-counter="{{ $groupKey }}">0 of {{ count($permissionGroups[$group]) }} selected</span>
                        <label class="permission-select-all" onclick="event.stopPropagation(); event.preventDefault(); this.querySelector('input').click();">
                            <input type="checkbox" data-select-group="{{ $groupKey }}" @disabled($isSuperAdmin) onclick="event.stopPropagation()">
                            <span>Select All</span>
                        </label>
                        <span class="permission-group-chevron" aria-hidden="true">▾</span>
                    </span>
                </div>

                <div class="permission-matrix-table-wrap" data-permission-group-items="{{ $groupKey }}">
                    <table class="permission-matrix-table">
                        <thead>
                            <tr>
                                <th>Module / Resource</th>
                                @foreach ($actions as $actionLabel)
                                    <th>{{ $actionLabel }}</th>
                                @endforeach
                                <th>Other</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($resources as $resource)
                                @php
                                    $otherPermissions = collect($resource['permissions'])
                                        ->reject(fn ($permission, $action) => array_key_exists($action, $actions));
                                @endphp
                                <tr>
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
                                                    <span>{{ $actionLabel }}</span>
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
        <a href="{{ route('admin.system.roles.index') }}" class="btn btn-muted">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $mode === 'create' ? 'Create Role' : 'Update Role' }}</button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-role-permission-form]');

        if (!form) return;

        const permissionCheckboxes = () => Array.from(form.querySelectorAll('input[name="permissions[]"]:not(:disabled)'));
        const groupToggles = () => Array.from(form.querySelectorAll('[data-select-group]:not(:disabled)'));

        const syncGroupToggle = (toggle) => {
            const group = form.querySelector(`[data-permission-group-items="${toggle.dataset.selectGroup}"]`);
            const checkboxes = Array.from(group.querySelectorAll('input[name="permissions[]"]:not(:disabled)'));
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const counter = form.querySelector(`[data-group-counter="${toggle.dataset.selectGroup}"]`);

            toggle.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            toggle.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;

            if (counter) {
                counter.textContent = `${checkedCount} of ${checkboxes.length} selected`;
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

        const syncAllGroupToggles = () => {
            groupToggles().forEach(syncGroupToggle);
            syncCellStates();
        };

        groupToggles().forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const group = form.querySelector(`[data-permission-group-items="${toggle.dataset.selectGroup}"]`);
                group.querySelectorAll('input[name="permissions[]"]:not(:disabled)').forEach((checkbox) => {
                    checkbox.checked = toggle.checked;
                });
                syncGroupToggle(toggle);
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

        permissionCheckboxes().forEach((checkbox) => checkbox.addEventListener('change', syncAllGroupToggles));
        syncAllGroupToggles();

        // Accordion toggle (div-based, no details/summary quirks)
        form.querySelectorAll('[data-group-toggle]').forEach((header) => {
            header.addEventListener('click', (e) => {
                if (e.target.closest('.permission-select-all')) return;
                const card = header.closest('[data-permission-group]');
                const isOpen = card.classList.toggle('is-open');
                header.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
            header.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    if (!e.target.closest('.permission-select-all')) header.click();
                }
            });
        });
    });
</script>
