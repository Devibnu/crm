@extends('admin.layouts.app')

@section('title', 'Service Management Dashboard - Krakatau CRM')

@section('content')
    @php
        $badgeClass = static function (?string $status): string {
            return match ((string) $status) {
                'open', 'in_progress', 'unread', 'active', 'positive' => 'status-active',
                'pending', 'waiting_customer', 'medium', 'neutral' => 'status-pending',
                'resolved', 'closed', 'published', 'high' => 'status-won',
                'urgent', 'negative' => 'status-lost',
                default => 'status-inactive',
            };
        };
    @endphp

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'ticket'])</div>
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
                        <h2>Ticket Status Overview</h2>
                        <p>Distribusi ticket berdasarkan status dan prioritas.</p>
                    </div>
                </div>
                <div class="dashboard-panel-grid" style="margin-bottom:0;">
                    <div class="dashboard-status-list">
                        <div>
                            <span>Total Tickets <small>Open: {{ number_format($metrics['open_tickets']) }} | Resolved: {{ number_format($metrics['resolved_tickets']) }}</small></span>
                            <strong>{{ number_format($metrics['total_tickets']) }}</strong>
                        </div>
                        @forelse ($ticketStatusByStatus as $status)
                            <div>
                                <span>{{ str($status->status)->headline() }}</span>
                                <strong>{{ number_format($status->total) }}</strong>
                            </div>
                        @empty
                            <div><span>No ticket statuses</span><strong>0</strong></div>
                        @endforelse
                    </div>
                    <div class="dashboard-status-list">
                        <div>
                            <span>High Priority Tickets</span>
                            <strong>{{ number_format($metrics['high_priority_tickets']) }}</strong>
                        </div>
                        @forelse ($ticketStatusByPriority as $priority)
                            <div>
                                <span>{{ str($priority->priority)->headline() }}</span>
                                <strong>{{ number_format($priority->total) }}</strong>
                            </div>
                        @empty
                            <div><span>No ticket priorities</span><strong>0</strong></div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card">
                <div class="sales-section-head">
                    <div>
                        <h2>Omnichannel Overview</h2>
                        <p>Distribusi message per channel dan status utama.</p>
                    </div>
                </div>
                <div class="dashboard-panel-grid" style="margin-bottom:0;">
                    <div class="dashboard-status-list">
                        <div>
                            <span>Total Messages <small>Unread/Pending/Resolved</small></span>
                            <strong>{{ number_format($metrics['total_omnichannel_messages']) }}</strong>
                        </div>
                        @forelse ($omnichannelByChannel as $channel)
                            <div>
                                <span>{{ str($channel->channel)->headline() }}</span>
                                <strong>{{ number_format($channel->total) }}</strong>
                            </div>
                        @empty
                            <div><span>No channels</span><strong>0</strong></div>
                        @endforelse
                    </div>
                    <div class="dashboard-status-list">
                        @foreach ($omnichannelStatusOverview as $status)
                            <div>
                                <span>{{ $status['label'] }}</span>
                                <strong>{{ number_format($status['value']) }}</strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>CSAT Overview</h2>
                    <p>Average rating, feedback volume, dan sentiment breakdown.</p>
                </div>
            </div>
            <div class="sales-summary-grid">
                <article class="card sales-summary-card">
                    <span>Average Rating</span>
                    <strong>{{ number_format($metrics['average_csat'], 1, ',', '.') }}</strong>
                    <small>Skala 1 - 5</small>
                </article>
                <article class="card sales-summary-card">
                    <span>Feedback Count</span>
                    <strong>{{ number_format($metrics['total_csat_feedback']) }}</strong>
                    <small>Total CSAT responses</small>
                </article>
                <article class="card sales-summary-card">
                    <span>Knowledge Articles</span>
                    <strong>{{ number_format($metrics['total_knowledge_articles']) }}</strong>
                    <small>{{ number_format($metrics['published_articles']) }} published</small>
                </article>
                <article class="card sales-summary-card">
                    <span>SLA Policies</span>
                    <strong>{{ number_format($metrics['active_sla_policies']) }}</strong>
                    <small>{{ number_format($metrics['total_sla_policies']) }} total</small>
                </article>
            </div>
            <div class="dashboard-status-list">
                <div>
                    <span>Total Case Resolutions</span>
                    <strong>{{ number_format($metrics['total_case_resolutions']) }}</strong>
                </div>
                @forelse ($csatSentimentBreakdown as $sentiment)
                    <div>
                        <span>{{ str($sentiment->sentiment)->headline() }}</span>
                        <strong>{{ number_format($sentiment->total) }}</strong>
                    </div>
                @empty
                    <div><span>No CSAT sentiment data</span><strong>0</strong></div>
                @endforelse
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Tickets</h2>
                    <p>5 ticket terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Ticket</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Channel</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentTickets as $ticket)
                        <tr>
                            <td>{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->subject }}</td>
                            <td><span class="status-badge {{ $badgeClass($ticket->status) }}">{{ str($ticket->status)->headline() }}</span></td>
                            <td><span class="status-badge {{ $badgeClass($ticket->priority) }}">{{ str($ticket->priority)->headline() }}</span></td>
                            <td>{{ str($ticket->channel)->headline() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty">No tickets found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Omnichannel Messages</h2>
                    <p>5 pesan omnichannel terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Channel</th>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Received</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentOmnichannelMessages as $message)
                        <tr>
                            <td>{{ str($message->channel)->headline() }}</td>
                            <td>{{ $message->sender_name ?: '-' }}</td>
                            <td>{{ $message->subject ?: '-' }}</td>
                            <td><span class="status-badge {{ $badgeClass($message->status) }}">{{ str($message->status)->headline() }}</span></td>
                            <td>{{ optional($message->received_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty">No omnichannel messages found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Customer Satisfaction Feedback</h2>
                    <p>5 feedback CSAT terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Ticket</th>
                        <th>Rating</th>
                        <th>Sentiment</th>
                        <th>Submitted</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentCsatFeedback as $feedback)
                        <tr>
                            <td>{{ $feedback->customer?->name ?: '-' }}</td>
                            <td>{{ $feedback->ticket?->ticket_number ?: '-' }}</td>
                            <td>{{ number_format((float) $feedback->rating, 1, ',', '.') }}</td>
                            <td><span class="status-badge {{ $badgeClass($feedback->sentiment) }}">{{ str($feedback->sentiment)->headline() }}</span></td>
                            <td>{{ optional($feedback->submitted_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty">No customer satisfaction feedback found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recent Knowledge Articles</h2>
                    <p>5 artikel knowledge base terbaru.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Published At</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentKnowledgeArticles as $article)
                        <tr>
                            <td>{{ $article->title }}</td>
                            <td>{{ $article->category ?: '-' }}</td>
                            <td>
                                <span class="status-badge {{ $article->is_published ? 'status-won' : 'status-pending' }}">
                                    {{ $article->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td>{{ optional($article->published_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="customer-empty">No knowledge articles found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
