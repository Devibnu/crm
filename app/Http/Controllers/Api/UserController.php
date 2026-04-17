<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function directory(Request $request): JsonResponse
    {
        $query = User::query()
            ->select('id', 'full_name', 'email', 'role', 'status')
            ->where('status', 'active');

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        return response()->json([
            'data' => $query
                ->orderBy('full_name')
                ->get()
                ->map(fn (User $user) => [
                    'id' => $user->id,
                    'fullName' => $user->full_name,
                    'email' => $user->email,
                    'role' => $user->role,
                ])
                ->values(),
        ]);
    }

    private function modulePermissionRules(): array
    {
        return [
            'modulePermissions' => 'sometimes|array',
            'modulePermissions.customers' => 'required_with:modulePermissions|in:full,manage,handle,view',
            'modulePermissions.tickets' => 'required_with:modulePermissions|in:full,manage,handle,view',
            'modulePermissions.inbox' => 'required_with:modulePermissions|in:full,manage,handle,view',
            'modulePermissions.whatsapp' => 'required_with:modulePermissions|in:full,manage,handle,view',
            'modulePermissions.invoice' => 'required_with:modulePermissions|in:full,manage,handle,view',
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'manage', 'Admin');

        $query = User::query();

        if ($q = $request->input('q')) {
            $query->where(function ($qb) use ($q) {
                $qb->where('full_name', 'ilike', "%{$q}%")
                   ->orWhere('email', 'ilike', "%{$q}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($plan = $request->input('plan')) {
            $query->where('current_plan', $plan);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortBy = $request->input('sortBy', 'id');
        $orderBy = $request->input('orderBy', 'asc');

        $sortColumn = match ($sortBy) {
            'user' => 'full_name',
            'email' => 'email',
            'role' => 'role',
            'plan' => 'current_plan',
            'status' => 'status',
            'billing' => 'billing',
            default => 'id',
        };

        $query->orderBy($sortColumn, $orderBy);

        $itemsPerPage = (int) $request->input('itemsPerPage', 10);
        $page = (int) $request->input('page', 1);

        $total = $query->count();
        $users = $query->skip(($page - 1) * $itemsPerPage)
                       ->take($itemsPerPage)
                       ->get();

        return response()->json([
            'users' => $users->map->toListResponse()->values(),
            'totalPages' => (int) ceil($total / $itemsPerPage),
            'totalUsers' => $total,
            'page' => $page,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $this->authorizeAbility(request(), 'manage', 'Admin');

        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user->toListResponse());
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeAbility($request, 'manage', 'Admin');

        $request->validate(array_merge([
            'fullName' => 'required|string',
            'email' => 'required|email|unique:users',
        ], $this->modulePermissionRules()));

        $role = $request->input('role', 'editor');

        $user = User::create([
            'full_name' => $request->input('fullName'),
            'username' => $request->input('username', strtolower(str_replace(' ', '', $request->input('fullName')))),
            'email' => $request->input('email'),
            'password' => bcrypt('password'),
            'company' => $request->input('company', ''),
            'role' => $role,
            'module_permissions' => $request->input('modulePermissions', User::defaultModulePermissionsForRole($role)),
            'country' => $request->input('country', ''),
            'contact' => $request->input('contact', ''),
            'current_plan' => $request->input('currentPlan', 'basic'),
            'status' => $request->input('status', 'active'),
            'avatar' => $request->input('avatar', ''),
            'billing' => $request->input('billing', 'Auto Debit'),
        ]);

        return response()->json(['body' => $user->toListResponse()], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->authorizeAbility($request, 'manage', 'Admin');

        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $request->validate($this->modulePermissionRules());

        $mappings = [
            'fullName' => 'full_name',
            'username' => 'username',
            'email' => 'email',
            'company' => 'company',
            'role' => 'role',
            'country' => 'country',
            'contact' => 'contact',
            'currentPlan' => 'current_plan',
            'status' => 'status',
            'avatar' => 'avatar',
            'billing' => 'billing',
            'taxId' => 'tax_id',
            'language' => 'language',
        ];

        $data = [];
        foreach ($mappings as $camel => $snake) {
            if ($request->has($camel)) {
                $data[$snake] = $request->input($camel);
            }
        }

        if ($request->has('modulePermissions')) {
            $data['module_permissions'] = $request->input('modulePermissions');
        } elseif ($request->has('role') && ! $user->module_permissions) {
            $data['module_permissions'] = User::defaultModulePermissionsForRole($request->input('role'));
        }

        $user->update($data);

        return response()->json($user->fresh()->toListResponse());
    }

    public function destroy(int $id): Response
    {
        $this->authorizeAbility(request(), 'manage', 'Admin');

        $user = User::find($id);

        if (! $user) {
            return response('User not found', 404);
        }

        $user->delete();

        return response()->noContent();
    }
}
