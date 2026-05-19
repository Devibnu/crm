@extends('admin.layouts.app')

@section('title', 'Service Management Dashboard - Krakatau CRM')

@section('content')
    @php
        $tx = static fn (string $en, string $id): array => ['en' => $en, 'id' => $id];
        $titleTranslation = $tx('Service Management Dashboard', 'Dashboard Service Management');
        $descriptionTranslation = $tx(
            'Service performance summary across tickets, SLA, omnichannel, CSAT, and knowledge base.',
            'Ringkasan performa layanan customer, ticket, SLA, omnichannel, CSAT, dan knowledge base.'
        );
        $summaryCardItems = collect($summaryCards)->map(function (array $card) use ($tx) {
            $labelTranslation = match ($card['label']) {
                'Total Tickets' => $tx('Total Tickets', 'Total Tiket'),
                'Open Tickets' => $tx('Open Tickets', 'Tiket Terbuka'),
                'Unread Messages' => $tx('Unread Messages', 'Pesan Belum Dibaca'),
                'Average CSAT' => $tx('Average CSAT', 'Rata-rata CSAT'),
                'Knowledge Articles' => $tx('Knowledge Articles', 'Artikel Knowledge'),
                'Active SLA' => $tx('Active SLA', 'SLA Aktif'),
                default => $tx($card['label'], $card['label']),
            };

            return $card + ['label_translation' => $labelTranslation];
        });
        $serviceLabel = static function (?string $value) use ($tx): array {
            return match ((string) $value) {
                'open' => $tx('Open', 'Open'),
                'in_progress' => $tx('In Progress', 'Sedang Diproses'),
                'waiting_customer' => $tx('Waiting Customer', 'Menunggu Customer'),
                'resolved' => $tx('Resolved', 'Selesai'),
                'closed' => $tx('Closed', 'Ditutup'),
                'urgent' => $tx('Urgent', 'Urgent'),
                'high' => $tx('High', 'Tinggi'),
                'medium' => $tx('Medium', 'Sedang'),
                'low' => $tx('Low', 'Rendah'),
                'email' => $tx('Email', 'Email'),
                'whatsapp' => $tx('WhatsApp', 'WhatsApp'),
                'live_chat' => $tx('Live Chat', 'Live Chat'),
                'facebook' => $tx('Facebook', 'Facebook'),
                'instagram' => $tx('Instagram', 'Instagram'),
                'unread' => $tx('Unread', 'Belum Dibaca'),
                'pending' => $tx('Pending', 'Pending'),
                'positive' => $tx('Positive', 'Positif'),
                'neutral' => $tx('Neutral', 'Netral'),
                'negative' => $tx('Negative', 'Negatif'),
                default => $tx(str((string) $value)->headline()->toString(), str((string) $value)->headline()->toString()),
            };
        };
        $badgeClass = static function (?string $status): string {
            return match ((string) $status) {
                'open', 'in_progress', 'unread', 'active', 'positive' => 'status-active',
                'pending', 'waiting_customer', 'medium', 'neutral' => 'status-pending',
                'resolved', 'closed', 'published', 'high' => 'status-won',
                'urgent', 'negative' => 'status-lost',
                default => 'status-inactive',
            };
        };

        $chartPalette = [
            'open' => '#0091b3',
            'in_progress' => '#00bad1',
            'waiting_customer' => '#ff9f43',
            'resolved' => '#28c76f',
            'closed' => '#16a34a',
            'unread' => '#0091b3',
            'pending' => '#ff9f43',
            'resolved_channel' => '#28c76f',
            'email' => '#0091b3',
            'whatsapp' => '#28c76f',
            'live_chat' => '#00bad1',
            'facebook' => '#3b82f6',
            'instagram' => '#ec4899',
            'positive' => '#28c76f',
            'neutral' => '#ff9f43',
            'negative' => '#ff4c51',
            'urgent' => '#ff4c51',
            'high' => '#ff9f43',
            'medium' => '#00bad1',
            'low' => '#8b879a',
        ];

        $ticketStatusItems = $ticketStatusByStatus->values();
        $ticketTotal = max((int) $ticketStatusItems->sum('total'), 1);
        $ticketSegments = [];
        $ticketOffset = 0;

        foreach ($ticketStatusItems as $item) {
            $share = round(((int) $item->total / $ticketTotal) * 100, 2);
            $color = $chartPalette[$item->status] ?? '#8b879a';
            $ticketSegments[] = "{$color} {$ticketOffset}% ".($ticketOffset + $share).'%';
            $ticketOffset += $share;
        }

        $ticketChart = $ticketSegments !== []
            ? 'conic-gradient('.implode(', ', $ticketSegments).')'
            : 'conic-gradient(#eef2ff 0% 100%)';

        $priorityItems = $ticketStatusByPriority->values();
        $priorityMax = max((int) $priorityItems->max('total'), 1);

        $channelItems = $omnichannelByChannel->values();
        $channelMax = max((int) $channelItems->max('total'), 1);
        $omniResolved = (int) collect($omnichannelStatusOverview)->firstWhere('label', 'Resolved')['value'] ?? 0;

        $csatRate = $metrics['total_csat_feedback'] > 0
            ? round(($metrics['average_csat'] / 5) * 100, 1)
            : 0;
        $csatRing = 'conic-gradient(#28c76f 0% '.$csatRate.'%, #e9edf7 '.$csatRate.'% 100%)';

        $sentimentItems = $csatSentimentBreakdown->values();
        $sentimentMax = max((int) $sentimentItems->max('total'), 1);
    @endphp

    <section class="service-page customer-list-page sales-workspace sales-dashboard-page service-dashboard-page">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">@include('admin.partials.sidebar-icon', ['icon' => 'ticket'])</div>
            <div>
                <span data-doc-title-en="Service Management Dashboard - Krakatau CRM" data-doc-title-id="Dashboard Service Management - Krakatau CRM" hidden></span>
                <span class="service-badge dashboard-hero-badge" data-lang-en="Service Control Center" data-lang-id="Pusat Kendali Layanan">Service Control Center</span>
                <h1 data-lang-en="{{ $titleTranslation['en'] }}" data-lang-id="{{ $titleTranslation['id'] }}">{{ $titleTranslation['en'] }}</h1>
                <p data-lang-en="{{ $descriptionTranslation['en'] }}" data-lang-id="{{ $descriptionTranslation['id'] }}">{{ $descriptionTranslation['en'] }}</p>
            </div>
        </article>

        <section class="sales-summary-grid">
            @foreach ($summaryCardItems as $card)
                <article class="card sales-summary-card">
                    <span data-lang-en="{{ $card['label_translation']['en'] }}" data-lang-id="{{ $card['label_translation']['id'] }}">{{ $card['label_translation']['en'] }}</span>
                    <strong>{{ $card['value'] }}</strong>
                    <small>{{ $card['hint'] }}</small>
                </article>
            @endforeach
        </section>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card sales-chart-card--lead">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Ticket Status Overview" data-lang-id="Ringkasan Status Tiket">Ticket Status Overview</h2>
                        <p data-lang-en="Ticket distribution by primary status and service volume." data-lang-id="Distribusi ticket berdasarkan status utama dan volume layanan.">Ticket distribution by primary status and service volume.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--lead">
                    <div class="sales-donut-panel">
                        <div class="sales-donut-chart" style="--chart-fill: {{ $ticketChart }};">
                            <div class="sales-donut-center">
                                <span data-lang-en="Total Tickets" data-lang-id="Total Tiket">Total Tickets</span>
                                <strong>{{ number_format($metrics['total_tickets']) }}</strong>
                                <small data-lang-en="{{ number_format($metrics['open_tickets']) }} open / {{ number_format($metrics['resolved_tickets']) }} resolved" data-lang-id="{{ number_format($metrics['open_tickets']) }} open / {{ number_format($metrics['resolved_tickets']) }} selesai">{{ number_format($metrics['open_tickets']) }} open / {{ number_format($metrics['resolved_tickets']) }} resolved</small>
                            </div>
                        </div>
                    </div>

                    <div class="sales-legend-list">
                        @forelse ($ticketStatusItems as $status)
                            @php($share = round(((int) $status->total / $ticketTotal) * 100, 1))
                            @php($color = $chartPalette[$status->status] ?? '#8b879a')
                            <div class="sales-legend-item">
                                <span class="sales-legend-dot" style="--legend-color: {{ $color }};"></span>
                                <div class="sales-legend-copy">
                                    @php($ticketStatus = $serviceLabel($status->status))
                                    <strong data-lang-en="{{ $ticketStatus['en'] }}" data-lang-id="{{ $ticketStatus['id'] }}">{{ $ticketStatus['en'] }}</strong>
                                    <small data-lang-en="{{ number_format($status->total) }} tickets / {{ $share }}%" data-lang-id="{{ number_format($status->total) }} tiket / {{ $share }}%">{{ number_format($status->total) }} tickets / {{ $share }}%</small>
                                </div>
                                <div class="sales-legend-metric">{{ number_format($status->total) }}</div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No ticket status data yet." data-lang-id="Belum ada data status ticket.">No ticket status data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card sales-chart-card--pipeline">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Priority Pressure" data-lang-id="Tekanan Prioritas">Priority Pressure</h2>
                        <p data-lang-en="Ticket workload pressure by priority level." data-lang-id="Tekanan workload ticket berdasarkan level prioritas.">Ticket workload pressure by priority level.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="sales-chart-hero">
                        <div>
                            <span data-lang-en="High Priority Tickets" data-lang-id="Tiket Prioritas Tinggi">High Priority Tickets</span>
                            <strong>{{ number_format($metrics['high_priority_tickets']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Open Tickets" data-lang-id="Tiket Terbuka">Open Tickets</span>
                            <strong>{{ number_format($metrics['open_tickets']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Resolved Tickets" data-lang-id="Tiket Selesai">Resolved Tickets</span>
                            <strong>{{ number_format($metrics['resolved_tickets']) }}</strong>
                        </div>
                    </div>

                    <div class="sales-funnel-list">
                        @forelse ($priorityItems as $priority)
                            @php($countWidth = max(12, round(((int) $priority->total / $priorityMax) * 100, 1)))
                            @php($color = $chartPalette[$priority->priority] ?? '#8b879a')
                            <div class="sales-funnel-item">
                                <div class="sales-funnel-head">
                                    <div>
                                        @php($priorityLabel = $serviceLabel($priority->priority))
                                        <strong data-lang-en="{{ $priorityLabel['en'] }}" data-lang-id="{{ $priorityLabel['id'] }}">{{ $priorityLabel['en'] }}</strong>
                                        <small data-lang-en="Priority distribution" data-lang-id="Distribusi prioritas">Priority distribution</small>
                                    </div>
                                    <span data-lang-en="{{ number_format($priority->total) }} tickets" data-lang-id="{{ number_format($priority->total) }} tiket">{{ number_format($priority->total) }} tickets</span>
                                </div>
                                <div class="sales-funnel-track">
                                    <div class="sales-funnel-bar" style="--bar-width: {{ $countWidth }}%; --bar-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No ticket priority data yet." data-lang-id="Belum ada data prioritas ticket.">No ticket priority data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>
        </div>

        <div class="dashboard-panel-grid sales-chart-grid">
            <article class="card customer-table-card sales-chart-card sales-chart-card--pipeline">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="Omnichannel Overview" data-lang-id="Ringkasan Omnichannel">Omnichannel Overview</h2>
                        <p data-lang-en="Inbound channels, unread load, pending response, and resolved queue." data-lang-id="Channel inbound, unread load, pending response, dan resolved queue.">Inbound channels, unread load, pending response, and resolved queue.</p>
                    </div>
                </div>
                <div class="sales-chart-body">
                    <div class="sales-chart-hero">
                        <div>
                            <span data-lang-en="Total Messages" data-lang-id="Total Pesan">Total Messages</span>
                            <strong>{{ number_format($metrics['total_omnichannel_messages']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Unread Messages" data-lang-id="Pesan Belum Dibaca">Unread Messages</span>
                            <strong>{{ number_format($metrics['unread_messages']) }}</strong>
                        </div>
                        <div>
                            <span data-lang-en="Resolved Queue" data-lang-id="Antrian Selesai">Resolved Queue</span>
                            <strong>{{ number_format($omniResolved) }}</strong>
                        </div>
                    </div>

                    <div class="sales-funnel-list">
                        @forelse ($channelItems as $channel)
                            @php($countWidth = max(12, round(((int) $channel->total / $channelMax) * 100, 1)))
                            @php($color = $chartPalette[$channel->channel] ?? '#00bad1')
                            <div class="sales-funnel-item">
                                <div class="sales-funnel-head">
                                    <div>
                                        @php($channelLabel = $serviceLabel($channel->channel))
                                        <strong data-lang-en="{{ $channelLabel['en'] }}" data-lang-id="{{ $channelLabel['id'] }}">{{ $channelLabel['en'] }}</strong>
                                        <small data-lang-en="Inbound traffic share" data-lang-id="Porsi trafik masuk">Inbound traffic share</small>
                                    </div>
                                    <span>{{ number_format($channel->total) }} msgs</span>
                                </div>
                                <div class="sales-funnel-track">
                                    <div class="sales-funnel-bar" style="--bar-width: {{ $countWidth }}%; --bar-color: {{ $color }};"></div>
                                </div>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No omnichannel channel data yet." data-lang-id="Belum ada data channel omnichannel.">No omnichannel channel data yet.</div>
                        @endforelse
                    </div>
                </div>
            </article>

            <article class="card customer-table-card sales-chart-card sales-chart-card--quotation">
                <div class="sales-section-head">
                    <div>
                        <h2 data-lang-en="CSAT Overview" data-lang-id="Ringkasan CSAT">CSAT Overview</h2>
                        <p data-lang-en="Average rating, total feedback, and customer sentiment." data-lang-id="Average rating, total feedback, dan sentiment pelanggan.">Average rating, total feedback, and customer sentiment.</p>
                    </div>
                </div>
                <div class="sales-chart-body sales-chart-body--quotation">
                    <div class="sales-quotation-ring-panel">
                        <div class="sales-quotation-ring" style="--ring-fill: {{ $csatRing }};">
                            <div class="sales-quotation-ring-center">
                                <span data-lang-en="CSAT Score" data-lang-id="Skor CSAT">CSAT Score</span>
                                <strong>{{ number_format((float) $metrics['average_csat'], 1, ',', '.') }}</strong>
                                <small>{{ number_format($metrics['total_csat_feedback']) }} feedback • {{ number_format($csatRate, 1, ',', '.') }}% of max score</small>
                            </div>
                        </div>
                        <div class="sales-quotation-summary">
                            <div>
                                <span data-lang-en="Knowledge Articles" data-lang-id="Artikel Knowledge">Knowledge Articles</span>
                                <strong>{{ number_format($metrics['total_knowledge_articles']) }}</strong>
                            </div>
                            <div>
                                <span data-lang-en="Active SLA" data-lang-id="SLA Aktif">Active SLA</span>
                                <strong>{{ number_format($metrics['active_sla_policies']) }}</strong>
                            </div>
                        </div>
                    </div>

                    <div class="sales-quotation-bars">
                        @forelse ($sentimentItems as $sentiment)
                            @php($barWidth = max(10, round(((int) $sentiment->total / $sentimentMax) * 100, 1)))
                            @php($color = $chartPalette[$sentiment->sentiment] ?? '#8b879a')
                            <div class="sales-quotation-item">
                                <div class="sales-quotation-head">
                                    @php($sentimentLabel = $serviceLabel($sentiment->sentiment))
                                    <strong data-lang-en="{{ $sentimentLabel['en'] }}" data-lang-id="{{ $sentimentLabel['id'] }}">{{ $sentimentLabel['en'] }}</strong>
                                    <span>{{ number_format($sentiment->total) }}</span>
                                </div>
                                <div class="sales-quotation-track">
                                    <div class="sales-quotation-bar" style="--bar-width: {{ $barWidth }}%; --bar-color: {{ $color }};"></div>
                                </div>
                                <small data-lang-en="Sentiment breakdown" data-lang-id="Distribusi sentimen">Sentiment breakdown</small>
                            </div>
                        @empty
                            <div class="sales-empty-chart-state" data-lang-en="No CSAT sentiment data yet." data-lang-id="Belum ada data sentiment CSAT.">No CSAT sentiment data yet.</div>
                        @endforelse
                        <div class="sales-quotation-item">
                            <div class="sales-quotation-head">
                                <strong data-lang-en="Case Resolutions" data-lang-id="Penyelesaian Kasus">Case Resolutions</strong>
                                <span>{{ number_format($metrics['total_case_resolutions']) }}</span>
                            </div>
                            <div class="sales-quotation-track">
                                <div class="sales-quotation-bar" style="--bar-width: {{ min(100, max(10, $metrics['total_case_resolutions'] * 8)) }}%; --bar-color: #7367f0;"></div>
                            </div>
                            <small data-lang-en="Total case resolution records" data-lang-id="Total record penyelesaian kasus">Total case resolution records</small>
                        </div>
                    </div>
                </div>
            </article>
        </div>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Tickets" data-lang-id="Tiket Terbaru">Recent Tickets</h2>
                    <p data-lang-en="5 latest tickets." data-lang-id="5 tiket terbaru.">5 latest tickets.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th data-lang-en="Ticket" data-lang-id="Tiket">Ticket</th>
                        <th data-lang-en="Subject" data-lang-id="Subjek">Subject</th>
                        <th data-lang-en="Status" data-lang-id="Status">Status</th>
                        <th data-lang-en="Priority" data-lang-id="Prioritas">Priority</th>
                        <th data-lang-en="Channel" data-lang-id="Channel">Channel</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentTickets as $ticket)
                        <tr>
                            <td>{{ $ticket->ticket_number }}</td>
                            <td>{{ $ticket->subject }}</td>
                            @php($recentTicketStatus = $serviceLabel($ticket->status))
                            @php($recentTicketPriority = $serviceLabel($ticket->priority))
                            @php($recentTicketChannel = $serviceLabel($ticket->channel))
                            <td><span class="status-badge {{ $badgeClass($ticket->status) }}" data-lang-en="{{ $recentTicketStatus['en'] }}" data-lang-id="{{ $recentTicketStatus['id'] }}">{{ $recentTicketStatus['en'] }}</span></td>
                            <td><span class="status-badge {{ $badgeClass($ticket->priority) }}" data-lang-en="{{ $recentTicketPriority['en'] }}" data-lang-id="{{ $recentTicketPriority['id'] }}">{{ $recentTicketPriority['en'] }}</span></td>
                            <td data-lang-en="{{ $recentTicketChannel['en'] }}" data-lang-id="{{ $recentTicketChannel['id'] }}">{{ $recentTicketChannel['en'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty" data-lang-en="No tickets found." data-lang-id="Tidak ada tiket.">No tickets found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Omnichannel Messages" data-lang-id="Pesan Omnichannel Terbaru">Recent Omnichannel Messages</h2>
                    <p data-lang-en="5 latest omnichannel messages." data-lang-id="5 pesan omnichannel terbaru.">5 latest omnichannel messages.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th data-lang-en="Channel" data-lang-id="Channel">Channel</th>
                        <th data-lang-en="Sender" data-lang-id="Pengirim">Sender</th>
                        <th data-lang-en="Subject" data-lang-id="Subjek">Subject</th>
                        <th data-lang-en="Status" data-lang-id="Status">Status</th>
                        <th data-lang-en="Received" data-lang-id="Diterima">Received</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentOmnichannelMessages as $message)
                        <tr>
                            @php($messageChannel = $serviceLabel($message->channel))
                            @php($messageStatus = $serviceLabel($message->status))
                            <td data-lang-en="{{ $messageChannel['en'] }}" data-lang-id="{{ $messageChannel['id'] }}">{{ $messageChannel['en'] }}</td>
                            <td>{{ $message->sender_name ?: '-' }}</td>
                            <td>{{ $message->subject ?: '-' }}</td>
                            <td><span class="status-badge {{ $badgeClass($message->status) }}" data-lang-en="{{ $messageStatus['en'] }}" data-lang-id="{{ $messageStatus['id'] }}">{{ $messageStatus['en'] }}</span></td>
                            <td>{{ optional($message->received_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty" data-lang-en="No omnichannel messages found." data-lang-id="Tidak ada pesan omnichannel.">No omnichannel messages found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Customer Satisfaction Feedback" data-lang-id="Feedback Customer Satisfaction Terbaru">Recent Customer Satisfaction Feedback</h2>
                    <p data-lang-en="5 latest CSAT feedback entries." data-lang-id="5 feedback CSAT terbaru.">5 latest CSAT feedback entries.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th data-lang-en="Customer" data-lang-id="Customer">Customer</th>
                        <th data-lang-en="Ticket" data-lang-id="Tiket">Ticket</th>
                        <th data-lang-en="Rating" data-lang-id="Rating">Rating</th>
                        <th data-lang-en="Sentiment" data-lang-id="Sentimen">Sentiment</th>
                        <th data-lang-en="Submitted" data-lang-id="Dikirim">Submitted</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentCsatFeedback as $feedback)
                        <tr>
                            <td>{{ $feedback->customer?->name ?: '-' }}</td>
                            <td>{{ $feedback->ticket?->ticket_number ?: '-' }}</td>
                            <td>{{ number_format((float) $feedback->rating, 1, ',', '.') }}</td>
                            @php($feedbackSentiment = $serviceLabel($feedback->sentiment))
                            <td><span class="status-badge {{ $badgeClass($feedback->sentiment) }}" data-lang-en="{{ $feedbackSentiment['en'] }}" data-lang-id="{{ $feedbackSentiment['id'] }}">{{ $feedbackSentiment['en'] }}</span></td>
                            <td>{{ optional($feedback->submitted_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="customer-empty" data-lang-en="No customer satisfaction feedback found." data-lang-id="Tidak ada feedback customer satisfaction.">No customer satisfaction feedback found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2 data-lang-en="Recent Knowledge Articles" data-lang-id="Artikel Knowledge Terbaru">Recent Knowledge Articles</h2>
                    <p data-lang-en="5 latest knowledge base articles." data-lang-id="5 artikel knowledge base terbaru.">5 latest knowledge base articles.</p>
                </div>
            </div>
            <div class="customer-table-wrap">
                <table class="customer-table">
                    <thead>
                    <tr>
                        <th data-lang-en="Title" data-lang-id="Judul">Title</th>
                        <th data-lang-en="Category" data-lang-id="Kategori">Category</th>
                        <th data-lang-en="Status" data-lang-id="Status">Status</th>
                        <th data-lang-en="Published At" data-lang-id="Dipublikasikan Pada">Published At</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($recentKnowledgeArticles as $article)
                        <tr>
                            <td>{{ $article->title }}</td>
                            <td>{{ $article->category ?: '-' }}</td>
                            <td>
                                <span class="status-badge {{ $article->is_published ? 'status-won' : 'status-pending' }}" data-lang-en="{{ $article->is_published ? 'Published' : 'Draft' }}" data-lang-id="{{ $article->is_published ? 'Dipublikasikan' : 'Draft' }}">
                                    {{ $article->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td>{{ optional($article->published_at)->format('d M Y H:i') ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="customer-empty" data-lang-en="No knowledge articles found." data-lang-id="Tidak ada artikel knowledge.">No knowledge articles found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
