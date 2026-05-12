@extends('admin.layouts.app')

@section('title', 'Marketing Automation Dashboard - Krakatau CRM')

@section('content')
    @php
        $badgeClass = static function (?string $status): string {
            return match ((string) $status) {
                'running', 'active', 'completed', 'published' => 'status-active',
                'scheduled', 'draft' => 'status-pending',
                'failed', 'cancelled', 'archived', 'inactive', 'paused' => 'status-lost',
                default => 'status-inactive',
            };
        };
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'campaign'])</div>
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
                        <h2>Campaign Status Overview</h2>
                        <p>Total campaign, campaign aktif/running, dan campaign selesai.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Total Campaigns <small>Running: {{ number_format($metrics['running_campaigns']) }} | Completed: {{ number_format($metrics['completed_campaigns']) }}</small></span>
                        <strong>{{ number_format($metrics['total_campaigns']) }}</strong>
                    </div>
                    @forelse ($campaignStatusOverview as $status)
                        <div>
                            <span>{{ str($status->status)->headline() }}</span>
                            <strong>{{ number_format($status->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No campaign statuses</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Execution Performance Overview</h2>
                        <p>Total eksekusi, eksekusi selesai, dan total sent.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Total Executions <small>Completed: {{ number_format($metrics['completed_executions']) }}</small></span>
                        <strong>{{ number_format($metrics['total_executions']) }}</strong>
                    </div>
                    <div>
                        <span>Total Sent</span>
                        <strong>{{ number_format($metrics['total_sent']) }}</strong>
                    </div>
                    @forelse ($executionPerformanceOverview as $execution)
                        <div>
                            <span>{{ str($execution->status)->headline() }} <small>Sent: {{ number_format($execution->sent_total) }}</small></span>
                            <strong>{{ number_format($execution->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No execution statuses</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>
        </div>

        <div class="dashboard-panel-grid">
            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Landing Page Performance</h2>
                        <p>Total landing page dan submissions terkumpul.</p>
                    </div>
                </div>
                <div class="sales-summary-grid" style="margin-bottom:0;">
                    <article class="card sales-summary-card">
                        <span>Total Landing Pages</span>
                        <strong>{{ number_format($metrics['total_landing_pages']) }}</strong>
                        <small>Marketing assets</small>
                    </article>
                    <article class="card sales-summary-card">
                        <span>Total Submissions</span>
                        <strong>{{ number_format($metrics['total_submissions']) }}</strong>
                        <small>Lead capture results</small>
                    </article>
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Social Engagement Overview</h2>
                        <p>Volume social post, impressions, dan engagement rate rata-rata.</p>
                    </div>
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Total Social Posts</span>
                        <strong>{{ number_format($metrics['total_social_posts']) }}</strong>
                    </div>
                    <div>
                        <span>Total Impressions</span>
                        <strong>{{ number_format($metrics['total_impressions']) }}</strong>
                    </div>
                    <div>
                        <span>Average Engagement Rate</span>
                        <strong>{{ number_format((float) $metrics['average_engagement_rate'], 2, ',', '.') }}%</strong>
                    </div>
                    @forelse ($socialByPlatform as $platform)
                        <div>
                            <span>{{ str($platform->platform)->headline() }} <small>{{ number_format($platform->impressions_total) }} impressions</small></span>
                            <strong>{{ number_format($platform->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No social platforms</span><strong>0</strong></div>
                    @endforelse
                </div>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Automation Overview</h2>
                    <p>Status automation dan lead scoring rules yang aktif.</p>
                </div>
            </div>
            <div class="sales-summary-grid">
                <article class="card sales-summary-card">
                    <span>Total Automations</span>
                    <strong>{{ number_format($metrics['total_automations']) }}</strong>
                    <small>{{ number_format($metrics['active_automations']) }} active</small>
                </article>
                <article class="card sales-summary-card">
                    <span>Total Lead Scoring Rules</span>
                    <strong>{{ number_format($metrics['total_lead_scoring_rules']) }}</strong>
                    <small>{{ number_format($metrics['active_lead_scoring_rules']) }} active</small>
                </article>
                <article class="card sales-summary-card">
                    <span>Audience Segments</span>
                    <strong>{{ number_format($totalAudienceSegments) }}</strong>
                    <small>Available targeting groups</small>
                </article>
            </div>
            <div class="dashboard-panel-grid" style="margin-bottom:0;">
                <div class="dashboard-status-list">
                    <div>
                        <span>Automation Status</span>
                        <strong>{{ number_format($metrics['total_automations']) }}</strong>
                    </div>
                    @forelse ($automationStatusOverview as $automation)
                        <div>
                            <span>{{ str($automation->status)->headline() }}</span>
                            <strong>{{ number_format($automation->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No automation statuses</span><strong>0</strong></div>
                    @endforelse
                </div>
                <div class="dashboard-status-list">
                    <div>
                        <span>Lead Scoring Status</span>
                        <strong>{{ number_format($metrics['total_lead_scoring_rules']) }}</strong>
                    </div>
                    @forelse ($leadScoringStatusOverview as $rule)
                        <div>
                            <span>{{ str($rule->status)->headline() }}</span>
                            <strong>{{ number_format($rule->total) }}</strong>
                        </div>
                    @empty
                        <div><span>No lead scoring statuses</span><strong>0</strong></div>
                    @endforelse
                </div>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Campaigns</h2>
                    <p>5 campaign terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Campaign</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Actual Leads</th>
                            <th>Start Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentCampaigns as $campaign)
                            <tr>
                                <td>{{ $campaign->name }}</td>
                                <td>{{ str($campaign->type)->headline() }}</td>
                                <td><span class="status-badge {{ $badgeClass($campaign->status) }}">{{ str($campaign->status)->headline() }}</span></td>
                                <td>{{ number_format((int) $campaign->actual_leads) }}</td>
                                <td>{{ optional($campaign->start_date)->format('d M Y') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty">No campaigns found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Executions</h2>
                    <p>5 execution terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Execution</th>
                            <th>Campaign</th>
                            <th>Channel</th>
                            <th>Status</th>
                            <th>Sent</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentExecutions as $execution)
                            <tr>
                                <td>{{ $execution->execution_name }}</td>
                                <td>{{ $execution->marketingCampaign?->name ?: '-' }}</td>
                                <td>{{ str($execution->channel)->headline() }}</td>
                                <td><span class="status-badge {{ $badgeClass($execution->status) }}">{{ str($execution->status)->headline() }}</span></td>
                                <td>{{ number_format((int) $execution->sent_count) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty">No executions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Landing Pages</h2>
                    <p>5 landing page terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Submissions</th>
                            <th>Published At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentLandingPages as $landingPage)
                            <tr>
                                <td>{{ $landingPage->title }}</td>
                                <td><span class="status-badge {{ $badgeClass($landingPage->status) }}">{{ str($landingPage->status)->headline() }}</span></td>
                                <td>{{ number_format((int) $landingPage->views_count) }}</td>
                                <td>{{ number_format((int) $landingPage->submissions_count) }}</td>
                                <td>{{ optional($landingPage->published_at)->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty">No landing pages found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Social Posts</h2>
                    <p>5 social post terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                        <tr>
                            <th>Platform</th>
                            <th>Post Title</th>
                            <th>Status</th>
                            <th>Impressions</th>
                            <th>Engagement Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentSocialPosts as $socialPost)
                            <tr>
                                <td>{{ str($socialPost->platform)->headline() }}</td>
                                <td>{{ $socialPost->post_title }}</td>
                                <td><span class="status-badge {{ $badgeClass($socialPost->status) }}">{{ str($socialPost->status)->headline() }}</span></td>
                                <td>{{ number_format((int) $socialPost->impressions_count) }}</td>
                                <td>{{ number_format((float) $socialPost->engagement_rate, 2, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="customer-empty">No social posts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
