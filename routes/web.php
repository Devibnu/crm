<?php

use App\Http\Controllers\Admin\CustomerController;
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
use App\Http\Controllers\Admin\MarketingCampaignController;
use App\Http\Controllers\Admin\OmnichannelInboxController;
use App\Http\Controllers\Admin\OpportunityController;
use App\Http\Controllers\Admin\QuotationController;
use App\Http\Controllers\Admin\SalesActivityController;
use App\Http\Controllers\Admin\SalesPipelineController;
use App\Http\Controllers\Admin\SlaPolicyController;
use App\Http\Controllers\Admin\SocialMediaEngagementController;
use App\Http\Controllers\Admin\TicketController;
use App\Http\Controllers\Admin\WinLostAnalysisController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$serviceMenu = [
    ['title' => 'Omnichannel Inbox', 'icon' => 'inbox', 'route' => 'admin.service.omnichannel.index'],
    ['title' => 'Ticket Management', 'icon' => 'ticket', 'route' => 'admin.service.tickets.index'],
    ['title' => 'SLA Management', 'icon' => 'timer', 'route' => 'admin.service.sla.index'],
    ['title' => 'Case Resolution', 'icon' => 'case', 'route' => 'admin.service.case-resolutions.index'],
    ['title' => 'Customer Satisfaction', 'icon' => 'star', 'route' => 'admin.service.customer-satisfaction.index'],
    ['title' => 'Knowledge Base', 'icon' => 'book', 'route' => 'admin.service.knowledge-base.index'],
];

$salesMenu = [
    ['title' => 'Lead Management', 'icon' => 'lead', 'route' => 'admin.sales.leads'],
    ['title' => 'Opportunity Management', 'icon' => 'opportunity', 'route' => 'admin.sales.opportunities'],
    ['title' => 'Pipeline & Forecasting', 'icon' => 'pipeline', 'route' => 'admin.sales.pipeline'],
    ['title' => 'Sales Activity Tracking', 'icon' => 'activity', 'route' => 'admin.sales.activities.index'],
    ['title' => 'Quotation & Deal', 'icon' => 'deal', 'route' => 'admin.sales.deals.index'],
    ['title' => 'Win/Lost Analysis', 'icon' => 'analysis', 'route' => 'admin.sales.win-loss'],
];

$customersMenu = [
    ['title' => 'Customer List', 'icon' => 'user', 'route' => 'admin.customers.index', 'badge' => 'MVP Basic'],
    ['title' => 'Customer Profile', 'icon' => 'user', 'route' => 'admin.customers.profile', 'badge' => 'MVP Basic'],
    ['title' => 'Interaction History', 'icon' => 'mail', 'route' => 'admin.customers.interactions', 'badge' => 'MVP Basic'],
    ['title' => 'Preferences', 'icon' => 'lock', 'route' => 'admin.customers.preferences', 'badge' => 'MVP Basic'],
    ['title' => 'Transactions', 'icon' => 'cart', 'route' => 'admin.customers.transactions', 'badge' => 'MVP Basic'],
    ['title' => 'Behavior', 'icon' => 'activity', 'route' => 'admin.customers.behavior', 'badge' => 'MVP Basic'],
];

$marketingMenu = [
    ['title' => 'Campaign Management', 'icon' => 'campaign', 'route' => 'admin.marketing.campaigns.index'],
    ['title' => 'Audience Segmentation', 'icon' => 'audience', 'route' => 'admin.marketing.audiences.index'],
    ['title' => 'Campaign Execution', 'icon' => 'execution', 'route' => 'admin.marketing.executions.index'],
    ['title' => 'Landing Page & Form', 'icon' => 'landing', 'route' => 'admin.marketing.landing-pages.index'],
    ['title' => 'Social Media Engagement', 'icon' => 'social', 'route' => 'admin.marketing.social-engagements.index'],
];

View::share('serviceMenu', $serviceMenu);
View::share('salesMenu', $salesMenu);
View::share('customersMenu', $customersMenu);
View::share('marketingMenu', $marketingMenu);

Route::redirect('/', '/admin')->name('home');
Route::view('/dashboards/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.dashboards');
Route::view('/service/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.service');
Route::view('/sales/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.sales');
Route::view('/apps/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.apps');
Route::view('/pages/{any?}', 'admin.vuexy')->where('any', '.*')->name('vuexy.pages');
Route::redirect('/vuexy', '/');
Route::redirect('/vuexy/{any}', '/')->where('any', '.*');

Route::get('/admin', function () {
    $stats = [
        [
            'title' => 'Orders',
            'subtitle' => 'Last Week',
            'value' => '124k',
            'change' => '+12.6%',
            'tone' => 'primary',
            'bars' => [58, 50, 22, 44, 48, 32, 68],
        ],
        [
            'title' => 'Sales',
            'subtitle' => 'Last Year',
            'value' => '175k',
            'change' => '-16.2%',
            'tone' => 'success',
            'line' => 'M0 58 C35 54 48 82 82 78 C126 72 119 8 170 18 C203 24 220 45 260 47',
        ],
        [
            'title' => 'Total Profit',
            'subtitle' => 'Last week',
            'value' => '1.28k',
            'change' => '-12.2%',
            'tone' => 'danger',
            'icon' => 'card',
        ],
        [
            'title' => 'Total Sales',
            'subtitle' => 'Last week',
            'value' => '$4,673',
            'change' => '+25.2%',
            'tone' => 'success',
            'icon' => 'cash',
        ],
    ];

    $earningBars = [
        ['month' => 'Jan', 'value' => 28],
        ['month' => 'Feb', 'value' => 10],
        ['month' => 'Mar', 'value' => 45, 'active' => true],
        ['month' => 'Apr', 'value' => 38],
        ['month' => 'May', 'value' => 15],
        ['month' => 'Jun', 'value' => 30],
        ['month' => 'Jul', 'value' => 35],
        ['month' => 'Aug', 'value' => 30],
        ['month' => 'Sep', 'value' => 8],
    ];

    return view('admin.dashboard', [
        'stats' => $stats,
        'earningBars' => $earningBars,
    ]);
})->name('admin.dashboard');

Route::prefix('admin/service')->name('admin.service.')->group(function () {
    Route::resource('omnichannel', OmnichannelInboxController::class);
    Route::resource('tickets', TicketController::class);
    Route::resource('sla', SlaPolicyController::class);
    Route::resource('case-resolutions', CaseResolutionController::class);
    Route::resource('customer-satisfaction', CustomerSatisfactionController::class);
    Route::resource('knowledge-base', KnowledgeBaseController::class);
});

Route::prefix('admin/sales')->name('admin.sales.')->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->name('leads');
    Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create');
    Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->whereNumber('lead')->name('leads.show');
    Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->whereNumber('lead')->name('leads.edit');
    Route::put('/leads/{lead}', [LeadController::class, 'update'])->whereNumber('lead')->name('leads.update');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->whereNumber('lead')->name('leads.destroy');
    Route::get('/opportunities', [OpportunityController::class, 'index'])->name('opportunities');
    Route::get('/opportunities/create', [OpportunityController::class, 'create'])->name('opportunities.create');
    Route::post('/opportunities', [OpportunityController::class, 'store'])->name('opportunities.store');
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show'])->whereNumber('opportunity')->name('opportunities.show');
    Route::get('/opportunities/{opportunity}/edit', [OpportunityController::class, 'edit'])->whereNumber('opportunity')->name('opportunities.edit');
    Route::put('/opportunities/{opportunity}', [OpportunityController::class, 'update'])->whereNumber('opportunity')->name('opportunities.update');
    Route::delete('/opportunities/{opportunity}', [OpportunityController::class, 'destroy'])->whereNumber('opportunity')->name('opportunities.destroy');
    Route::get('/pipeline', [SalesPipelineController::class, 'index'])->name('pipeline');
    Route::resource('activities', SalesActivityController::class);
    Route::resource('deals', QuotationController::class)->parameters(['deals' => 'quotation']);
    Route::get('/win-loss', [WinLostAnalysisController::class, 'index'])->name('win-loss');
    Route::redirect('/win-lost-analysis', '/admin/sales/win-loss')->name('win-lost-analysis');
    Route::redirect('/winloss', '/admin/sales/win-loss')->name('winloss');
});

Route::prefix('admin/marketing')->name('admin.marketing.')->group(function () {
    Route::resource('campaigns', MarketingCampaignController::class);
    Route::resource('audiences', AudienceSegmentController::class);
    Route::resource('executions', CampaignExecutionController::class);
    Route::resource('landing-pages', LandingPageController::class);
    Route::resource('social-engagements', SocialMediaEngagementController::class);
});

Route::prefix('admin/customers')->name('admin.customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::get('/create', [CustomerController::class, 'create'])->name('create');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::get('/interactions', [CustomerInteractionController::class, 'index'])->name('interactions');
    Route::get('/{customer}/interactions/create', [CustomerInteractionController::class, 'create'])->whereNumber('customer')->name('interactions.create');
    Route::post('/{customer}/interactions', [CustomerInteractionController::class, 'store'])->whereNumber('customer')->name('interactions.store');
    Route::get('/interactions/{interaction}/edit', [CustomerInteractionController::class, 'edit'])->whereNumber('interaction')->name('interactions.edit');
    Route::put('/interactions/{interaction}', [CustomerInteractionController::class, 'update'])->whereNumber('interaction')->name('interactions.update');
    Route::delete('/interactions/{interaction}', [CustomerInteractionController::class, 'destroy'])->whereNumber('interaction')->name('interactions.destroy');
    Route::get('/transactions', [CustomerTransactionController::class, 'index'])->name('transactions');
    Route::get('/{customer}/transactions/create', [CustomerTransactionController::class, 'create'])->whereNumber('customer')->name('transactions.create');
    Route::post('/{customer}/transactions', [CustomerTransactionController::class, 'store'])->whereNumber('customer')->name('transactions.store');
    Route::get('/transactions/{transaction}/edit', [CustomerTransactionController::class, 'edit'])->whereNumber('transaction')->name('transactions.edit');
    Route::put('/transactions/{transaction}', [CustomerTransactionController::class, 'update'])->whereNumber('transaction')->name('transactions.update');
    Route::delete('/transactions/{transaction}', [CustomerTransactionController::class, 'destroy'])->whereNumber('transaction')->name('transactions.destroy');
    Route::get('/preferences', [CustomerPreferenceController::class, 'index'])->name('preferences');
    Route::get('/{customer}/preferences/create', [CustomerPreferenceController::class, 'create'])->whereNumber('customer')->name('preferences.create');
    Route::post('/{customer}/preferences', [CustomerPreferenceController::class, 'store'])->whereNumber('customer')->name('preferences.store');
    Route::get('/preferences/{preference}/edit', [CustomerPreferenceController::class, 'edit'])->whereNumber('preference')->name('preferences.edit');
    Route::put('/preferences/{preference}', [CustomerPreferenceController::class, 'update'])->whereNumber('preference')->name('preferences.update');
    Route::delete('/preferences/{preference}', [CustomerPreferenceController::class, 'destroy'])->whereNumber('preference')->name('preferences.destroy');
    Route::get('/behavior', [CustomerBehaviorController::class, 'index'])->name('behavior');
    Route::get('/{customer}/behavior/create', [CustomerBehaviorController::class, 'create'])->whereNumber('customer')->name('behavior.create');
    Route::post('/{customer}/behavior', [CustomerBehaviorController::class, 'store'])->whereNumber('customer')->name('behavior.store');
    Route::get('/behavior/{behavior}/edit', [CustomerBehaviorController::class, 'edit'])->whereNumber('behavior')->name('behavior.edit');
    Route::put('/behavior/{behavior}', [CustomerBehaviorController::class, 'update'])->whereNumber('behavior')->name('behavior.update');
    Route::delete('/behavior/{behavior}', [CustomerBehaviorController::class, 'destroy'])->whereNumber('behavior')->name('behavior.destroy');

    Route::view('/profile', 'admin.customers.profile')->name('profile');

    Route::get('/{customer}/edit', [CustomerController::class, 'edit'])->whereNumber('customer')->name('edit');
    Route::put('/{customer}', [CustomerController::class, 'update'])->whereNumber('customer')->name('update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->whereNumber('customer')->name('destroy');
    Route::get('/{customer}', [CustomerController::class, 'show'])->whereNumber('customer')->name('show');
});
