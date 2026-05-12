<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AudienceSegment;
use App\Models\CampaignExecution;
use App\Models\CaseResolution;
use App\Models\Customer;
use App\Models\CustomerBehavior;
use App\Models\CustomerInteraction;
use App\Models\CustomerPreference;
use App\Models\CustomerSatisfaction;
use App\Models\CustomerTransaction;
use App\Models\KnowledgeBase;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\LeadScoringRule;
use App\Models\MarketingAutomation;
use App\Models\MarketingCampaign;
use App\Models\OmnichannelMessage;
use App\Models\Opportunity;
use App\Models\Quotation;
use App\Models\SalesActivity;
use App\Models\SlaPolicy;
use App\Models\SocialMediaEngagement;
use App\Models\Ticket;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $totalInteractions = CustomerInteraction::count();
        $totalTransactions = CustomerTransaction::count();

        $totalLeads = Lead::count();
        $qualifiedLeads = Lead::where('status', 'qualified')->count();
        $totalOpportunities = Opportunity::count();
        $pipelineValue = Opportunity::whereNotIn('status', ['won', 'lost'])->sum('estimated_value');
        $wonValue = Opportunity::where('status', 'won')->sum('estimated_value');
        $wonCount = Opportunity::where('status', 'won')->count();
        $lostCount = Opportunity::where('status', 'lost')->count();
        $winRate = ($wonCount + $lostCount) > 0 ? ($wonCount / ($wonCount + $lostCount)) * 100 : 0;
        $totalSalesActivities = SalesActivity::count();
        $totalQuotations = Quotation::count();

        $totalTickets = Ticket::count();
        $openTickets = Ticket::whereIn('status', ['open', 'in_progress', 'waiting_customer'])->count();
        $omnichannelUnread = OmnichannelMessage::whereIn('status', ['unread', 'new', 'open'])->count();
        $averageCsat = (float) CustomerSatisfaction::avg('rating');
        $knowledgeArticles = KnowledgeBase::count();

        $totalCampaigns = MarketingCampaign::count();
        $runningCampaigns = MarketingCampaign::whereIn('status', ['running', 'active'])->count();
        $campaignExecutions = CampaignExecution::count();
        $landingPageSubmissions = (int) LandingPage::sum('submissions_count');
        $socialEngagementPosts = SocialMediaEngagement::count();
        $automationRules = MarketingAutomation::count();
        $leadScoringRules = LeadScoringRule::count();

        return view('admin.dashboard', [
            'pageTitle' => 'CRM Overview',
            'pageDescription' => 'Ringkasan performa CRM lintas Service, Sales, Marketing, dan Customer 360.',
            'summaryCards' => [
                ['label' => 'Total Customers', 'value' => number_format($totalCustomers), 'hint' => number_format($activeCustomers).' active'],
                ['label' => 'Total Leads', 'value' => number_format($totalLeads), 'hint' => number_format($qualifiedLeads).' qualified'],
                ['label' => 'Pipeline Value', 'value' => $this->money($pipelineValue), 'hint' => 'Won '.$this->money($wonValue)],
                ['label' => 'Open Tickets', 'value' => number_format($openTickets), 'hint' => number_format($totalTickets).' total tickets'],
                ['label' => 'Running Campaigns', 'value' => number_format($runningCampaigns), 'hint' => number_format($totalCampaigns).' total campaigns'],
                ['label' => 'Win Rate', 'value' => $this->percent($winRate), 'hint' => number_format($wonCount).' won / '.number_format($lostCount).' lost'],
            ],
            'moduleHealthCards' => [
                [
                    'title' => 'Customer Profile 360',
                    'link_label' => 'Open Customer Module',
                    'link_route' => 'admin.customers.index',
                    'metrics' => [
                        ['label' => 'Total Customers', 'value' => number_format($totalCustomers)],
                        ['label' => 'Active Customers', 'value' => number_format($activeCustomers)],
                        ['label' => 'Total Interactions', 'value' => number_format($totalInteractions)],
                        ['label' => 'Total Transactions', 'value' => number_format($totalTransactions)],
                    ],
                ],
                [
                    'title' => 'Sales Enablement',
                    'link_label' => 'Open Sales Module',
                    'link_route' => 'admin.sales.leads',
                    'metrics' => [
                        ['label' => 'Total Leads', 'value' => number_format($totalLeads)],
                        ['label' => 'Qualified Leads', 'value' => number_format($qualifiedLeads)],
                        ['label' => 'Total Opportunities', 'value' => number_format($totalOpportunities)],
                        ['label' => 'Pipeline Value', 'value' => $this->money($pipelineValue)],
                        ['label' => 'Win Rate', 'value' => $this->percent($winRate)],
                        ['label' => 'Sales Activities', 'value' => number_format($totalSalesActivities)],
                        ['label' => 'Total Quotations', 'value' => number_format($totalQuotations)],
                    ],
                ],
                [
                    'title' => 'Service Management',
                    'link_label' => 'Open Service Module',
                    'link_route' => 'admin.service.tickets.index',
                    'metrics' => [
                        ['label' => 'Total Tickets', 'value' => number_format($totalTickets)],
                        ['label' => 'Open Tickets', 'value' => number_format($openTickets)],
                        ['label' => 'Omnichannel Unread', 'value' => number_format($omnichannelUnread)],
                        ['label' => 'Average CSAT', 'value' => $this->percent($averageCsat / 5 * 100)],
                        ['label' => 'Knowledge Articles', 'value' => number_format($knowledgeArticles)],
                    ],
                ],
                [
                    'title' => 'Marketing Automation',
                    'link_label' => 'Open Marketing Module',
                    'link_route' => 'admin.marketing.campaigns.index',
                    'metrics' => [
                        ['label' => 'Total Campaigns', 'value' => number_format($totalCampaigns)],
                        ['label' => 'Running Campaigns', 'value' => number_format($runningCampaigns)],
                        ['label' => 'Campaign Executions', 'value' => number_format($campaignExecutions)],
                        ['label' => 'Landing Submissions', 'value' => number_format($landingPageSubmissions)],
                        ['label' => 'Social Posts', 'value' => number_format($socialEngagementPosts)],
                        ['label' => 'Automation Rules', 'value' => number_format($automationRules)],
                        ['label' => 'Lead Scoring Rules', 'value' => number_format($leadScoringRules)],
                    ],
                ],
            ],
            'recentCustomers' => Customer::query()
                ->select(['id', 'name', 'email', 'status', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentLeads' => Lead::query()
                ->select(['id', 'name', 'status', 'source', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentOpportunities' => Opportunity::query()
                ->select(['id', 'title', 'status', 'estimated_value', 'expected_close_date', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentTickets' => Ticket::query()
                ->select(['id', 'ticket_number', 'subject', 'status', 'priority', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentCampaigns' => MarketingCampaign::query()
                ->select(['id', 'name', 'status', 'type', 'start_date', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function crmOverview(): View
    {
        return $this->index();
    }

    public function serviceManagement(): View
    {
        $totalTickets = Ticket::count();
        $openTickets = Ticket::whereIn('status', ['open', 'in_progress', 'waiting_customer'])->count();
        $resolvedTickets = Ticket::whereIn('status', ['resolved', 'closed'])->count();
        $highPriorityTickets = Ticket::whereIn('priority', ['high', 'urgent'])->count();

        $totalOmnichannelMessages = OmnichannelMessage::count();
        $unreadMessages = OmnichannelMessage::where('status', 'unread')->count();
        $pendingMessages = OmnichannelMessage::where('status', 'pending')->count();

        $averageCsatValue = (float) CustomerSatisfaction::avg('rating');
        $averageCsat = number_format($averageCsatValue, 1, ',', '.');
        $totalCsatFeedback = CustomerSatisfaction::count();

        $totalKnowledgeArticles = KnowledgeBase::count();
        $publishedArticles = KnowledgeBase::where('is_published', true)->count();

        $totalSlaPolicies = SlaPolicy::count();
        $activeSlaPolicies = SlaPolicy::where('is_active', true)->count();

        $totalCaseResolutions = CaseResolution::count();

        return view('admin.dashboard.service-management', [
            'title' => 'Service Management Dashboard',
            'description' => 'Ringkasan performa layanan customer, ticket, SLA, omnichannel, CSAT, dan knowledge base.',
            'summaryCards' => [
                ['label' => 'Total Tickets', 'value' => number_format($totalTickets), 'hint' => number_format($resolvedTickets).' resolved'],
                ['label' => 'Open Tickets', 'value' => number_format($openTickets), 'hint' => number_format($highPriorityTickets).' high priority'],
                ['label' => 'Unread Messages', 'value' => number_format($unreadMessages), 'hint' => number_format($pendingMessages).' pending'],
                ['label' => 'Average CSAT', 'value' => $averageCsat, 'hint' => number_format($totalCsatFeedback).' feedbacks'],
                ['label' => 'Knowledge Articles', 'value' => number_format($totalKnowledgeArticles), 'hint' => number_format($publishedArticles).' published'],
                ['label' => 'Active SLA', 'value' => number_format($activeSlaPolicies), 'hint' => number_format($totalSlaPolicies).' total policies'],
            ],
            'metrics' => [
                'total_tickets' => $totalTickets,
                'open_tickets' => $openTickets,
                'resolved_tickets' => $resolvedTickets,
                'high_priority_tickets' => $highPriorityTickets,
                'total_omnichannel_messages' => $totalOmnichannelMessages,
                'unread_messages' => $unreadMessages,
                'pending_messages' => $pendingMessages,
                'average_csat' => $averageCsatValue,
                'total_csat_feedback' => $totalCsatFeedback,
                'total_knowledge_articles' => $totalKnowledgeArticles,
                'published_articles' => $publishedArticles,
                'total_sla_policies' => $totalSlaPolicies,
                'active_sla_policies' => $activeSlaPolicies,
                'total_case_resolutions' => $totalCaseResolutions,
            ],
            'ticketStatusByStatus' => Ticket::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
            'ticketStatusByPriority' => Ticket::query()
                ->selectRaw('priority, count(*) as total')
                ->groupBy('priority')
                ->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
                ->get(),
            'omnichannelByChannel' => OmnichannelMessage::query()
                ->selectRaw('channel, count(*) as total')
                ->groupBy('channel')
                ->orderBy('channel')
                ->get(),
            'omnichannelStatusOverview' => [
                ['label' => 'Unread', 'value' => $unreadMessages],
                ['label' => 'Pending', 'value' => $pendingMessages],
                ['label' => 'Resolved', 'value' => OmnichannelMessage::where('status', 'resolved')->count()],
            ],
            'csatSentimentBreakdown' => CustomerSatisfaction::query()
                ->selectRaw('sentiment, count(*) as total')
                ->groupBy('sentiment')
                ->orderByRaw("CASE sentiment WHEN 'positive' THEN 1 WHEN 'neutral' THEN 2 WHEN 'negative' THEN 3 ELSE 4 END")
                ->get(),
            'recentTickets' => Ticket::query()
                ->select(['id', 'ticket_number', 'subject', 'status', 'priority', 'channel', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentOmnichannelMessages' => OmnichannelMessage::query()
                ->select(['id', 'channel', 'sender_name', 'subject', 'status', 'received_at'])
                ->orderByDesc('received_at')
                ->limit(5)
                ->get(),
            'recentCsatFeedback' => CustomerSatisfaction::query()
                ->with(['customer:id,name', 'ticket:id,ticket_number'])
                ->select(['id', 'ticket_id', 'customer_id', 'rating', 'feedback', 'sentiment', 'submitted_at'])
                ->orderByDesc('submitted_at')
                ->limit(5)
                ->get(),
            'recentKnowledgeArticles' => KnowledgeBase::query()
                ->select(['id', 'title', 'category', 'is_published', 'published_at', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function salesEnablement(): View
    {
        $totalLeads = Lead::count();
        $qualifiedLeads = Lead::where('status', 'qualified')->count();
        $convertedLeads = Lead::where('status', 'converted')->count();

        $totalOpportunities = Opportunity::count();
        $openOpportunities = Opportunity::whereNotIn('status', ['won', 'lost'])->count();
        $pipelineValueRaw = (float) Opportunity::whereNotIn('status', ['won', 'lost'])->sum('estimated_value');
        $weightedForecastRaw = (float) Opportunity::whereNotIn('status', ['won', 'lost'])
            ->selectRaw('COALESCE(sum(estimated_value * probability / 100), 0) as total')
            ->value('total');
        $wonValueRaw = (float) Opportunity::where('status', 'won')->sum('estimated_value');
        $lostValueRaw = (float) Opportunity::where('status', 'lost')->sum('estimated_value');

        $wonCount = Opportunity::where('status', 'won')->count();
        $lostCount = Opportunity::where('status', 'lost')->count();
        $winRateRaw = ($wonCount + $lostCount) > 0 ? ($wonCount / ($wonCount + $lostCount)) * 100 : 0;

        $totalSalesActivities = SalesActivity::count();

        $totalQuotations = Quotation::count();
        $acceptedQuotations = Quotation::where('status', 'accepted')->count();
        $quotationValueRaw = (float) Quotation::sum('amount');

        return view('admin.dashboard.sales-enablement', [
            'title' => 'Sales Enablement Dashboard',
            'description' => 'Ringkasan performa lead, opportunity, forecast, aktivitas sales, dan quotation.',
            'summaryCards' => [
                ['label' => 'Total Leads', 'value' => number_format($totalLeads), 'hint' => number_format($qualifiedLeads).' qualified'],
                ['label' => 'Total Opportunities', 'value' => number_format($totalOpportunities), 'hint' => number_format($openOpportunities).' open'],
                ['label' => 'Pipeline Value', 'value' => $this->money($pipelineValueRaw), 'hint' => 'Weighted '.$this->money($weightedForecastRaw)],
                ['label' => 'Win Rate', 'value' => $this->percent($winRateRaw), 'hint' => number_format($wonCount).' won / '.number_format($lostCount).' lost'],
                ['label' => 'Sales Activities', 'value' => number_format($totalSalesActivities), 'hint' => 'Tracked interactions'],
                ['label' => 'Quotation Value', 'value' => $this->money($quotationValueRaw), 'hint' => number_format($acceptedQuotations).' accepted'],
            ],
            'metrics' => [
                'total_leads' => $totalLeads,
                'qualified_leads' => $qualifiedLeads,
                'converted_leads' => $convertedLeads,
                'total_opportunities' => $totalOpportunities,
                'open_opportunities' => $openOpportunities,
                'pipeline_value' => $pipelineValueRaw,
                'weighted_forecast' => $weightedForecastRaw,
                'won_value' => $wonValueRaw,
                'lost_value' => $lostValueRaw,
                'win_rate' => $winRateRaw,
                'total_sales_activities' => $totalSalesActivities,
                'total_quotations' => $totalQuotations,
                'accepted_quotations' => $acceptedQuotations,
                'quotation_value' => $quotationValueRaw,
            ],
            'leadStatusOverview' => Lead::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
            'opportunityPipelineOverview' => Opportunity::query()
                ->selectRaw('status, count(*) as total, COALESCE(sum(estimated_value), 0) as value_total')
                ->groupBy('status')
                ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'qualified' THEN 2 WHEN 'proposal' THEN 3 WHEN 'negotiation' THEN 4 WHEN 'won' THEN 5 WHEN 'lost' THEN 6 ELSE 7 END")
                ->get(),
            'quotationStatusOverview' => Quotation::query()
                ->selectRaw('status, count(*) as total, COALESCE(sum(amount), 0) as value_total')
                ->groupBy('status')
                ->orderByRaw("CASE status WHEN 'draft' THEN 1 WHEN 'sent' THEN 2 WHEN 'accepted' THEN 3 WHEN 'rejected' THEN 4 WHEN 'expired' THEN 5 ELSE 6 END")
                ->get(),
            'recentLeads' => Lead::query()
                ->select(['id', 'name', 'company_name', 'status', 'priority', 'source', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentOpportunities' => Opportunity::query()
                ->select(['id', 'title', 'status', 'probability', 'estimated_value', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentSalesActivities' => SalesActivity::query()
                ->select(['id', 'type', 'subject', 'related_type', 'assigned_to', 'activity_at', 'created_at'])
                ->orderByDesc('activity_at')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'recentQuotations' => Quotation::query()
                ->select(['id', 'quote_number', 'title', 'status', 'amount', 'issued_at', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
        ]);
    }

    public function marketingAutomation(): View
    {
        $totalCampaigns = MarketingCampaign::count();
        $runningCampaigns = MarketingCampaign::whereIn('status', ['running', 'active'])->count();
        $completedCampaigns = MarketingCampaign::where('status', 'completed')->count();

        $totalExecutions = CampaignExecution::count();
        $completedExecutions = CampaignExecution::where('status', 'completed')->count();
        $totalSent = (int) CampaignExecution::sum('sent_count');

        $totalLandingPages = LandingPage::count();
        $totalSubmissions = (int) LandingPage::sum('submissions_count');

        $totalSocialPosts = SocialMediaEngagement::count();
        $totalImpressions = (int) SocialMediaEngagement::sum('impressions_count');
        $averageEngagementRate = (float) SocialMediaEngagement::avg('engagement_rate');

        $totalAutomations = MarketingAutomation::count();
        $activeAutomations = MarketingAutomation::where('status', 'active')->count();

        $totalLeadScoringRules = LeadScoringRule::count();
        $activeLeadScoringRules = LeadScoringRule::where('status', 'active')->count();

        return view('admin.dashboard.marketing-automation', [
            'title' => 'Marketing Automation Dashboard',
            'description' => 'Ringkasan performa campaign, execution, landing page, social engagement, dan automation.',
            'summaryCards' => [
                ['label' => 'Total Campaigns', 'value' => number_format($totalCampaigns), 'hint' => number_format($runningCampaigns).' running'],
                ['label' => 'Total Executions', 'value' => number_format($totalExecutions), 'hint' => number_format($completedExecutions).' completed'],
                ['label' => 'Total Sent', 'value' => number_format($totalSent), 'hint' => 'Messages delivered from executions'],
                ['label' => 'Landing Pages', 'value' => number_format($totalLandingPages), 'hint' => number_format($totalSubmissions).' submissions'],
                ['label' => 'Social Posts', 'value' => number_format($totalSocialPosts), 'hint' => number_format($totalImpressions).' impressions'],
                ['label' => 'Automations', 'value' => number_format($totalAutomations), 'hint' => number_format($activeAutomations).' active'],
            ],
            'metrics' => [
                'total_campaigns' => $totalCampaigns,
                'running_campaigns' => $runningCampaigns,
                'completed_campaigns' => $completedCampaigns,
                'total_executions' => $totalExecutions,
                'completed_executions' => $completedExecutions,
                'total_sent' => $totalSent,
                'total_landing_pages' => $totalLandingPages,
                'total_submissions' => $totalSubmissions,
                'total_social_posts' => $totalSocialPosts,
                'total_impressions' => $totalImpressions,
                'average_engagement_rate' => $averageEngagementRate,
                'total_automations' => $totalAutomations,
                'active_automations' => $activeAutomations,
                'total_lead_scoring_rules' => $totalLeadScoringRules,
                'active_lead_scoring_rules' => $activeLeadScoringRules,
            ],
            'campaignStatusOverview' => MarketingCampaign::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
            'executionPerformanceOverview' => CampaignExecution::query()
                ->selectRaw('status, count(*) as total, COALESCE(sum(sent_count), 0) as sent_total')
                ->groupBy('status')
                ->orderByRaw("CASE status WHEN 'scheduled' THEN 1 WHEN 'running' THEN 2 WHEN 'completed' THEN 3 WHEN 'failed' THEN 4 WHEN 'cancelled' THEN 5 ELSE 6 END")
                ->get(),
            'socialByPlatform' => SocialMediaEngagement::query()
                ->selectRaw('platform, count(*) as total, COALESCE(sum(impressions_count), 0) as impressions_total')
                ->groupBy('platform')
                ->orderBy('platform')
                ->get(),
            'automationStatusOverview' => MarketingAutomation::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
            'leadScoringStatusOverview' => LeadScoringRule::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
            'recentCampaigns' => MarketingCampaign::query()
                ->select(['id', 'name', 'type', 'status', 'actual_leads', 'start_date', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentExecutions' => CampaignExecution::query()
                ->with('marketingCampaign:id,name')
                ->select(['id', 'marketing_campaign_id', 'execution_name', 'channel', 'status', 'sent_count', 'completed_at', 'created_at'])
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'recentLandingPages' => LandingPage::query()
                ->select(['id', 'title', 'status', 'views_count', 'submissions_count', 'published_at', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentSocialPosts' => SocialMediaEngagement::query()
                ->select(['id', 'platform', 'post_title', 'status', 'impressions_count', 'engagement_rate', 'posted_at', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'totalAudienceSegments' => AudienceSegment::count(),
        ]);
    }

    public function customerProfile(): View
    {
        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $inactiveCustomers = Customer::where('status', 'inactive')->count();
        $blacklistCustomers = Customer::where('status', 'blacklist')->count();

        $totalInteractions = CustomerInteraction::count();
        $totalPreferences = CustomerPreference::count();

        $totalTransactions = CustomerTransaction::count();
        $totalTransactionValue = (float) CustomerTransaction::sum('amount');
        $wonTransactionValue = (float) CustomerTransaction::where('status', 'won')->sum('amount');

        $totalBehaviors = CustomerBehavior::count();
        $averageEngagementScore = (float) CustomerBehavior::avg('engagement_score');

        return view('admin.dashboard.customer-profile', [
            'title' => 'Customer Profile 360 Dashboard',
            'description' => 'Ringkasan profil customer, interaction, preference, transaction, dan behavior lifecycle.',
            'summaryCards' => [
                ['label' => 'Total Customers', 'value' => number_format($totalCustomers), 'hint' => number_format($activeCustomers).' active'],
                ['label' => 'Total Interactions', 'value' => number_format($totalInteractions), 'hint' => 'All channels combined'],
                ['label' => 'Total Preferences', 'value' => number_format($totalPreferences), 'hint' => 'Customer preference records'],
                ['label' => 'Total Transactions', 'value' => number_format($totalTransactions), 'hint' => $this->money($totalTransactionValue).' total value'],
                ['label' => 'Won Transaction Value', 'value' => $this->money($wonTransactionValue), 'hint' => 'Closed won transactions'],
                ['label' => 'Average Engagement Score', 'value' => number_format($averageEngagementScore, 1, ',', '.'), 'hint' => number_format($totalBehaviors).' behavior records'],
            ],
            'metrics' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'inactive_customers' => $inactiveCustomers,
                'blacklist_customers' => $blacklistCustomers,
                'total_interactions' => $totalInteractions,
                'total_preferences' => $totalPreferences,
                'total_transactions' => $totalTransactions,
                'total_transaction_value' => $totalTransactionValue,
                'won_transaction_value' => $wonTransactionValue,
                'average_engagement_score' => $averageEngagementScore,
                'total_behaviors' => $totalBehaviors,
            ],
            'customerStatusOverview' => Customer::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
            'interactionTypeOverview' => CustomerInteraction::query()
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->orderBy('type')
                ->get(),
            'transactionStatusOverview' => CustomerTransaction::query()
                ->selectRaw('status, count(*) as total, COALESCE(sum(amount), 0) as value_total')
                ->groupBy('status')
                ->orderByRaw("CASE status WHEN 'pending' THEN 1 WHEN 'won' THEN 2 WHEN 'lost' THEN 3 WHEN 'cancelled' THEN 4 ELSE 5 END")
                ->get(),
            'behaviorLifecycleOverview' => CustomerBehavior::query()
                ->selectRaw('lifecycle_stage, count(*) as total, COALESCE(avg(engagement_score), 0) as avg_engagement')
                ->groupBy('lifecycle_stage')
                ->orderByRaw("CASE lifecycle_stage WHEN 'lead' THEN 1 WHEN 'prospect' THEN 2 WHEN 'active' THEN 3 WHEN 'loyal' THEN 4 WHEN 'churned' THEN 5 ELSE 6 END")
                ->get(),
            'recentCustomers' => Customer::query()
                ->select(['id', 'name', 'email', 'status', 'source', 'owner_name', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentInteractions' => CustomerInteraction::query()
                ->with('customer:id,name')
                ->select(['id', 'customer_id', 'type', 'subject', 'handled_by', 'interaction_at', 'created_at'])
                ->orderByDesc('interaction_at')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
            'recentTransactions' => CustomerTransaction::query()
                ->with('customer:id,name')
                ->select(['id', 'customer_id', 'title', 'amount', 'status', 'closing_date', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentPreferences' => CustomerPreference::query()
                ->with('customer:id,name')
                ->select(['id', 'customer_id', 'preferred_channel', 'product_interest', 'communication_consent', 'segment', 'created_at'])
                ->latest()
                ->limit(5)
                ->get(),
            'recentBehaviors' => CustomerBehavior::query()
                ->with('customer:id,name')
                ->select(['id', 'customer_id', 'lifecycle_stage', 'engagement_score', 'product_interest', 'last_activity_at', 'created_at'])
                ->orderByDesc('last_activity_at')
                ->orderByDesc('created_at')
                ->limit(5)
                ->get(),
        ]);
    }

    private function money(float|int|string $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }

    private function percent(float|int $value): string
    {
        return number_format((float) $value, 1, ',', '.').'%';
    }
}
