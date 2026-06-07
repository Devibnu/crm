@extends('admin.layouts.app')

@section('title', $broadcast->name.' - WhatsApp Broadcast - Krakatau CRM')

@section('content')
    @php($asRate = fn ($numerator, $denominator) => $denominator > 0 ? number_format(($numerator / $denominator) * 100, 2) . '%' : '0.00%')
    @php($queuedCount = $queuedCount ?? 0)
    @php($sentCount = $broadcast->total_sent ?? $broadcast->sent_count)
    @php($failedCount = $broadcast->total_failed ?? $broadcast->failed_count)
    @php($deliveredCount = $broadcast->delivered_count)
    @php($readCount = $broadcast->read_count)
    @php($repliedCount = $broadcast->replied_count)
    @php($progressTotal = max(1, $broadcast->total_recipients))
    @php($progressWidth = fn ($value) => min(100, max(0, ($value / $progressTotal) * 100)))

    <section class="service-page customer-list-page sales-workspace">
        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card wa-broadcast-hero-card">
            <div class="wa-broadcast-hero">
                <div class="wa-broadcast-title-block">
                    <div class="service-card-icon">
                        @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
                    </div>
                    <div>
                        <p class="wa-broadcast-eyebrow">WhatsApp Broadcast Detail</p>
                        <h1>{{ $broadcast->name }}</h1>
                        <p>{{ $broadcast->marketingCampaign?->name ?: 'Without campaign' }}</p>
                        <div class="wa-broadcast-badges">
                            <span class="status-badge type-{{ $broadcast->target_type }}">{{ ucfirst($broadcast->target_type) }}</span>
                            <span class="status-badge status-{{ $broadcast->status }}">{{ ucfirst($broadcast->status) }}</span>
                        </div>
                    </div>
                </div>
                <div class="table-actions wa-broadcast-actions">
                    @if (in_array($broadcast->status, ['draft', 'scheduled', 'failed', 'cancelled'], true))
                        <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.start', $broadcast) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Start Broadcast</button>
                        </form>
                    @endif
                    @if ($broadcast->status === 'sending')
                        <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.pause', $broadcast) }}">
                            @csrf
                            <button type="submit" class="btn btn-muted">Pause Broadcast</button>
                        </form>
                    @endif
                    @if ($broadcast->status === 'paused')
                        <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.resume', $broadcast) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">Resume Broadcast</button>
                        </form>
                    @endif
                    @if ($queuedCount > 0)
                        <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.retry-queue', $broadcast) }}">
                            @csrf
                            <button type="submit" class="btn btn-muted">Retry Queue</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.edit', $broadcast) }}" class="btn btn-primary">Edit</a>
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.index') }}" class="btn btn-muted">Back</a>
                    <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.destroy', $broadcast) }}" onsubmit="return confirm('Delete broadcast ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </article>

        <article class="card customer-show-card wa-broadcast-progress-card">
            <div class="sales-section-head">
                <div>
                    <h2>Progress Overview</h2>
                    <p>Ringkasan status queue dan performa pengiriman broadcast.</p>
                </div>
            </div>

            <div class="wa-progress-grid">
                <div class="wa-progress-stat"><span>Total Recipients</span><strong>{{ number_format($broadcast->total_recipients) }}</strong></div>
                <div class="wa-progress-stat"><span>Queued</span><strong>{{ number_format($queuedCount) }}</strong></div>
                <div class="wa-progress-stat"><span>Sent</span><strong>{{ number_format($sentCount) }}</strong></div>
                <div class="wa-progress-stat"><span>Failed</span><strong>{{ number_format($failedCount) }}</strong></div>
                <div class="wa-progress-stat"><span>Delivered</span><strong>{{ number_format($deliveredCount) }}</strong></div>
                <div class="wa-progress-stat"><span>Read</span><strong>{{ number_format($readCount) }}</strong></div>
                <div class="wa-progress-stat"><span>Replied</span><strong>{{ number_format($repliedCount) }}</strong></div>
            </div>

            <div class="wa-progress-bars">
                <div class="wa-rate-strip">
                    <span>Delivery Rate <strong>{{ $asRate($deliveredCount, $sentCount) }}</strong></span>
                    <span>Reply Rate <strong>{{ $asRate($repliedCount, $broadcast->total_recipients) }}</strong></span>
                </div>
                <div class="wa-progress-row">
                    <div><strong>Sent progress</strong><span>{{ number_format($sentCount) }} of {{ number_format($broadcast->total_recipients) }}</span></div>
                    <div class="wa-progress-track"><span class="wa-progress-fill sent" style="width: {{ $progressWidth($sentCount) }}%;"></span></div>
                    <b>{{ $asRate($sentCount, $broadcast->total_recipients) }}</b>
                </div>
                <div class="wa-progress-row">
                    <div><strong>Failed progress</strong><span>{{ number_format($failedCount) }} of {{ number_format($broadcast->total_recipients) }}</span></div>
                    <div class="wa-progress-track"><span class="wa-progress-fill failed" style="width: {{ $progressWidth($failedCount) }}%;"></span></div>
                    <b>{{ $asRate($failedCount, $broadcast->total_recipients) }}</b>
                </div>
                <div class="wa-progress-row">
                    <div><strong>Reply progress</strong><span>{{ number_format($repliedCount) }} of {{ number_format($broadcast->total_recipients) }}</span></div>
                    <div class="wa-progress-track"><span class="wa-progress-fill replied" style="width: {{ $progressWidth($repliedCount) }}%;"></span></div>
                    <b>{{ $asRate($repliedCount, $broadcast->total_recipients) }}</b>
                </div>
            </div>
        </article>

        <article class="card customer-show-card wa-message-preview-card">
            <div class="sales-section-head">
                <div>
                    <h2>Message Preview</h2>
                    <p>Template dan metadata pengiriman broadcast.</p>
                </div>
            </div>
            <div class="wa-message-preview-grid">
                <div class="wa-message-template">
                    <span>Message Template</span>
                    <p>{{ $broadcast->message_template }}</p>
                </div>
                <div class="wa-message-meta">
                    <div><strong>Notes</strong><span>{{ $broadcast->notes ?: 'No notes available' }}</span></div>
                    <div><strong>Created By</strong><span>{{ $broadcast->created_by ?: '-' }}</span></div>
                    <div><strong>Scheduled At</strong><span>{{ $broadcast->scheduled_at?->format('d M Y H:i') ?: '-' }}</span></div>
                    <div><strong>Sent At</strong><span>{{ $broadcast->sent_at?->format('d M Y H:i') ?: '-' }}</span></div>
                </div>
            </div>
        </article>

        <article class="card customer-table-card wa-recipients-card">
            <div class="sales-section-head">
                <div>
                    <h2>Recipients Tracking</h2>
                    <p><span class="wa-section-kicker">Broadcast Recipients</span> Monitoring status pengiriman per penerima.</p>
                </div>
            </div>

            @if (($defaultWhatsAppProvider ?? null) === 'meta')
                <div class="customer-alert">
                    Meta menerima pesan bukan berarti pesan langsung delivered. Delivered/read dikirim melalui webhook.
                </div>
            @endif

            <div class="customer-table-wrap wa-recipient-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Provider Message ID</th>
                            <th>Sent At</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recipientRows as $recipient)
                            @php($recipientName = trim($recipient->recipient_name ?: 'Recipient'))
                            @php($initials = collect(explode(' ', $recipientName))->filter()->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('') ?: 'R')
                            <tr>
                                <td>
                                    <div class="wa-recipient-identity">
                                        <span class="wa-recipient-avatar">{{ strtoupper($initials) }}</span>
                                        <div>
                                            <strong>{{ $recipientName }}</strong>
                                            <small>{{ ucfirst($recipient->recipient_type) }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $recipient->phone_number }}</td>
                                <td><span class="status-badge type-{{ $recipient->recipient_type }}">{{ ucfirst($recipient->recipient_type) }}</span></td>
                                <td>
                                    <span class="status-badge wa-status-{{ $recipient->status }}">
                                        {{ ($defaultWhatsAppProvider ?? null) === 'meta' && $recipient->status === 'sent' && $recipient->provider_message_id ? 'Accepted by Meta' : ucfirst($recipient->status) }}
                                    </span>
                                </td>
                                <td>{{ $recipient->provider_message_id ?: '-' }}</td>
                                <td>{{ $recipient->sent_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>
                                    @if ($recipient->error_message)
                                        <span class="wa-error-text">{{ str($recipient->error_message)->limit(48) }}</span>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="customer-empty">
                                    <div class="wa-empty-state">
                                        <strong>Belum ada recipient untuk broadcast ini.</strong>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($recipientRows->hasPages())
                <div class="customer-pagination wa-pagination">
                    <div class="pagination-info">
                        Menampilkan {{ $recipientRows->firstItem() }}-{{ $recipientRows->lastItem() }} dari {{ $recipientRows->total() }} recipients
                    </div>
                    <div class="pagination-links wa-pagination-links">
                        @if ($recipientRows->onFirstPage())
                            <span class="btn btn-sm btn-disabled">Prev</span>
                        @else
                            <a href="{{ $recipientRows->previousPageUrl() }}" class="btn btn-sm btn-muted">Prev</a>
                        @endif

                        @foreach ($recipientRows->getUrlRange(max(1, $recipientRows->currentPage() - 1), min($recipientRows->lastPage(), $recipientRows->currentPage() + 1)) as $page => $url)
                            @if ($page === $recipientRows->currentPage())
                                <span class="btn btn-sm btn-primary">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="btn btn-sm btn-muted">{{ $page }}</a>
                            @endif
                        @endforeach

                        @if ($recipientRows->hasMorePages())
                            <a href="{{ $recipientRows->nextPageUrl() }}" class="btn btn-sm btn-muted">Next</a>
                        @else
                            <span class="btn btn-sm btn-disabled">Next</span>
                        @endif
                    </div>
                </div>
            @endif
        </article>
    </section>
@endsection
