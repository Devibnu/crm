<?php

namespace App\Providers;

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
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
        Gate::before(static fn (User $user): ?bool => $user->hasRole('super_admin') ? true : null);

        Relation::morphMap([
            'lead' => \App\Models\Lead::class,
            'opportunity' => \App\Models\Opportunity::class,
            'customer' => \App\Models\Customer::class,
        ]);

        View::share('dashboardMenu', [
            ['title' => 'CRM Overview', 'icon' => 'dashboard', 'route' => 'admin.dashboard'],
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
            ['title' => 'Opportunity Management', 'icon' => 'opportunity', 'route' => 'admin.sales.opportunities', 'active' => 'admin.sales.opportunities*', 'permission' => 'opportunities.view'],
            ['title' => 'Sales Activity Tracking', 'icon' => 'activity', 'route' => 'admin.sales.activities.index', 'active' => 'admin.sales.activities.*', 'permission' => 'activities.view'],
            ['title' => 'Quotation & Deal', 'icon' => 'deal', 'route' => 'admin.sales.deals.index', 'active' => 'admin.sales.deals.*', 'permission' => 'quotations.view'],
            ['title' => 'Pipeline & Forecasting', 'icon' => 'pipeline', 'route' => 'admin.sales.pipeline', 'active' => 'admin.sales.pipeline*', 'permission' => 'pipeline.view'],
            ['title' => 'Win/Lost Analysis', 'icon' => 'analysis', 'route' => 'admin.sales.win-loss', 'permission' => 'winloss.view'],
        ]);

        View::share('customersMenu', [
            ['title' => 'Customer List', 'icon' => 'user', 'route' => 'admin.customers.index', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ['title' => 'Customer Profile', 'icon' => 'user', 'route' => 'admin.customers.profile', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ['title' => 'Interaction History', 'icon' => 'mail', 'route' => 'admin.customers.interactions', 'badge' => 'MVP Basic', 'permission' => 'interactions.view'],
            ['title' => 'Transactions', 'icon' => 'cart', 'route' => 'admin.customers.transactions', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ['title' => 'Preferences', 'icon' => 'lock', 'route' => 'admin.customers.preferences', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
            ['title' => 'Behavior', 'icon' => 'activity', 'route' => 'admin.customers.behavior', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
        ]);

        View::share('marketingMenu', [
            ['title' => 'Audience Segmentation', 'icon' => 'audience', 'route' => 'admin.marketing.audiences.index', 'permission' => 'audiences.view'],
            ['title' => 'Lead Scoring & Routing', 'icon' => 'scoring', 'route' => 'admin.marketing.lead-scoring.index', 'permission' => 'lead_scoring.view'],
            ['title' => 'Campaign Management', 'icon' => 'campaign', 'route' => 'admin.marketing.campaigns.index', 'permission' => 'campaigns.view'],
            ['title' => 'Landing Page & Form', 'icon' => 'landing', 'route' => 'admin.marketing.landing-pages.index', 'permission' => 'landing_pages.view'],
            ['title' => 'Campaign Execution', 'icon' => 'execution', 'route' => 'admin.marketing.executions.index', 'permission' => 'executions.view'],
            ['title' => 'Automation & Nurturing', 'icon' => 'automation', 'route' => 'admin.marketing.automations.index', 'permission' => 'automations.view'],
            ['title' => 'Social Media Engagement', 'icon' => 'social', 'route' => 'admin.marketing.social-engagements.index', 'permission' => 'social.view'],
        ]);

        View::share('whatsAppMarketingMenu', [
            ['title' => 'WhatsApp Providers', 'icon' => 'chat', 'route' => 'admin.system.whatsapp-providers.index', 'permission' => 'whatsapp_providers.view'],
            ['title' => 'WhatsApp Cloud API', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-cloud-api.index', 'permission' => 'whatsapp_cloud_api.view'],
            ['title' => 'WhatsApp Templates', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-templates.index', 'permission' => 'whatsapp_templates.view'],
            ['title' => 'WhatsApp Broadcast', 'icon' => 'chat', 'route' => 'admin.marketing.whatsapp-broadcasts.index', 'permission' => 'whatsapp_broadcasts.view'],
            ['title' => 'WhatsApp Reply Inbox', 'icon' => 'inbox', 'route' => 'admin.marketing.whatsapp-replies.index', 'permission' => 'whatsapp_replies.view'],
        ]);

        View::share('systemMenu', [
            ['title' => 'Users', 'icon' => 'user', 'route' => 'admin.system.users.index', 'active' => 'admin.system.users.*', 'permission' => 'users.view'],
            ['title' => 'Roles & Permissions', 'icon' => 'lock', 'route' => 'admin.system.roles.index', 'active' => 'admin.system.roles.*', 'permission' => 'roles.view'],
            ['title' => 'Menu Management', 'icon' => 'list', 'route' => 'admin.system.menus.index', 'active' => 'admin.system.menus.*', 'permission' => 'menus.view'],
            ['title' => 'Branding', 'icon' => 'brand', 'route' => 'admin.system.branding.edit', 'active' => 'admin.system.branding.*', 'permission' => 'branding.view'],
        ]);

        View::composer('*', function ($view) {
            $view->with('branding', BrandingSetting::current());
        });
    }
}
