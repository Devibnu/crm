@php
    $ticket = $ticket ?? null;
    $selectedStatus = old('status', $ticket->status ?? 'open');
    $selectedPriority = old('priority', $ticket->priority ?? 'medium');
    $selectedChannel = old('channel', $ticket->channel ?? 'web');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Ticket Details</h2>
        <div class="customer-form-grid">
            @if ($ticket)
                <label class="field">
                    <span>Ticket Number</span>
                    <input type="text" value="{{ $ticket->ticket_number }}" disabled>
                </label>
            @endif

            <label class="field">
                <span>Subject <strong>*</strong></span>
                <input type="text" name="subject" value="{{ old('subject', $ticket->subject ?? '') }}" maxlength="255" required>
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

            <label class="field field-full">
                <span>Description</span>
                <textarea name="description" rows="5">{{ old('description', $ticket->description ?? '') }}</textarea>
                @error('description')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Customer & Assignment</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Customer</span>
                <select name="customer_id">
                    <option value="">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) old('customer_id', $ticket->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Assigned To</span>
                <input type="text" name="assigned_to" value="{{ old('assigned_to', $ticket->assigned_to ?? '') }}" maxlength="255">
                @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Timeline</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Due At</span>
                <input type="datetime-local" name="due_at" value="{{ old('due_at', optional($ticket->due_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('due_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolved At</span>
                <input type="datetime-local" name="resolved_at" value="{{ old('resolved_at', optional($ticket->resolved_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('resolved_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Closed At</span>
                <input type="datetime-local" name="closed_at" value="{{ old('closed_at', optional($ticket->closed_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('closed_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>
