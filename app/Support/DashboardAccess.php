<?php

namespace App\Support;

use App\Models\Menu;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Schema;

class DashboardAccess
{
    protected const ROUTES = [
        'CRM Overview' => 'admin.dashboard',
        'Service Management' => 'admin.dashboard.service',
        'Sales Enablement' => 'admin.dashboard.sales',
        'Marketing Automation' => 'admin.dashboard.marketing',
        'Customer Profile 360' => 'admin.dashboard.customer',
    ];

    public static function visibleMenuItems(array $items, ?Authenticatable $user): array
    {
        $allowedTitles = self::allowedTitles($user);

        return array_values(array_filter($items, function (array $item) use ($allowedTitles): bool {
            $title = (string) ($item['title'] ?? '');

            return in_array($title, $allowedTitles, true);
        }));
    }

    public static function canAccess(?Authenticatable $user, string $title): bool
    {
        return in_array($title, self::allowedTitles($user), true);
    }

    public static function firstAccessibleRouteName(?Authenticatable $user): ?string
    {
        foreach (self::allowedTitles($user) as $title) {
            if (isset(self::ROUTES[$title])) {
                return self::ROUTES[$title];
            }
        }

        return null;
    }

    public static function abortUnlessCanAccess(?Authenticatable $user, string $title): void
    {
        abort_unless(self::canAccess($user, $title), 403);
    }

    public static function routeNameForTitle(string $title): ?string
    {
        return self::ROUTES[$title] ?? null;
    }

    protected static function allowedTitles(?Authenticatable $user): array
    {
        if (! $user) {
            return array_keys(self::ROUTES);
        }

        $menuTitles = self::allowedTitlesFromMenus($user);

        if ($menuTitles !== null) {
            return $menuTitles;
        }

        return self::allowedTitlesFromRoles($user);
    }

    protected static function allowedTitlesFromMenus(Authenticatable $user): ?array
    {
        if (! method_exists($user, 'roles')) {
            return null;
        }

        if (! Schema::hasTable('menus') || ! Schema::hasTable('role_menu') || ! Schema::hasTable('roles')) {
            return null;
        }

        if (! Menu::query()->where('section', 'dashboard')->exists()) {
            return null;
        }

        $roleNames = $user->roles()->pluck('name')->filter()->values()->all();

        if ($roleNames === []) {
            return [];
        }

        return Menu::query()
            ->select('menus.title', 'menus.sort_order')
            ->join('role_menu', 'role_menu.menu_id', '=', 'menus.id')
            ->join('roles', 'roles.id', '=', 'role_menu.role_id')
            ->whereIn('roles.name', $roleNames)
            ->where('menus.section', 'dashboard')
            ->whereIn('menus.title', array_keys(self::ROUTES))
            ->where('menus.is_active', true)
            ->orderBy('menus.sort_order')
            ->orderBy('menus.title')
            ->distinct()
            ->pluck('menus.title')
            ->values()
            ->all();
    }

    protected static function allowedTitlesFromRoles(Authenticatable $user): array
    {
        if (! method_exists($user, 'getRoleNames')) {
            return array_keys(self::ROUTES);
        }

        $matrix = [
            'super_admin' => array_keys(self::ROUTES),
            'admin' => array_keys(self::ROUTES),
            'manager' => array_keys(self::ROUTES),
            'sales' => ['Sales Enablement', 'Customer Profile 360'],
            'marketing' => ['Marketing Automation', 'Customer Profile 360'],
            'support' => ['Service Management', 'Customer Profile 360'],
        ];

        $titles = collect($user->getRoleNames())
            ->flatMap(fn (string $roleName) => $matrix[$roleName] ?? [])
            ->unique()
            ->values()
            ->all();

        return $titles !== [] ? $titles : array_keys(self::ROUTES);
    }
}
