<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            DB::table('role_menu')->delete();
            Menu::query()->delete();

            foreach ($this->menuTree() as $item) {
                $this->createMenuItem($item);
            }
        });
    }

    protected function createMenuItem(array $item, ?Menu $parent = null): Menu
    {
        $menu = Menu::create([
            'parent_id' => $parent?->id,
            'section' => $item['section'],
            'title' => $item['title'],
            'route' => $item['route'] ?? null,
            'icon' => $item['icon'] ?? null,
            'sort_order' => $item['sort_order'],
            'is_active' => $item['is_active'] ?? true,
        ]);

        foreach ($item['children'] ?? [] as $child) {
            $this->createMenuItem($child, $menu);
        }

        return $menu;
    }

    protected function menuTree(): array
    {
        return [
            [
                'section' => 'dashboard',
                'title' => 'Dashboard',
                'route' => '/dashboards/crm',
                'icon' => 'tabler-layout-dashboard',
                'sort_order' => 10,
                'children' => [
                    ['section' => 'dashboard', 'title' => 'CRM Overview', 'route' => '/dashboards/crm', 'icon' => 'tabler-chart-donut-3', 'sort_order' => 10],
                    ['section' => 'dashboard', 'title' => 'Service Management', 'route' => '/dashboards/service-management', 'icon' => 'tabler-headset', 'sort_order' => 20],
                    ['section' => 'dashboard', 'title' => 'Sales Enablement', 'route' => '/dashboards/sales-enablement', 'icon' => 'tabler-briefcase-2', 'sort_order' => 30],
                    ['section' => 'dashboard', 'title' => 'Marketing Automation', 'route' => '/dashboards/marketing-automation', 'icon' => 'tabler-speakerphone', 'sort_order' => 40],
                    ['section' => 'dashboard', 'title' => 'Customer Profile 360', 'route' => '/dashboards/customer-profile-360', 'icon' => 'tabler-user-circle', 'sort_order' => 50],
                ],
            ],
            [
                'section' => 'service-management',
                'title' => 'Service Management',
                'route' => '/dashboards/service-management',
                'icon' => 'tabler-headset',
                'sort_order' => 20,
                'children' => [
                    ['section' => 'service-management', 'title' => 'Omnichannel Inbox', 'route' => '/service/omnichannel', 'icon' => 'tabler-messages', 'sort_order' => 10],
                    ['section' => 'service-management', 'title' => 'Ticket Management', 'route' => '/service/tickets', 'icon' => 'tabler-ticket', 'sort_order' => 20],
                    ['section' => 'service-management', 'title' => 'SLA Management', 'route' => '/service/sla', 'icon' => 'tabler-clock-hour-4', 'sort_order' => 30],
                    ['section' => 'service-management', 'title' => 'Case Resolution', 'route' => '/service/cases', 'icon' => 'tabler-briefcase-2', 'sort_order' => 40],
                ],
            ],
            [
                'section' => 'sales-enablement',
                'title' => 'Sales Enablement',
                'route' => '/dashboards/sales-enablement',
                'icon' => 'tabler-briefcase-2',
                'sort_order' => 30,
                'children' => [
                    ['section' => 'sales-enablement', 'title' => 'Lead Management', 'route' => '/sales/leads', 'icon' => 'tabler-user-plus', 'sort_order' => 10],
                    ['section' => 'sales-enablement', 'title' => 'Opportunity Management', 'route' => '/sales/opportunities', 'icon' => 'tabler-target-arrow', 'sort_order' => 20],
                    ['section' => 'sales-enablement', 'title' => 'Pipeline & Forecasting', 'route' => '/sales/pipeline', 'icon' => 'tabler-chart-line', 'sort_order' => 30],
                    ['section' => 'sales-enablement', 'title' => 'Sales Activity Tracking', 'route' => '/sales/activities', 'icon' => 'tabler-activity-heartbeat', 'sort_order' => 40],
                    ['section' => 'sales-enablement', 'title' => 'Quotation & Deal', 'route' => '/sales/deals', 'icon' => 'tabler-file-dollar', 'sort_order' => 50],
                ],
            ],
            [
                'section' => 'marketing-automation',
                'title' => 'Marketing Automation',
                'route' => '/dashboards/marketing-automation',
                'icon' => 'tabler-speakerphone',
                'sort_order' => 40,
                'children' => [
                    ['section' => 'marketing-automation', 'title' => 'Campaign Management', 'route' => '/marketing/campaign-management', 'icon' => 'tabler-megaphone', 'sort_order' => 10],
                    ['section' => 'marketing-automation', 'title' => 'Landing Page & Form Builder', 'route' => '/marketing/landing-page-form-builder', 'icon' => 'tabler-browser', 'sort_order' => 20],
                    ['section' => 'marketing-automation', 'title' => 'Social Media Engagement', 'route' => '/marketing/social-media-engagement', 'icon' => 'tabler-brand-instagram', 'sort_order' => 30],
                    ['section' => 'marketing-automation', 'title' => 'Customer Data Platform', 'route' => '/marketing/customer-data-platform', 'icon' => 'tabler-database', 'sort_order' => 40],
                    ['section' => 'marketing-automation', 'title' => 'Consent Management', 'route' => '/marketing/consent-management', 'icon' => 'tabler-shield-check', 'sort_order' => 50],
                    [
                        'section' => 'marketing-automation',
                        'title' => 'Marketing Analytics',
                        'route' => null,
                        'icon' => 'tabler-chart-funnel',
                        'sort_order' => 60,
                        'children' => [
                            ['section' => 'marketing-automation', 'title' => 'Campaign Performance', 'route' => '/marketing/marketing-analytics/campaign-performance', 'icon' => 'tabler-chart-bar', 'sort_order' => 10],
                            ['section' => 'marketing-automation', 'title' => 'Attribution Overview', 'route' => '/marketing/marketing-analytics/attribution-overview', 'icon' => 'tabler-chart-covariate', 'sort_order' => 20],
                        ],
                    ],
                ],
            ],
            [
                'section' => 'customer-profile-360',
                'title' => 'Customer Profile 360',
                'route' => '/dashboards/customer-profile-360',
                'icon' => 'tabler-user-circle',
                'sort_order' => 50,
                'children' => [
                    ['section' => 'customer-profile-360', 'title' => 'Customer Master', 'route' => '/customer-profile/customer-master', 'icon' => 'tabler-users-group', 'sort_order' => 10],
                    ['section' => 'customer-profile-360', 'title' => 'Interaction History', 'route' => '/customer-profile/interaction-history', 'icon' => 'tabler-history', 'sort_order' => 20],
                    ['section' => 'customer-profile-360', 'title' => 'Segmentation', 'route' => '/customer-profile/segmentation', 'icon' => 'tabler-users', 'sort_order' => 30],
                    ['section' => 'customer-profile-360', 'title' => 'Consent & Preferences', 'route' => '/customer-profile/consent-preferences', 'icon' => 'tabler-adjustments-horizontal', 'sort_order' => 40],
                    ['section' => 'customer-profile-360', 'title' => 'Customer Timeline', 'route' => '/customer-profile/customer-timeline', 'icon' => 'tabler-timeline', 'sort_order' => 50],
                ],
            ],
        ];
    }
}
