@php
    $message = $message ?? null;
    $selectedCustomer = old('customer_id', $message->customer_id ?? '');
    $selectedChannel = old('channel', $message->channel ?? 'email');
    $selectedDirection = old('direction', $message->direction ?? 'inbound');
    $selectedStatus = old('status', $message->status ?? 'unread');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Message Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Customer</span>
                <select name="customer_id">
                    <option value="">Tanpa customer</option>
                    @foreach ($customers as $customer)
                        <option value="{{ $customer->id }}" @selected((string) $selectedCustomer === (string) $customer->id)>{{ $customer->name }}</option>
                    @endforeach
                </select>
                @error('customer_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Channel <strong>*</strong></span>
                <select name="channel" required>
                    @foreach ($channelOptions as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucfirst($channel) }}</option>
                    @endforeach
                </select>
                @error('channel')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Direction <strong>*</strong></span>
                <select name="direction" required>
                    @foreach ($directionOptions as $direction)
                        <option value="{{ $direction }}" @selected($selectedDirection === $direction)>{{ ucfirst($direction) }}</option>
                    @endforeach
                </select>
                @error('direction')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Sender Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Sender Name</span>
                <input type="text" name="sender_name" value="{{ old('sender_name', $message->sender_name ?? '') }}" maxlength="255">
                @error('sender_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Sender Contact</span>
                <input type="text" name="sender_contact" value="{{ old('sender_contact', $message->sender_contact ?? '') }}" maxlength="255">
                @error('sender_contact')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Subject</span>
                <input type="text" name="subject" value="{{ old('subject', $message->subject ?? '') }}" maxlength="255">
                @error('subject')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Message Content</h2>
        <label class="field">
            <span>Message <strong>*</strong></span>
            <textarea name="message" rows="8" required>{{ old('message', $message->message ?? '') }}</textarea>
            @error('message')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2>Assignment & Status</h2>
        <div class="customer-form-grid">
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
                <span>Assigned To</span>
                <input type="text" name="assigned_to" value="{{ old('assigned_to', $message->assigned_to ?? '') }}" maxlength="255">
                @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Received At</span>
                <input type="datetime-local" name="received_at" value="{{ old('received_at', optional($message->received_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('received_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolved At</span>
                <input type="datetime-local" name="resolved_at" value="{{ old('resolved_at', optional($message->resolved_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('resolved_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>
</div>
