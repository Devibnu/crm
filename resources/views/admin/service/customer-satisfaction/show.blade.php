@extends('admin.layouts.app')

@section('title', 'Customer Satisfaction Detail - Krakatau CRM')

@section('content')
    <section class="lead-list-page customer-profile-page customer-360-dashboard sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">
                    @include('admin.partials.sidebar-icon', ['icon' => 'star'])
                </div>
                <div>
                    <span class="crm-record-kicker">CUSTOMER SATISFACTION</span>
                    <h1>{{ $satisfaction->customer?->name ?: 'Customer Feedback' }}</h1>
                    <div class="customer-profile-hero-meta" aria-label="Customer satisfaction summary">
                        <span>Customer Satisfaction Detail</span>
                        <span>{{ $satisfaction->ticket?->ticket_number ?: 'No ticket linked' }}</span>
                        <span>{{ $satisfaction->rating }}/5 rating</span>
                        <span>{{ ucfirst($satisfaction->survey_channel) }} channel</span>
                        <span>{{ $satisfaction->submitted_at?->format('d M Y H:i') ?: 'No submission date' }}</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                <span class="status-badge sentiment-{{ $satisfaction->sentiment }}">{{ ucfirst($satisfaction->sentiment) }}</span>
                @can('csat.update')
                    <a href="{{ route('admin.service.customer-satisfaction.edit', $satisfaction) }}" class="btn lead-banner-cta">Edit</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <section class="customer-360-dashboard-grid" aria-label="Feedback overview">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Feedback Summary</span>
                        <h2>{{ $satisfaction->feedback ? 'Customer response recorded' : 'No written feedback' }}</h2>
                    </div>
                    <span class="status-badge rating-{{ $satisfaction->rating }}">{{ $satisfaction->rating }}/5</span>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Rating</span>
                        <strong>{{ $satisfaction->rating }}/5</strong>
                        <small>Customer satisfaction score</small>
                    </div>
                    <div>
                        <span>Sentiment</span>
                        <strong>{{ ucfirst($satisfaction->sentiment) }}</strong>
                        <small>Feedback tone</small>
                    </div>
                    <div>
                        <span>Follow Up</span>
                        <strong>{{ $satisfaction->follow_up_required ? 'Required' : 'No' }}</strong>
                        <small>{{ $satisfaction->follow_up_notes ? 'Notes available' : 'No follow-up notes' }}</small>
                    </div>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Related Context</span>
                        <h2>{{ $satisfaction->ticket?->ticket_number ?: 'No ticket linked' }}</h2>
                    </div>
                    <span class="status-badge channel-{{ $satisfaction->survey_channel }}">{{ ucfirst($satisfaction->survey_channel) }}</span>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Customer</span>
                        <strong>{{ $satisfaction->customer?->name ?: '-' }}</strong>
                        <small>Feedback owner</small>
                    </div>
                    <div>
                        <span>Ticket Subject</span>
                        <strong>{{ $satisfaction->ticket?->subject ?: '-' }}</strong>
                        <small>{{ $satisfaction->ticket?->ticket_number ?: 'No ticket number' }}</small>
                    </div>
                    <div>
                        <span>Submitted</span>
                        <strong>{{ $satisfaction->submitted_at?->format('d M Y') ?: '-' }}</strong>
                        <small>{{ $satisfaction->submitted_at?->format('H:i') ?: 'No time recorded' }}</small>
                    </div>
                </div>
            </article>
        </section>

        <section class="customer-360-dashboard-grid" aria-label="Feedback details">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Feedback</span>
                        <h2>Customer voice</h2>
                    </div>
                </div>
                <div class="customer-notes">
                    <p>{{ $satisfaction->feedback ?: 'No feedback available' }}</p>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Follow Up Notes</span>
                        <h2>{{ $satisfaction->follow_up_required ? 'Action required' : 'No action required' }}</h2>
                    </div>
                    <span class="status-badge status-{{ $satisfaction->follow_up_required ? 'pending' : 'active' }}">{{ $satisfaction->follow_up_required ? 'Required' : 'Clear' }}</span>
                </div>
                <div class="customer-notes">
                    <p>{{ $satisfaction->follow_up_notes ?: 'No follow up notes available' }}</p>
                </div>
            </article>
        </section>

        <section class="customer-360-action-toolbar" aria-label="Feedback actions">
            <span>Quick Actions</span>
            <div>
                <a href="{{ route('admin.service.customer-satisfaction.index') }}" class="customer-360-action-pill">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'star'])</span>
                    <strong>Back</strong>
                </a>
                @if ($satisfaction->ticket)
                    @can('tickets.view')
                    <a href="{{ route('admin.service.tickets.show', $satisfaction->ticket) }}" class="customer-360-action-pill">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'ticket'])</span>
                        <strong>Ticket 360</strong>
                    </a>
                    @endcan
                @endif
                @can('csat.update')
                    <a href="{{ route('admin.service.customer-satisfaction.edit', $satisfaction) }}" class="customer-360-action-pill">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'star'])</span>
                        <strong>Edit</strong>
                    </a>
                @endcan
                @can('csat.delete')
                    <form method="POST" action="{{ route('admin.service.customer-satisfaction.destroy', $satisfaction) }}" onsubmit="return confirm('Delete feedback ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                @endcan
            </div>
        </section>
    </section>
@endsection
