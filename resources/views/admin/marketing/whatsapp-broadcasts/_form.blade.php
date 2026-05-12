@php
    $broadcast = $broadcast ?? null;
    $selectedStatus = old('status', $broadcast->status ?? 'draft');
    $selectedTargetType = old('target_type', $broadcast->target_type ?? 'customer');
    $selectedRecipientType = old('recipient_type', $defaultRecipientType ?? 'customer');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Broadcast Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Related Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="">Without campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $broadcast->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Broadcast Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $broadcast->name ?? '') }}" maxlength="255" placeholder="Ramadan Promo WA Blast" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Status <strong>*</strong></span>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Target Type <strong>*</strong></span>
                <select name="target_type" required>
                    @foreach ($targetTypeOptions as $targetType)
                        <option value="{{ $targetType }}" @selected($selectedTargetType === $targetType)>{{ ucfirst($targetType) }}</option>
                    @endforeach
                </select>
                @error('target_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Recipient Source <strong>*</strong></span>
                <select name="recipient_type" required>
                    @foreach ($recipientTypeOptions as $recipientType)
                        <option value="{{ $recipientType }}" @selected($selectedRecipientType === $recipientType)>{{ ucfirst($recipientType) }}</option>
                    @endforeach
                </select>
                @error('recipient_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $broadcast->created_by ?? '') }}" maxlength="255" placeholder="Marketing Ops">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Schedule</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Scheduled At</span>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($broadcast->scheduled_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('scheduled_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Sent At</span>
                <input type="datetime-local" name="sent_at" value="{{ old('sent_at', optional($broadcast->sent_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('sent_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Message</h2>
        <label class="field">
            <span>Message Template <strong>*</strong></span>
            <textarea name="message_template" rows="6" placeholder="Halo {{name}}, ini promo terbaru kami..." required>{{ old('message_template', $broadcast->message_template ?? '') }}</textarea>
            @error('message_template')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field">
            <span>Notes</span>
            <textarea name="notes" rows="4" placeholder="Catatan internal campaign WA.">{{ old('notes', $broadcast->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
