@extends('admin.layouts.app')

@section('title', $broadcast->name.' - WhatsApp Broadcast - Krakatau CRM')

@section('content')
    @php($asRate = fn ($numerator, $denominator) => $denominator > 0 ? number_format(($numerator / $denominator) * 100, 2) . '%' : '0.00%')

    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'chat'])
            </div>
            <div>
                <h1>WhatsApp Broadcast Detail</h1>
                <p>Lihat recipients, status tracking, dan performa reply campaign WhatsApp.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $broadcast->name }}</h2>
                    <p>{{ $broadcast->marketingCampaign?->name ?: 'Without campaign' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge type-{{ $broadcast->target_type }}">{{ ucfirst($broadcast->target_type) }}</span>
                    <span class="status-badge status-{{ $broadcast->status }}">{{ ucfirst($broadcast->status) }}</span>
                    <a href="{{ route('admin.marketing.whatsapp-broadcasts.edit', $broadcast) }}" class="btn btn-primary">Edit</a>
                    <form method="POST" action="{{ route('admin.marketing.whatsapp-broadcasts.destroy', $broadcast) }}" onsubmit="return confirm('Delete broadcast ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Total Recipients</span>
                    <strong>{{ number_format($broadcast->total_recipients) }}</strong>
                </div>
                <div>
                    <span>Delivery Rate</span>
                    <strong>{{ $asRate($broadcast->delivered_count, $broadcast->sent_count) }}</strong>
                </div>
                <div>
                    <span>Read Rate</span>
                    <strong>{{ $asRate($broadcast->read_count, $broadcast->delivered_count) }}</strong>
                </div>
                <div>
                    <span>Reply Rate</span>
                    <strong>{{ $asRate($broadcast->replied_count, $broadcast->total_recipients) }}</strong>
                </div>
            </div>

            <div class="dashboard-status-list" style="margin-bottom: 20px;">
                @foreach ($statusTracking as $row)
                    <div>
                        <span>{{ $row['label'] }}</span>
                        <strong>{{ number_format($row['value']) }}</strong>
                    </div>
                @endforeach
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Broadcast Name</strong><span>{{ $broadcast->name }}</span></div>
                <div><strong>Related Campaign</strong><span>{{ $broadcast->marketingCampaign?->name ?: '-' }}</span></div>
                <div><strong>Target Type</strong><span>{{ ucfirst($broadcast->target_type) }}</span></div>
                <div><strong>Status</strong><span>{{ ucfirst($broadcast->status) }}</span></div>
                <div><strong>Scheduled At</strong><span>{{ $broadcast->scheduled_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Sent At</strong><span>{{ $broadcast->sent_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Created By</strong><span>{{ $broadcast->created_by ?: '-' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Message Template</h3>
                <p>{{ $broadcast->message_template }}</p>
            </div>

            <div class="customer-notes">
                <h3>Notes</h3>
                <p>{{ $broadcast->notes ?: 'No notes available' }}</p>
            </div>
        </article>

        <article class="card customer-table-card">
            <div class="sales-section-head">
                <div>
                    <h2>Broadcast Recipients</h2>
                    <p>Daftar recipient real yang diambil dari customer atau lead sesuai source.</p>
                </div>
            </div>

            <div class="customer-table-wrap">
                <table class="customer-table sales-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th>Sent At</th>
                            <th>Delivered At</th>
                            <th>Read At</th>
                            <th>Replied At</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recipientRows as $recipient)
                            <tr>
                                <td>{{ $recipient->recipient_name }}</td>
                                <td>{{ $recipient->phone_number }}</td>
                                <td><span class="status-badge type-{{ $recipient->recipient_type }}">{{ ucfirst($recipient->recipient_type) }}</span></td>
                                <td><span class="status-badge status-{{ $recipient->status }}">{{ ucfirst($recipient->status) }}</span></td>
                                <td>{{ $recipient->sent_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $recipient->delivered_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $recipient->read_at?->format('d M Y H:i') ?: '-' }}</td>
                                <td>{{ $recipient->replied_at?->format('d M Y H:i') ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="customer-empty">Belum ada recipients untuk broadcast ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </article>
    </section>
@endsection
