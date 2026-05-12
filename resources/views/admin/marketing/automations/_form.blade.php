@php
    $automation = $automation ?? null;
    $selectedTrigger = old('trigger_type', $automation->trigger_type ?? 'form_submit');
    $selectedAction = old('action_type', $automation->action_type ?? 'send_email');
    $selectedStatus = old('status', $automation->status ?? 'draft');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Automation Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="">Tanpa campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $automation->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Audience Segment</span>
                <select name="audience_segment_id">
                    <option value="">Tanpa segment</option>
                    @foreach ($segments as $segment)
                        <option value="{{ $segment->id }}" @selected((string) old('audience_segment_id', $automation->audience_segment_id ?? '') === (string) $segment->id)>{{ $segment->name }}</option>
                    @endforeach
                </select>
                @error('audience_segment_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $automation->name ?? '') }}" maxlength="255" placeholder="Welcome nurture rule" required>
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
                <span>Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $automation->created_by ?? '') }}" maxlength="255" placeholder="Marketing Ops">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Trigger & Action</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Trigger Type <strong>*</strong></span>
                <select name="trigger_type" required>
                    @foreach ($triggerOptions as $trigger)
                        <option value="{{ $trigger }}" @selected($selectedTrigger === $trigger)>{{ ucwords(str_replace('_', ' ', $trigger)) }}</option>
                    @endforeach
                </select>
                @error('trigger_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Action Type <strong>*</strong></span>
                <select name="action_type" required>
                    @foreach ($actionOptions as $action)
                        <option value="{{ $action }}" @selected($selectedAction === $action)>{{ ucwords(str_replace('_', ' ', $action)) }}</option>
                    @endforeach
                </select>
                @error('action_type')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Delay Minutes</span>
                <input type="number" name="delay_minutes" value="{{ old('delay_minutes', $automation->delay_minutes ?? 0) }}" min="0" step="1">
                @error('delay_minutes')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Rules & Payload</h2>
        <label class="field">
            <span>Conditions JSON</span>
            <textarea name="conditions" rows="7" placeholder='{"source":"landing_page","score":{">=":70}}'>{{ old('conditions', $conditionsJson ?? '') }}</textarea>
            @error('conditions')<small class="error">{{ $message }}</small>@enderror
        </label>

        <label class="field">
            <span>Action Payload JSON</span>
            <textarea name="action_payload" rows="7" placeholder='{"template":"welcome_email","subject":"Welcome"}'>{{ old('action_payload', $actionPayloadJson ?? '') }}</textarea>
            @error('action_payload')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2>Notes</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Executed Count</span>
                <input type="number" name="executed_count" value="{{ old('executed_count', $automation->executed_count ?? 0) }}" min="0" step="1">
                @error('executed_count')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Last Executed At</span>
                <input type="datetime-local" name="last_executed_at" value="{{ old('last_executed_at', optional($automation->last_executed_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('last_executed_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span>Notes</span>
            <textarea name="notes" rows="5" placeholder="Automation notes, intent, or follow-up context.">{{ old('notes', $automation->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
