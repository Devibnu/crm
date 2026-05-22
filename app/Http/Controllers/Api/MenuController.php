<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Schema;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        if (! Schema::hasTable('menus') || ! Schema::hasTable('role_menu')) {
            return response()->json(['data' => []]);
        }

        $roleNames = auth()->check()
            ? auth()->user()->roles()->pluck('name')->all()
            : [];

        $menus = Menu::query()
            ->with('roles:id,name')
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'data' => Menu::buildNavigationTree($menus, $roleNames),
        ]);
    }
}
