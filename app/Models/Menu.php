<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Permission\Models\Role;

class Menu extends Model
{
    protected $fillable = [
        'parent_id',
        'section',
        'title',
        'route',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_menu')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    public static function buildNavigationTree(Collection $menus, array $roleNames = []): array
    {
        $allowedMenus = $menus
            ->filter(function (self $menu) use ($roleNames) {
                if ($roleNames === []) {
                    return true;
                }

                if ($menu->roles->isEmpty()) {
                    return true;
                }

                return $menu->roles->pluck('name')->intersect($roleNames)->isNotEmpty();
            })
            ->keyBy('id');

        $childrenByParent = $allowedMenus
            ->groupBy(fn (self $menu) => $menu->parent_id ?? 0)
            ->map(function (Collection $group) {
                return $group->sortBy([
                    ['sort_order', 'asc'],
                    ['title', 'asc'],
                ])->values();
            });

        $mapChildren = function (int|string|null $parentId) use (&$mapChildren, $childrenByParent): array {
            return $childrenByParent
                ->get($parentId ?? 0, collect())
                ->map(function (self $menu) use (&$mapChildren) {
                    $children = $mapChildren($menu->id);

                    if ($menu->route === null && $children === []) {
                        return null;
                    }

                    return [
                        'id' => $menu->id,
                        'parent_id' => $menu->parent_id,
                        'section' => $menu->section,
                        'title' => $menu->title,
                        'route' => $menu->route,
                        'icon' => $menu->icon,
                        'sort_order' => $menu->sort_order,
                        'is_active' => $menu->is_active,
                        'children' => $children,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        };

        return $mapChildren(null);
    }
}
