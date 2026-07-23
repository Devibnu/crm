@php
    $ticket = $ticket ?? null;
    $conversation = $conversation ?? null;
    $prefillCustomer = $prefillCustomer ?? null;
    $prefillSubject = $prefillSubject ?? null;
    $prefillDescription = $prefillDescription ?? null;
    $prefillChannel = $prefillChannel ?? null;
    $prefillPriority = $prefillPriority ?? null;
    $prefillStatus = $prefillStatus ?? null;
    $prefillAssignedTo = $prefillAssignedTo ?? null;
    $selectedCustomerId = old('customer_id', $ticket->customer_id ?? $prefillCustomer?->id ?? '');
    $selectedStatus = old('status', $ticket->status ?? $prefillStatus ?? 'open');
    $selectedPriority = old('priority', $ticket->priority ?? $prefillPriority ?? 'medium');
    $selectedChannel = old('channel', $ticket->channel ?? $prefillChannel ?? 'web');
@endphp

<div class="lead-form-groups customer-form-groups">
    @if ($conversation)
        <input type="hidden" name="conversation_id" value="{{ old('conversation_id', $conversation->id) }}">
    @endif

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Ticket Information</h2>
            <p>Core service request identity and workflow state.</p>
        </div>
        <div class="lead-form-grid">
            @if ($ticket)
                <label class="field">
                    <span>Ticket Number</span>
                    <input type="text" value="{{ $ticket->ticket_number }}" disabled>
                </label>
            @endif

            <label class="field">
                <span>Subject <strong>*</strong></span>
                <input type="text" name="subject" value="{{ old('subject', $ticket->subject ?? $prefillSubject ?? '') }}" maxlength="255" required>
                @error('subject')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Status <strong>*</strong></span>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Due At</span>
                <input type="datetime-local" name="due_at" value="{{ old('due_at', optional($ticket->due_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('due_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Customer Information</h2>
            <p>Customer context connected to this service ticket.</p>
        </div>
        <div class="lead-form-grid">
            <label class="field">
                <span>Customer</span>
                <select name="customer_id">
                    <option value="">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Assignment</h2>
            <p>Ownership and handling context.</p>
        </div>
        <div class="lead-form-grid">
            <label class="field">
                <span>Assigned To</span>
                <input type="text" name="assigned_to" value="{{ old('assigned_to', $ticket->assigned_to ?? $prefillAssignedTo ?? '') }}" maxlength="255">
                @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Priority & Channel</h2>
            <p>Service urgency and intake channel.</p>
        </div>
        <div class="lead-form-grid">
            <label class="field">
                <span>Priority <strong>*</strong></span>
                <select name="priority" required>
                    @foreach ($priorityOptions as $priority)
                        <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                @error('priority')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Channel <strong>*</strong></span>
                <select name="channel" required>
                    @foreach ($channelOptions as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst(str_replace('_', ' ', $channel)) }}</option>
                    @endforeach
                </select>
                @error('channel')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Description</h2>
            <p>Customer-facing issue context and request details.</p>
        </div>
        <label class="field">
            <span>Description</span>
            <textarea name="description" rows="5">{{ old('description', $ticket->description ?? $prefillDescription ?? '') }}</textarea>
            @error('description')<small class="error">{{ $message }}</small>@enderror
        </label>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Internal Notes</h2>
            <p>Private service context for future phases.</p>
        </div>
        <label class="field">
            <span>Internal Notes</span>
            <textarea rows="4" disabled>{{ $ticket?->closed_at ? 'Closed at '.$ticket->closed_at->format('d M Y H:i') : '' }}</textarea>
        </label>
    </section>
</div>
