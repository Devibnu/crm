<?php

namespace App\Http\Controllers\Admin\System;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        if (! $this->menuTablesExist()) {
            return view('admin.system.menus.index', [
                'menus' => collect(),
                'search' => $search,
                'isFiltered' => $search !== '',
                'menuFeatureReady' => false,
                'summary' => [
                    'total' => 0,
                    'root' => 0,
                    'active' => 0,
                    'inactive' => 0,
                ],
            ]);
        }

        $menus = Menu::query()
            ->with(['parent:id,title', 'roles:id,name', 'children:id,parent_id'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%")
                        ->orWhere('route', 'like', "%{$search}%")
                        ->orWhere('icon', 'like', "%{$search}%");
                });
            })
            ->ordered()
            ->get();

        return view('admin.system.menus.index', [
            'menus' => $this->flattenMenus($menus),
            'search' => $search,
            'isFiltered' => $search !== '',
            'menuFeatureReady' => true,
            'summary' => [
                'total' => $menus->count(),
                'root' => $menus->whereNull('parent_id')->count(),
                'active' => $menus->where('is_active', true)->count(),
                'inactive' => $menus->where('is_active', false)->count(),
            ],
        ]);
    }

    public function preview(Request $request): View
    {
        if (! $this->menuTablesExist()) {
            return redirect()
                ->route('admin.system.menus.index')
                ->with('error', 'Tabel menu belum tersedia. Jalankan migrasi menu terlebih dahulu.');
        }

        $roles = $this->roles();
        $selectedRole = (string) $request->query('role', '');
        $selectedRole = $roles->pluck('name')->contains($selectedRole) ? $selectedRole : '';

        $allMenus = Menu::query()
            ->with('roles:id,name')
            ->ordered()
            ->get();

        $activeMenus = $allMenus->where('is_active', true)->values();
        $roleNames = $selectedRole !== '' ? [$selectedRole] : [];
        $navigation = Menu::buildNavigationTree($activeMenus, $roleNames);
        $bottomNavigation = collect($navigation)
            ->take(5)
            ->map(fn (array $item) => [
                'title' => $item['title'],
                'route' => $item['route'] ?: collect($item['children'])->pluck('route')->filter()->first(),
                'icon' => $item['icon'],
            ])
            ->filter(fn (array $item) => filled($item['route']))
            ->values()
            ->all();

        return view('admin.system.menus.preview', [
            'roles' => $roles,
            'selectedRole' => $selectedRole,
            'navigation' => $navigation,
            'bottomNavigation' => $bottomNavigation,
            'sortableTree' => $this->treeMenus($allMenus),
        ]);
    }

    public function create(): View
    {
        if (! $this->menuTablesExist()) {
            abort(503, 'Tabel menu belum tersedia. Jalankan migrasi menu terlebih dahulu.');
        }

        $menu = new Menu([
            'sort_order' => 10,
            'is_active' => true,
        ]);

        return view('admin.system.menus.create', [
            'menu' => $menu,
            'roles' => $this->roles(),
            'sectionOptions' => $this->sectionOptions(),
            'parentOptions' => $this->parentOptions(),
            'selectedRoles' => old('roles', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->menuTablesExist()) {
            return redirect()
                ->route('admin.system.menus.index')
                ->with('error', 'Tabel menu belum tersedia. Jalankan migrasi menu terlebih dahulu.');
        }

        $validated = $request->validate($this->rules());

        DB::transaction(function () use ($validated) {
            $menu = Menu::create($this->payload($validated));
            $menu->roles()->sync($validated['roles'] ?? []);
        });

        return redirect()
            ->route('admin.system.menus.index')
            ->with('success', 'Menu berhasil ditambahkan.');
    }

    public function edit(Menu $menu): View
    {
        if (! $this->menuTablesExist()) {
            abort(503, 'Tabel menu belum tersedia. Jalankan migrasi menu terlebih dahulu.');
        }

        return view('admin.system.menus.edit', [
            'menu' => $menu->load('roles'),
            'roles' => $this->roles(),
            'sectionOptions' => $this->sectionOptions(),
            'parentOptions' => $this->parentOptions($menu),
            'selectedRoles' => old('roles', $menu->roles->pluck('id')->map(fn ($id) => (string) $id)->all()),
        ]);
    }

    public function update(Request $request, Menu $menu): RedirectResponse
    {
        if (! $this->menuTablesExist()) {
            return redirect()
                ->route('admin.system.menus.index')
                ->with('error', 'Tabel menu belum tersedia. Jalankan migrasi menu terlebih dahulu.');
        }

        $validated = $request->validate($this->rules($menu));

        DB::transaction(function () use ($menu, $validated) {
            $menu->update($this->payload($validated));
            $menu->roles()->sync($validated['roles'] ?? []);
        });

        return redirect()
            ->route('admin.system.menus.index')
            ->with('success', 'Menu berhasil diperbarui.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        if (! $this->menuTablesExist()) {
            return redirect()
                ->route('admin.system.menus.index')
                ->with('error', 'Tabel menu belum tersedia. Jalankan migrasi menu terlebih dahulu.');
        }

        $payload = json_decode((string) $request->input('groups_json', '[]'), true);

        if (! is_array($payload) || $payload === []) {
            return redirect()
                ->back()
                ->with('error', 'Payload urutan menu tidak valid.');
        }

        DB::transaction(function () use ($payload) {
            foreach ($payload as $group) {
                $parentId = array_key_exists('parent_id', $group) && $group['parent_id'] !== null
                    ? (int) $group['parent_id']
                    : null;

                $orderedIds = collect($group['ordered_ids'] ?? [])
                    ->map(fn ($value) => (int) $value)
                    ->filter()
                    ->values();

                $siblings = Menu::query()
                    ->where('parent_id', $parentId)
                    ->ordered()
                    ->pluck('id')
                    ->map(fn ($value) => (int) $value)
                    ->values();

                if ($orderedIds->count() !== $siblings->count() || $orderedIds->diff($siblings)->isNotEmpty() || $siblings->diff($orderedIds)->isNotEmpty()) {
                    continue;
                }

                foreach ($orderedIds as $index => $menuId) {
                    Menu::query()
                        ->whereKey($menuId)
                        ->update(['sort_order' => ($index + 1) * 10]);
                }
            }
        });

        return redirect()
            ->back()
            ->with('success', 'Urutan menu berhasil diperbarui.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        if (! $this->menuTablesExist()) {
            return redirect()
                ->route('admin.system.menus.index')
                ->with('error', 'Tabel menu belum tersedia. Jalankan migrasi menu terlebih dahulu.');
        }

        if ($menu->children()->exists()) {
            return redirect()
                ->route('admin.system.menus.index')
                ->with('error', 'Menu yang masih memiliki submenu tidak dapat dihapus.');
        }

        $menu->delete();

        return redirect()
            ->route('admin.system.menus.index')
            ->with('success', 'Menu berhasil dihapus.');
    }

    protected function rules(?Menu $menu = null): array
    {
        $disallowedParentIds = $menu ? array_merge([$menu->id], $this->descendantIds($menu)) : [];

        return [
            'parent_id' => [
                'nullable',
                'integer',
                Rule::exists('menus', 'id'),
                Rule::notIn($disallowedParentIds),
            ],
            'section' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'route' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'id')],
        ];
    }

    protected function payload(array $validated): array
    {
        return [
            'parent_id' => $validated['parent_id'] ?? null,
            'section' => $validated['section'],
            'title' => $validated['title'],
            'route' => $validated['route'] ?: null,
            'icon' => $validated['icon'] ?: null,
            'sort_order' => $validated['sort_order'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ];
    }

    protected function sectionOptions(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'service-management' => 'Service Management',
            'sales-enablement' => 'Sales Enablement',
            'marketing-automation' => 'Marketing Automation',
            'customer-profile-360' => 'Customer Profile 360',
            'system' => 'System',
        ];
    }

    protected function roles(): Collection
    {
        return Role::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function parentOptions(?Menu $currentMenu = null): array
    {
        $excludedIds = $currentMenu ? array_merge([$currentMenu->id], $this->descendantIds($currentMenu)) : [];
        $menus = Menu::query()
            ->when($excludedIds !== [], fn ($query) => $query->whereNotIn('id', $excludedIds))
            ->ordered()
            ->get();

        return $this->flattenParentOptions($menus);
    }

    protected function flattenParentOptions(Collection $menus): array
    {
        $grouped = $menus->groupBy(fn (Menu $menu) => $menu->parent_id ?? 0);
        $options = [];

        $append = function (int $parentId = 0, int $depth = 0) use (&$append, &$options, $grouped): void {
            foreach ($grouped->get($parentId, collect()) as $menu) {
                $options[$menu->id] = str_repeat('-- ', $depth).$menu->title;
                $append($menu->id, $depth + 1);
            }
        };

        $append();

        return $options;
    }

    protected function flattenMenus(Collection $menus): Collection
    {
        $grouped = $menus->groupBy(fn (Menu $menu) => $menu->parent_id ?? 0);
        $rows = collect();

        $append = function (int $parentId = 0, int $depth = 0) use (&$append, $grouped, $rows): void {
            foreach ($grouped->get($parentId, collect()) as $menu) {
                $menu->depth = $depth;
                $rows->push($menu);
                $append($menu->id, $depth + 1);
            }
        };

        $append();

        return $rows;
    }

    protected function treeMenus(Collection $menus, ?int $parentId = null): array
    {
        return $menus
            ->where('parent_id', $parentId)
            ->sortBy([
                ['sort_order', 'asc'],
                ['title', 'asc'],
            ])
            ->map(function (Menu $menu) use ($menus) {
                return [
                    'id' => $menu->id,
                    'title' => $menu->title,
                    'section' => $menu->section,
                    'route' => $menu->route,
                    'icon' => $menu->icon,
                    'is_active' => $menu->is_active,
                    'sort_order' => $menu->sort_order,
                    'roles' => $menu->roles->pluck('name')->all(),
                    'children' => $this->treeMenus($menus, $menu->id),
                ];
            })
            ->values()
            ->all();
    }

    protected function descendantIds(Menu $menu): array
    {
        $menus = Menu::query()->get(['id', 'parent_id']);
        $childrenByParent = $menus->groupBy(fn (Menu $item) => $item->parent_id ?? 0);
        $descendants = [];

        $collect = function (int $parentId) use (&$collect, &$descendants, $childrenByParent): void {
            foreach ($childrenByParent->get($parentId, collect()) as $child) {
                $descendants[] = $child->id;
                $collect($child->id);
            }
        };

        $collect($menu->id);

        return $descendants;
    }

    protected function menuTablesExist(): bool
    {
        return Schema::hasTable('menus') && Schema::hasTable('role_menu');
    }
}
