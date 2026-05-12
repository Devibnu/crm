@extends('admin.layouts.app')

@section('title', 'Customer Profile 360 Dashboard - Krakatau CRM')

@section('content')
    @php
        $badgeClass = static function (?string $status): string {
            return match ((string) $status) {
                'new', 'active', 'won', 'lead', 'prospect', 'loyal', 'high' => 'status-active',
                'inactive', 'pending', 'meeting', 'email', 'follow_up', 'active_customer' => 'status-pending',
                'lost', 'cancelled', 'churned', 'blacklist', 'none' => 'status-lost',
                default => 'status-inactive',
            };
        };
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'user'])</div>
            <div>
                <h1>{{ $title }}</h1>
                <p>{{ $description }}</p>
            </div>
        </article>

        <section class="sales-summary-grid">
            @foreach ($summaryCards as $card)
                <article class="card sales-summary-card">
                    <span>{{ $card['label'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small>{{ $card['hint'] }}</small>
                </article>
            @endforeach
        </section>

        <div class="dashboard-panel-grid">
            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Customer Status Overview</h2>
                        <p>Distribusi customer berdasarkan status utama.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Total Customers <small>Active: {{ number_format($metrics['active_customers']) }} | Inactive: {{ number_format($metrics['inactive_customers']) }}</small></span>
                        <strong>{{ number_format($metrics['total_customers']) }}</strong>
                    </div>
                    <div>
                        <span>Blacklist Customers</span>
                        <strong>{{ number_format($metrics['blacklist_customers']) }}</strong>
                    </div>
                    @forelse ($customerStatusOverview as $status)
                        <div>
                            <span>{{ str($status->status)->headline() }}</span>
                            <strong>{{ number_format($status->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No customer statuses</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Interaction Type Overview</h2>
                        <p>Total interaction per type channel.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Total Interactions</span>
                        <strong>{{ number_format($metrics['total_interactions']) }}</strong>
                    </div>
                    @forelse ($interactionTypeOverview as $interaction)
                        <div>
                            <span>{{ str($interaction->type)->headline() }}</span>
                            <strong>{{ number_format($interaction->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No interaction types</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="dashboard-panel-grid">
            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Transaction Status Overview</h2>
                        <p>Status transaksi dan total nilai per status.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Total Transactions <small>Won Value: {{ 'Rp '.number_format((float) $metrics['won_transaction_value'], 0, ',', '.') }}</small></span>
                        <strong>{{ number_format($metrics['total_transactions']) }}</strong>
                    </div>
                    <div>
                        <span>Total Transaction Value</span>
                        <strong>{{ 'Rp '.number_format((float) $metrics['total_transaction_value'], 0, ',', '.') }}</strong>
                    </div>
                    @forelse ($transactionStatusOverview as $transaction)
                        <div>
                            <span>{{ str($transaction->status)->headline() }} <small>{{ 'Rp '.number_format((float) $transaction->value_total, 0, ',', '.') }}</small></span>
                            <strong>{{ number_format($transaction->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No transaction statuses</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Behavior Lifecycle Overview</h2>
                        <p>Lifecycle stage customer dan engagement rata-rata per stage.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Total Behaviors</span>
                        <strong>{{ number_format($metrics['total_behaviors']) }}</strong>
                    </div>
                    <div>
                        <span>Average Engagement Score</span>
                        <strong>{{ number_format((float) $metrics['average_engagement_score'], 1, ',', '.') }}</strong>
                    </div>
                    @forelse ($behaviorLifecycleOverview as $behavior)
                        <div>
                            <span>{{ str($behavior->lifecycle_stage)->headline() }} <small>Avg: {{ number_format((float) $behavior->avg_engagement, 1, ',', '.') }}</small></span>
                            <strong>{{ number_format($behavior->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No behavior lifecycle data</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Customers</h2>
                    <p>5 customer terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Owner</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentCustomers as $customer)
                        <tr>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->email ?: '-' }}</td>
                            <td><span class="status-badge {{ $badgeClass($customer->status) }}">{{ str($customer->status)->headline() }}</span></td>
                            <td>{{ $customer->source ?: '-' }}</td>
                            <td>{{ $customer->owner_name ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty">No customers found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Interactions</h2>
                    <p>5 interaction terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Subject</th>
                        <th>Handled By</th>
                        <th>Interaction At</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentInteractions as $interaction)
                        <tr>
                            <td>{{ $interaction->customer?->name ?: '-' }}</td>
                            <td><span class="status-badge {{ $badgeClass($interaction->type) }}">{{ str($interaction->type)->headline() }}</span></td>
                            <td>{{ $interaction->subject }}</td>
                            <td>{{ $interaction->handled_by ?: '-' }}</td>
                            <td>{{ optional($interaction->interaction_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty">No interactions found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Transactions</h2>
                    <p>5 transaksi terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Closing Date</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentTransactions as $transaction)
                        <tr>
                            <td>{{ $transaction->customer?->name ?: '-' }}</td>
                            <td>{{ $transaction->title }}</td>
                            <td><span class="status-badge {{ $badgeClass($transaction->status) }}">{{ str($transaction->status)->headline() }}</span></td>
                            <td>{{ 'Rp '.number_format((float) $transaction->amount, 0, ',', '.') }}</td>
                            <td>{{ optional($transaction->closing_date)->format('d M Y') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty">No transactions found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Preferences</h2>
                    <p>5 preference terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Preferred Channel</th>
                        <th>Product Interest</th>
                        <th>Segment</th>
                        <th>Consent</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentPreferences as $preference)
                        <tr>
                            <td>{{ $preference->customer?->name ?: '-' }}</td>
                            <td>{{ str($preference->preferred_channel)->headline() }}</td>
                            <td>{{ $preference->product_interest ?: '-' }}</td>
                            <td>{{ $preference->segment ?: '-' }}</td>
                            <td>
                                <span class="status-badge {{ $preference->communication_consent ? 'status-active' : 'status-lost' }}">
                                    {{ $preference->communication_consent ? 'Consent' : 'No Consent' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty">No preferences found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Behavior Data</h2>
                    <p>5 behavior data terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Lifecycle Stage</th>
                        <th>Engagement Score</th>
                        <th>Product Interest</th>
                        <th>Last Activity</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentBehaviors as $behavior)
                        <tr>
                            <td>{{ $behavior->customer?->name ?: '-' }}</td>
                            <td><span class="status-badge {{ $badgeClass($behavior->lifecycle_stage) }}">{{ str($behavior->lifecycle_stage)->headline() }}</span></td>
                            <td>{{ number_format((float) $behavior->engagement_score, 0, ',', '.') }}</td>
                            <td>{{ $behavior->product_interest ?: '-' }}</td>
                            <td>{{ optional($behavior->last_activity_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty">No behavior data found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
