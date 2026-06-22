<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    private const DEFAULT_PASSWORD = 'KrakatauCRM@123';

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.system.users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.system.users.create', [
            'user' => new User(),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $usesDefaultPassword = empty($validated['password']);
        $password = $usesDefaultPassword ? self::DEFAULT_PASSWORD : $validated['password'];

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($password),
        ]);
        $user->assignRole($validated['role']);

        $redirect = redirect()
            ->route('admin.system.users.show', $user)
            ->with('success', 'User berhasil dibuat.');

        if ($usesDefaultPassword) {
            $redirect->with('default_password', self::DEFAULT_PASSWORD);
        }

        return $redirect;
    }

    public function show(User $user): View
    {
        return view('admin.system.users.show', [
            'user' => $user->load('roles'),
            'roleBadgeClass' => $this->roleBadgeClasses(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.system.users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->roleOptions(),
            'selectedRole' => $user->roles->first()?->name,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => ['required', 'string', Rule::exists('roles', 'name')],
        ]);

        $userData = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $userData['password'] = Hash::make($validated['password']);
        }

        $user->update($userData);
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.system.users.show', $user)
            ->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->hasRole('super_admin')) {
            return redirect()
                ->route('admin.system.users.index')
                ->with('error', 'Super admin tidak boleh dihapus.');
        }

        $user->delete();

        return redirect()
            ->route('admin.system.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private function roleOptions()
    {
        return Role::query()->orderBy('name')->pluck('name');
    }

    /** @return array<string, string> */
    private function roleBadgeClasses(): array
    {
        return [
            'super_admin' => 'role-badge--super-admin',
            'admin' => 'role-badge--admin',
            'manager' => 'role-badge--manager',
            'sales' => 'role-badge--sales',
            'marketing' => 'role-badge--marketing',
            'support' => 'role-badge--support',
        ];
    }
}
