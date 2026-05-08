@extends('admin.layouts.app')

@section('title', 'Customer Satisfaction Detail - Krakatau CRM')

@section('content')
    <section class="service-page customer-list-page sales-workspace">
        <article class="card service-card customer-list-card">
            <div class="service-card-icon">
                @include('admin.partials.sidebar-icon', ['icon' => 'star'])
            </div>
            <div>
                <h1>Customer Satisfaction Detail</h1>
                <p>Detail survei kepuasan pelanggan, sentiment, channel, dan tindak lanjut feedback.</p>
            </div>
        </article>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <article class="card customer-show-card">
            <div class="customer-show-head">
                <div>
                    <h2>{{ $satisfaction->customer?->name ?: 'Customer Feedback' }}</h2>
                    <p>{{ $satisfaction->ticket?->ticket_number ?: 'No ticket linked' }}</p>
                </div>
                <div class="table-actions">
                    <span class="status-badge sentiment-{{ $satisfaction->sentiment }}">{{ ucfirst($satisfaction->sentiment) }}</span>
                    <a href="{{ route('admin.service.customer-satisfaction.edit', $satisfaction) }}" class="btn btn-primary">Edit</a>
                </div>
            </div>

            <div class="sales-detail-hero">
                <div>
                    <span>Rating</span>
                    <strong>{{ $satisfaction->rating }}/5</strong>
                </div>
                <div>
                    <span>Sentiment</span>
                    <strong>{{ ucfirst($satisfaction->sentiment) }}</strong>
                </div>
                <div>
                    <span>Channel</span>
                    <strong>{{ ucfirst($satisfaction->survey_channel) }}</strong>
                </div>
            </div>

            <div class="customer-show-grid sales-detail-grid">
                <div><strong>Customer</strong><span>{{ $satisfaction->customer?->name ?: '-' }}</span></div>
                <div><strong>Ticket</strong><span>{{ $satisfaction->ticket?->ticket_number ?: '-' }}</span></div>
                <div><strong>Ticket Subject</strong><span>{{ $satisfaction->ticket?->subject ?: '-' }}</span></div>
                <div><strong>Rating</strong><span>{{ $satisfaction->rating }}/5</span></div>
                <div><strong>Survey Channel</strong><span>{{ ucfirst($satisfaction->survey_channel) }}</span></div>
                <div><strong>Sentiment</strong><span>{{ ucfirst($satisfaction->sentiment) }}</span></div>
                <div><strong>Submitted At</strong><span>{{ $satisfaction->submitted_at?->format('d M Y H:i') ?: '-' }}</span></div>
                <div><strong>Follow Up Required</strong><span>{{ $satisfaction->follow_up_required ? 'Yes' : 'No' }}</span></div>
            </div>

            <div class="customer-notes">
                <h3>Feedback</h3>
                <p>{{ $satisfaction->feedback ?: 'No feedback available' }}</p>
            </div>

            <div class="customer-notes">
                <h3>Follow Up Notes</h3>
                <p>{{ $satisfaction->follow_up_notes ?: 'No follow up notes available' }}</p>
            </div>

            <div class="form-actions">
                <a href="{{ route('admin.service.customer-satisfaction.index') }}" class="btn btn-muted">Back</a>
                <form method="POST" action="{{ route('admin.service.customer-satisfaction.destroy', $satisfaction) }}" onsubmit="return confirm('Delete feedback ini?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </article>
    </section>
@endsection
