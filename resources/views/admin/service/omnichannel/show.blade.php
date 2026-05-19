@extends('admin.layouts.app')

@section('title', 'Omnichannel Message - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'inbox'])
            </div>
            <div>
                <h1>Omnichannel Inbox</h1>
                <p>Centralized inbox untuk Email, WhatsApp, Chat, Social, Phone, dan Web.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $message->subject ?: ($message->sender_name ?: 'Omnichannel Message') }}</h2>
                    <p>{{ $message->sender_contact ?: 'No sender contact' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge status-{{ $message->status }}">{{ ucfirst(str_replace('_', ' ', $message->status)) }}</span>
                    <a href="{{ route('admin.service.omnichannel.edit', $message) }}" class="btn btn-primary">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Channel</span>
                    <strong>{{ ucfirst($message->channel) }}</strong>
                </div>
                <div>
                    <span>Direction</span>
                    <strong>{{ ucfirst($message->direction) }}</strong>
                </div>
                <div>
                    <span>Status</span>
                    <strong>{{ ucfirst(str_replace('_', ' ', $message->status)) }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Channel</strong><span><span class="status-badge channel-{{ $message->channel }}">{{ ucfirst($message->channel) }}</span></span></div>
                <div><strong>Direction</strong><span><span class="status-badge direction-{{ $message->direction }}">{{ ucfirst($message->direction) }}</span></span></div>
                <div><strong>Sender Name</strong><span>{{ $message->sender_name ?: '-' }}</span></div>
                <div><strong>Sender Contact</strong><span>{{ $message->sender_contact ?: '-' }}</span></div>
                <div><strong>Subject</strong><span>{{ $message->subject ?: '-' }}</span></div>
                <div><strong>Status</strong><span><span class="status-badge status-{{ $message->status }}">{{ ucfirst(str_replace('_', ' ', $message->status)) }}</span></span></div>
                <div><strong>Assigned To</strong><span>{{ $message->assigned_to ?: '-' }}</span></div>
                <div><strong>Received At</strong><span>{{ $message->received_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Resolved At</strong><span>{{ $message->resolved_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div>
                    <strong>Related Customer</strong>
                    <span>
                        @if ($message->customer)
                            <a href="{{ route('admin.customers.show', $message->customer) }}" class="sales-title-link">{{ $message->customer->name }}</a>
                        @else
                            -
                        @endif
                    </span>
                </div>
            </div>

            <div class="customer-notes">
                <h3>Message Content</h3>
                <div>{!! nl2br(e($message->message)) !!}</div>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.service.omnichannel.index') }}" class="btn btn-muted">Back</a>
                <form method="POST" action="{{ route('admin.service.omnichannel.destroy', $message) }}" onsubmit="return confirm('Delete message ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
