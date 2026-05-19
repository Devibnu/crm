<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserRoleController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $users = User::query()
            ->with('roles')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('roles', function ($roleQuery) use ($search) {
                            $roleQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.system.users.index', [
            'users' => $users,
            'roles' => $this->roleOptions(),
            'search' => $search,
            'summary' => $this->summary(),
            'roleBadgeClass' => $this->roleBadgeClass(),
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
        $validated = $request->validate($this->rules());

        $this->syncUserIdSequence();

        try {
            $user = $this->createUserWithRole($validated, $request);
        } catch (QueryException $exception) {
            if (! $this->isDuplicateUserPrimaryKeyException($exception)) {
                throw $exception;
            }

            $this->syncUserIdSequence();
            $user = $this->createUserWithRole($validated, $request);
        }

        return redirect()
            ->route('admin.system.users.index')
            ->with('success', 'User berhasil ditambahkan.');
    }

    public function show(User $user): View
    {
        return view('admin.system.users.show', [
            'user' => $user->load('roles'),
            'roleBadgeClass' => $this->roleBadgeClass(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.system.users.edit', [
            'user' => $user->load('roles'),
            'roles' => $this->roleOptions(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate($this->rules($user));

        if ($message = $this->guardRoleMutation($user, $validated['role'])) {
            return redirect()
                ->route('admin.system.users.edit', $user)
                ->with('error', $message);
        }

        $user->update($this->payload($validated, $request, $user));
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.system.users.show', $user)
            ->with('success', 'Data user berhasil diperbarui.');
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
        ]);

        if ($message = $this->guardRoleMutation($user, $validated['role'])) {
            return redirect()
                ->route('admin.system.users.index')
                ->with('error', $message);
        }

        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.system.users.index')
            ->with('success', 'Role user berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            return redirect()
                ->route('admin.system.users.index')
                ->with('error', 'Akun yang sedang digunakan tidak dapat dihapus.');
        }

        if ($user->hasRole('super_admin') && User::query()->role('super_admin')->count() <= 1) {
            return redirect()
                ->route('admin.system.users.index')
                ->with('error', 'Super admin terakhir tidak dapat dihapus.');
        }

        $user->delete();

        return redirect()
            ->route('admin.system.users.index')
            ->with('success', 'User berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => [
                $user ? 'nullable' : 'required',
                'confirmed',
                Password::min(8),
            ],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'is_verified' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(array $validated, Request $request, ?User $user = null, bool $creating = false): array
    {
        $shouldVerify = $request->boolean('is_verified');
        $verifiedAt = null;

        if ($shouldVerify) {
            $verifiedAt = $creating || ! $user?->email_verified_at || $user->email !== $validated['email']
                ? now()
                : $user->email_verified_at;
        }

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'email_verified_at' => $verifiedAt,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = $validated['password'];
        }

        return $payload;
    }

    /**
     * @return array<int, string>
     */
    protected function roleOptions(): array
    {
        return Role::query()->orderBy('name')->pluck('name')->all();
    }

    /**
     * @return array<string, int>
     */
    protected function summary(): array
    {
        return [
            'total' => User::query()->count(),
            'superAdmin' => User::query()->whereHas('roles', fn ($query) => $query->where('name', 'super_admin'))->count(),
            'adminManager' => User::query()->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'manager']))->count(),
            'others' => User::query()->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['super_admin', 'admin', 'manager']))->count(),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function roleBadgeClass(): array
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

    protected function guardRoleMutation(User $user, string $role): ?string
    {
        if ($user->hasRole('super_admin') && $role !== 'super_admin' && User::query()->role('super_admin')->count() <= 1) {
            return 'Super admin terakhir tidak dapat diturunkan role-nya.';
        }

        return null;
    }

    protected function createUserWithRole(array $validated, Request $request): User
    {
        return DB::transaction(function () use ($validated, $request) {
            $user = User::create($this->payload($validated, $request, creating: true));
            $user->syncRoles([$validated['role']]);

            return $user;
        });
    }

    protected function syncUserIdSequence(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            select setval(
                pg_get_serial_sequence('users', 'id'),
                coalesce((select max(id) from users), 1),
                true
            )
        ");
    }

    protected function isDuplicateUserPrimaryKeyException(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return $exception->getCode() === '23505'
            && str_contains($message, 'users_pkey');
    }
}
