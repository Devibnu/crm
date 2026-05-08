@php
    $resolution = $resolution ?? null;
    $selectedResolutionType = old('resolution_type', $resolution->resolution_type ?? 'fixed');
    $selectedCustomerNotified = old('customer_notified', isset($resolution) ? (int) $resolution->customer_notified : 0);
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Ticket Information</h2>
        <label class="field">
            <span>Ticket <strong>*</strong></span>
            <select name="ticket_id" required>
                <option value="">Pilih ticket</option>
                @foreach ($tickets as $ticket)
                    <option value="{{ $ticket->id }}" @selected((string) old('ticket_id', $resolution->ticket_id ?? '') === (string) $ticket->id)>
                        {{ $ticket->ticket_number }} - {{ $ticket->subject }}
                    </option>
                @endforeach
            </select>
            @error('ticket_id')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2>Resolution Details</h2>
        <div class="customer-form-grid">
            <label class="field">
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
                <span>Resolved By</span>
                <input type="text" name="resolved_by" value="{{ old('resolved_by', $resolution->resolved_by ?? '') }}" maxlength="255">
                @error('resolved_by')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolved At</span>
                <input type="datetime-local" name="resolved_at" value="{{ old('resolved_at', optional($resolution->resolved_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('resolved_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Resolution Notes</span>
                <textarea name="resolution_notes" rows="4">{{ old('resolution_notes', $resolution->resolution_notes ?? '') }}</textarea>
                @error('resolution_notes')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Root Cause</span>
                <textarea name="root_cause" rows="4">{{ old('root_cause', $resolution->root_cause ?? '') }}</textarea>
                @error('root_cause')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Notification</h2>
        <label class="field">
            <span>Customer Notified <strong>*</strong></span>
            <select name="customer_notified" required>
                <option value="0" @selected((string) $selectedCustomerNotified === '0')>Not notified</option>
                <option value="1" @selected((string) $selectedCustomerNotified === '1')>Notified</option>
            </select>
            @error('customer_notified')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
