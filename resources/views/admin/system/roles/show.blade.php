@extends('admin.layouts.app')

@section('title', 'Role Detail - Krakatau CRM')

@section('content')
    @php
        $isProtected = $role->name === 'super_admin';
        $roleInitials = collect(explode('_', $role->name))->filter()->take(2)->map(fn ($part) => strtoupper($part[0]))->join('');
        $groupIcons = [
            'Customer Profile 360' => 'C360',
            'Sales Enablement' => 'S',
            'Service Management' => 'CS',
            'Marketing Automation' => 'M',
            'WhatsApp Marketing' => 'WA',
            'System' => 'SYS',
        ];
        $groupDescriptions = [
            'Customer Profile 360' => 'Akses data customer, interaksi, dan profil pelanggan.',
            'Sales Enablement' => 'Akses lead, opportunity, pipeline, aktivitas, dan quotation.',
            'Service Management' => 'Akses ticket, omnichannel, SLA, dan knowledge base.',
            'Marketing Automation' => 'Akses campaign, audience, automation, dan aktivitas marketing.',
            'WhatsApp Marketing' => 'Akses provider, Cloud API, template, broadcast, dan reply WhatsApp.',
            'System' => 'Akses pengelolaan user, role, dan konfigurasi sistem.',
        ];
        $actionLabels = ['view' => 'Lihat', 'create' => 'Tambah', 'update' => 'Ubah', 'delete' => 'Hapus'];
        $sectionOrder = ['Sales Enablement', 'Customer Profile 360', 'WhatsApp Marketing', 'Service Management', 'Marketing Automation', 'System'];
        $orderedPermissionGroups = collect($sectionOrder)
            ->filter(fn ($section) => isset($permissionGroups[$section]))
            ->mapWithKeys(fn ($section) => [$section => $permissionGroups[$section]]);
        $roleHasPermission = fn (string $permission): bool => $isProtected || $role->hasPermissionTo($permission);
        $totalActivePermissions = $isProtected
            ? collect($permissionGroups)->flatten()->unique()->count()
            : $role->permissions->count();
        $totalActiveModules = $isProtected
            ? collect($permissionGroups)->flatten()->map(fn ($permission) => str($permission)->before('.')->toString())->unique()->count()
            : $role->permissions->map(fn ($permission) => str($permission->name)->before('.')->toString())->unique()->count();
        $friendlyPermissionLabel = function (string $permission) use ($permissionResourceLabels, $actionLabels): string {
            if ($permission === 'pipeline.view') {
                return 'Kelola Pipeline';
            }

            [$resource, $action] = array_pad(explode('.', $permission, 2), 2, '');
            $resourceLabel = $permissionResourceLabels[$resource] ?? str($resource)->replace('_', ' ')->title();
            $actionLabel = $actionLabels[$action] ?? 'Kelola';

            return $actionLabel.' '.$resourceLabel;
        };
    @endphp

    <section class="service-page customer-list-page role-detail-page">
        <header class="lead-list-header users-page-header">
            <div>
                <span class="crm-record-kicker">System Management</span>
                <h1>Role Detail</h1>
                <p>Kelola detail role, permission, dan user terkait.</p>
            </div>
            <div class="users-detail-header-actions">
                <a href="{{ route('admin.system.roles.edit', $role) }}" class="btn btn-primary">Edit Role</a>
                <a href="{{ route('admin.system.roles.index') }}" class="btn btn-muted">Back to Roles</a>
            </div>
        </header>

        @include('admin.system.roles.partials.alerts')

        <section class="card role-profile-summary" aria-label="Role summary">
            <div class="role-profile-identity">
                <span class="role-profile-avatar">{{ $roleInitials }}</span>
                <div>
                    <span class="users-profile-kicker">Access Role</span>
                    <h2>{{ $role->name }}</h2>
                    <p>Role akses untuk pengguna Krakatau CRM.</p>
                </div>
            </div>
            <div class="role-profile-facts">
                <div><span>Total Permissions</span><strong>{{ $totalActivePermissions }}</strong></div>
                <div><span>Active Modules</span><strong>{{ $totalActiveModules }}</strong></div>
                <div><span>Total Users</span><strong>{{ $role->users->count() }}</strong></div>
                <div><span>Status</span><strong class="{{ $isProtected ? 'roles-protected-badge' : 'role-editable-badge' }}">{{ $isProtected ? 'Protected' : 'Editable' }}</strong></div>
            </div>
        </section>

        <section class="role-readonly-access-section">
            <header class="role-detail-section-head">
                <div><span>Read-only Access</span><h2>Akses Menu &amp; Fitur</h2></div>
                <p>{{ $totalActiveModules }} module aktif dalam {{ $orderedPermissionGroups->count() }} section.</p>
            </header>

            <div class="role-readonly-section-list">
                @foreach ($orderedPermissionGroups as $group => $permissions)
                    @php
                        $modules = collect($permissions)->groupBy(fn ($permission) => str($permission)->before('.')->toString());
                        $activeModuleCount = $modules->filter(fn ($modulePermissions) => $modulePermissions->contains(fn ($permission) => $roleHasPermission($permission)))->count();
                    @endphp
                    <article class="card role-readonly-section-card">
                        <header>
                            <span class="role-module-icon">{{ $groupIcons[$group] ?? strtoupper(substr($group, 0, 1)) }}</span>
                            <div>
                                <h3>{{ $group }}</h3>
                                <p>{{ $groupDescriptions[$group] ?? 'Akses menu dan fitur pada module ini.' }}</p>
                            </div>
                            <strong class="role-module-active-badge">{{ $activeModuleCount }}/{{ $modules->count() }} module aktif</strong>
                        </header>
                        <div class="role-readonly-modules">
                            @foreach ($modules as $prefix => $modulePermissions)
                                @php
                                    $activePermissions = $modulePermissions->filter(fn ($permission) => $roleHasPermission($permission))->values();
                                    $activeActions = $activePermissions->map(fn ($permission) => str($permission)->after('.')->toString());
                                    $hasFullAccess = collect(['view', 'create', 'update', 'delete'])->every(fn ($action) => $activeActions->contains($action));
                                    $accessStatus = $activePermissions->isEmpty()
                                        ? ['label' => 'No Access', 'class' => 'is-none']
                                        : ($hasFullAccess
                                            ? ['label' => 'Full Access', 'class' => 'is-full']
                                            : ($activePermissions->count() === 1 && $activeActions->contains('view')
                                                ? ['label' => 'View Only', 'class' => 'is-view']
                                                : ['label' => 'Custom', 'class' => 'is-custom']));
                                @endphp
                                <details class="role-readonly-module">
                                    <summary>
                                        <span class="role-readonly-module-state {{ $activePermissions->isNotEmpty() ? 'is-active' : '' }}" aria-hidden="true"></span>
                                        <strong>{{ $permissionResourceLabels[$prefix] ?? str($prefix)->replace('_', ' ')->title() }}</strong>
                                        <span class="role-access-status {{ $accessStatus['class'] }}">{{ $accessStatus['label'] }}</span>
                                        <small>{{ $activePermissions->count() }}/{{ $modulePermissions->count() }} akses aktif</small>
                                        <i aria-hidden="true">▾</i>
                                    </summary>
                                    <div class="role-readonly-permission-detail">
                                        <p>Lihat detail akses</p>
                                        <div>
                                            @foreach ($modulePermissions as $permission)
                                                <span @class(['is-active' => $roleHasPermission($permission)])>
                                                    <strong>{{ $friendlyPermissionLabel($permission) }}</strong>
                                                    <small>{{ $permission }}</small>
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                </details>
                            @endforeach
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <div class="role-detail-bottom-grid">
            <section class="card role-impact-card">
                <header><span>Impact</span><h2>Role Impact</h2></header>
                <p>Role ini mengatur akses menu dan fitur untuk seluruh user terkait. Perubahan permission akan langsung diterapkan pada akses mereka.</p>
                <div><strong>{{ $role->users->count() }}</strong><span>user terdampak</span></div>
            </section>

            <section class="card role-users-card">
                <header><span>Users</span><h2>Assigned Users</h2></header>
                <div class="role-assigned-users">
                    @forelse ($role->users->take(5) as $user)
                        <a href="{{ route('admin.system.users.show', $user) }}">
                            <span>{{ collect(explode(' ', $user->name))->filter()->take(2)->map(fn ($word) => strtoupper($word[0]))->join('') }}</span>
                            <div><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></div>
                        </a>
                    @empty
                        <p>Belum ada user yang menggunakan role ini.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </section>
@endsection
