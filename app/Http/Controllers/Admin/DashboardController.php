<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\DashboardAccess;
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
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        if (! DashboardAccess::canAccess(auth()->user(), 'CRM Overview')) {
            $firstAccessibleRoute = DashboardAccess::firstAccessibleRouteName(auth()->user());

            abort_if($firstAccessibleRoute === null, 403);

            return redirect()->route($firstAccessibleRoute);
        }

        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();
        $totalInteractions = CustomerInteraction::count();
        $totalTransactions = CustomerTransaction::count();
        $totalTransactionValue = (float) CustomerTransaction::sum('amount');

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
        $trackedSlaTickets = Ticket::query()
            ->whereNotNull('due_at')
            ->where(function ($query) {
                $query->whereNotNull('resolved_at')->orWhereNotNull('closed_at');
            })
            ->count();

        $metSlaTickets = Ticket::query()
            ->whereNotNull('due_at')
            ->where(function ($query) {
                $query->whereNotNull('resolved_at')->orWhereNotNull('closed_at');
            })
            ->whereRaw('COALESCE(resolved_at, closed_at) <= due_at')
            ->count();

        $breachedSlaTickets = max(0, $trackedSlaTickets - $metSlaTickets);
        $slaRate = $trackedSlaTickets > 0 ? ($metSlaTickets / $trackedSlaTickets) * 100 : 0;

        $trendMonths = $this->lastMonths(6);
        $trendStart = $trendMonths->first();

        $revenueTrendRows = CustomerTransaction::query()
            ->select(['closing_date', 'created_at', 'amount'])
            ->when($trendStart, function ($query) use ($trendStart) {
                $query->where(function ($innerQuery) use ($trendStart) {
                    $innerQuery
                        ->whereDate('closing_date', '>=', $trendStart)
                        ->orWhere('created_at', '>=', $trendStart);
                });
            })
            ->get()
            ->map(fn (CustomerTransaction $transaction) => [
                'date' => $transaction->closing_date ?? $transaction->created_at,
                'amount' => (float) $transaction->amount,
            ]);

        $leadGrowthRows = Lead::query()
            ->select(['created_at', 'status'])
            ->when($trendStart, function ($query) use ($trendStart) {
                $query->where('created_at', '>=', $trendStart);
            })
            ->get();

        $revenueTrendValues = $this->bucketSumByMonth($revenueTrendRows, $trendMonths, 'date', 'amount');
        $leadGrowthValues = $this->bucketByMonth($leadGrowthRows->pluck('created_at'), $trendMonths);
        $qualifiedLeadGrowthValues = $this->bucketByMonth(
            $leadGrowthRows->where('status', 'qualified')->pluck('created_at'),
            $trendMonths
        );

        $salesFunnel = collect([
            ['label' => 'Leads', 'value' => $totalLeads, 'color' => '#7367f0'],
            ['label' => 'Qualified', 'value' => $qualifiedLeads, 'color' => '#00bad1'],
            ['label' => 'Opportunities', 'value' => $totalOpportunities, 'color' => '#28c76f'],
            ['label' => 'Quotations', 'value' => $totalQuotations, 'color' => '#ff9f43'],
            ['label' => 'Won', 'value' => $wonCount, 'color' => '#16a34a'],
        ]);

        $leadSourceOverview = Lead::query()
            ->selectRaw("COALESCE(NULLIF(source, ''), 'Direct') as source_label, count(*) as total")
            ->groupByRaw("COALESCE(NULLIF(source, ''), 'Direct')")
            ->orderByDesc('total')
            ->get();

        $teamBuckets = collect();
        $mergeTeamRows = static function (Collection $target, Collection $rows, string $countKey, string $bucketKey, float $weight, string $metric): Collection {
            foreach ($rows as $row) {
                $name = (string) $row->{$bucketKey};
                $current = $target->get($name, [
                    'name' => $name,
                    'score' => 0.0,
                    'leads' => 0,
                    'opportunities' => 0,
                    'tickets' => 0,
                    'activities' => 0,
                ]);
                $count = (int) $row->{$countKey};
                $current[$metric] += $count;
                $current['score'] += $count * $weight;
                $target->put($name, $current);
            }

            return $target;
        };

        $teamBuckets = $mergeTeamRows(
            $teamBuckets,
            Lead::query()
                ->selectRaw("COALESCE(NULLIF(assigned_to, ''), 'Unassigned') as bucket, count(*) as total")
                ->groupByRaw("COALESCE(NULLIF(assigned_to, ''), 'Unassigned')")
                ->get(),
            'total',
            'bucket',
            1.0,
            'leads'
        );
        $teamBuckets = $mergeTeamRows(
            $teamBuckets,
            Opportunity::query()
                ->selectRaw("COALESCE(NULLIF(assigned_to, ''), 'Unassigned') as bucket, count(*) as total")
                ->groupByRaw("COALESCE(NULLIF(assigned_to, ''), 'Unassigned')")
                ->get(),
            'total',
            'bucket',
            1.4,
            'opportunities'
        );
        $teamBuckets = $mergeTeamRows(
            $teamBuckets,
            Ticket::query()
                ->selectRaw("COALESCE(NULLIF(assigned_to, ''), 'Unassigned') as bucket, count(*) as total")
                ->groupByRaw("COALESCE(NULLIF(assigned_to, ''), 'Unassigned')")
                ->get(),
            'total',
            'bucket',
            1.1,
            'tickets'
        );
        $teamBuckets = $mergeTeamRows(
            $teamBuckets,
            SalesActivity::query()
                ->selectRaw("COALESCE(NULLIF(assigned_to, ''), 'Unassigned') as bucket, count(*) as total")
                ->groupByRaw("COALESCE(NULLIF(assigned_to, ''), 'Unassigned')")
                ->get(),
            'total',
            'bucket',
            0.8,
            'activities'
        );

        $teamPerformance = $teamBuckets
            ->sortByDesc('score')
            ->take(5)
            ->values();

        $pipelineOverview = Opportunity::query()
            ->selectRaw('status, count(*) as total, COALESCE(sum(estimated_value), 0) as value_total')
            ->groupBy('status')
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'proposal' THEN 2 WHEN 'negotiation' THEN 3 WHEN 'won' THEN 4 WHEN 'lost' THEN 5 ELSE 6 END")
            ->get();

        $engagedCustomers = CustomerInteraction::query()->whereNotNull('customer_id')->distinct()->count('customer_id');
        $interestedCustomers = CustomerPreference::query()->whereNotNull('customer_id')->distinct()->count('customer_id');
        $transactingCustomers = CustomerTransaction::query()->whereNotNull('customer_id')->distinct()->count('customer_id');
        $loyalCustomers = CustomerBehavior::query()->where('lifecycle_stage', 'loyal')->whereNotNull('customer_id')->distinct()->count('customer_id');
        $churnedCustomers = CustomerBehavior::query()->where('lifecycle_stage', 'churned')->whereNotNull('customer_id')->distinct()->count('customer_id');

        $customerJourney = [
            'nodes' => [
                ['key' => 'customers', 'label' => 'Customers', 'count' => $totalCustomers, 'color' => '#7367f0'],
                ['key' => 'engaged', 'label' => 'Engaged', 'count' => $engagedCustomers, 'color' => '#00bad1'],
                ['key' => 'interested', 'label' => 'Interested', 'count' => $interestedCustomers, 'color' => '#28c76f'],
                ['key' => 'transacting', 'label' => 'Transacting', 'count' => $transactingCustomers, 'color' => '#ff9f43'],
                ['key' => 'loyal', 'label' => 'Loyal', 'count' => $loyalCustomers, 'color' => '#16a34a'],
                ['key' => 'churned', 'label' => 'Churned', 'count' => $churnedCustomers, 'color' => '#ff4c51'],
            ],
            'links' => [
                ['from' => 'customers', 'to' => 'engaged', 'value' => min($totalCustomers, $engagedCustomers), 'color' => '#7367f0'],
                ['from' => 'engaged', 'to' => 'interested', 'value' => min($engagedCustomers, $interestedCustomers), 'color' => '#00bad1'],
                ['from' => 'interested', 'to' => 'transacting', 'value' => min($interestedCustomers, $transactingCustomers), 'color' => '#28c76f'],
                ['from' => 'transacting', 'to' => 'loyal', 'value' => min($transactingCustomers, $loyalCustomers), 'color' => '#16a34a'],
                ['from' => 'engaged', 'to' => 'churned', 'value' => min($engagedCustomers, $churnedCustomers), 'color' => '#ff4c51'],
            ],
        ];

        $timeline = collect()
            ->merge(
                Customer::query()
                    ->select(['id', 'name', 'status', 'created_at'])
                    ->latest()
                    ->limit(3)
                    ->get()
                    ->map(fn (Customer $customer) => [
                        'label' => 'Customer Added',
                        'title' => $customer->name,
                        'meta' => str((string) $customer->status)->headline()->toString(),
                        'timestamp' => $customer->created_at,
                        'accent' => '#7367f0',
                    ])
            )
            ->merge(
                Lead::query()
                    ->select(['id', 'name', 'status', 'source', 'created_at'])
                    ->latest()
                    ->limit(3)
                    ->get()
                    ->map(fn (Lead $lead) => [
                        'label' => 'Lead Captured',
                        'title' => $lead->name,
                        'meta' => str((string) $lead->status)->headline()->toString().' / '.($lead->source ?: 'Direct'),
                        'timestamp' => $lead->created_at,
                        'accent' => '#00bad1',
                    ])
            )
            ->merge(
                SalesActivity::query()
                    ->select(['id', 'type', 'subject', 'assigned_to', 'activity_at', 'created_at'])
                    ->orderByDesc('activity_at')
                    ->orderByDesc('created_at')
                    ->limit(3)
                    ->get()
                    ->map(fn (SalesActivity $activity) => [
                        'label' => 'Sales Activity',
                        'title' => $activity->subject,
                        'meta' => str((string) $activity->type)->headline()->toString().' / '.($activity->assigned_to ?: 'Unassigned'),
                        'timestamp' => $activity->activity_at ?? $activity->created_at,
                        'accent' => '#28c76f',
                    ])
            )
            ->merge(
                Ticket::query()
                    ->select(['id', 'ticket_number', 'subject', 'priority', 'created_at'])
                    ->latest()
                    ->limit(3)
                    ->get()
                    ->map(fn (Ticket $ticket) => [
                        'label' => 'Service Ticket',
                        'title' => $ticket->ticket_number,
                        'meta' => ($ticket->subject ?: 'No subject').' / '.str((string) $ticket->priority)->headline()->toString(),
                        'timestamp' => $ticket->created_at,
                        'accent' => '#ff9f43',
                    ])
            )
            ->merge(
                MarketingCampaign::query()
                    ->select(['id', 'name', 'status', 'created_at'])
                    ->latest()
                    ->limit(2)
                    ->get()
                    ->map(fn (MarketingCampaign $campaign) => [
                        'label' => 'Campaign Updated',
                        'title' => $campaign->name,
                        'meta' => str((string) $campaign->status)->headline()->toString(),
                        'timestamp' => $campaign->created_at,
                        'accent' => '#7c3aed',
                    ])
            )
            ->sortByDesc(fn (array $item) => $item['timestamp'])
            ->take(10)
            ->values();

        $regionalSpread = collect();
        $mergeRegionalRows = static function (Collection $target, Collection $rows): Collection {
            foreach ($rows as $row) {
                $label = (string) $row->label;
                $target->put($label, ($target->get($label, 0) + (int) $row->total));
            }

            return $target;
        };

        $regionalSpread = $mergeRegionalRows(
            $regionalSpread,
            Customer::query()
                ->selectRaw("COALESCE(NULLIF(source, ''), 'Direct') as label, count(*) as total")
                ->groupByRaw("COALESCE(NULLIF(source, ''), 'Direct')")
                ->get()
        );
        $regionalSpread = $mergeRegionalRows(
            $regionalSpread,
            Lead::query()
                ->selectRaw("COALESCE(NULLIF(source, ''), 'Direct') as label, count(*) as total")
                ->groupByRaw("COALESCE(NULLIF(source, ''), 'Direct')")
                ->get()
        );

        $regionalPositions = [
            ['zone' => 'West', 'x' => 110, 'y' => 130],
            ['zone' => 'Central', 'x' => 245, 'y' => 150],
            ['zone' => 'Jakarta Hub', 'x' => 320, 'y' => 168],
            ['zone' => 'East', 'x' => 470, 'y' => 148],
            ['zone' => 'Growth Zone', 'x' => 620, 'y' => 122],
        ];

        $regionalCoverage = $regionalSpread
            ->sortDesc()
            ->take(5)
            ->keys()
            ->values()
            ->map(function (string $label, int $index) use ($regionalPositions, $regionalSpread) {
                $position = $regionalPositions[$index] ?? end($regionalPositions);

                return [
                    'zone' => $position['zone'],
                    'label' => $label,
                    'total' => (int) $regionalSpread->get($label, 0),
                    'x' => $position['x'],
                    'y' => $position['y'],
                ];
            });

        if ($regionalCoverage->isEmpty()) {
            $regionalCoverage = collect([
                ['zone' => 'National', 'label' => 'No regional tag', 'total' => $totalCustomers + $totalLeads, 'x' => 320, 'y' => 150],
            ]);
        }

        return view('admin.dashboard', [
            'pageTitle' => 'CRM Overview',
            'pageDescription' => 'Ringkasan CRM lintas revenue, lead, sales, service, marketing, dan customer journey dalam satu executive workspace.',
            'summaryCards' => [
                ['label' => 'Revenue', 'value' => $this->money($totalTransactionValue), 'hint' => 'Won '.$this->money($wonValue)],
                ['label' => 'Lead Growth', 'value' => number_format($totalLeads), 'hint' => number_format($qualifiedLeads).' qualified'],
                ['label' => 'Pipeline', 'value' => $this->money($pipelineValue), 'hint' => number_format($totalOpportunities).' opportunities'],
                ['label' => 'SLA Rate', 'value' => $this->percent($slaRate), 'hint' => number_format($metSlaTickets).' met / '.number_format($trackedSlaTickets).' tracked'],
                ['label' => 'Customers', 'value' => number_format($totalCustomers), 'hint' => number_format($activeCustomers).' active'],
                ['label' => 'Campaigns', 'value' => number_format($runningCampaigns), 'hint' => number_format($totalCampaigns).' running/total'],
            ],
            'metrics' => [
                'total_customers' => $totalCustomers,
                'active_customers' => $activeCustomers,
                'total_leads' => $totalLeads,
                'qualified_leads' => $qualifiedLeads,
                'total_opportunities' => $totalOpportunities,
                'pipeline_value' => $pipelineValue,
                'won_value' => $wonValue,
                'won_count' => $wonCount,
                'lost_count' => $lostCount,
                'win_rate' => $winRate,
                'total_transactions' => $totalTransactions,
                'total_transaction_value' => $totalTransactionValue,
                'open_tickets' => $openTickets,
                'tracked_sla_tickets' => $trackedSlaTickets,
                'met_sla_tickets' => $metSlaTickets,
                'breached_sla_tickets' => $breachedSlaTickets,
                'sla_rate' => $slaRate,
                'running_campaigns' => $runningCampaigns,
                'average_csat' => $averageCsat,
                'knowledge_articles' => $knowledgeArticles,
                'campaign_executions' => $campaignExecutions,
                'landing_submissions' => $landingPageSubmissions,
                'social_posts' => $socialEngagementPosts,
                'automation_rules' => $automationRules,
                'lead_scoring_rules' => $leadScoringRules,
                'total_sales_activities' => $totalSalesActivities,
                'omnichannel_unread' => $omnichannelUnread,
            ],
            'revenueTrend' => [
                'labels' => $trendMonths->map(fn (Carbon $month) => $month->format('M y'))->all(),
                'values' => $revenueTrendValues,
            ],
            'leadGrowth' => [
                'labels' => $trendMonths->map(fn (Carbon $month) => $month->format('M y'))->all(),
                'values' => $leadGrowthValues,
                'qualified_values' => $qualifiedLeadGrowthValues,
            ],
            'salesFunnel' => $salesFunnel,
            'leadSourceOverview' => $leadSourceOverview,
            'teamPerformance' => $teamPerformance,
            'pipelineOverview' => $pipelineOverview,
            'customerJourney' => $customerJourney,
            'activityTimeline' => $timeline,
            'regionalCoverage' => $regionalCoverage,
        ]);
    }

    public function crmOverview(): View
    {
        DashboardAccess::abortUnlessCanAccess(auth()->user(), 'CRM Overview');

        return $this->index();
    }

    public function serviceManagement(): View
    {
        DashboardAccess::abortUnlessCanAccess(auth()->user(), 'Service Management');

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
        DashboardAccess::abortUnlessCanAccess(auth()->user(), 'Sales Enablement');

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
        DashboardAccess::abortUnlessCanAccess(auth()->user(), 'Marketing Automation');

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
        $trendMonths = $this->lastMonths(6);
        $trendStart = $trendMonths->first()?->copy()->startOfMonth();

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
            'trendPerformance' => [
                'labels' => $trendMonths->map(fn (Carbon $month) => $month->format('M y'))->all(),
                'series' => [
                    [
                        'name' => 'Campaigns',
                        'color' => '#7367f0',
                        'values' => $this->bucketByMonth(
                            MarketingCampaign::query()
                                ->select(['start_date', 'created_at'])
                                ->when($trendStart, function ($query) use ($trendStart) {
                                    $query->where(function ($innerQuery) use ($trendStart) {
                                        $innerQuery
                                            ->whereDate('start_date', '>=', $trendStart)
                                            ->orWhere('created_at', '>=', $trendStart);
                                    });
                                })
                                ->get()
                                ->map(fn (MarketingCampaign $campaign) => $campaign->start_date ?? $campaign->created_at),
                            $trendMonths
                        ),
                    ],
                    [
                        'name' => 'Executions',
                        'color' => '#28c76f',
                        'values' => $this->bucketByMonth(
                            CampaignExecution::query()
                                ->select(['completed_at', 'created_at'])
                                ->when($trendStart, function ($query) use ($trendStart) {
                                    $query->where(function ($innerQuery) use ($trendStart) {
                                        $innerQuery
                                            ->where('completed_at', '>=', $trendStart)
                                            ->orWhere('created_at', '>=', $trendStart);
                                    });
                                })
                                ->get()
                                ->map(fn (CampaignExecution $execution) => $execution->completed_at ?? $execution->created_at),
                            $trendMonths
                        ),
                    ],
                    [
                        'name' => 'Social Posts',
                        'color' => '#00bad1',
                        'values' => $this->bucketByMonth(
                            SocialMediaEngagement::query()
                                ->select(['posted_at', 'created_at'])
                                ->when($trendStart, function ($query) use ($trendStart) {
                                    $query->where(function ($innerQuery) use ($trendStart) {
                                        $innerQuery
                                            ->where('posted_at', '>=', $trendStart)
                                            ->orWhere('created_at', '>=', $trendStart);
                                    });
                                })
                                ->get()
                                ->map(fn (SocialMediaEngagement $socialPost) => $socialPost->posted_at ?? $socialPost->created_at),
                            $trendMonths
                        ),
                    ],
                ],
            ],
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

    /**
     * @return Collection<int, Carbon>
     */
    protected function lastMonths(int $count): Collection
    {
        return collect(range($count - 1, 0))
            ->map(fn (int $offset) => now()->copy()->startOfMonth()->subMonths($offset))
            ->values();
    }

    /**
     * @param Collection<int, \DateTimeInterface|string|null> $dates
     * @param Collection<int, Carbon> $months
     * @return array<int, int>
     */
    protected function bucketByMonth(Collection $dates, Collection $months): array
    {
        $counts = $months
            ->mapWithKeys(fn (Carbon $month) => [$month->format('Y-m') => 0]);

        foreach ($dates as $date) {
            if ($date === null) {
                continue;
            }

            $key = Carbon::parse($date)->format('Y-m');

            if ($counts->has($key)) {
                $counts->put($key, ((int) $counts->get($key, 0)) + 1);
            }
        }

        return $counts->values()->all();
    }

    /**
     * @param Collection<int, array<string, mixed>> $rows
     * @param Collection<int, Carbon> $months
     * @return array<int, float>
     */
    protected function bucketSumByMonth(Collection $rows, Collection $months, string $dateKey, string $amountKey): array
    {
        $totals = $months
            ->mapWithKeys(fn (Carbon $month) => [$month->format('Y-m') => 0.0]);

        foreach ($rows as $row) {
            $date = data_get($row, $dateKey);

            if ($date === null) {
                continue;
            }

            $key = Carbon::parse($date)->format('Y-m');

            if ($totals->has($key)) {
                $totals->put($key, ((float) $totals->get($key, 0)) + (float) data_get($row, $amountKey, 0));
            }
        }

        return $totals
            ->values()
            ->map(fn (float $value) => round($value, 2))
            ->all();
    }

    public function customerProfile(): View
    {
        DashboardAccess::abortUnlessCanAccess(auth()->user(), 'Customer Profile 360');

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
        $averageSentimentRating = (float) CustomerSatisfaction::avg('rating');

        $trendMonths = $this->lastMonths(6);
        $trendStart = $trendMonths->first();

        $interactionChannelOverview = CustomerPreference::query()
            ->selectRaw('preferred_channel as channel, count(*) as total')
            ->whereNotNull('preferred_channel')
            ->groupBy('preferred_channel')
            ->orderBy('preferred_channel')
            ->get();

        $channelTotal = max((int) $interactionChannelOverview->sum('total'), 1);

        $engagedCustomers = CustomerInteraction::query()->whereNotNull('customer_id')->distinct()->count('customer_id');
        $interestedCustomers = CustomerPreference::query()->whereNotNull('customer_id')->distinct()->count('customer_id');
        $transactingCustomers = CustomerTransaction::query()->whereNotNull('customer_id')->distinct()->count('customer_id');
        $loyalCustomers = CustomerBehavior::query()->where('lifecycle_stage', 'loyal')->whereNotNull('customer_id')->distinct()->count('customer_id');
        $churnedCustomers = CustomerBehavior::query()->where('lifecycle_stage', 'churned')->whereNotNull('customer_id')->distinct()->count('customer_id');

        $journeyNodes = [
            ['key' => 'customers', 'label' => 'Customers', 'count' => $totalCustomers, 'color' => '#7367f0'],
            ['key' => 'engaged', 'label' => 'Engaged', 'count' => $engagedCustomers, 'color' => '#00bad1'],
            ['key' => 'interested', 'label' => 'Interested', 'count' => $interestedCustomers, 'color' => '#28c76f'],
            ['key' => 'transacting', 'label' => 'Transacting', 'count' => $transactingCustomers, 'color' => '#ff9f43'],
            ['key' => 'loyal', 'label' => 'Loyal', 'count' => $loyalCustomers, 'color' => '#16a34a'],
            ['key' => 'churned', 'label' => 'Churned', 'count' => $churnedCustomers, 'color' => '#ff4c51'],
        ];

        $journeyLinks = [
            ['from' => 'customers', 'to' => 'engaged', 'value' => min($totalCustomers, $engagedCustomers), 'color' => '#7367f0'],
            ['from' => 'engaged', 'to' => 'interested', 'value' => min($engagedCustomers, $interestedCustomers), 'color' => '#00bad1'],
            ['from' => 'interested', 'to' => 'transacting', 'value' => min($interestedCustomers, $transactingCustomers), 'color' => '#28c76f'],
            ['from' => 'transacting', 'to' => 'loyal', 'value' => min($transactingCustomers, $loyalCustomers), 'color' => '#16a34a'],
            ['from' => 'engaged', 'to' => 'churned', 'value' => min($engagedCustomers, $churnedCustomers), 'color' => '#ff4c51'],
        ];

        $interactionTypeStacks = CustomerInteraction::query()
            ->leftJoin('customers', 'customers.id', '=', 'customer_interactions.customer_id')
            ->selectRaw('customer_interactions.type, COALESCE(customers.status, ?) as customer_status, count(*) as total', ['inactive'])
            ->groupBy('customer_interactions.type', 'customer_status')
            ->orderBy('customer_interactions.type')
            ->get()
            ->groupBy('type')
            ->map(function (Collection $group, string $type) {
                $segments = collect(['active', 'inactive', 'blacklist'])
                    ->map(function (string $status) use ($group) {
                        $match = $group->firstWhere('customer_status', $status);

                        return [
                            'status' => $status,
                            'total' => (int) optional($match)->total,
                        ];
                    })
                    ->values();

                return [
                    'type' => $type,
                    'total' => (int) $group->sum('total'),
                    'segments' => $segments->all(),
                ];
            })
            ->values();

        $heatmapDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $heatmapBands = [
            ['label' => '00-05', 'start' => 0, 'end' => 5],
            ['label' => '06-09', 'start' => 6, 'end' => 9],
            ['label' => '10-13', 'start' => 10, 'end' => 13],
            ['label' => '14-17', 'start' => 14, 'end' => 17],
            ['label' => '18-21', 'start' => 18, 'end' => 21],
            ['label' => '22-23', 'start' => 22, 'end' => 23],
        ];

        $heatmapMap = [];

        foreach ($heatmapDays as $day) {
            foreach ($heatmapBands as $band) {
                $heatmapMap[$day][$band['label']] = 0;
            }
        }

        $interactionTimes = CustomerInteraction::query()
            ->select(['interaction_at', 'created_at'])
            ->get()
            ->map(fn (CustomerInteraction $interaction) => $interaction->interaction_at ?? $interaction->created_at)
            ->filter();

        foreach ($interactionTimes as $interactionTime) {
            $date = Carbon::parse($interactionTime);
            $day = $date->format('D');
            $hour = (int) $date->format('G');

            foreach ($heatmapBands as $band) {
                if ($hour >= $band['start'] && $hour <= $band['end']) {
                    $heatmapMap[$day][$band['label']] = ($heatmapMap[$day][$band['label']] ?? 0) + 1;
                    break;
                }
            }
        }

        $engagementHeatmap = collect($heatmapDays)
            ->map(function (string $day) use ($heatmapBands, $heatmapMap) {
                return [
                    'day' => $day,
                    'cells' => collect($heatmapBands)
                        ->map(fn (array $band) => [
                            'label' => $band['label'],
                            'value' => $heatmapMap[$day][$band['label']] ?? 0,
                        ])
                        ->all(),
                ];
            });

        $sentimentOverview = CustomerSatisfaction::query()
            ->selectRaw('sentiment, count(*) as total')
            ->groupBy('sentiment')
            ->orderByRaw("CASE sentiment WHEN 'positive' THEN 1 WHEN 'neutral' THEN 2 WHEN 'negative' THEN 3 ELSE 4 END")
            ->get();

        $sentimentTotal = max((int) $sentimentOverview->sum('total'), 1);
        $sentimentPositive = (int) optional($sentimentOverview->firstWhere('sentiment', 'positive'))->total;
        $sentimentNeutral = (int) optional($sentimentOverview->firstWhere('sentiment', 'neutral'))->total;
        $sentimentNegative = (int) optional($sentimentOverview->firstWhere('sentiment', 'negative'))->total;
        $sentimentScore = round((($sentimentPositive * 100) + ($sentimentNeutral * 60) + ($sentimentNegative * 20)) / $sentimentTotal, 1);

        $customerHistory = collect()
            ->merge(
                Customer::query()
                    ->select(['id', 'name', 'status', 'created_at'])
                    ->latest()
                    ->limit(4)
                    ->get()
                    ->map(fn (Customer $customer) => [
                        'type' => 'customer',
                        'label' => 'Customer Added',
                        'title' => $customer->name,
                        'meta' => str((string) $customer->status)->headline()->toString(),
                        'timestamp' => $customer->created_at,
                        'accent' => '#7367f0',
                    ])
            )
            ->merge(
                CustomerInteraction::query()
                    ->with('customer:id,name')
                    ->select(['id', 'customer_id', 'type', 'subject', 'interaction_at', 'created_at'])
                    ->orderByDesc('interaction_at')
                    ->orderByDesc('created_at')
                    ->limit(4)
                    ->get()
                    ->map(fn (CustomerInteraction $interaction) => [
                        'type' => 'interaction',
                        'label' => 'Interaction Logged',
                        'title' => $interaction->customer?->name ?: $interaction->subject,
                        'meta' => str((string) $interaction->type)->headline()->toString().' / '.($interaction->subject ?: 'No subject'),
                        'timestamp' => $interaction->interaction_at ?? $interaction->created_at,
                        'accent' => '#00bad1',
                    ])
            )
            ->merge(
                CustomerTransaction::query()
                    ->with('customer:id,name')
                    ->select(['id', 'customer_id', 'title', 'status', 'closing_date', 'created_at'])
                    ->latest()
                    ->limit(4)
                    ->get()
                    ->map(fn (CustomerTransaction $transaction) => [
                        'type' => 'transaction',
                        'label' => 'Transaction Updated',
                        'title' => $transaction->customer?->name ?: $transaction->title,
                        'meta' => str((string) $transaction->status)->headline()->toString().' / '.$transaction->title,
                        'timestamp' => $transaction->closing_date ?? $transaction->created_at,
                        'accent' => '#ff9f43',
                    ])
            )
            ->merge(
                CustomerBehavior::query()
                    ->with('customer:id,name')
                    ->select(['id', 'customer_id', 'lifecycle_stage', 'last_activity_at', 'created_at'])
                    ->orderByDesc('last_activity_at')
                    ->orderByDesc('created_at')
                    ->limit(4)
                    ->get()
                    ->map(fn (CustomerBehavior $behavior) => [
                        'type' => 'behavior',
                        'label' => 'Behavior Snapshot',
                        'title' => $behavior->customer?->name ?: 'Behavior Record',
                        'meta' => str((string) $behavior->lifecycle_stage)->headline()->toString(),
                        'timestamp' => $behavior->last_activity_at ?? $behavior->created_at,
                        'accent' => '#28c76f',
                    ])
            )
            ->sortByDesc(fn (array $event) => $event['timestamp'])
            ->take(10)
            ->values();

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
                'average_sentiment_rating' => $averageSentimentRating,
                'sentiment_score' => $sentimentScore,
            ],
            'customerStatusOverview' => Customer::query()
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->orderBy('status')
                ->get(),
            'interactionChannelOverview' => $interactionChannelOverview,
            'interactionTypeOverview' => CustomerInteraction::query()
                ->selectRaw('type, count(*) as total')
                ->groupBy('type')
                ->orderBy('type')
                ->get(),
            'activityTrend' => [
                'labels' => $trendMonths->map(fn (Carbon $month) => $month->format('M y'))->all(),
                'series' => [
                    [
                        'name' => 'Interactions',
                        'color' => '#7367f0',
                        'values' => $this->bucketByMonth(
                            CustomerInteraction::query()
                                ->select(['interaction_at', 'created_at'])
                                ->when($trendStart, function ($query) use ($trendStart) {
                                    $query->where(function ($innerQuery) use ($trendStart) {
                                        $innerQuery
                                            ->where('interaction_at', '>=', $trendStart)
                                            ->orWhere('created_at', '>=', $trendStart);
                                    });
                                })
                                ->get()
                                ->map(fn (CustomerInteraction $interaction) => $interaction->interaction_at ?? $interaction->created_at),
                            $trendMonths
                        ),
                    ],
                    [
                        'name' => 'Transactions',
                        'color' => '#28c76f',
                        'values' => $this->bucketByMonth(
                            CustomerTransaction::query()
                                ->select(['closing_date', 'created_at'])
                                ->when($trendStart, function ($query) use ($trendStart) {
                                    $query->where(function ($innerQuery) use ($trendStart) {
                                        $innerQuery
                                            ->whereDate('closing_date', '>=', $trendStart)
                                            ->orWhere('created_at', '>=', $trendStart);
                                    });
                                })
                                ->get()
                                ->map(fn (CustomerTransaction $transaction) => $transaction->closing_date ?? $transaction->created_at),
                            $trendMonths
                        ),
                    ],
                    [
                        'name' => 'Behavior Updates',
                        'color' => '#00bad1',
                        'values' => $this->bucketByMonth(
                            CustomerBehavior::query()
                                ->select(['last_activity_at', 'created_at'])
                                ->when($trendStart, function ($query) use ($trendStart) {
                                    $query->where(function ($innerQuery) use ($trendStart) {
                                        $innerQuery
                                            ->where('last_activity_at', '>=', $trendStart)
                                            ->orWhere('created_at', '>=', $trendStart);
                                    });
                                })
                                ->get()
                                ->map(fn (CustomerBehavior $behavior) => $behavior->last_activity_at ?? $behavior->created_at),
                            $trendMonths
                        ),
                    ],
                ],
            ],
            'customerJourney' => [
                'nodes' => $journeyNodes,
                'links' => $journeyLinks,
            ],
            'interactionTypeStacked' => $interactionTypeStacks,
            'engagementHeatmap' => $engagementHeatmap,
            'engagementHeatmapBands' => collect($heatmapBands)->pluck('label')->all(),
            'sentimentOverview' => $sentimentOverview,
            'customerHistory' => $customerHistory,
            'channelTotal' => $channelTotal,
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
