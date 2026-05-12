<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Support\RbacPermissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionController extends Controller
{
    public function index(): View
    {
        return view('admin.system.roles.index', [
            'roles' => Role::query()->with('permissions')->orderBy('name')->get(),
            'permissionGroups' => RbacPermissions::groups(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'alpha_dash:ascii', 'unique:roles,name'],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(RbacPermissions::all())],
        ]);

        Role::create(['name' => $validated['name'], 'guard_name' => 'web'])
            ->syncPermissions($validated['permissions'] ?? []);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.system.roles.index')
            ->with('success', 'Role berhasil ditambahkan.');
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $validated = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(RbacPermissions::all())],
        ]);

        if ($role->name === 'super_admin') {
            $role->syncPermissions(RbacPermissions::all());
        } else {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.system.roles.index')
            ->with('success', 'Permission role berhasil diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'super_admin') {
            return redirect()
                ->route('admin.system.roles.index')
                ->with('error', 'Role super_admin tidak dapat dihapus.');
        }

        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('admin.system.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}
