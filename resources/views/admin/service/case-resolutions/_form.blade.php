@php
    $resolution = $resolution ?? null;
    $selectedTicketId = old('ticket_id', $resolution->ticket_id ?? '');
    $selectedTicket = $selectedTicketId ? $tickets->firstWhere('id', (int) $selectedTicketId) : $resolution?->ticket;
    $selectedResolutionType = old('resolution_type', $resolution->resolution_type ?? 'fixed');
    $selectedResolutionOutcome = old('resolution_outcome', $resolution->resolution_outcome ?? 'resolved');
    $selectedRootCause = old('root_cause', $resolution->root_cause ?? 'unknown');
    $selectedCustomerNotified = old('customer_notified', isset($resolution) ? (int) $resolution->customer_notified : 0);
    $selectedKnowledgeCandidate = old('knowledge_candidate', isset($resolution) ? (int) $resolution->knowledge_candidate : 0);
    $selectedKnowledgeArticle = old('knowledge_article_id', $resolution->knowledge_article_id ?? '');
    $ticketEscalationTypes = $selectedTicket?->slaEscalations?->pluck('type') ?? collect();
    $hasSlaBreach = $ticketEscalationTypes->contains(fn ($type) => str_contains($type, 'breach'));
    $hasSlaWarning = $ticketEscalationTypes->contains(fn ($type) => str_contains($type, 'warning'));
    $slaLabel = $selectedTicket ? ($hasSlaBreach ? 'Breached' : ($hasSlaWarning ? 'Warning' : ucfirst(str_replace('_', ' ', $selectedTicket->overallSlaStatus())))) : '-';
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Ticket Information</h2>
        <p>Read-only service context for the ticket being resolved.</p>
        <div class="customer-form-grid">
            <label class="field field-full">
                <span>Ticket <strong>*</strong></span>
                <select name="ticket_id" required>
                    <option value="">Pilih ticket</option>
                    @foreach ($tickets as $ticket)
                        <option value="{{ $ticket->id }}" @selected((string) $selectedTicketId === (string) $ticket->id)>
                            {{ $ticket->ticket_number }} - {{ $ticket->subject }}
                        </option>
                    @endforeach
                </select>
                @error('ticket_id')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <div class="customer-profile-latest-list customer-360-sales-summary">
            <div>
                <span>Ticket Number</span>
                <strong>{{ $selectedTicket?->ticket_number ?: '-' }}</strong>
                <small>{{ $selectedTicket?->subject ?: 'Select a ticket to attach this resolution.' }}</small>
            </div>
            <div>
                <span>Customer</span>
                <strong>{{ $selectedTicket?->customer?->name ?: '-' }}</strong>
                <small>{{ $selectedTicket?->customer?->company_name ?: 'No company' }}</small>
            </div>
            <div>
                <span>Priority / Status</span>
                <strong>{{ $selectedTicket ? ucfirst($selectedTicket->priority).' / '.ucfirst(str_replace('_', ' ', $selectedTicket->status)) : '-' }}</strong>
                <small>{{ $selectedTicket?->channel ? ucfirst(str_replace('_', ' ', $selectedTicket->channel)) : 'No channel' }}</small>
            </div>
            <div>
                <span>Assigned Agent</span>
                <strong>{{ $selectedTicket?->assigned_to ?: '-' }}</strong>
                <small>{{ $selectedTicket ? 'Current owner' : 'No ticket selected' }}</small>
            </div>
            <div>
                <span>SLA Status</span>
                <strong>{{ $slaLabel }}</strong>
                <small>{{ $selectedTicket?->resolution_due_at?->format('d M Y H:i') ?: 'No SLA due date' }}</small>
            </div>
            <div>
                <span>Business Calendar</span>
                <strong>{{ $selectedTicket?->slaBusinessCalendar?->name ?: '-' }}</strong>
                <small>{{ $selectedTicket?->slaBusinessCalendar?->timezone ?: 'No calendar snapshot' }}</small>
            </div>
            <div>
                <span>Escalation Status</span>
                <strong>{{ $hasSlaBreach ? 'Breached' : ($hasSlaWarning ? 'Warning' : 'No escalation') }}</strong>
                <small>{{ $selectedTicket?->slaEscalations?->count() ?: 0 }} escalation events</small>
            </div>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Resolution</h2>
        <p>Document the solve path, root cause, and final outcome.</p>
        <div class="customer-form-grid">
            <label class="field field-full">
                <span>Resolution Summary <strong>*</strong></span>
                <input type="text" name="resolution_summary" value="{{ old('resolution_summary', $resolution->resolution_summary ?? '') }}" maxlength="255" required>
                @error('resolution_summary')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolution Type <strong>*</strong></span>
                <select name="resolution_type" required>
                    @foreach ($resolutionTypeOptions as $type)
                        <option value="{{ $type }}" @selected($selectedResolutionType === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
                @error('resolution_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolution Outcome <strong>*</strong></span>
                <select name="resolution_outcome" required>
                    @foreach ($resolutionOutcomeOptions as $outcome)
                        <option value="{{ $outcome }}" @selected($selectedResolutionOutcome === $outcome)>{{ ucfirst(str_replace('_', ' ', $outcome)) }}</option>
                    @endforeach
                </select>
                @error('resolution_outcome')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Root Cause</span>
                <select name="root_cause">
                    @foreach ($rootCauseOptions as $rootCause)
                        <option value="{{ $rootCause }}" @selected($selectedRootCause === $rootCause)>{{ ucfirst(str_replace('_', ' ', $rootCause)) }}</option>
                    @endforeach
                </select>
                @error('root_cause')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolved By</span>
                <input type="text" name="resolved_by" value="{{ old('resolved_by', $resolution->resolved_by ?? '') }}" maxlength="255">
                @error('resolved_by')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolved At</span>
                <input type="datetime-local" name="resolved_at" value="{{ old('resolved_at', optional($resolution->resolved_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('resolved_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolution Duration</span>
                <input type="number" min="0" name="resolution_duration_minutes" value="{{ old('resolution_duration_minutes', $resolution->resolution_duration_minutes ?? '') }}" placeholder="Minutes">
                @error('resolution_duration_minutes')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Resolution Detail</span>
                <textarea name="resolution_notes" rows="4">{{ old('resolution_notes', $resolution->resolution_notes ?? '') }}</textarea>
                @error('resolution_notes')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Workaround</span>
                <textarea name="workaround" rows="3">{{ old('workaround', $resolution->workaround ?? '') }}</textarea>
                @error('workaround')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Permanent Fix</span>
                <textarea name="permanent_fix" rows="3">{{ old('permanent_fix', $resolution->permanent_fix ?? '') }}</textarea>
                @error('permanent_fix')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Internal Notes</span>
                <textarea name="internal_notes" rows="3">{{ old('internal_notes', $resolution->internal_notes ?? '') }}</textarea>
                @error('internal_notes')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Customer</h2>
        <p>Track customer notification and confirmation timestamps.</p>
        <div class="customer-form-grid">
            <label class="field">
                <span>Customer Notified <strong>*</strong></span>
                <select name="customer_notified" required>
                    <option value="0" @selected((string) $selectedCustomerNotified === '0')>No</option>
                    <option value="1" @selected((string) $selectedCustomerNotified === '1')>Yes</option>
                </select>
                @error('customer_notified')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Customer Notified At</span>
                <input type="datetime-local" name="customer_notified_at" value="{{ old('customer_notified_at', optional($resolution->customer_notified_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('customer_notified_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Customer Confirmation</span>
                <input type="text" value="{{ old('customer_confirmation_at', $resolution?->customer_confirmation_at ? 'Confirmed' : 'Pending') }}" disabled>
            </label>

            <label class="field">
                <span>Customer Confirmation At</span>
                <input type="datetime-local" name="customer_confirmation_at" value="{{ old('customer_confirmation_at', optional($resolution->customer_confirmation_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('customer_confirmation_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Knowledge</h2>
        <p>Flag repeatable solutions and link an existing knowledge article.</p>
        <div class="customer-form-grid">
            <label class="field">
                <span>Good Candidate for Knowledge Base <strong>*</strong></span>
                <select name="knowledge_candidate" required>
                    <option value="0" @selected((string) $selectedKnowledgeCandidate === '0')>No</option>
                    <option value="1" @selected((string) $selectedKnowledgeCandidate === '1')>Yes</option>
                </select>
                @error('knowledge_candidate')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Linked Knowledge Article</span>
                <select name="knowledge_article_id">
                    <option value="">No linked article</option>
                    @foreach ($knowledgeArticles as $article)
                        <option value="{{ $article->id }}" @selected((string) $selectedKnowledgeArticle === (string) $article->id)>{{ $article->title }}</option>
                    @endforeach
                </select>
                @error('knowledge_article_id')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>
