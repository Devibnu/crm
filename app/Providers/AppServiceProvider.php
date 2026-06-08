<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            'lead' => \App\Models\Lead::class,
            'opportunity' => \App\Models\Opportunity::class,
            'customer' => \App\Models\Customer::class,
        ]);

        View::share('dashboardMenu', [
            ['title' => 'CRM Overview', 'icon' => 'dashboard', 'route' => 'admin.dashboard'],
            ['title' => 'Service Management', 'icon' => 'ticket', 'route' => 'admin.dashboard.service'],
            ['title' => 'Sales Enablement', 'icon' => 'pipeline', 'route' => 'admin.dashboard.sales'],
            ['title' => 'Marketing Automation', 'icon' => 'campaign', 'route' => 'admin.dashboard.marketing'],
            ['title' => 'Customer Profile 360', 'icon' => 'user', 'route' => 'admin.dashboard.customer'],
        ]);

        View::share('serviceMenu', [
            ['title' => 'Omnichannel Inbox', 'icon' => 'inbox', 'route' => 'admin.service.omnichannel.index', 'permission' => 'omnichannel.view'],
            ['title' => 'Ticket Management', 'icon' => 'ticket', 'route' => 'admin.service.tickets.index', 'permission' => 'tickets.view'],
            ['title' => 'SLA Management', 'icon' => 'timer', 'route' => 'admin.service.sla.index', 'permission' => 'sla.view'],
            ['title' => 'Case Resolution', 'icon' => 'case', 'route' => 'admin.service.case-resolutions.index', 'permission' => 'cases.view'],
            ['title' => 'Customer Satisfaction', 'icon' => 'star', 'route' => 'admin.service.customer-satisfaction.index', 'permission' => 'csat.view'],
            ['title' => 'Knowledge Base', 'icon' => 'book', 'route' => 'admin.service.knowledge-base.index', 'permission' => 'knowledge.view'],
        ]);

        View::share('salesMenu', [
            ['title' => 'Lead Management', 'icon' => 'lead', 'route' => 'admin.sales.leads', 'permission' => 'leads.view'],
            ['title' => 'Opportunity Management', 'icon' => 'opportunity', 'route' => 'admin.sales.opportunities', 'permission' => 'opportunities.view'],
            ['title' => 'Pipeline & Forecasting', 'icon' => 'pipeline', 'route' => 'admin.sales.pipeline', 'permission' => 'pipeline.view'],
            ['title' => 'Sales Activity Tracking', 'icon' => 'activity', 'route' => 'admin.sales.activities.index', 'permission' => 'activities.view'],
            ['title' => 'Quotation & Deal', 'icon' => 'deal', 'route' => 'admin.sales.deals.index', 'permission' => 'quotations.view'],
            ['title' => 'Win/Lost Analysis', 'icon' => 'analysis', 'route' => 'admin.sales.win-loss', 'permission' => 'winloss.view'],
        ]);

        View::share('customersMenu', [
            ['title' => 'Customer List', 'icon' => 'user', 'route' => 'admin.customers.index', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ['title' => 'Customer Profile', 'icon' => 'user', 'route' => 'admin.customers.profile', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ['title' => 'Interaction History', 'icon' => 'mail', 'route' => 'admin.customers.interactions', 'badge' => 'MVP Basic', 'permission' => 'interactions.view'],
            ['title' => 'Preferences', 'icon' => 'lock', 'route' => 'admin.customers.preferences', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ['title' => 'Transactions', 'icon' => 'cart', 'route' => 'admin.customers.transactions', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ['title' => 'Behavior', 'icon' => 'activity', 'route' => 'admin.customers.behavior', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
        ]);

        View::share('marketingMenu', [
            ['title' => 'Campaign Management', 'icon' => 'campaign', 'route' => 'admin.marketing.campaigns.index', 'permission' => 'campaigns.view'],
            ['title' => 'Audience Segmentation', 'icon' => 'audience', 'route' => 'admin.marketing.audiences.index', 'permission' => 'audiences.view'],
            ['title' => 'Campaign Execution', 'icon' => 'execution', 'route' => 'admin.marketing.executions.index', 'permission' => 'executions.view'],
            ['title' => 'Landing Page & Form', 'icon' => 'landing', 'route' => 'admin.marketing.landing-pages.index', 'permission' => 'landing_pages.view'],
            ['title' => 'Social Media Engagement', 'icon' => 'social', 'route' => 'admin.marketing.social-engagements.index', 'permission' => 'social.view'],
            ['title' => 'Automation & Nurturing', 'icon' => 'automation', 'route' => 'admin.marketing.automations.index', 'permission' => 'automations.view'],
            ['title' => 'WhatsApp Cloud API', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-cloud-api.index'],
            ['title' => 'WhatsApp Templates', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-templates.index'],
            ['title' => 'WhatsApp Broadcast', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-broadcasts.index'],
            ['title' => 'WhatsApp Reply Inbox', 'icon' => 'inbox', 'route' => 'admin.marketing.whatsapp-replies.index'],
            ['title' => 'Lead Scoring & Routing', 'icon' => 'scoring', 'route' => 'admin.marketing.lead-scoring.index', 'permission' => 'lead_scoring.view'],
        ]);

        View::share('systemMenu', [
            ['title' => 'Users', 'icon' => 'user', 'route' => 'admin.system.users.index'],
            ['title' => 'Roles & Permissions', 'icon' => 'lock', 'route' => 'admin.system.roles.index'],
            ['title' => 'Menu Management', 'icon' => 'list', 'route' => 'admin.system.menus.index'],
            ['title' => 'WhatsApp Providers', 'icon' => 'chat', 'route' => 'admin.system.whatsapp-providers.index'],
        ]);
    }
}
