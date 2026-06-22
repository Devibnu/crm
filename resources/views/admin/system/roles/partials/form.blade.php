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

    // Build permission matrix dari permissionGroups yang sudah di-group di RbacPermissions
    $permissionMatrix = [];
    
    foreach ($permissionGroups as $groupName => $permissions) {
        $resources = [];
        
        foreach ($permissions as $permission) {
            [$resource, $action] = array_pad(explode('.', $permission, 2), 2, 'other');
            
            if (!isset($resources[$resource])) {
                $resources[$resource] = [];
            }
            
            $resources[$resource][] = [
                'name' => $permission,
                'action' => $action,
            ];
        }
        
        // Build resource entries
        $resourceEntries = [];
        foreach ($resources as $resource => $perms) {
            $resourceLabel = str($resource)->replace('_', ' ')->title()->toString();
            
            $resourceEntry = [
                'resource' => $resource,
                'label' => $resourceLabel,
                'permissions' => [],
                'other' => [],
            ];
            
            // Organize permissions by action
            foreach ($perms as $perm) {
                if (in_array($perm['action'], $standardActions)) {
                    $resourceEntry['permissions'][$perm['action']] = $perm;
                } else {
                    $resourceEntry['other'][] = $perm;
                }
            }
            
            $resourceEntries[] = $resourceEntry;
        }
        
        $permissionMatrix[$groupName] = $resourceEntries;
    }
@endphp

<div class="role-form-shell" data-role-permission-form>
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
                    <span>Nama Role</span>
                    <input
                        type="text"
                        name="name"
                        value="{{ old('name', $role->name) }}"
                        placeholder="sales_manager"
                        required
                        @readonly($isSuperAdmin)
                    >
                    <small>Lowercase dan underscore. Contoh: sales_manager</small>
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
                        <h2>📖 Panduan Akses</h2>
                        <p>Arti setiap permission.</p>
                    </div>
                </div>
                <div class="role-access-guide-items">
                    <div class="guide-item">
                        <strong>👁 Lihat</strong>
                        <span>User bisa membuka dan melihat data</span>
                    </div>
                    <div class="guide-item">
                        <strong>➕ Tambah</strong>
                        <span>User bisa membuat data baru</span>
                    </div>
                    <div class="guide-item">
                        <strong>✏️ Ubah</strong>
                        <span>User bisa mengedit data</span>
                    </div>
                    <div class="guide-item">
                        <strong>🗑 Hapus</strong>
                        <span>User bisa menghapus data</span>
                    </div>
                </div>
            </section>
        </aside>

        <section class="role-permission-right">
            <article class="permission-matrix-card">
                <div class="permission-matrix-head">
                    <div>
                        <h2>🎯 Akses Menu & Fitur</h2>
                        <p>Pilih menu dan aksi yang boleh digunakan oleh role ini.</p>
                    </div>
                </div>

                <div class="permission-accordion-stack">
        @foreach ($permissionMatrix as $groupName => $resources)
            @php $groupKey = 'permission-group-'.md5($groupName); @endphp
            <div class="permission-group-card is-open" data-permission-group>
                <div class="permission-group-header" data-group-toggle role="button" tabindex="0" aria-expanded="true">
                    <span class="permission-group-title">
                        <strong class="permission-group-name">{{ $groupName }}</strong>
                        <span class="permission-group-desc">{{ count($resources) }} modul tersedia</span>
                    </span>
                    <span class="permission-group-meta">
                        <span class="permission-selected-counter" data-group-counter="{{ $groupKey }}">0 diaktifkan</span>
                        <label class="permission-select-all" onclick="event.stopPropagation(); event.preventDefault(); this.querySelector('input').click();">
                            <input type="checkbox" data-select-group="{{ $groupKey }}" @disabled($isSuperAdmin) onclick="event.stopPropagation()">
                            <span>Pilih Semua</span>
                        </label>
                        <span class="permission-group-chevron" aria-hidden="true">▾</span>
                    </span>
                </div>

                <div class="permission-matrix-table-wrap" data-permission-group-items="{{ $groupKey }}">
                    <table class="permission-matrix-table">
                        <thead>
                            <tr>
                                <th>Modul / Fitur</th>
                                <th>Lihat</th>
                                <th>Tambah</th>
                                <th>Ubah</th>
                                <th>Hapus</th>
                                <th>Akses Lainnya</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($resources as $resource)
                                <tr>
                                    <td class="permission-resource-cell">{{ $resource['label'] }}</td>
                                    @foreach (['view', 'create', 'update', 'delete'] as $action)
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
                                                    <span>{{ $actionLabels[$action] }}</span>
                                                </label>
                                            @else
                                                <span class="permission-empty">-</span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="permission-action-cell permission-other-cell">
                                        @forelse ($resource['other'] as $permission)
                                            <label class="permission-cell-check permission-other-check">
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
        <a href="{{ route('admin.system.roles.index') }}" class="btn btn-muted">Batal</a>
        <button type="submit" class="btn btn-primary">{{ $mode === 'create' ? 'Buat Role' : 'Perbarui Role' }}</button>
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
                counter.textContent = `${checkedCount} diaktifkan`;
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
