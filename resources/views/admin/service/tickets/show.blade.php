@extends('admin.layouts.app')

@section('title', $ticket->ticket_number.' - Ticket - Krakatau CRM')

@section('content')
    @php
        $ticketStatusBadge = $ticket->status === 'reopened' ? 'status-pending' : 'status-'.$ticket->status;
        $escalationTypes = $ticket->slaEscalations->pluck('type');
        $hasSlaBreach = $escalationTypes->contains(fn ($type) => str_contains($type, 'breach'));
        $hasSlaWarning = $escalationTypes->contains(fn ($type) => str_contains($type, 'warning'));
        $slaEscalationLabel = $hasSlaBreach ? 'Breached' : ($hasSlaWarning ? 'Warning' : 'No escalation');
        $slaEscalationBadge = $hasSlaBreach ? 'status-inactive' : ($hasSlaWarning ? 'status-pending' : 'status-active');
        $latestResolution = $ticket->caseResolutions->first();
    @endphp

    <section class="lead-list-page customer-profile-page customer-360-dashboard sales-workspace">
        <header class="lead-list-header customer-profile-lead-hero customer-360-hero">
            <div class="customer-profile-hero-main">
                <div class="customer-profile-avatar customer-profile-avatar-lg">{{ strtoupper(substr($ticket->ticket_number, 0, 1)) }}</div>
                <div>
                    <span class="crm-record-kicker">SERVICE MANAGEMENT</span>
                    <h1>Ticket Detail</h1>
                    <div class="customer-profile-hero-meta" aria-label="Ticket summary">
                        <span>{{ $ticket->ticket_number }}</span>
                        <span>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                        <span>{{ ucfirst($ticket->priority) }}</span>
                        <span>{{ ucfirst(str_replace('_', ' ', $ticket->channel)) }}</span>
                        <span>{{ $ticket->assigned_to ?: 'Unassigned' }}</span>
                    </div>
                    <div class="customer-360-hero-meta-line">
                        <span>Created: {{ $ticket->created_at?->format('d M Y H:i') ?: '-' }}</span>
                        <span>Due: {{ $ticket->due_at?->format('d M Y H:i') ?: '-' }}</span>
                    </div>
                </div>
            </div>
            <div class="customer-profile-actions customer-360-hero-actions">
                <span class="status-badge {{ $ticketStatusBadge }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span>
                @can('tickets.update')
                    <a href="{{ route('admin.service.tickets.edit', $ticket) }}" class="btn lead-banner-cta">Edit</a>
                @endcan
            </div>
        </header>

        @if (session('success'))
            <div class="card customer-alert success">{{ session('success') }}</div>
        @endif

        <section class="customer-360-dashboard-grid" aria-label="Ticket 360">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Customer Summary</span>
                        <h2>{{ $ticket->customer?->name ?: 'No customer linked' }}</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Customer</span>
                        <strong>{{ $ticket->customer?->name ?: '-' }}</strong>
                        <small>{{ $ticket->customer ? 'Linked customer record' : 'No customer linked' }}</small>
                    </div>
                    <div>
                        <span>Assignment</span>
                        <strong>{{ $ticket->assigned_to ?: '-' }}</strong>
                        <small>{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</small>
                    </div>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Ticket Information</span>
                        <h2>{{ $ticket->subject }}</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Status</span>
                        <strong><span class="status-badge {{ $ticketStatusBadge }}">{{ ucfirst(str_replace('_', ' ', $ticket->status)) }}</span></strong>
                        <small>{{ $ticket->ticket_number }}</small>
                    </div>
                    <div>
                        <span>Priority</span>
                        <strong><span class="status-badge priority-{{ $ticket->priority }}">{{ ucfirst($ticket->priority) }}</span></strong>
                        <small>{{ ucfirst(str_replace('_', ' ', $ticket->channel)) }}</small>
                    </div>
                </div>
            </article>
        </section>

        <section class="customer-profile-workspace customer-360-section" aria-label="Case resolution">
            <div class="customer-profile-section-head">
                <div>
                    <span>Case Resolution</span>
                    <h2>{{ $latestResolution?->resolution_summary ?: 'No resolution documented' }}</h2>
                </div>
                @if ($latestResolution)
                    <span class="status-badge resolution-{{ $latestResolution->resolution_type }}">{{ ucfirst(str_replace('_', ' ', $latestResolution->resolution_outcome ?: 'resolved')) }}</span>
                @endif
            </div>

            @if ($latestResolution)
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Latest Resolution</span>
                        <strong>{{ $latestResolution->resolved_at?->format('d M Y H:i') ?: '-' }}</strong>
                        <small>{{ $latestResolution->resolved_by ?: 'No resolver' }}</small>
                    </div>
                    <div>
                        <span>Root Cause</span>
                        <strong>{{ ucfirst(str_replace('_', ' ', $latestResolution->root_cause ?: 'unknown')) }}</strong>
                        <small>{{ ucfirst(str_replace('_', ' ', $latestResolution->resolution_type)) }}</small>
                    </div>
                    <div>
                        <span>Outcome</span>
                        <strong>{{ ucfirst(str_replace('_', ' ', $latestResolution->resolution_outcome ?: 'resolved')) }}</strong>
                        <small>{{ $latestResolution->knowledge_candidate ? 'Knowledge candidate' : 'No knowledge flag' }}</small>
                    </div>
                    <div>
                        <span>Reopened Count</span>
                        <strong>{{ number_format($latestResolution->reopened_count) }}</strong>
                        <small>Preserved after reopen</small>
                    </div>
                </div>

                <div class="customer-360-dashboard-grid" aria-label="Resolution fix details">
                    <article class="customer-profile-latest-card customer-360-section">
                        <div class="customer-profile-section-head">
                            <div>
                                <span>Workaround</span>
                                <h2>{{ $latestResolution->workaround ? 'Temporary solution' : 'No workaround' }}</h2>
                            </div>
                        </div>
                        <div class="customer-notes">
                            <p>{{ $latestResolution->workaround ?: 'No workaround recorded' }}</p>
                        </div>
                    </article>
                    <article class="customer-profile-latest-card customer-360-section">
                        <div class="customer-profile-section-head">
                            <div>
                                <span>Permanent Fix</span>
                                <h2>{{ $latestResolution->permanent_fix ? 'Permanent fix recorded' : 'No permanent fix' }}</h2>
                            </div>
                        </div>
                        <div class="customer-notes">
                            <p>{{ $latestResolution->permanent_fix ?: 'No permanent fix recorded' }}</p>
                        </div>
                    </article>
                </div>

                <div class="customer-360-timeline">
                    @foreach ($ticket->caseResolutions as $resolution)
                        <article class="customer-360-timeline-item">
                            <span aria-hidden="true"></span>
                            <div>
                                <small>{{ $resolution->resolved_at?->format('d M Y H:i') ?: '-' }}</small>
                                <strong>{{ $resolution->resolution_summary }}</strong>
                                <p>{{ ucfirst(str_replace('_', ' ', $resolution->resolution_outcome ?: 'resolved')) }} by {{ $resolution->resolved_by ?: 'agent' }}. Reopened {{ number_format($resolution->reopened_count) }} time(s).</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Status</span>
                        <strong>No resolution yet</strong>
                        <small>Case resolution records will appear here after agents document the solve path.</small>
                    </div>
                </div>
            @endif
        </section>

        <section class="customer-profile-workspace customer-360-section" aria-label="Ticket timeline">
            <div class="customer-profile-section-head">
                <div>
                    <span>Timeline</span>
                    <h2>Service milestones</h2>
                </div>
            </div>
            <div class="customer-360-timeline">
                <article class="customer-360-timeline-item">
                    <span aria-hidden="true"></span>
                    <div>
                        <small>{{ $ticket->created_at?->format('d M Y H:i') ?: '-' }}</small>
                        <strong>Ticket Created</strong>
                        <p>{{ $ticket->ticket_number }} opened from {{ ucfirst(str_replace('_', ' ', $ticket->channel)) }} channel.</p>
                    </div>
                </article>
                @if ($ticket->due_at)
                    <article class="customer-360-timeline-item">
                        <span aria-hidden="true"></span>
                        <div>
                            <small>{{ $ticket->due_at->format('d M Y H:i') }}</small>
                            <strong>Due Date</strong>
                            <p>Target handling date for this ticket.</p>
                        </div>
                    </article>
                @endif
                @if ($ticket->resolved_at)
                    <article class="customer-360-timeline-item">
                        <span aria-hidden="true"></span>
                        <div>
                            <small>{{ $ticket->resolved_at->format('d M Y H:i') }}</small>
                            <strong>Resolved</strong>
                            <p>Ticket reached resolved state.</p>
                        </div>
                    </article>
                @endif
                @if ($ticket->closed_at)
                    <article class="customer-360-timeline-item">
                        <span aria-hidden="true"></span>
                        <div>
                            <small>{{ $ticket->closed_at->format('d M Y H:i') }}</small>
                            <strong>Closed</strong>
                            <p>Ticket closure timestamp recorded.</p>
                        </div>
                    </article>
                @endif
            </div>
        </section>

        <section class="customer-profile-workspace customer-360-section" aria-label="SLA escalation">
            <div class="customer-profile-section-head">
                <div>
                    <span>SLA Escalation</span>
                    <h2>{{ $slaEscalationLabel }}</h2>
                </div>
                <span class="status-badge {{ $slaEscalationBadge }}">{{ $slaEscalationLabel }}</span>
            </div>

            @if ($ticket->slaEscalations->isEmpty())
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Status</span>
                        <strong>No escalation</strong>
                        <small>SLA warning and breach events will appear here.</small>
                    </div>
                </div>
            @else
                <div class="customer-360-timeline">
                    @foreach ($ticket->slaEscalations as $escalation)
                        <article class="customer-360-timeline-item">
                            <span aria-hidden="true"></span>
                            <div>
                                <small>{{ $escalation->triggered_at?->format('d M Y H:i') ?: '-' }}</small>
                                <strong>{{ ucwords(str_replace('_', ' ', $escalation->type)) }}</strong>
                                <p>{{ ucfirst($escalation->status) }} escalation for {{ $ticket->ticket_number }}.</p>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>

        <section class="customer-360-dashboard-grid" aria-label="Ticket details">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Description</span>
                        <h2>Customer request</h2>
                    </div>
                </div>
                <div class="customer-notes">
                    <p>{{ $ticket->description ?: 'No description available' }}</p>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Related Lead</span>
                        <h2>{{ $ticket->lead?->name ?: 'No lead linked' }}</h2>
                    </div>
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Lead</span>
                        <strong>{{ $ticket->lead?->name ?: '-' }}</strong>
                        <small>{{ $ticket->lead?->source ?: 'No source' }}</small>
                    </div>
                </div>
            </article>
        </section>

        <section class="customer-360-dashboard-grid" aria-label="Ticket connected records">
            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>WhatsApp Conversation</span>
                        <h2>{{ $ticket->sourceConversation?->contact_name ?: ($ticket->sourceConversation?->phone_number ?: 'No conversation linked') }}</h2>
                    </div>
                    @if ($ticket->sourceConversation)
                        <a href="{{ route('admin.service.omnichannel.index', ['conversation' => $ticket->sourceConversation->id]) }}#contact" class="btn btn-sm btn-muted">Open Conversation</a>
                    @endif
                </div>
                <div class="customer-profile-latest-list customer-360-sales-summary">
                    <div>
                        <span>Source Conversation</span>
                        <strong>{{ $ticket->sourceConversation?->contact_name ?: '-' }}</strong>
                        <small>{{ $ticket->sourceConversation?->phone_number ?: '-' }}</small>
                    </div>
                </div>
            </article>

            <article class="customer-profile-latest-card customer-360-section">
                <div class="customer-profile-section-head">
                    <div>
                        <span>Internal Notes</span>
                        <h2>Service context</h2>
                    </div>
                </div>
                <div class="customer-notes">
                    <p>{{ $ticket->closed_at ? 'Closed at '.$ticket->closed_at->format('d M Y H:i') : 'No internal notes available' }}</p>
                </div>
            </article>
        </section>

        <section class="customer-360-action-toolbar" aria-label="Quick actions">
            <span>Quick Actions</span>
            <div>
                <a href="{{ route('admin.service.tickets.index') }}" class="customer-360-action-pill">
                    <span>@include('admin.partials.sidebar-icon', ['icon' => 'ticket'])</span>
                    <strong>Back</strong>
                </a>
                @can('tickets.update')
                    <a href="{{ route('admin.service.tickets.edit', $ticket) }}" class="customer-360-action-pill">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'ticket'])</span>
                        <strong>Edit</strong>
                    </a>
                @endcan
                @if ($ticket->sourceConversation)
                    <a href="{{ route('admin.service.omnichannel.index', ['conversation' => $ticket->sourceConversation->id]) }}#contact" class="customer-360-action-pill">
                        <span>@include('admin.partials.sidebar-icon', ['icon' => 'inbox'])</span>
                        <strong>Open Conversation</strong>
                    </a>
                @endif
                @can('tickets.delete')
                    <form method="POST" action="{{ route('admin.service.tickets.destroy', $ticket) }}" onsubmit="return confirm('Delete ticket ini?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                @endcan
            </div>
        </section>
    </section>
@endsection
