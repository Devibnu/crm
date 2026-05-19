@php
    $broadcast = $broadcast ?? null;
    $selectedStatus = old('status', $broadcast->status ?? 'draft');
    $selectedTargetType = old('target_type', $broadcast->target_type ?? 'customer');
    $selectedRecipientType = old('recipient_type', $defaultRecipientType ?? 'customer');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Broadcast Information" data-lang-id="Informasi Broadcast">Broadcast Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Related Campaign" data-lang-id="Campaign Terkait">Related Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="" data-lang-en="Without campaign" data-lang-id="Tanpa campaign">Without campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $broadcast->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Broadcast Name" data-lang-id="Nama Broadcast">Broadcast Name</span> <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $broadcast->name ?? '') }}" maxlength="255" placeholder="Ramadan Promo WA Blast" data-placeholder-en="Ramadan Promo WA Blast" data-placeholder-id="Blast WA Promo Ramadan" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Status" data-lang-id="Status">Status</span> <strong>*</strong></span>
                <select name="status" required>
                    @foreach ($statusOptions as $status)
                        <option value="{{ $status }}" @selected($selectedStatus === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                @error('status')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Target Type" data-lang-id="Tipe Target">Target Type</span> <strong>*</strong></span>
                <select name="target_type" required>
                    @foreach ($targetTypeOptions as $targetType)
                        <option value="{{ $targetType }}" @selected($selectedTargetType === $targetType)>{{ ucfirst($targetType) }}</option>
                    @endforeach
                </select>
                @error('target_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Recipient Source" data-lang-id="Sumber Recipient">Recipient Source</span> <strong>*</strong></span>
                <select name="recipient_type" required>
                    @foreach ($recipientTypeOptions as $recipientType)
                        <option value="{{ $recipientType }}" @selected($selectedRecipientType === $recipientType)>{{ ucfirst($recipientType) }}</option>
                    @endforeach
                </select>
                @error('recipient_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $broadcast->created_by ?? '') }}" maxlength="255" placeholder="Marketing Ops" data-placeholder-en="Marketing Ops" data-placeholder-id="Operasi Marketing">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Schedule" data-lang-id="Jadwal">Schedule</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Scheduled At" data-lang-id="Dijadwalkan Pada">Scheduled At</span>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($broadcast->scheduled_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('scheduled_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Sent At" data-lang-id="Terkirim Pada">Sent At</span>
                <input type="datetime-local" name="sent_at" value="{{ old('sent_at', optional($broadcast->sent_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('sent_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Message" data-lang-id="Pesan">Message</h2>
        <label class="field">
            <span><span data-lang-en="Message Template" data-lang-id="Template Pesan">Message Template</span> <strong>*</strong></span>
            <textarea name="message_template" rows="6" placeholder="Halo {{name}}, ini promo terbaru kami..." data-placeholder-en="Hello {{name}}, this is our latest promo..." data-placeholder-id="Halo {{name}}, ini promo terbaru kami..." required>{{ old('message_template', $broadcast->message_template ?? '') }}</textarea>
            @error('message_template')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field">
            <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
            <textarea name="notes" rows="4" placeholder="Internal WhatsApp campaign notes." data-placeholder-en="Internal WhatsApp campaign notes." data-placeholder-id="Catatan internal campaign WA.">{{ old('notes', $broadcast->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
