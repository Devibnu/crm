<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\RbacPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SystemRoleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('admin.system.roles.index', [
            'roles' => Role::query()
                ->withCount(['permissions', 'users'])
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->get(),
            'search' => $search,
            'summary' => [
                'roles' => Role::query()->count(),
                'permissions' => Permission::query()->count(),
                'protected_roles' => Role::query()->where('name', 'super_admin')->count(),
                'assigned_users' => Role::query()->withCount('users')->get()->sum('users_count'),
            ],
        ]);
    }

    public function create(): View
    {
        $permissionCatalog = $this->databasePermissionCatalog();

        return view('admin.system.roles.create', [
            'role' => new Role(['guard_name' => 'web']),
            'permissionGroups' => $permissionCatalog['groups'],
            'permissionResourceLabels' => $permissionCatalog['labels'],
            'selectedPermissions' => old('permissions', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'alpha_dash:ascii', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.system.roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function show(Role $role): View
    {
        $permissionCatalog = $this->databasePermissionCatalog();

        return view('admin.system.roles.show', [
            'role' => $role->load(['permissions', 'users']),
            'permissionGroups' => $permissionCatalog['groups'],
            'permissionResourceLabels' => $permissionCatalog['labels'],
        ]);
    }

    public function edit(Role $role): View
    {
        $permissionCatalog = $this->databasePermissionCatalog();
        $selectedPermissions = $role->name === 'super_admin'
            ? $permissionCatalog['permissions']
            : $role->load('permissions')->permissions->pluck('name')->all();

        return view('admin.system.roles.edit', [
            'role' => $role,
            'permissionGroups' => $permissionCatalog['groups'],
            'permissionResourceLabels' => $permissionCatalog['labels'],
            'selectedPermissions' => old('permissions', $selectedPermissions),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        if ($role->name === 'super_admin') {
            $role->syncPermissions(
                Permission::query()->where('guard_name', 'web')->pluck('name')->all(),
            );
        } else {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.system.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin') {
            return redirect()
                ->route('admin.system.roles.index')
                ->with('error', 'Role super_admin tidak dapat dihapus.');
        }

        if ($role->users()->exists()) {
            return redirect()
                ->route('admin.system.roles.index')
                ->with('error', 'Role yang masih dipakai user tidak dapat dihapus.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.system.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }

    /** @return array{groups: array<string, array<int, string>>, labels: array<string, string>, permissions: array<int, string>} */
    private function databasePermissionCatalog(): array
    {
        $menuMap = [
            'leads' => ['Sales Enablement', 'Lead Management'],
            'opportunities' => ['Sales Enablement', 'Opportunity Management'],
            'activities' => ['Sales Enablement', 'Sales Activity Tracking'],
            'quotations' => ['Sales Enablement', 'Quotation & Deal'],
            'pipeline' => ['Sales Enablement', 'Pipeline & Forecasting'],
            'winloss' => ['Sales Enablement', 'Win/Lost Analysis'],
            'customers' => ['Customer Profile 360', 'Customer List / Customer Profile'],
            'interactions' => ['Customer Profile 360', 'Interaction History'],
            'transactions' => ['Customer Profile 360', 'Transactions'],
            'preferences' => ['Customer Profile 360', 'Preferences'],
            'behavior' => ['Customer Profile 360', 'Behavior'],
            'audiences' => ['Marketing Automation', 'Audience Segmentation'],
            'lead_scoring' => ['Marketing Automation', 'Lead Scoring & Routing'],
            'campaigns' => ['Marketing Automation', 'Campaign Management'],
            'landing_pages' => ['Marketing Automation', 'Landing Page & Form'],
            'executions' => ['Marketing Automation', 'Campaign Execution'],
            'whatsapp_broadcasts' => ['WhatsApp Marketing', 'WhatsApp Broadcast'],
            'whatsapp_replies' => ['WhatsApp Marketing', 'WhatsApp Reply Inbox'],
            'whatsapp_templates' => ['WhatsApp Marketing', 'WhatsApp Templates'],
            'whatsapp_cloud_api' => ['WhatsApp Marketing', 'WhatsApp Cloud API'],
            'whatsapp_providers' => ['WhatsApp Marketing', 'WhatsApp Providers'],
            'social' => ['Marketing Automation', 'Social Media Engagement'],
            'automations' => ['Marketing Automation', 'Marketing Automation'],
            'omnichannel' => ['Service Management', 'Omnichannel Inbox'],
            'omnichannel_notes' => ['Service Management', 'Omnichannel Inbox'],
            'tickets' => ['Service Management', 'Ticket Management'],
            'sla' => ['Service Management', 'SLA Management'],
            'cases' => ['Service Management', 'Case Resolution'],
            'csat' => ['Service Management', 'Customer Satisfaction'],
            'knowledge' => ['Service Management', 'Knowledge Base'],
            'users' => ['System', 'Users'],
            'roles' => ['System', 'Roles & Permissions'],
            'menus' => ['System', 'Menu Management'],
            'branding' => ['System', 'Branding'],
        ];
        $groups = array_fill_keys([
            'Sales Enablement',
            'Customer Profile 360',
            'Marketing Automation',
            'WhatsApp Marketing',
            'Service Management',
            'System',
        ], []);
        $labels = [];

        $permissions = collect(RbacPermissions::all())
            ->merge(
                Permission::query()
                    ->where('guard_name', 'web')
                    ->pluck('name'),
            )
            ->unique()
            ->sort()
            ->values()
            ->all();

        foreach ($permissions as $permission) {
            $prefix = str($permission)->before('.')->toString();
            [$section, $label] = $menuMap[$prefix] ?? ['System', str($prefix)->replace('_', ' ')->title()->toString()];
            $groups[$section][] = $permission;
            $labels[$prefix] = $label;
        }

        return [
            'groups' => array_filter($groups),
            'labels' => $labels,
            'permissions' => $permissions,
        ];
    }
}
