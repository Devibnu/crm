@php
    $resolution = $resolution ?? null;
    $selectedResolutionType = old('resolution_type', $resolution->resolution_type ?? 'fixed');
    $selectedCustomerNotified = old('customer_notified', isset($resolution) ? (int) $resolution->customer_notified : 0);
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Ticket Information" data-lang-id="Informasi Tiket">Ticket Information</h2>
        <label class="field">
            <span data-lang-en="Ticket" data-lang-id="Tiket">Ticket</span> <strong>*</strong>
            <select name="ticket_id" required>
                <option value="" data-lang-en="Select ticket" data-lang-id="Pilih tiket">Pilih ticket</option>
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
        <h2 data-lang-en="Resolution Details" data-lang-id="Detail Penyelesaian">Resolution Details</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Resolution Summary" data-lang-id="Ringkasan Penyelesaian">Resolution Summary</span> <strong>*</strong>
                <input type="text" name="resolution_summary" value="{{ old('resolution_summary', $resolution->resolution_summary ?? '') }}" maxlength="255" required>
                @error('resolution_summary')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Resolution Type" data-lang-id="Tipe Penyelesaian">Resolution Type</span> <strong>*</strong>
                <select name="resolution_type" required>
                    @foreach ($resolutionTypeOptions as $type)
                        <option value="{{ $type }}" @selected($selectedResolutionType === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
                @error('resolution_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Resolved By" data-lang-id="Diselesaikan Oleh">Resolved By</span>
                <input type="text" name="resolved_by" value="{{ old('resolved_by', $resolution->resolved_by ?? '') }}" maxlength="255">
                @error('resolved_by')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Resolved At" data-lang-id="Diselesaikan Pada">Resolved At</span>
                <input type="datetime-local" name="resolved_at" value="{{ old('resolved_at', optional($resolution->resolved_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('resolved_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span data-lang-en="Resolution Notes" data-lang-id="Catatan Penyelesaian">Resolution Notes</span>
                <textarea name="resolution_notes" rows="4">{{ old('resolution_notes', $resolution->resolution_notes ?? '') }}</textarea>
                @error('resolution_notes')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span data-lang-en="Root Cause" data-lang-id="Akar Masalah">Root Cause</span>
                <textarea name="root_cause" rows="4">{{ old('root_cause', $resolution->root_cause ?? '') }}</textarea>
                @error('root_cause')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Notification" data-lang-id="Notifikasi">Notification</h2>
        <label class="field">
            <span data-lang-en="Customer Notified" data-lang-id="Customer Diberi Kabar">Customer Notified</span> <strong>*</strong>
            <select name="customer_notified" required>
                <option value="0" @selected((string) $selectedCustomerNotified === '0') data-lang-en="Not notified" data-lang-id="Belum dikabari">Not notified</option>
                <option value="1" @selected((string) $selectedCustomerNotified === '1') data-lang-en="Notified" data-lang-id="Sudah dikabari">Notified</option>
            </select>
            @error('customer_notified')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
