<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AudienceSegmentController;
use App\Http\Controllers\Admin\CampaignExecutionController;
use App\Http\Controllers\Admin\CustomerBehaviorController;
use App\Http\Controllers\Admin\CustomerInteractionController;
use App\Http\Controllers\Admin\CustomerPreferenceController;
use App\Http\Controllers\Admin\CustomerSatisfactionController;
use App\Http\Controllers\Admin\CustomerTransactionController;
use App\Http\Controllers\Admin\CaseResolutionController;
use App\Http\Controllers\Admin\KnowledgeBaseController;
use App\Http\Controllers\Admin\LandingPageController;
use App\Http\Controllers\Admin\LeadController;
use App\Http\Controllers\Admin\LeadScoringRuleController;
use App\Http\Controllers\Admin\MarketingCampaignController;
use App\Http\Controllers\Admin\MarketingAutomationController;
use App\Http\Controllers\Admin\WhatsAppBroadcastController;
use App\Http\Controllers\Admin\WhatsAppCloudApiController;
use App\Http\Controllers\Admin\WhatsAppProviderController;
use App\Http\Controllers\Admin\WhatsAppReplyInboxController;
use App\Http\Controllers\Admin\WhatsAppTemplateController;
use App\Http\Controllers\Webhook\WhatsAppWebhookController;
use App\Http\Controllers\Admin\OmnichannelInboxController;
use App\Http\Controllers\Admin\OpportunityController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\SalesActivityController;
use App\Http\Controllers\Admin\SalesPipelineController;
use App\Http\Controllers\Admin\SlaPolicyController;
use App\Http\Controllers\Admin\SocialMediaEngagementController;
use App\Http\Controllers\Admin\SystemRoleController;
use App\Http\Controllers\Admin\System\MenuController as SystemMenuController;
use App\Http\Controllers\Admin\System\UserRoleController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\WinLostAnalysisController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$serviceMenu = [
    ['title' => 'Omnichannel Inbox', 'icon' => 'inbox', 'route' => 'admin.service.omnichannel.index', 'permission' => 'omnichannel.view'],
    ['title' => 'Ticket Management', 'icon' => 'ticket', 'route' => 'admin.service.tickets.index', 'permission' => 'tickets.view'],
    ['title' => 'SLA Management', 'icon' => 'timer', 'route' => 'admin.service.sla.index', 'permission' => 'sla.view'],
    ['title' => 'Case Resolution', 'icon' => 'case', 'route' => 'admin.service.case-resolutions.index', 'permission' => 'cases.view'],
    ['title' => 'Customer Satisfaction', 'icon' => 'star', 'route' => 'admin.service.customer-satisfaction.index', 'permission' => 'csat.view'],
    ['title' => 'Knowledge Base', 'icon' => 'book', 'route' => 'admin.service.knowledge-base.index', 'permission' => 'knowledge.view'],
];

$salesMenu = [
    ['title' => 'Lead Management', 'icon' => 'lead', 'route' => 'admin.sales.leads', 'permission' => 'leads.view'],
    ['title' => 'Opportunity Management', 'icon' => 'opportunity', 'route' => 'admin.sales.opportunities', 'permission' => 'opportunities.view'],
    ['title' => 'Pipeline & Forecasting', 'icon' => 'pipeline', 'route' => 'admin.sales.pipeline', 'permission' => 'pipeline.view'],
    ['title' => 'Sales Activity Tracking', 'icon' => 'activity', 'route' => 'admin.sales.activities.index', 'permission' => 'activities.view'],
    ['title' => 'Quotation & Deal', 'icon' => 'deal', 'route' => 'admin.sales.deals.index', 'permission' => 'quotations.view'],
    ['title' => 'Win/Lost Analysis', 'icon' => 'analysis', 'route' => 'admin.sales.win-loss', 'permission' => 'winloss.view'],
];

$customersMenu = [
    ['title' => 'Customer List', 'icon' => 'user', 'route' => 'admin.customers.index', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
    ['title' => 'Customer Profile', 'icon' => 'user', 'route' => 'admin.customers.profile', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
    ['title' => 'Interaction History', 'icon' => 'mail', 'route' => 'admin.customers.interactions', 'badge' => 'MVP Basic', 'permission' => 'interactions.view'],
    ['title' => 'Preferences', 'icon' => 'lock', 'route' => 'admin.customers.preferences', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
    ['title' => 'Transactions', 'icon' => 'cart', 'route' => 'admin.customers.transactions', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
    ['title' => 'Behavior', 'icon' => 'activity', 'route' => 'admin.customers.behavior', 'badge' => 'MVP Basic', 'permission' => 'customers.view'],
];

$marketingMenu = [
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
];

$systemMenu = [
    ['title' => 'Users', 'icon' => 'user', 'route' => 'admin.system.users.index'],
    ['title' => 'Roles & Permissions', 'icon' => 'lock', 'route' => 'admin.system.roles.index'],
    ['title' => 'Menu Management', 'icon' => 'list', 'route' => 'admin.system.menus.index'],
    ['title' => 'WhatsApp Providers', 'icon' => 'chat', 'route' => 'admin.system.whatsapp-providers.index'],
];

$dashboardMenu = [
    ['title' => 'CRM Overview', 'icon' => 'dashboard', 'route' => 'admin.dashboard'],
    ['title' => 'Service Management', 'icon' => 'ticket', 'route' => 'admin.dashboard.service'],
    ['title' => 'Sales Enablement', 'icon' => 'pipeline', 'route' => 'admin.dashboard.sales'],
    ['title' => 'Marketing Automation', 'icon' => 'campaign', 'route' => 'admin.dashboard.marketing'],
    ['title' => 'Customer Profile 360', 'icon' => 'user', 'route' => 'admin.dashboard.customer'],
];

View::share('dashboardMenu', $dashboardMenu);
View::share('serviceMenu', $serviceMenu);
View::share('salesMenu', $salesMenu);
View::share('customersMenu', $customersMenu);
View::share('marketingMenu', $marketingMenu);
View::share('systemMenu', $systemMenu);

$applyResourceMiddleware = function ($resource, string $permission) {
    return $resource
        ->middlewareFor(['index', 'show'], "permission:{$permission}.view")
        ->middlewareFor(['create', 'store'], "permission:{$permission}.create")
        ->middlewareFor(['edit', 'update'], "permission:{$permission}.update")
        ->middlewareFor('destroy', "permission:{$permission}.delete");
};

Route::redirect('/', '/login')->name('home');
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');
Route::view('/dashboards/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.dashboards');
Route::view('/service/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.service');
Route::view('/sales/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.sales');
Route::view('/apps/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.apps');
Route::view('/pages/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.pages');
Route::redirect('/vuexy', '/');
Route::redirect('/vuexy/{any}', '/')->where('any', '.*');
Route::post('/webhooks/whatsapp/fonnte', [WhatsAppWebhookController::class, 'handleFonnte'])->name('webhooks.whatsapp.fonnte');
Route::get('/webhooks/whatsapp/meta', [WhatsAppWebhookController::class, 'verifyMeta'])->name('webhooks.whatsapp.meta.verify');
Route::post('/webhooks/whatsapp/meta', [WhatsAppWebhookController::class, 'handleMeta'])->name('webhooks.whatsapp.meta');

Route::middleware('auth')->group(function () use ($applyResourceMiddleware) {
Route::get('/admin', [DashboardController::class, 'index'])->name('admin.dashboard');
Route::prefix('admin/dashboard')->name('admin.dashboard.')->group(function () {
    Route::get('crm-overview', [DashboardController::class, 'crmOverview'])->name('crm');
    Route::get('service-management', [DashboardController::class, 'serviceManagement'])->name('service');
    Route::get('sales-enablement', [DashboardController::class, 'salesEnablement'])->name('sales');
    Route::get('marketing-automation', [DashboardController::class, 'marketingAutomation'])->name('marketing');
    Route::get('customer-profile', [DashboardController::class, 'customerProfile'])->name('customer');
});

Route::prefix('admin/service')->name('admin.service.')->group(function () use ($applyResourceMiddleware) {
    Route::get('omnichannel-inbox', [OmnichannelInboxController::class, 'index'])
        ->middleware('permission:omnichannel.view')
        ->name('omnichannel-inbox');
    Route::post('omnichannel/conversations/{conversation}/reply', [OmnichannelInboxController::class, 'reply'])
        ->middleware('permission:omnichannel.create')
        ->name('omnichannel.reply');
    Route::post('omnichannel/conversations/{conversation}/assign', [OmnichannelInboxController::class, 'assign'])
        ->middleware('permission:omnichannel.update')
        ->name('omnichannel.assign');
    Route::post('omnichannel/conversations/{conversation}/resolve', [OmnichannelInboxController::class, 'resolve'])
        ->middleware('permission:omnichannel.update')
        ->name('omnichannel.resolve');
    $applyResourceMiddleware(Route::resource('omnichannel', OmnichannelInboxController::class), 'omnichannel');
    $applyResourceMiddleware(Route::resource('tickets', TicketController::class), 'tickets');
    Route::resource('sla', SlaPolicyController::class)->middleware('permission:sla.view');
    Route::resource('case-resolutions', CaseResolutionController::class)->middleware('permission:cases.view');
    Route::resource('customer-satisfaction', CustomerSatisfactionController::class)->middleware('permission:csat.view');
    $applyResourceMiddleware(Route::resource('knowledge-base', KnowledgeBaseController::class), 'knowledge');
});

Route::prefix('admin/sales')->name('admin.sales.')->group(function () use ($applyResourceMiddleware) {
    Route::get('/leads', [LeadController::class, 'index'])->middleware('permission:leads.view')->name('leads');
    Route::get('/leads/create', [LeadController::class, 'create'])->middleware('permission:leads.create')->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->middleware('permission:leads.create')->name('leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->middleware('permission:leads.view')->whereNumber('lead')->name('leads.show');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->middleware('permission:leads.update')->whereNumber('lead')->name('leads.edit');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->middleware('permission:leads.update')->whereNumber('lead')->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->middleware('permission:leads.delete')->whereNumber('lead')->name('leads.destroy');
    Route::get('/opportunities', [OpportunityController::class, 'index'])->middleware('permission:opportunities.view')->name('opportunities');
    Route::get('/opportunities/create', [OpportunityController::class, 'create'])->middleware('permission:opportunities.create')->name('opportunities.create');
    Route::post('/opportunities', [OpportunityController::class, 'store'])->middleware('permission:opportunities.create')->name('opportunities.store');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->middleware('permission:opportunities.view')->whereNumber('opportunity')->name('opportunities.show');
    Route::get('/opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])->middleware('permission:opportunities.update')->whereNumber('opportunity')->name('opportunities.edit');
    Route::put('/opportunities/{opportunity}', [OpportunityController::class, 'update'])->middleware('permission:opportunities.update')->whereNumber('opportunity')->name('opportunities.update');
    Route::delete('/opportunities/{opportunity}', [OpportunityController::class, 'destroy'])->middleware('permission:opportunities.delete')->whereNumber('opportunity')->name('opportunities.destroy');
    Route::get('/pipeline', [SalesPipelineController::class, 'index'])->middleware('permission:pipeline.view')->name('pipeline');
    $applyResourceMiddleware(Route::resource('activities', SalesActivityController::class), 'activities');
    $applyResourceMiddleware(Route::resource('deals', QuotationController::class)->parameters(['deals' => 'quotation']), 'quotations');
    Route::get('/win-loss', [WinLostAnalysisController::class, 'index'])->middleware('permission:winloss.view')->name('win-loss');
    Route::redirect('/win-lost-analysis', '/admin/sales/win-loss')->name('win-lost-analysis');
    Route::redirect('/winloss', '/admin/sales/win-loss')->name('winloss');
});

Route::prefix('admin/marketing')->name('admin.marketing.')->group(function () use ($applyResourceMiddleware) {
    $applyResourceMiddleware(Route::resource('campaigns', MarketingCampaignController::class), 'campaigns');
    $applyResourceMiddleware(Route::resource('audiences', AudienceSegmentController::class), 'audiences');
    $applyResourceMiddleware(Route::resource('executions', CampaignExecutionController::class), 'executions');
    $applyResourceMiddleware(Route::resource('landing-pages', LandingPageController::class), 'landing_pages');
    $applyResourceMiddleware(Route::resource('social-engagements', SocialMediaEngagementController::class), 'social');
    $applyResourceMiddleware(Route::resource('automations', MarketingAutomationController::class), 'automations');
    $applyResourceMiddleware(Route::resource('lead-scoring', LeadScoringRuleController::class)->parameters(['lead-scoring' => 'leadScoring']), 'lead_scoring');
    Route::get('whatsapp-cloud-api', [WhatsAppCloudApiController::class, 'index'])->name('whatsapp-cloud-api.index');
    Route::post('whatsapp-cloud-api/sync', [WhatsAppCloudApiController::class, 'sync'])->name('whatsapp-cloud-api.sync');
    Route::post('whatsapp-cloud-api/refresh-connection', [WhatsAppCloudApiController::class, 'refreshConnection'])->name('whatsapp-cloud-api.refresh-connection');
    Route::get('whatsapp-cloud-api/templates/{template}', [WhatsAppCloudApiController::class, 'show'])->name('whatsapp-cloud-api.templates.show');
    Route::post('whatsapp-cloud-api/templates/{template}/default', [WhatsAppCloudApiController::class, 'setDefault'])->name('whatsapp-cloud-api.templates.default');
    Route::post('whatsapp-cloud-api/templates/{template}/send-test', [WhatsAppCloudApiController::class, 'sendTest'])->name('whatsapp-cloud-api.templates.send-test');
    Route::post('whatsapp-templates/sync', [WhatsAppTemplateController::class, 'sync'])->name('whatsapp-templates.sync');
    Route::post('whatsapp-templates/{whatsappTemplate}/default', [WhatsAppTemplateController::class, 'setDefault'])->name('whatsapp-templates.default');
    Route::post('whatsapp-templates/{whatsappTemplate}/send-test', [WhatsAppTemplateController::class, 'sendTest'])->name('whatsapp-templates.send-test');
    Route::resource('whatsapp-templates', WhatsAppTemplateController::class)->parameters(['whatsapp-templates' => 'whatsappTemplate']);
    Route::post('whatsapp-broadcasts/{whatsappBroadcast}/start', [WhatsAppBroadcastController::class, 'start'])->name('whatsapp-broadcasts.start');
    Route::post('whatsapp-broadcasts/{whatsappBroadcast}/pause', [WhatsAppBroadcastController::class, 'pause'])->name('whatsapp-broadcasts.pause');
    Route::post('whatsapp-broadcasts/{whatsappBroadcast}/resume', [WhatsAppBroadcastController::class, 'resume'])->name('whatsapp-broadcasts.resume');
    Route::post('whatsapp-broadcasts/{whatsappBroadcast}/retry-queue', [WhatsAppBroadcastController::class, 'retryQueue'])->name('whatsapp-broadcasts.retry-queue');
    Route::resource('whatsapp-broadcasts', WhatsAppBroadcastController::class);
    Route::get('/whatsapp-replies', [WhatsAppReplyInboxController::class, 'index'])->name('whatsapp-replies.index');
});

Route::prefix('admin/customers')->name('admin.customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->middleware('permission:customers.view')->name('index');
    Route::get('/create', [CustomerController::class, 'create'])->middleware('permission:customers.create')->name('create');
    Route::post('/', [CustomerController::class, 'store'])->middleware('permission:customers.create')->name('store');
    Route::get('/interactions', [CustomerInteractionController::class, 'index'])->middleware('permission:interactions.view')->name('interactions');
    Route::get('/{customer}/interactions/create', [CustomerInteractionController::class, 'create'])->middleware('permission:interactions.create')->whereNumber('customer')->name('interactions.create');
    Route::post('/{customer}/interactions', [CustomerInteractionController::class, 'store'])->middleware('permission:interactions.create')->whereNumber('customer')->name('interactions.store');
    Route::get('/interactions/{interaction}/edit', [CustomerInteractionController::class, 'edit'])->middleware('permission:interactions.update')->whereNumber('interaction')->name('interactions.edit');
    Route::put('/interactions/{interaction}', [CustomerInteractionController::class, 'update'])->middleware('permission:interactions.update')->whereNumber('interaction')->name('interactions.update');
    Route::delete('/interactions/{interaction}', [CustomerInteractionController::class, 'destroy'])->middleware('permission:interactions.delete')->whereNumber('interaction')->name('interactions.destroy');
    Route::get('/transactions', [CustomerTransactionController::class, 'index'])->middleware('permission:customers.view')->name('transactions');
    Route::get('/{customer}/transactions/create', [CustomerTransactionController::class, 'create'])->middleware('permission:customers.create')->whereNumber('customer')->name('transactions.create');
    Route::post('/{customer}/transactions', [CustomerTransactionController::class, 'store'])->middleware('permission:customers.create')->whereNumber('customer')->name('transactions.store');
    Route::get('/transactions/{transaction}/edit', [CustomerTransactionController::class, 'edit'])->middleware('permission:customers.update')->whereNumber('transaction')->name('transactions.edit');
    Route::put('/transactions/{transaction}', [CustomerTransactionController::class, 'update'])->middleware('permission:customers.update')->whereNumber('transaction')->name('transactions.update');
    Route::delete('/transactions/{transaction}', [CustomerTransactionController::class, 'destroy'])->middleware('permission:customers.delete')->whereNumber('transaction')->name('transactions.destroy');
    Route::get('/preferences', [CustomerPreferenceController::class, 'index'])->middleware('permission:customers.view')->name('preferences');
    Route::get('/{customer}/preferences/create', [CustomerPreferenceController::class, 'create'])->middleware('permission:customers.create')->whereNumber('customer')->name('preferences.create');
    Route::post('/{customer}/preferences', [CustomerPreferenceController::class, 'store'])->middleware('permission:customers.create')->whereNumber('customer')->name('preferences.store');
    Route::get('/preferences/{preference}/edit', [CustomerPreferenceController::class, 'edit'])->middleware('permission:customers.update')->whereNumber('preference')->name('preferences.edit');
    Route::put('/preferences/{preference}', [CustomerPreferenceController::class, 'update'])->middleware('permission:customers.update')->whereNumber('preference')->name('preferences.update');
    Route::delete('/preferences/{preference}', [CustomerPreferenceController::class, 'destroy'])->middleware('permission:customers.delete')->whereNumber('preference')->name('preferences.destroy');
    Route::get('/behavior', [CustomerBehaviorController::class, 'index'])->middleware('permission:customers.view')->name('behavior');
    Route::get('/{customer}/behavior/create', [CustomerBehaviorController::class, 'create'])->middleware('permission:customers.create')->whereNumber('customer')->name('behavior.create');
    Route::post('/{customer}/behavior', [CustomerBehaviorController::class, 'store'])->middleware('permission:customers.create')->whereNumber('customer')->name('behavior.store');
    Route::get('/behavior/{behavior}/edit', [CustomerBehaviorController::class, 'edit'])->middleware('permission:customers.update')->whereNumber('behavior')->name('behavior.edit');
    Route::put('/behavior/{behavior}', [CustomerBehaviorController::class, 'update'])->middleware('permission:customers.update')->whereNumber('behavior')->name('behavior.update');
    Route::delete('/behavior/{behavior}', [CustomerBehaviorController::class, 'destroy'])->middleware('permission:customers.delete')->whereNumber('behavior')->name('behavior.destroy');

    Route::view('/profile', 'admin.customers.profile')->middleware('permission:customers.view')->name('profile');

    Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->middleware('permission:customers.update')->whereNumber('customer')->name('edit');
    Route::put('/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.update')->whereNumber('customer')->name('update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customers.delete')->whereNumber('customer')->name('destroy');
    Route::get('/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view')->whereNumber('customer')->name('show');
});

Route::prefix('admin/system')->name('admin.system.')->middleware('role:super_admin|admin')->group(function () {
    Route::get('users', [UserRoleController::class, 'index'])->name('users.index');
    Route::put('users/{user}', [UserRoleController::class, 'update'])->name('users.update');
    Route::get('menus/preview', [SystemMenuController::class, 'preview'])->name('menus.preview');
    Route::post('menus/reorder', [SystemMenuController::class, 'reorder'])->name('menus.reorder');
    Route::resource('menus', SystemMenuController::class)->except('show');
    Route::resource('roles', SystemRoleController::class);
    // TODO remove on production: temporary internal route for WhatsApp provider connection testing.
    Route::post('whatsapp-providers/test-send', [WhatsAppProviderController::class, 'testSend'])->name('whatsapp-providers.test-send');
    Route::resource('whatsapp-providers', WhatsAppProviderController::class);
});
});
