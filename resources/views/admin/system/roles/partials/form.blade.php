@php
    $isSuperAdmin = $role->name === 'super_admin';

    // Action labels yang user-friendly
    $actionLabels = [
        'view' => 'Lihat',
        'create' => 'Tambah',
        'update' => 'Ubah',
        'delete' => 'Hapus',
    ];

    $standardActions = ['view', 'create', 'update', 'delete'];

    $mergedResourceMap = [
        'omnichannel_notes' => 'omnichannel',
    ];

    $mergedResourceSubgroups = [
        'omnichannel' => [
            'omnichannel' => 'Inbox / Conversation',
            'omnichannel_notes' => 'Internal Notes',
        ],
    ];

    // Build permission matrix dari seluruh permission database yang sudah dikelompokkan per module UI.
    $permissionMatrix = [];
    
    foreach ($permissionGroups as $groupName => $permissions) {
        $resources = [];
        
        foreach ($permissions as $permission) {
            [$rawResource, $action] = array_pad(explode('.', $permission, 2), 2, 'other');
            $resource = $mergedResourceMap[$rawResource] ?? $rawResource;
            $subgroupLabel = $mergedResourceSubgroups[$resource][$rawResource] ?? null;
            
            if (!isset($resources[$resource])) {
                $resources[$resource] = [];
            }
            
            $resources[$resource][] = [
                'name' => $permission,
                'action' => $action,
                'raw_resource' => $rawResource,
                'subgroup' => $subgroupLabel,
            ];
        }
        
        // Build resource entries
        $resourceEntries = [];
        foreach ($resources as $resource => $perms) {
            $resourceLabel = $permissionResourceLabels[$resource] ?? str($resource)->replace('_', ' ')->title()->toString();
            
            $resourceEntry = [
                'resource' => $resource,
                'label' => $resourceLabel,
                'permissionGroups' => [],
            ];

            foreach (collect($perms)->groupBy(fn ($perm) => $perm['subgroup'] ?? '__default') as $subgroup => $subgroupPerms) {
                $permissionGroup = [
                    'label' => $subgroup === '__default' ? null : $subgroup,
                    'permissions' => [],
                    'other' => [],
                ];

                foreach ($subgroupPerms as $perm) {
                    if (in_array($perm['action'], $standardActions)) {
                        $permissionGroup['permissions'][$perm['action']] = $perm;
                    } else {
                        $permissionGroup['other'][] = $perm;
                    }
                }

                $resourceEntry['permissionGroups'][] = $permissionGroup;
            }
            
            $resourceEntries[] = $resourceEntry;
        }
        
        $permissionMatrix[$groupName] = $resourceEntries;
    }
@endphp

<div class="role-form-shell" data-role-permission-form data-role-form-mode="{{ $mode }}">
    <div class="role-permission-layout">
        <aside class="role-permission-left">
            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2>Info Role</h2>
                        <p>Identitas role dan kontrol cepat.</p>
                    </div>
                </div>

                @if ($isSuperAdmin)
                    <div class="role-protected-alert">⚠️ Role super admin dilindungi</div>
                @endif

                <label class="role-name-field">
                    <span>Nama Role <b>*</b></span>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $role->name) }}"
                        placeholder="sales_manager"
                        required
                        @readonly($isSuperAdmin)
                    >
                    <small>Wajib diisi. Gunakan huruf kecil dan underscore, contoh: sales_manager.</small>
                    @error('name')<small class="error">{{ $message }}</small>@enderror
                </label>
            </section>

            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2>Kontrol Cepat</h2>
                        <p>Gunakan untuk semua permission.</p>
                    </div>
                </div>
                <div class="role-quick-actions">
                    <button type="button" class="btn btn-primary" data-select-all-permissions @disabled($isSuperAdmin)>Pilih Semua</button>
                    <button type="button" class="btn btn-muted" data-clear-all-permissions @disabled($isSuperAdmin)>Bersihkan Semua</button>
                </div>
            </section>

            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2>Preset Role</h2>
                        <p>Pilih template akses untuk memulai lebih cepat.</p>
                    </div>
                </div>
                <div class="role-preset-grid">
                    <button type="button" data-role-preset="sales" @disabled($isSuperAdmin)>Sales</button>
                    <button type="button" data-role-preset="marketing" @disabled($isSuperAdmin)>Marketing</button>
                    <button type="button" data-role-preset="service" @disabled($isSuperAdmin)>Customer Service</button>
                    <button type="button" data-role-preset="manager" @disabled($isSuperAdmin)>Manager</button>
                    <button type="button" data-role-preset="administrator" @disabled($isSuperAdmin)>Administrator</button>
                </div>
                <small class="role-preset-status" data-role-preset-status>Pilih preset atau atur akses secara manual.</small>
            </section>

            <section class="role-form-card">
                <div class="role-form-card-header">
                    <div>
                        <h2>Panduan Akses</h2>
                        <p>Arti aksi yang dapat diberikan.</p>
                    </div>
                </div>
                <div class="role-access-guide-items">
                    <div class="guide-item">
                        <strong>Lihat</strong>
                        <span>User bisa membuka data</span>
                    </div>
                    <div class="guide-item">
                        <strong>Tambah</strong>
                        <span>User bisa membuat data baru</span>
                    </div>
                    <div class="guide-item">
                        <strong>Ubah</strong>
                        <span>User bisa mengedit data</span>
                    </div>
                    <div class="guide-item">
                        <strong>Hapus</strong>
                        <span>User bisa menghapus data</span>
                    </div>
                </div>
            </section>
        </aside>

        <section class="role-permission-right">
            <article class="permission-matrix-card">
                <div class="permission-matrix-head">
                    <div>
                        <h2>Akses Menu &amp; Fitur</h2>
                        <p>Pilih menu dan aksi yang boleh digunakan oleh role ini.</p>
                        @if ($mode === 'create')
                            <small class="permission-required-help">Pilih minimal 1 akses agar role dapat digunakan.</small>
                            <small class="permission-validation-error" data-permission-validation hidden>Pilih minimal 1 permission sebelum membuat role.</small>
                        @endif
                    </div>
                </div>

                <div class="permission-accordion-stack">
        @foreach ($permissionMatrix as $groupName => $resources)
            @php
                $groupKey = 'permission-group-'.md5($groupName);
                $groupPermissionNames = collect($resources)
                    ->flatMap(fn ($resource) => collect($resource['permissionGroups'])
                        ->flatMap(fn ($permissionGroup) => array_merge(
                            array_column($permissionGroup['permissions'], 'name'),
                            array_column($permissionGroup['other'], 'name'),
                        )));
                $groupSelectedCount = $isSuperAdmin
                    ? $groupPermissionNames->count()
                    : $groupPermissionNames->filter(fn ($permission) => in_array($permission, $selectedPermissions, true))->count();
            @endphp
            <div class="permission-group-card" data-permission-group data-section-name="{{ $groupName }}">
                <div class="permission-group-header" data-group-toggle role="button" tabindex="0" aria-expanded="false">
                    <span class="permission-group-title">
                        <strong class="permission-group-name">{{ $groupName }}</strong>
                        <span class="permission-group-desc">{{ $groupPermissionNames->count() }} permission tersedia</span>
                    </span>
                    <span class="permission-group-meta">
                        <span class="permission-selected-counter" data-group-counter="{{ $groupKey }}">{{ $groupSelectedCount }} dari {{ $groupPermissionNames->count() }} akses dipilih</span>
                        <label class="permission-select-all" onclick="event.stopPropagation(); event.preventDefault(); this.querySelector('input').click();">
                            <input type="checkbox" data-select-group="{{ $groupKey }}" @checked($groupPermissionNames->isNotEmpty() && $groupSelectedCount === $groupPermissionNames->count()) @disabled($isSuperAdmin) onclick="event.stopPropagation()">
                            <span>Pilih Semua {{ $groupName }}</span>
                        </label>
                        <span class="permission-group-chevron" aria-hidden="true">▾</span>
                    </span>
                </div>

                <div class="permission-module-list" data-permission-group-items="{{ $groupKey }}">
                    @foreach ($resources as $resource)
                        @php
                            $moduleKey = $groupKey.'-'.md5($resource['resource']);
                            $modulePermissionNames = collect($resource['permissionGroups'])
                                ->flatMap(fn ($permissionGroup) => collect($permissionGroup['permissions'])->pluck('name')
                                    ->merge(collect($permissionGroup['other'])->pluck('name')));
                            $modulePermissionCount = $modulePermissionNames->count();
                            $moduleSelectedCount = $isSuperAdmin
                                ? $modulePermissionNames->count()
                                : $modulePermissionNames->filter(fn ($permission) => in_array($permission, $selectedPermissions, true))->count();
                        @endphp
                        <details class="permission-module-card" data-permission-module>
                            <summary>
                                <label class="permission-module-toggle" onclick="event.stopPropagation()">
                                    <input type="checkbox" data-select-module="{{ $moduleKey }}" @checked($modulePermissionNames->isNotEmpty() && $moduleSelectedCount === $modulePermissionNames->count()) @disabled($isSuperAdmin)>
                                    <span aria-hidden="true"></span>
                                </label>
                                <div class="permission-module-title">
                                    <strong>{{ $resource['label'] }}</strong>
                                    <small>Klik untuk melihat detail akses</small>
                                </div>
                                <span class="permission-module-counter" data-module-counter="{{ $moduleKey }}">{{ $moduleSelectedCount }}/{{ $modulePermissionCount }} akses aktif</span>
                                <i aria-hidden="true">▾</i>
                            </summary>
                            <div class="permission-module-actions" data-permission-module-items="{{ $moduleKey }}">
                                @foreach ($resource['permissionGroups'] as $permissionGroup)
                                    @if ($permissionGroup['label'])
                                        <h4 class="permission-module-subtitle">{{ $permissionGroup['label'] }}</h4>
                                    @endif
                                    @foreach (['view', 'create', 'update', 'delete'] as $action)
                                        @php $permission = $permissionGroup['permissions'][$action] ?? null; @endphp
                                        @if ($permission)
                                            <label class="permission-cell-check">
                                                <input
                                                    type="checkbox"
                                                    name="permissions[]"
                                                    value="{{ $permission['name'] }}"
                                                    @checked(in_array($permission['name'], $selectedPermissions, true) || $isSuperAdmin)
                                                    @disabled($isSuperAdmin)
                                                >
                                                <span>{{ $actionLabels[$action] }}<small>{{ $permission['name'] }}</small></span>
                                            </label>
                                        @endif
                                    @endforeach
                                    @foreach ($permissionGroup['other'] as $permission)
                                        <label class="permission-cell-check permission-other-check">
                                            <input
                                                type="checkbox"
                                                name="permissions[]"
                                                value="{{ $permission['name'] }}"
                                                @checked(in_array($permission['name'], $selectedPermissions, true) || $isSuperAdmin)
                                                @disabled($isSuperAdmin)
                                            >
                                            <span class="permission-other-label">Akses Lainnya<small>{{ $permission['name'] }}</small></span>
                                        </label>
                                    @endforeach
                                @endforeach
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        @endforeach
                </div>
            </article>
        </section>
    </div>

    <div class="role-form-footer">
        <p class="role-form-footer-note">Perubahan akses akan diterapkan setelah disimpan.</p>
        <div class="role-form-footer-actions">
            <a href="{{ route('admin.system.roles.index') }}" class="btn btn-muted">Batal</a>
            <button type="submit" class="btn btn-primary">{{ $mode === 'create' ? 'Buat Role' : 'Perbarui Role' }}</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.querySelector('[data-role-permission-form]');

        if (!form) return;

        const permissionCheckboxes = () => Array.from(form.querySelectorAll('input[name="permissions[]"]'));
        const editablePermissionCheckboxes = () => permissionCheckboxes().filter((checkbox) => !checkbox.disabled);
        const groupToggles = () => Array.from(form.querySelectorAll('[data-select-group]'));
        const editableGroupToggles = () => groupToggles().filter((toggle) => !toggle.disabled);
        const moduleToggles = () => Array.from(form.querySelectorAll('[data-select-module]'));
        const editableModuleToggles = () => moduleToggles().filter((toggle) => !toggle.disabled);

        const syncGroupToggle = (toggle) => {
            const group = form.querySelector(`[data-permission-group-items="${toggle.dataset.selectGroup}"]`);
            const checkboxes = Array.from(group.querySelectorAll('input[name="permissions[]"]'));
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const counter = form.querySelector(`[data-group-counter="${toggle.dataset.selectGroup}"]`);

            toggle.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            toggle.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;

            if (counter) {
                counter.textContent = `${checkedCount} dari ${checkboxes.length} akses dipilih`;
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

        const syncModuleToggle = (toggle) => {
            const module = form.querySelector(`[data-permission-module-items="${toggle.dataset.selectModule}"]`);
            const checkboxes = Array.from(module.querySelectorAll('input[name="permissions[]"]'));
            const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;
            const counter = form.querySelector(`[data-module-counter="${toggle.dataset.selectModule}"]`);

            toggle.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
            toggle.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
            if (counter) counter.textContent = `${checkedCount}/${checkboxes.length} akses aktif`;
        };

        const syncAllGroupToggles = () => {
            groupToggles().forEach(syncGroupToggle);
            moduleToggles().forEach(syncModuleToggle);
            syncCellStates();
        };

        editableGroupToggles().forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const group = form.querySelector(`[data-permission-group-items="${toggle.dataset.selectGroup}"]`);
                group.querySelectorAll('input[name="permissions[]"]:not(:disabled)').forEach((checkbox) => {
                    checkbox.checked = toggle.checked;
                });
                syncAllGroupToggles();
            });
        });

        editableModuleToggles().forEach((toggle) => {
            toggle.addEventListener('change', () => {
                const module = form.querySelector(`[data-permission-module-items="${toggle.dataset.selectModule}"]`);
                module.querySelectorAll('input[name="permissions[]"]:not(:disabled)').forEach((checkbox) => {
                    checkbox.checked = toggle.checked;
                });
                syncAllGroupToggles();
            });
        });

        form.querySelector('[data-select-all-permissions]')?.addEventListener('click', () => {
            editablePermissionCheckboxes().forEach((checkbox) => {
                checkbox.checked = true;
            });
            syncAllGroupToggles();
        });

        form.querySelector('[data-clear-all-permissions]')?.addEventListener('click', () => {
            editablePermissionCheckboxes().forEach((checkbox) => {
                checkbox.checked = false;
            });
            syncAllGroupToggles();
        });

        const presetMatches = (checkbox, preset) => {
            const permission = checkbox.value;
            const action = permission.split('.')[1] || '';
            const section = checkbox.closest('[data-section-name]')?.dataset.sectionName;

            if (preset === 'sales') return section === 'Sales Enablement';
            if (preset === 'marketing') return ['Marketing Automation', 'WhatsApp Marketing'].includes(section)
                && !permission.startsWith('whatsapp_providers.');
            if (preset === 'service') return section === 'Service Management'
                || ['customers.view', 'customers.update', 'interactions.view', 'interactions.create'].includes(permission);
            if (preset === 'manager') return ['view', 'update'].includes(action);
            if (preset === 'administrator') return !['users.delete', 'roles.delete'].includes(permission);

            return false;
        };

        form.querySelectorAll('[data-role-preset]').forEach((button) => {
            button.addEventListener('click', () => {
                editablePermissionCheckboxes().forEach((checkbox) => {
                    checkbox.checked = presetMatches(checkbox, button.dataset.rolePreset);
                });
                form.querySelectorAll('[data-role-preset]').forEach((item) => item.classList.toggle('is-active', item === button));
                const status = form.querySelector('[data-role-preset-status]');
                if (status) status.textContent = `Preset ${button.textContent.trim()} diterapkan. Anda masih dapat menyesuaikan akses.`;
                if (validationError) validationError.hidden = true;
                syncAllGroupToggles();
            });
        });

        const validationError = form.querySelector('[data-permission-validation]');
        const htmlForm = form.closest('form');

        editablePermissionCheckboxes().forEach((checkbox) => checkbox.addEventListener('change', () => {
            if (validationError && editablePermissionCheckboxes().some((item) => item.checked)) validationError.hidden = true;
            syncAllGroupToggles();
        }));

        if (form.dataset.roleFormMode === 'create') {
            htmlForm?.addEventListener('submit', (event) => {
                if (editablePermissionCheckboxes().some((checkbox) => checkbox.checked)) return;

                event.preventDefault();
                validationError.hidden = false;
                validationError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });
        }

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
