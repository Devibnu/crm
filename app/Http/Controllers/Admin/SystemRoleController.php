<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Support\RbacPermissions;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SystemRoleController extends Controller
{
    public function index(): View
    {
        return view('admin.system.roles.index', [
            'roles' => Role::query()
                ->withCount(['permissions', 'users'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.system.roles.create', [
            'role' => new Role(['guard_name' => 'web']),
            'permissionGroups' => RbacPermissions::groups(),
            'selectedPermissions' => old('permissions', []),
            'menuGroups' => $this->menuGroups(),
            'selectedMenuIds' => old('menu_ids', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => ['required', 'string', 'max:255', 'alpha_dash:ascii', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ], $this->menuValidationRules()));

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);
        $this->syncRoleMenus($role, $validated['menu_ids'] ?? []);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.system.roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function show(Role $role): View
    {
        $selectedMenuIds = $this->selectedMenuIds($role);

        return view('admin.system.roles.show', [
            'role' => $role->load(['permissions', 'users']),
            'permissionGroups' => RbacPermissions::groups(),
            'menuGroups' => $this->menuGroups(),
            'selectedMenuIds' => $selectedMenuIds,
        ]);
    }

    public function edit(Role $role): View
    {
        $selectedMenuIds = $this->selectedMenuIds($role);

        return view('admin.system.roles.edit', [
            'role' => $role->load('permissions'),
            'permissionGroups' => RbacPermissions::groups(),
            'selectedPermissions' => old('permissions', $role->permissions->pluck('name')->all()),
            'menuGroups' => $this->menuGroups(),
            'selectedMenuIds' => old('menu_ids', $selectedMenuIds),
        ]);
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate(array_merge([
            'name' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ], $this->menuValidationRules()));

        if ($role->name === 'super_admin') {
            $role->syncPermissions(RbacPermissions::all());
            $this->syncRoleMenus($role, $this->allActiveMenuIds());
        } else {
            $role->update(['name' => $validated['name']]);
            $role->syncPermissions($validated['permissions'] ?? []);
            $this->syncRoleMenus($role, $validated['menu_ids'] ?? []);
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

    protected function menuGroups(): array
    {
        if (! $this->menuFeatureReady()) {
            return [];
        }

        $sectionLabels = [
            'dashboard' => 'Dashboard',
            'service-management' => 'Service Management',
            'sales-enablement' => 'Sales Enablement',
            'marketing-automation' => 'Marketing Automation',
            'customer-profile-360' => 'Customer Profile 360',
            'system' => 'System',
        ];

        $menus = Menu::query()
            ->active()
            ->ordered()
            ->get(['id', 'parent_id', 'section', 'title', 'route', 'icon']);

        return collect($sectionLabels)
            ->mapWithKeys(function (string $label, string $section) use ($menus) {
                $sectionMenus = $menus->where('section', $section)->values();

                return [$label => $this->buildMenuTree($sectionMenus)];
            })
            ->filter(fn (array $items) => $items !== [])
            ->all();
    }

    protected function buildMenuTree(Collection $menus, ?int $parentId = null, int $depth = 0): array
    {
        return $menus
            ->filter(fn (Menu $menu) => $menu->parent_id === $parentId)
            ->map(function (Menu $menu) use ($menus, $depth) {
                return [
                    'id' => $menu->id,
                    'title' => $menu->title,
                    'route' => $menu->route,
                    'depth' => $depth,
                    'children' => $this->buildMenuTree($menus, $menu->id, $depth + 1),
                ];
            })
            ->values()
            ->all();
    }

    protected function selectedMenuIds(Role $role): array
    {
        if (! $this->menuFeatureReady()) {
            return [];
        }

        return DB::table('role_menu')
            ->where('role_id', $role->id)
            ->pluck('menu_id')
            ->map(fn ($menuId) => (int) $menuId)
            ->all();
    }

    protected function syncRoleMenus(Role $role, array $menuIds): void
    {
        if (! $this->menuFeatureReady()) {
            return;
        }

        $menuIds = $this->expandMenuIdsWithAncestors($menuIds);

        DB::table('role_menu')->where('role_id', $role->id)->delete();

        $payload = $menuIds
            ->map(fn (int $menuId) => [
                'role_id' => $role->id,
                'menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if ($payload !== []) {
            DB::table('role_menu')->insert($payload);
        }
    }

    protected function expandMenuIdsWithAncestors(array $menuIds): \Illuminate\Support\Collection
    {
        $selectedIds = collect($menuIds)
            ->map(fn ($menuId) => (int) $menuId)
            ->filter()
            ->unique()
            ->values();

        if ($selectedIds->isEmpty()) {
            return collect();
        }

        $parentMap = Menu::query()
            ->whereIn('id', $selectedIds->all())
            ->orWhereIn('parent_id', $selectedIds->all())
            ->pluck('parent_id', 'id');

        $resolvedIds = $selectedIds->values();

        foreach ($selectedIds as $menuId) {
            $parentId = $parentMap->get($menuId);

            while ($parentId !== null) {
                $resolvedIds->push((int) $parentId);
                $parentId = Menu::query()->whereKey($parentId)->value('parent_id');
            }
        }

        return $resolvedIds->filter()->unique()->values();
    }

    protected function allActiveMenuIds(): array
    {
        if (! $this->menuFeatureReady()) {
            return [];
        }

        return Menu::query()
            ->active()
            ->pluck('id')
            ->map(fn ($menuId) => (int) $menuId)
            ->all();
    }

    protected function menuFeatureReady(): bool
    {
        return Schema::hasTable('menus') && Schema::hasTable('role_menu');
    }

    protected function menuValidationRules(): array
    {
        if (! $this->menuFeatureReady()) {
            return [];
        }

        return [
            'menu_ids' => ['nullable', 'array'],
            'menu_ids.*' => ['integer', Rule::exists('menus', 'id')],
        ];
    }
}
