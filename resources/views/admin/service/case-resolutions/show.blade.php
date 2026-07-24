@extends('admin.layouts.app')

@section('title', $resolution->resolution_summary.' - Case Resolution - Krakatau CRM')

@section('content')
    @php
        $ticket = $resolution->ticket;
        $ticketStatusBadge = $ticket?->status === 'reopened' ? 'status-pending' : 'status-'.($ticket?->status ?: 'inactive');
        $escalationTypes = $ticket?->slaEscalations?->pluck('type') ?? collect();
        $hasSlaBreach = $escalationTypes->contains(fn ($type) => str_contains($type, 'breach'));
        $hasSlaWarning = $escalationTypes->contains(fn ($type) => str_contains($type, 'warning'));
        $slaLabel = $ticket ? ($hasSlaBreach ? 'Breached' : ($hasSlaWarning ? 'Warning' : ucfirst(str_replace('_', ' ', $ticket->overallSlaStatus())))) : '-';
    @endphp

    <section class="lead-list-page customer-profile-page customer-360-dashboard sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">
                    @include('admin.partials.sidebar-icon', ['icon' => 'case'])
                </div>
                <div>
                    <span class="crm-record-kicker">CASE RESOLUTION</span>
                    <h1>{{ $resolution->resolution_summary }}</h1>
                    <div class="customer-profile-hero-meta" aria-label="Resolution summary">
                        <span>{{ $ticket?->ticket_number ?: 'Ticket #'.$resolution->ticket_id }}</span>
                        <span>{{ $ticket?->customer?->name ?: 'No customer linked' }}</span>
                        <span>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_outcome ?: 'resolved')) }}</span>
                        <span>{{ $resolution->resolved_by ?: 'No resolver' }}</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                <span class="status-badge resolution-{{ $resolution->resolution_type }}">{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</span>
                @can('cases.update')
                    <a href="{{ route('admin.service.case-resolutions.edit', $resolution) }}" class="btn lead-banner-cta">Edit</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <section class="customer-360-dashboard-grid" aria-label="Resolution overview">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Ticket Information</span>
                        <h2>{{ $ticket?->subject ?: 'No ticket subject' }}</h2>
                    </div>
                    @if ($ticket)
                        <span class="status-badge {{ $ticketStatusBadge }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                    @endif
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Ticket Number</span>
                        <strong>{{ $ticket?->ticket_number ?: '-' }}</strong>
                        <small>{{ $ticket?->priority ? ucfirst($ticket->priority).' priority' : 'No priority' }}</small>
                    </div>
                    <div>
                        <span>Customer</span>
                        <strong>{{ $ticket?->customer?->name ?: '-' }}</strong>
                        <small>{{ $ticket?->customer?->company_name ?: 'No company' }}</small>
                    </div>
                    <div>
                        <span>Assigned Agent</span>
                        <strong>{{ $ticket?->assigned_to ?: '-' }}</strong>
                        <small>{{ $ticket?->channel ? ucfirst(str_replace('_', ' ', $ticket->channel)) : 'No channel' }}</small>
                    </div>
                    <div>
                        <span>SLA Status</span>
                        <strong>{{ $slaLabel }}</strong>
                        <small>{{ $ticket?->slaBusinessCalendar?->name ?: 'No business calendar' }}</small>
                    </div>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Resolution Outcome</span>
                        <h2>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_outcome ?: 'resolved')) }}</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Root Cause</span>
                        <strong>{{ ucfirst(str_replace('_', ' ', $resolution->root_cause ?: 'unknown')) }}</strong>
                        <small>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_type)) }}</small>
                    </div>
                    <div>
                        <span>Reopened Count</span>
                        <strong>{{ number_format($resolution->reopened_count) }}</strong>
                        <small>After this resolution</small>
                    </div>
                    <div>
                        <span>Knowledge Candidate</span>
                        <strong>{{ $resolution->knowledge_candidate ? 'Yes' : 'No' }}</strong>
                        <small>{{ $resolution->knowledgeArticle?->title ?: 'No article linked' }}</small>
                    </div>
                </div>
            </article>
        </section>

        <section class="customer-profile-workspace customer-360-section" aria-label="Resolution timeline">
            <div class="customer-profile-section-head">
                <div>
                    <span>Resolution Timeline</span>
                    <h2>Resolution milestones</h2>
                </div>
            </div>
            <div class="customer-360-timeline">
                @if ($ticket?->created_at)
                    <article class="customer-360-timeline-item">
                        <span aria-hidden="true"></span>
                        <div>
                            <small>{{ $ticket->created_at->format('d M Y H:i') }}</small>
                            <strong>Ticket Created</strong>
                            <p>{{ $ticket->ticket_number }} opened for {{ $ticket->customer?->name ?: 'customer' }}.</p>
                        </div>
                    </article>
                @endif
                @if ($resolution->resolved_at)
                    <article class="customer-360-timeline-item">
                        <span aria-hidden="true"></span>
                        <div>
                            <small>{{ $resolution->resolved_at->format('d M Y H:i') }}</small>
                            <strong>Latest Resolution</strong>
                            <p>{{ $resolution->resolution_summary }}</p>
                        </div>
                    </article>
                @endif
                @if ($resolution->customer_notified_at)
                    <article class="customer-360-timeline-item">
                        <span aria-hidden="true"></span>
                        <div>
                            <small>{{ $resolution->customer_notified_at->format('d M Y H:i') }}</small>
                            <strong>Customer Notified</strong>
                            <p>Customer notification has been recorded.</p>
                        </div>
                    </article>
                @endif
                @if ($resolution->customer_confirmation_at)
                    <article class="customer-360-timeline-item">
                        <span aria-hidden="true"></span>
                        <div>
                            <small>{{ $resolution->customer_confirmation_at->format('d M Y H:i') }}</small>
                            <strong>Customer Confirmed</strong>
                            <p>Customer confirmation timestamp is available.</p>
                        </div>
                    </article>
                @endif
            </div>
        </section>

        <section class="customer-360-dashboard-grid" aria-label="Resolution details">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Resolution Detail</span>
                        <h2>How it was solved</h2>
                    </div>
                </div>
                <div class="customer-notes">
                    <p>{{ $resolution->resolution_notes ?: 'No resolution detail available' }}</p>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Workaround</span>
                        <h2>{{ $resolution->workaround ? 'Temporary path' : 'No workaround' }}</h2>
                    </div>
                </div>
                <div class="customer-notes">
                    <p>{{ $resolution->workaround ?: 'No workaround recorded' }}</p>
                </div>
            </article>
        </section>

        <section class="customer-360-dashboard-grid" aria-label="Resolution completion">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Permanent Fix</span>
                        <h2>{{ $resolution->permanent_fix ? 'Permanent fix recorded' : 'No permanent fix' }}</h2>
                    </div>
                </div>
                <div class="customer-notes">
                    <p>{{ $resolution->permanent_fix ?: 'No permanent fix recorded' }}</p>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Internal Notes</span>
                        <h2>Agent context</h2>
                    </div>
                </div>
                <div class="customer-notes">
                    <p>{{ $resolution->internal_notes ?: 'No internal notes available' }}</p>
                </div>
            </article>
        </section>

        <section class="customer-360-action-toolbar" aria-label="Resolution actions">
            <span>Quick Actions</span>
            <div>
                <a href="{{ route('admin.service.case-resolutions.index') }}" class="customer-360-action-pill">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'case'])</span>
                    <strong>Back</strong>
                </a>
                @if ($ticket)
                    <a href="{{ route('admin.service.tickets.show', $ticket) }}" class="customer-360-action-pill">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'ticket'])</span>
                        <strong>Ticket 360</strong>
                    </a>
                @endif
                @if ($resolution->knowledgeArticle)
                    <a href="{{ route('admin.service.knowledge-base.show', $resolution->knowledgeArticle) }}" class="customer-360-action-pill">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'book'])</span>
                        <strong>Knowledge</strong>
                    </a>
                @endif
                @can('cases.delete')
                    <form method="POST" action="{{ route('admin.service.case-resolutions.destroy', $resolution) }}" onsubmit="return confirm('Delete case resolution ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                @endcan
            </div>
        </section>
    </section>
@endsection
