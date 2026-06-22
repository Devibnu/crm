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
                <div><span>Total Permissions</span><strong>{{ $role->permissions->count() }}</strong></div>
                <div><span>Total Users</span><strong>{{ $role->users->count() }}</strong></div>
                <div><span>Status</span><strong class="{{ $isProtected ? 'roles-protected-badge' : 'role-editable-badge' }}">{{ $isProtected ? 'Protected' : 'Editable' }}</strong></div>
            </div>
        </section>

        <section class="role-permissions-section">
            <header class="role-detail-section-head">
                <div><span>Access Overview</span><h2>Akses Menu &amp; Fitur</h2></div>
                <p>{{ $role->permissions->count() }} permission aktif pada {{ count($permissionGroups) }} module.</p>
            </header>

            <p class="role-permissions-intro">Permission menentukan menu apa saja yang bisa dilihat dan aksi apa saja yang bisa dilakukan oleh role ini.</p>

            <aside class="card role-access-guide" aria-label="Cara membaca akses">
                <div><span>?</span><h3>Cara membaca akses</h3></div>
                <ul>
                    <li><strong>View</strong><span>User bisa melihat data</span></li>
                    <li><strong>Create</strong><span>User bisa menambah data</span></li>
                    <li><strong>Update</strong><span>User bisa mengubah data</span></li>
                    <li><strong>Delete</strong><span>User bisa menghapus data</span></li>
                </ul>
            </aside>

            <div class="role-module-grid">
                @foreach ($permissionGroups as $group => $permissions)
                    @php
                        $activePermissions = collect($permissions)->filter(fn ($permission) => $role->hasPermissionTo($permission))->values();
                        $activeModules = $activePermissions->groupBy(fn ($permission) => str($permission)->before('.')->toString());
                    @endphp
                    <article class="card role-module-card">
                        <header>
                            <span class="role-module-icon">{{ $groupIcons[$group] ?? strtoupper(substr($group, 0, 1)) }}</span>
                            <div>
                                <h3>{{ $group }}</h3>
                                <p>{{ $groupDescriptions[$group] ?? 'Akses menu dan fitur pada module ini.' }}</p>
                            </div>
                            <strong class="role-module-active-badge">{{ $activePermissions->count() }} akses aktif</strong>
                        </header>
                        @if ($activeModules->isNotEmpty())
                            <div class="role-sidebar-module-list">
                                @foreach ($activeModules as $prefix => $modulePermissions)
                                    <section class="role-sidebar-module">
                                        <header>
                                            <h4>{{ $permissionResourceLabels[$prefix] ?? str($prefix)->replace('_', ' ')->title() }}</h4>
                                            <span>{{ $modulePermissions->count() }} akses</span>
                                        </header>
                                        <div class="role-module-permissions">
                                            @foreach ($modulePermissions as $permission)
                                                <div class="role-permission-item">
                                                    <strong>{{ $friendlyPermissionLabel($permission) }}</strong>
                                                    <small>{{ $permission }}</small>
                                                </div>
                                            @endforeach
                                        </div>
                                    </section>
                                @endforeach
                            </div>
                        @else
                            <p class="role-module-empty">Tidak ada permission aktif pada module ini.</p>
                        @endif
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
