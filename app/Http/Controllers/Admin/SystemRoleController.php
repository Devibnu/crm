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
        return view('admin.system.roles.create', [
            'role' => new Role(['guard_name' => 'web']),
            'permissionGroups' => RbacPermissions::groups(),
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
        return view('admin.system.roles.show', [
            'role' => $role->load(['permissions', 'users']),
            'permissionGroups' => RbacPermissions::groups(),
        ]);
    }

    public function edit(Role $role): View
    {
        return view('admin.system.roles.edit', [
            'role' => $role->load('permissions'),
            'permissionGroups' => RbacPermissions::groups(),
            'selectedPermissions' => old('permissions', $role->permissions->pluck('name')->all()),
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
            $role->syncPermissions(RbacPermissions::all());
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
}
