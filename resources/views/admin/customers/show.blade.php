@extends('admin.layouts.app')

@section('title', $customer->name.' - Customer 360 - Krakatau CRM')

@section('content')
    @php
        $recentInteractions = $recentInteractions ?? collect();
        $recentTransactions = $recentTransactions ?? collect();
        $recentPreferences = $recentPreferences ?? collect();
        $recentBehaviors = $recentBehaviors ?? collect();
        $recentOpportunities = $recentOpportunities ?? collect();
        $recentSalesActivities = $recentSalesActivities ?? collect();
        $recentQuotations = $recentQuotations ?? collect();

        $latestInteraction = $recentInteractions->first();
        $latestTransaction = $recentTransactions->first();
        $latestBehavior = $recentBehaviors->first();
        $latestTimeline = collect($recentInteractions->map(fn ($interaction) => [
                'type' => 'Interaction',
                'title' => $interaction->subject,
                'meta' => ucwords(str_replace('_', ' ', $interaction->type)).' by '.($interaction->handled_by ?: 'Unassigned'),
                'date' => $interaction->interaction_at,
                'description' => $interaction->outcome ?: $interaction->description,
            ])->all())
            ->merge($recentTransactions->map(fn ($transaction) => [
                'type' => 'Transaction',
                'title' => $transaction->title,
                'meta' => 'Rp '.number_format((float) $transaction->amount, 0, ',', '.').' · '.ucfirst($transaction->status),
                'date' => $transaction->closing_date,
                'description' => $transaction->description,
            ]))
            ->sortByDesc(fn ($event) => $event['date']?->timestamp ?? 0)
            ->take(5)
            ->values();

        $lifecycle = $latestBehavior?->lifecycle_stage
            ? ucfirst($latestBehavior->lifecycle_stage)
            : match ($customer->status) {
                'active' => 'Active',
                'inactive' => 'Inactive',
                'blacklist' => 'Blocked',
                'new' => 'Prospect',
                default => 'Prospect',
            };

        $lastActivityAt = collect([
            $latestInteraction?->interaction_at,
            $latestTransaction?->closing_date,
            $recentSalesActivities->first()?->activity_at,
            $latestBehavior?->last_activity_at,
        ])->filter()->sortDesc()->first();

        $kpis = [
            ['label' => 'Interactions', 'value' => number_format($recentInteractions->count()), 'subtext' => 'latest records'],
            ['label' => 'Transactions', 'value' => number_format($recentTransactions->count()), 'subtext' => 'latest records'],
            ['label' => 'Opportunities', 'value' => number_format($recentOpportunities->count()), 'subtext' => 'latest records'],
            ['label' => 'Deals', 'value' => number_format($recentQuotations->count()), 'subtext' => 'latest quotations'],
        ];

        $quickActions = [
            ['label' => 'Interaction', 'href' => route('admin.customers.interactions.create', $customer), 'permission' => 'interactions.create', 'icon' => 'mail'],
            ['label' => 'Transaction', 'href' => route('admin.customers.transactions.create', $customer), 'permission' => 'customers.create', 'icon' => 'cart'],
            ['label' => 'Opportunity', 'href' => route('admin.sales.opportunities', ['customer_id' => $customer->id]), 'permission' => 'opportunities.view', 'icon' => 'opportunity'],
            ['label' => 'Deal', 'href' => route('admin.sales.deals.index', ['customer_id' => $customer->id]), 'permission' => 'quotations.view', 'icon' => 'deal'],
        ];

        $secondaryModules = [
            ['label' => 'Interaction History', 'href' => route('admin.customers.interactions', ['q' => $customer->name]), 'permission' => 'interactions.view'],
            ['label' => 'Transactions', 'href' => route('admin.customers.transactions', ['q' => $customer->name]), 'permission' => 'customers.view'],
            ['label' => 'Preferences', 'href' => route('admin.customers.preferences', ['q' => $customer->name]), 'permission' => 'customers.view'],
            ['label' => 'Behavior', 'href' => route('admin.customers.behavior', ['q' => $customer->name]), 'permission' => 'customers.view'],
            ['label' => 'Opportunity', 'href' => route('admin.sales.opportunities', ['customer_id' => $customer->id]), 'permission' => 'opportunities.view'],
            ['label' => 'Deals', 'href' => route('admin.sales.deals.index', ['customer_id' => $customer->id]), 'permission' => 'quotations.view'],
        ];
    @endphp

    <section class="lead-list-page customer-profile-page customer-360-dashboard">
        @include('admin.customers._success-toast')

        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">{{ strtoupper(substr($customer->name, 0, 1)) }}</div>
                <div>
                    <span class="crm-record-kicker">CUSTOMER PROFILE 360</span>
                    <h1>{{ $customer->name }}</h1>
                    <div class="customer-profile-hero-meta" aria-label="Customer identity">
                        <span>{{ $customer->company_name ?: 'No company' }}</span>
                        <span>{{ $customer->email ?: 'No email' }}</span>
                        <span>{{ $customer->phone ?: 'No phone' }}</span>
                        <span>{{ $customer->owner_name ?: 'No owner' }}</span>
                    </div>
                    <div class="customer-360-hero-meta-line">
                        <span>Last activity: {{ $lastActivityAt?->format('d M Y H:i') ?: 'No signal yet' }}</span>
                        <span>Updated: {{ $customer->updated_at?->format('d M Y H:i') ?: '-' }}</span>
                    </div>
                    <div class="customer-360-hero-badges" aria-label="Customer status">
                        <span class="status-badge status-{{ $customer->status }}">{{ ucfirst($customer->status) }}</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                @can('customers.update')
                    <a href="{{ route('admin.customers.edit', $customer) }}" class="btn lead-banner-cta" aria-label="Edit customer">Edit Customer</a>
                @endcan
                @can('interactions.create')
                    <a href="{{ route('admin.customers.interactions.create', $customer) }}" class="btn btn-muted" aria-label="Add interaction">Add Interaction</a>
                @endcan
                @can('customers.create')
                    <a href="{{ route('admin.customers.transactions.create', $customer) }}" class="btn btn-muted" aria-label="Add transaction">Add Transaction</a>
                @endcan
            </div>
        </header>

        <div class="lead-kpi-strip customer-profile-kpi-strip customer-360-kpi-strip" aria-label="Customer snapshot KPI">
            @foreach ($kpis as $kpi)
                <div>
                    <span>{{ $kpi['label'] }}</span>
                    <strong>{{ $kpi['value'] }}</strong>
                    <small>{{ $kpi['subtext'] }}</small>
                </div>
            @endforeach
        </div>

        <section class="customer-360-action-toolbar" aria-label="Quick actions">
            <span>Quick Actions</span>
            <div>
                @foreach ($quickActions as $action)
                    @can($action['permission'])
                        <a href="{{ $action['href'] }}" class="customer-360-action-pill" aria-label="{{ $action['label'] }}">
                            <span>@include('admin.partials.sidebar-icon', ['icon' => $action['icon']])</span>
                            <strong>{{ $action['label'] }}</strong>
                        </a>
                    @endcan
                @endforeach
                <details class="lead-row-menu customer-profile-row-menu customer-360-more-actions">
                    <summary aria-label="Open more customer actions">More</summary>
                    <div>
                        @foreach ($secondaryModules as $module)
                            @can($module['permission'])
                                <a href="{{ $module['href'] }}">{{ $module['label'] }}</a>
                            @endcan
                        @endforeach
                    </div>
                </details>
            </div>
        </section>

        <section class="customer-360-dashboard-grid" aria-label="Customer 360 dashboard">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Recent Activities</span>
                        <h2>Latest customer signals</h2>
                    </div>
                </div>

                @if ($latestTimeline->isEmpty())
                    <div class="lead-empty-state customer-profile-enterprise-empty">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'activity'])</span>
                        <strong>No recent activities</strong>
                        <p>Interaction and transaction signals will appear here.</p>
                    </div>
                @else
                    <div class="customer-360-timeline">
                        @foreach ($latestTimeline as $event)
                            <article class="customer-360-timeline-item">
                                <span aria-hidden="true"></span>
                                <div>
                                    <small>{{ $event['type'] }} · {{ $event['date']?->format('d M Y H:i') ?: '-' }}</small>
                                    <strong>{{ $event['title'] }}</strong>
                                    <p>{{ \Illuminate\Support\Str::limit($event['description'] ?: $event['meta'], 110) }}</p>
                                    <em>{{ $event['meta'] }}</em>
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Recent Transactions</span>
                        <h2>Commercial movement</h2>
                    </div>
                    <a href="{{ route('admin.customers.transactions', ['q' => $customer->name]) }}" class="btn btn-sm btn-muted">View All</a>
                </div>

                <div class="customer-profile-latest-list customer-360-transaction-list">
                    @forelse ($recentTransactions as $transaction)
                        <div>
                            <span>{{ $transaction->closing_date?->format('d M Y') ?: 'No date' }}</span>
                            <strong>{{ $transaction->title }}</strong>
                            <small>Rp {{ number_format((float) $transaction->amount, 0, ',', '.') }} · {{ ucfirst($transaction->status) }}</small>
                        </div>
                    @empty
                        <div>
                            <span>Transactions</span>
                            <strong>No transaction yet</strong>
                            <small>Create a transaction from quick actions.</small>
                        </div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="customer-profile-workspace customer-360-section" aria-label="Sales summary">
            <div class="customer-profile-section-head">
                <div>
                    <span>Sales Summary</span>
                    <h2>Pipeline and deal context</h2>
                </div>
            </div>
            <div class="customer-profile-latest-list customer-360-sales-summary">
                <div>
                    <span>Latest Opportunity</span>
                    <strong>{{ $recentOpportunities->first()?->title ?: 'No opportunity yet' }}</strong>
                    <small>{{ $recentOpportunities->first()?->status ? ucfirst($recentOpportunities->first()->status) : 'Open opportunity module' }}</small>
                </div>
                <div>
                    <span>Latest Deal</span>
                    <strong>{{ $recentQuotations->first()?->title ?: 'No deal yet' }}</strong>
                    <small>{{ $recentQuotations->first() ? 'Rp '.number_format((float) $recentQuotations->first()->amount, 0, ',', '.') : 'Open deal module' }}</small>
                </div>
                <div>
                    <span>Latest Sales Activity</span>
                    <strong>{{ $recentSalesActivities->first()?->subject ?: 'No sales activity yet' }}</strong>
                    <small>{{ $recentSalesActivities->first()?->activity_at?->format('d M Y H:i') ?: '-' }}</small>
                </div>
            </div>
        </section>

        <section class="customer-360-related" aria-label="Related modules">
            <span>Related Modules</span>
            <div>
                @foreach ($secondaryModules as $module)
                    @can($module['permission'])
                        <a href="{{ $module['href'] }}">{{ $module['label'] }}</a>
                    @endcan
                @endforeach
            </div>
        </section>
    </section>
@endsection
