<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

class RoleMenuSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'super_admin' => [
                'sections' => ['service-management', 'sales-enablement', 'marketing-automation', 'whatsapp-marketing', 'customer-profile-360', 'system'],
                'dashboard_titles' => ['CRM Overview', 'Service Management', 'Sales Enablement', 'Marketing Automation', 'Customer Profile 360'],
            ],
            'admin' => [
                'sections' => ['service-management', 'sales-enablement', 'marketing-automation', 'whatsapp-marketing', 'customer-profile-360', 'system'],
                'dashboard_titles' => ['CRM Overview', 'Service Management', 'Sales Enablement', 'Marketing Automation', 'Customer Profile 360'],
            ],
            'manager' => [
                'sections' => ['service-management', 'sales-enablement', 'marketing-automation', 'whatsapp-marketing', 'customer-profile-360'],
                'dashboard_titles' => ['CRM Overview', 'Service Management', 'Sales Enablement', 'Marketing Automation', 'Customer Profile 360'],
            ],
            'sales' => [
                'sections' => ['sales-enablement', 'customer-profile-360'],
                'dashboard_titles' => ['Sales Enablement', 'Customer Profile 360'],
            ],
            'marketing' => [
                'sections' => ['marketing-automation', 'whatsapp-marketing', 'customer-profile-360'],
                'dashboard_titles' => ['Marketing Automation', 'Customer Profile 360'],
            ],
            'support' => [
                'sections' => ['service-management', 'customer-profile-360'],
                'dashboard_titles' => ['Service Management', 'Customer Profile 360'],
            ],
        ];

        $dashboardParentId = Menu::query()
            ->where('section', 'dashboard')
            ->where('title', 'Dashboard')
            ->value('id');

        foreach ($roles as $roleName => $configuration) {
            $role = Role::findOrCreate($roleName, 'web');
            $sections = $configuration['sections'] ?? [];
            $dashboardTitles = $configuration['dashboard_titles'] ?? [];

            $menuIds = Menu::query()
                ->where(function ($query) use ($sections, $dashboardTitles, $dashboardParentId) {
                    if ($sections !== []) {
                        $query->whereIn('section', $sections);
                    }

                    if ($dashboardTitles !== []) {
                        $query->orWhere(function ($dashboardQuery) use ($dashboardTitles, $dashboardParentId) {
                            $dashboardQuery
                                ->where('section', 'dashboard')
                                ->whereIn('title', $dashboardTitles);

                            if ($dashboardParentId !== null) {
                                $dashboardQuery->orWhere('id', $dashboardParentId);
                            }
                        });
                    }
                })
                ->pluck('id');

            DB::table('role_menu')->where('role_id', $role->id)->delete();

            $payload = $menuIds->map(fn (int $menuId) => [
                'role_id' => $role->id,
                'menu_id' => $menuId,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all();

            if ($payload !== []) {
                DB::table('role_menu')->insert($payload);
            }
        }
    }
}
