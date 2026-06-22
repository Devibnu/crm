<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;

class RbacPermissions
{
    /**
     * @return array<string, array<int, string>>
     */
    public static function groups(): array
    {
        // Ambil semua permission dari database
        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        // Group berdasarkan prefix sebelum titik
        $grouped = [];
        foreach ($permissions as $permission) {
            // Parse prefix dari permission
            [$prefix] = explode('.', $permission, 2);
            
            // Format prefix ke title case dengan spasi dan underscore ke spasi
            $groupName = str($prefix)
                ->replace('_', ' ')
                ->title()
                ->toString();

            if (!isset($grouped[$groupName])) {
                $grouped[$groupName] = [];
            }
            
            $grouped[$groupName][] = $permission;
        }

        // Sort groups dan permissions dalam setiap group
        ksort($grouped);
        foreach ($grouped as &$perms) {
            sort($perms);
        }

        return $grouped;
    }

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return collect(self::groups())->flatten()->values()->all();
    }

    /**
     * @return array<int, string>
     */
    public static function viewPermissions(): array
    {
        return array_values(array_filter(self::all(), fn (string $permission): bool => str_ends_with($permission, '.view')));
    }
}
