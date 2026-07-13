<?php

namespace App\Support;

use App\Models\Menu;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class MenuResolver
{
    /** @var array<string, array<string, array<int, array<string, mixed>>>> */
    protected array $resolved = [];

    /** @return array<string, array<int, array<string, mixed>>> */
    public function forUser(?Authenticatable $user): array
    {
        $cacheKey = $user?->getAuthIdentifier().':'.implode('|', $user && method_exists($user, 'getRoleNames') ? $user->getRoleNames()->all() : []);

        return $this->resolved[$cacheKey] ??= $this->resolve($user);
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    protected function resolve(?Authenticatable $user): array
    {
        if (! Schema::hasTable('menus') || ! Menu::query()->active()->exists()) {
            return $this->fallbackMenus();
        }

        $menus = Menu::query()
            ->active()
            ->ordered()
            ->get()
            ->filter(fn (Menu $menu): bool => $this->canSeeMenu($user, $menu))
            ->groupBy('section')
            ->map(fn ($items) => $items->map(fn (Menu $menu): array => $this->toSidebarItem($menu))->values()->all());

        return [
            'dashboardMenu' => $menus->get('dashboard', []),
            'customersMenu' => $menus->get('customer-profile-360', []),
            'salesMenu' => $menus->get('sales-enablement', []),
            'projectMenu' => $menus->get('project-management', $this->fallbackMenus()['projectMenu']),
            'marketingMenu' => $menus->get('marketing-automation', []),
            'whatsAppMarketingMenu' => $menus->get('whatsapp-marketing', []),
            'serviceMenu' => $menus->get('service-management', []),
            'systemMenu' => $menus->get('system', []),
        ];
    }

    protected function canSeeMenu(?Authenticatable $user, Menu $menu): bool
    {
        $permission = $menu->permission_name;

        if (! filled($permission)) {
            return true;
        }

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('super_admin')) {
            return true;
        }

        return method_exists($user, 'can') && $user->can($permission);
    }

    /** @return array<string, mixed> */
    protected function toSidebarItem(Menu $menu): array
    {
        $route = $menu->route ?: $this->routeOverrideFor($menu);
        $isRouteName = filled($route) && Route::has($route);

        return array_filter([
            'title' => $menu->title,
            'icon' => $this->normalizeIcon($menu->icon),
            'route' => $isRouteName ? $route : null,
            'url' => $isRouteName ? null : $route,
            'href' => $this->hrefFor($route),
            'active' => $isRouteName ? $this->activePatternFor($route) : null,
            'permission' => $menu->permission_name,
        ], fn ($value) => $value !== null);
    }

    protected function hrefFor(?string $route): string
    {
        if (! filled($route)) {
            return '#';
        }

        if (Route::has($route)) {
            return route($route);
        }

        if (str_starts_with($route, 'http://') || str_starts_with($route, 'https://')) {
            return $route;
        }

        return url($route);
    }

    /** @return string|array<int, string> */
    protected function activePatternFor(string $route): string|array
    {
        return match ($route) {
            'admin.sales.leads' => 'admin.sales.leads*',
            'admin.sales.opportunities' => 'admin.sales.opportunities*',
            'admin.sales.pipeline' => 'admin.sales.pipeline*',
            'admin.sales.activities.index' => 'admin.sales.activities.*',
            'admin.sales.deals.index' => 'admin.sales.deals.*',
            'admin.projects.dashboard' => 'admin.projects.dashboard',
            'admin.projects.tasks.index' => 'admin.projects.tasks.*',
            'admin.projects.milestones.index' => 'admin.projects.milestones.*',
            'admin.projects.index' => [
                'admin.projects.index',
                'admin.projects.create',
                'admin.projects.store',
                'admin.projects.show',
                'admin.projects.edit',
                'admin.projects.update',
                'admin.projects.members.*',
            ],
            'admin.system.users.index' => 'admin.system.users.*',
            'admin.system.roles.index' => 'admin.system.roles.*',
            'admin.system.menus.index' => 'admin.system.menus.*',
            'admin.system.branding.edit' => 'admin.system.branding.*',
            default => $route,
        };
    }

    protected function routeOverrideFor(Menu $menu): ?string
    {
        if ($menu->section === 'project-management' && $menu->title === 'Tasks') {
            return 'admin.projects.tasks.index';
        }

        if ($menu->section === 'project-management' && $menu->title === 'Milestones') {
            return 'admin.projects.milestones.index';
        }

        return null;
    }

    protected function normalizeIcon(?string $icon): string
    {
        if (! filled($icon)) {
            return 'dashboard';
        }

        return str($icon)->after('tabler-')->toString();
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    public function fallbackMenus(): array
    {
        return [
            'dashboardMenu' => [
                ['title' => 'CRM Overview', 'icon' => 'dashboard', 'route' => 'admin.dashboard'],
            ],
            'customersMenu' => [
                ['title' => 'Customer List', 'icon' => 'user', 'route' => 'admin.customers.index', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
                ['title' => 'Customer Profile', 'icon' => 'user', 'route' => 'admin.customers.profile', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
                ['title' => 'Interaction History', 'icon' => 'mail', 'route' => 'admin.customers.interactions', 'badge' => 'MVP Basic', 'permission' => 'interactions.view'],
                ['title' => 'Transactions', 'icon' => 'cart', 'route' => 'admin.customers.transactions', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
                ['title' => 'Preferences', 'icon' => 'lock', 'route' => 'admin.customers.preferences', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
                ['title' => 'Behavior', 'icon' => 'activity', 'route' => 'admin.customers.behavior', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ],
            'salesMenu' => [
                ['title' => 'Lead Management', 'icon' => 'lead', 'route' => 'admin.sales.leads', 'active' => 'admin.sales.leads*', 'permission' => 'leads.view'],
                ['title' => 'Opportunity Management', 'icon' => 'opportunity', 'route' => 'admin.sales.opportunities', 'active' => 'admin.sales.opportunities*', 'permission' => 'opportunities.view'],
                ['title' => 'Sales Activity Tracking', 'icon' => 'activity', 'route' => 'admin.sales.activities.index', 'active' => 'admin.sales.activities.*', 'permission' => 'activities.view'],
                ['title' => 'Quotation & Deal', 'icon' => 'deal', 'route' => 'admin.sales.deals.index', 'active' => 'admin.sales.deals.*', 'permission' => 'quotations.view'],
                ['title' => 'Pipeline & Forecasting', 'icon' => 'pipeline', 'route' => 'admin.sales.pipeline', 'active' => 'admin.sales.pipeline*', 'permission' => 'pipeline.view'],
                ['title' => 'Win/Lost Analysis', 'icon' => 'analysis', 'route' => 'admin.sales.win-loss', 'permission' => 'winloss.view'],
            ],
            'projectMenu' => [
                ['title' => 'Project Dashboard', 'icon' => 'dashboard', 'route' => 'admin.projects.dashboard', 'active' => 'admin.projects.dashboard', 'permission' => 'projects.view'],
                ['title' => 'Projects', 'icon' => 'pipeline', 'route' => 'admin.projects.index', 'active' => ['admin.projects.index', 'admin.projects.create', 'admin.projects.store', 'admin.projects.show', 'admin.projects.edit', 'admin.projects.update', 'admin.projects.members.*'], 'permission' => 'projects.view'],
                ['title' => 'Milestones', 'icon' => 'calendar', 'route' => 'admin.projects.milestones.index', 'active' => 'admin.projects.milestones.*', 'permission' => 'project.milestone.read'],
                ['title' => 'Tasks', 'icon' => 'activity', 'route' => 'admin.projects.tasks.index', 'active' => 'admin.projects.tasks.*', 'permission' => 'projects.view'],
            ],
            'marketingMenu' => [
                ['title' => 'Audience Segmentation', 'icon' => 'audience', 'route' => 'admin.marketing.audiences.index', 'permission' => 'audiences.view'],
                ['title' => 'Lead Scoring & Routing', 'icon' => 'scoring', 'route' => 'admin.marketing.lead-scoring.index', 'permission' => 'lead_scoring.view'],
                ['title' => 'Campaign Management', 'icon' => 'campaign', 'route' => 'admin.marketing.campaigns.index', 'permission' => 'campaigns.view'],
                ['title' => 'Landing Page & Form', 'icon' => 'landing', 'route' => 'admin.marketing.landing-pages.index', 'permission' => 'landing_pages.view'],
                ['title' => 'Campaign Execution', 'icon' => 'execution', 'route' => 'admin.marketing.executions.index', 'permission' => 'executions.view'],
                ['title' => 'Automation & Nurturing', 'icon' => 'automation', 'route' => 'admin.marketing.automations.index', 'permission' => 'automations.view'],
                ['title' => 'Social Media Engagement', 'icon' => 'social', 'route' => 'admin.marketing.social-engagements.index', 'permission' => 'social.view'],
            ],
            'whatsAppMarketingMenu' => [
                ['title' => 'WhatsApp Providers', 'icon' => 'chat', 'route' => 'admin.system.whatsapp-providers.index', 'permission' => 'whatsapp_providers.view'],
                ['title' => 'WhatsApp Cloud API', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-cloud-api.index', 'permission' => 'whatsapp_cloud_api.view'],
                ['title' => 'WhatsApp Templates', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-templates.index', 'permission' => 'whatsapp_templates.view'],
                ['title' => 'WhatsApp Broadcast', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-broadcasts.index', 'permission' => 'whatsapp_broadcasts.view'],
                ['title' => 'WhatsApp Reply Inbox', 'icon' => 'inbox', 'route' => 'admin.marketing.whatsapp-replies.index', 'permission' => 'whatsapp_replies.view'],
            ],
            'serviceMenu' => [
                ['title' => 'Omnichannel Inbox', 'icon' => 'inbox', 'route' => 'admin.service.omnichannel.index', 'permission' => 'omnichannel.view'],
                ['title' => 'Ticket Management', 'icon' => 'ticket', 'route' => 'admin.service.tickets.index', 'permission' => 'tickets.view'],
                ['title' => 'SLA Management', 'icon' => 'timer', 'route' => 'admin.service.sla.index', 'permission' => 'sla.view'],
                ['title' => 'Case Resolution', 'icon' => 'case', 'route' => 'admin.service.case-resolutions.index', 'permission' => 'cases.view'],
                ['title' => 'Customer Satisfaction', 'icon' => 'star', 'route' => 'admin.service.customer-satisfaction.index', 'permission' => 'csat.view'],
                ['title' => 'Knowledge Base', 'icon' => 'book', 'route' => 'admin.service.knowledge-base.index', 'permission' => 'knowledge.view'],
            ],
            'systemMenu' => [
                ['title' => 'Users', 'icon' => 'user', 'route' => 'admin.system.users.index', 'active' => 'admin.system.users.*', 'permission' => 'users.view'],
                ['title' => 'Roles & Permissions', 'icon' => 'lock', 'route' => 'admin.system.roles.index', 'active' => 'admin.system.roles.*', 'permission' => 'roles.view'],
                ['title' => 'Menu Management', 'icon' => 'list', 'route' => 'admin.system.menus.index', 'active' => 'admin.system.menus.*', 'permission' => 'menus.view'],
                ['title' => 'Branding', 'icon' => 'brand', 'route' => 'admin.system.branding.edit', 'active' => 'admin.system.branding.*', 'permission' => 'branding.view'],
            ],
        ];
    }
}
