@php
    $automation = $automation ?? null;
    $selectedTrigger = old('trigger_type', $automation->trigger_type ?? 'form_submit');
    $selectedAction = old('action_type', $automation->action_type ?? 'send_email');
    $selectedStatus = old('status', $automation->status ?? 'draft');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Automation Context" data-lang-id="Konteks Otomasi">Automation Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Campaign" data-lang-id="Campaign">Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="" data-lang-en="No campaign" data-lang-id="Tanpa campaign">Tanpa campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $automation->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Audience Segment" data-lang-id="Segmen Audiens">Audience Segment</span>
                <select name="audience_segment_id">
                    <option value="" data-lang-en="No segment" data-lang-id="Tanpa segmen">Tanpa segment</option>
                    @foreach ($segments as $segment)
                        <option value="{{ $segment->id }}" @selected((string) old('audience_segment_id', $automation->audience_segment_id ?? '') === (string) $segment->id)>{{ $segment->name }}</option>
                    @endforeach
                </select>
                @error('audience_segment_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Name" data-lang-id="Nama">Name</span> <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $automation->name ?? '') }}" maxlength="255" placeholder="Welcome nurture rule" data-placeholder-en="Welcome nurture rule" data-placeholder-id="Aturan nurture penyambutan" required>
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
                <span data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $automation->created_by ?? '') }}" maxlength="255" placeholder="Marketing Ops" data-placeholder-en="Marketing Ops" data-placeholder-id="Operasi Marketing">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Trigger & Action" data-lang-id="Pemicu & Aksi">Trigger & Action</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span><span data-lang-en="Trigger Type" data-lang-id="Tipe Pemicu">Trigger Type</span> <strong>*</strong></span>
                <select name="trigger_type" required>
                    @foreach ($triggerOptions as $trigger)
                        <option value="{{ $trigger }}" @selected($selectedTrigger === $trigger)>{{ ucwords(str_replace('_', ' ', $trigger)) }}</option>
                    @endforeach
                </select>
                @error('trigger_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Action Type" data-lang-id="Tipe Aksi">Action Type</span> <strong>*</strong></span>
                <select name="action_type" required>
                    @foreach ($actionOptions as $action)
                        <option value="{{ $action }}" @selected($selectedAction === $action)>{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                    @endforeach
                </select>
                @error('action_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Delay Minutes" data-lang-id="Menit Jeda">Delay Minutes</span>
                <input type="number" name="delay_minutes" value="{{ old('delay_minutes', $automation->delay_minutes ?? 0) }}" min="0" step="1">
                @error('delay_minutes')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Rules & Payload" data-lang-id="Aturan & Payload">Rules & Payload</h2>
        <label class="field">
            <span data-lang-en="Conditions JSON" data-lang-id="JSON Kondisi">Conditions JSON</span>
            <textarea name="conditions" rows="7" placeholder='{"source":"landing_page","score":{">=":70}}' data-placeholder-en='{"source":"landing_page","score":{">=":70}}' data-placeholder-id='{"sumber":"landing_page","skor":{">=":70}}'>{{ old('conditions', $conditionsJson ?? '') }}</textarea>
            @error('conditions')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field">
            <span data-lang-en="Action Payload JSON" data-lang-id="JSON Payload Aksi">Action Payload JSON</span>
            <textarea name="action_payload" rows="7" placeholder='{"template":"welcome_email","subject":"Welcome"}' data-placeholder-en='{"template":"welcome_email","subject":"Welcome"}' data-placeholder-id='{"template":"email_selamat_datang","subject":"Selamat Datang"}'>{{ old('action_payload', $actionPayloadJson ?? '') }}</textarea>
            @error('action_payload')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Notes" data-lang-id="Catatan">Notes</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Executed Count" data-lang-id="Jumlah Eksekusi">Executed Count</span>
                <input type="number" name="executed_count" value="{{ old('executed_count', $automation->executed_count ?? 0) }}" min="0" step="1">
                @error('executed_count')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Last Executed At" data-lang-id="Dieksekusi Terakhir Pada">Last Executed At</span>
                <input type="datetime-local" name="last_executed_at" value="{{ old('last_executed_at', optional($automation->last_executed_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('last_executed_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
            <textarea name="notes" rows="5" placeholder="Automation notes, intent, or follow-up context." data-placeholder-en="Automation notes, intent, or follow-up context." data-placeholder-id="Catatan otomasi, tujuan, atau konteks tindak lanjut.">{{ old('notes', $automation->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
