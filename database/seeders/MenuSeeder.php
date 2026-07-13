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
            'permission_name' => $item['permission_name'] ?? null,
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
            ['section' => 'dashboard', 'title' => 'CRM Overview', 'route' => 'admin.dashboard', 'icon' => 'dashboard', 'sort_order' => 10],

            ['section' => 'customer-profile-360', 'title' => 'Customer List', 'route' => 'admin.customers.index', 'icon' => 'user', 'permission_name' => 'customers.view', 'sort_order' => 10],
            ['section' => 'customer-profile-360', 'title' => 'Customer Profile', 'route' => 'admin.customers.profile', 'icon' => 'user', 'permission_name' => 'customers.view', 'sort_order' => 20],
            ['section' => 'customer-profile-360', 'title' => 'Interaction History', 'route' => 'admin.customers.interactions', 'icon' => 'mail', 'permission_name' => 'interactions.view', 'sort_order' => 30],
            ['section' => 'customer-profile-360', 'title' => 'Transactions', 'route' => 'admin.customers.transactions', 'icon' => 'cart', 'permission_name' => 'customers.view', 'sort_order' => 40],
            ['section' => 'customer-profile-360', 'title' => 'Preferences', 'route' => 'admin.customers.preferences', 'icon' => 'lock', 'permission_name' => 'customers.view', 'sort_order' => 50],
            ['section' => 'customer-profile-360', 'title' => 'Behavior', 'route' => 'admin.customers.behavior', 'icon' => 'activity', 'permission_name' => 'customers.view', 'sort_order' => 60],

            ['section' => 'sales-enablement', 'title' => 'Lead Management', 'route' => 'admin.sales.leads', 'icon' => 'lead', 'permission_name' => 'leads.view', 'sort_order' => 10],
            ['section' => 'sales-enablement', 'title' => 'Opportunity Management', 'route' => 'admin.sales.opportunities', 'icon' => 'opportunity', 'permission_name' => 'opportunities.view', 'sort_order' => 20],
            ['section' => 'sales-enablement', 'title' => 'Sales Activity Tracking', 'route' => 'admin.sales.activities.index', 'icon' => 'activity', 'permission_name' => 'activities.view', 'sort_order' => 30],
            ['section' => 'sales-enablement', 'title' => 'Quotation & Deal', 'route' => 'admin.sales.deals.index', 'icon' => 'deal', 'permission_name' => 'quotations.view', 'sort_order' => 40],
            ['section' => 'sales-enablement', 'title' => 'Pipeline & Forecasting', 'route' => 'admin.sales.pipeline', 'icon' => 'pipeline', 'permission_name' => 'pipeline.view', 'sort_order' => 50],
            ['section' => 'sales-enablement', 'title' => 'Win/Lost Analysis', 'route' => 'admin.sales.win-loss', 'icon' => 'analysis', 'permission_name' => 'winloss.view', 'sort_order' => 60],

            ['section' => 'project-management', 'title' => 'Dashboard', 'route' => 'admin.projects.dashboard', 'icon' => 'dashboard', 'permission_name' => 'projects.view', 'sort_order' => 10],
            ['section' => 'project-management', 'title' => 'Projects', 'route' => 'admin.projects.index', 'icon' => 'pipeline', 'permission_name' => 'projects.view', 'sort_order' => 20],
            ['section' => 'project-management', 'title' => 'Tasks', 'route' => 'admin.projects.tasks.index', 'icon' => 'activity', 'permission_name' => 'projects.view', 'sort_order' => 30],
            ['section' => 'project-management', 'title' => 'Milestones', 'route' => 'admin.projects.milestones.index', 'icon' => 'calendar', 'permission_name' => 'project.milestone.read', 'sort_order' => 40],
            ['section' => 'project-management', 'title' => 'Timeline', 'route' => 'admin.projects.timeline.index', 'icon' => 'timer', 'permission_name' => 'project.timeline.read', 'sort_order' => 50],
            ['section' => 'project-management', 'title' => 'Timesheets', 'route' => 'admin.projects.timesheets.index', 'icon' => 'timer', 'permission_name' => 'project.timesheet.read', 'sort_order' => 60],
            ['section' => 'project-management', 'title' => 'Reports', 'route' => 'admin.projects.reports.index', 'icon' => 'analysis', 'permission_name' => 'project.report.read', 'sort_order' => 70],

            ['section' => 'marketing-automation', 'title' => 'Audience Segmentation', 'route' => 'admin.marketing.audiences.index', 'icon' => 'audience', 'permission_name' => 'audiences.view', 'sort_order' => 10],
            ['section' => 'marketing-automation', 'title' => 'Lead Scoring & Routing', 'route' => 'admin.marketing.lead-scoring.index', 'icon' => 'scoring', 'permission_name' => 'lead_scoring.view', 'sort_order' => 20],
            ['section' => 'marketing-automation', 'title' => 'Campaign Management', 'route' => 'admin.marketing.campaigns.index', 'icon' => 'campaign', 'permission_name' => 'campaigns.view', 'sort_order' => 30],
            ['section' => 'marketing-automation', 'title' => 'Landing Page & Form', 'route' => 'admin.marketing.landing-pages.index', 'icon' => 'landing', 'permission_name' => 'landing_pages.view', 'sort_order' => 40],
            ['section' => 'marketing-automation', 'title' => 'Campaign Execution', 'route' => 'admin.marketing.executions.index', 'icon' => 'execution', 'permission_name' => 'executions.view', 'sort_order' => 50],
            ['section' => 'marketing-automation', 'title' => 'Automation & Nurturing', 'route' => 'admin.marketing.automations.index', 'icon' => 'automation', 'permission_name' => 'automations.view', 'sort_order' => 60],
            ['section' => 'marketing-automation', 'title' => 'Social Media Engagement', 'route' => 'admin.marketing.social-engagements.index', 'icon' => 'social', 'permission_name' => 'social.view', 'sort_order' => 70],

            ['section' => 'whatsapp-marketing', 'title' => 'WhatsApp Providers', 'route' => 'admin.system.whatsapp-providers.index', 'icon' => 'chat', 'permission_name' => 'whatsapp_providers.view', 'sort_order' => 10],
            ['section' => 'whatsapp-marketing', 'title' => 'WhatsApp Cloud API', 'route' => 'admin.marketing.whatsapp-cloud-api.index', 'icon' => 'chat', 'permission_name' => 'whatsapp_cloud_api.view', 'sort_order' => 20],
            ['section' => 'whatsapp-marketing', 'title' => 'WhatsApp Templates', 'route' => 'admin.marketing.whatsapp-templates.index', 'icon' => 'chat', 'permission_name' => 'whatsapp_templates.view', 'sort_order' => 30],
            ['section' => 'whatsapp-marketing', 'title' => 'WhatsApp Broadcast', 'route' => 'admin.marketing.whatsapp-broadcasts.index', 'icon' => 'chat', 'permission_name' => 'whatsapp_broadcasts.view', 'sort_order' => 40],
            ['section' => 'whatsapp-marketing', 'title' => 'WhatsApp Reply Inbox', 'route' => 'admin.marketing.whatsapp-replies.index', 'icon' => 'inbox', 'permission_name' => 'whatsapp_replies.view', 'sort_order' => 50],

            ['section' => 'service-management', 'title' => 'Omnichannel Inbox', 'route' => 'admin.service.omnichannel.index', 'icon' => 'inbox', 'permission_name' => 'omnichannel.view', 'sort_order' => 10],
            ['section' => 'service-management', 'title' => 'Ticket Management', 'route' => 'admin.service.tickets.index', 'icon' => 'ticket', 'permission_name' => 'tickets.view', 'sort_order' => 20],
            ['section' => 'service-management', 'title' => 'SLA Management', 'route' => 'admin.service.sla.index', 'icon' => 'timer', 'permission_name' => 'sla.view', 'sort_order' => 30],
            ['section' => 'service-management', 'title' => 'Case Resolution', 'route' => 'admin.service.case-resolutions.index', 'icon' => 'case', 'permission_name' => 'cases.view', 'sort_order' => 40],
            ['section' => 'service-management', 'title' => 'Customer Satisfaction', 'route' => 'admin.service.customer-satisfaction.index', 'icon' => 'star', 'permission_name' => 'csat.view', 'sort_order' => 50],
            ['section' => 'service-management', 'title' => 'Knowledge Base', 'route' => 'admin.service.knowledge-base.index', 'icon' => 'book', 'permission_name' => 'knowledge.view', 'sort_order' => 60],

            ['section' => 'system', 'title' => 'Users', 'route' => 'admin.system.users.index', 'icon' => 'user', 'permission_name' => 'users.view', 'sort_order' => 10],
            ['section' => 'system', 'title' => 'Roles & Permissions', 'route' => 'admin.system.roles.index', 'icon' => 'lock', 'permission_name' => 'roles.view', 'sort_order' => 20],
            ['section' => 'system', 'title' => 'Menu Management', 'route' => 'admin.system.menus.index', 'icon' => 'list', 'permission_name' => 'menus.view', 'sort_order' => 30],
            ['section' => 'system', 'title' => 'Branding', 'route' => 'admin.system.branding.edit', 'icon' => 'brand', 'permission_name' => 'branding.view', 'sort_order' => 40],
        ];
    }
}
