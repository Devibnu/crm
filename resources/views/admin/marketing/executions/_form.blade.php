@php
    $execution = $execution ?? null;
    $selectedChannel = old('channel', $execution->channel ?? 'email');
    $selectedStatus = old('status', $execution->status ?? 'scheduled');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Execution Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="">Tanpa campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $execution->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Audience Segment</span>
                <select name="audience_segment_id">
                    <option value="">Tanpa segment</option>
                    @foreach ($segments as $segment)
                        <option value="{{ $segment->id }}" @selected((string) old('audience_segment_id', $execution->audience_segment_id ?? '') === (string) $segment->id)>{{ $segment->name }}</option>
                    @endforeach
                </select>
                @error('audience_segment_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Execution Name <strong>*</strong></span>
                <input type="text" name="execution_name" value="{{ old('execution_name', $execution->execution_name ?? '') }}" maxlength="255" placeholder="Email blast batch 1" required>
                @error('execution_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Channel <strong>*</strong></span>
                <select name="channel" required>
                    @foreach ($channelOptions as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucwords(str_replace('_', ' ', $channel)) }}</option>
                    @endforeach
                </select>
                @error('channel')<small class="error">{{ $message }}</small>@enderror
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
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Timeline</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Scheduled At</span>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($execution->scheduled_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('scheduled_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Started At</span>
                <input type="datetime-local" name="started_at" value="{{ old('started_at', optional($execution->started_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('started_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Completed At</span>
                <input type="datetime-local" name="completed_at" value="{{ old('completed_at', optional($execution->completed_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('completed_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Metrics</h2>
        <div class="customer-form-grid">
            @foreach (['sent_count' => 'Sent', 'delivered_count' => 'Delivered', 'opened_count' => 'Opened', 'clicked_count' => 'Clicked', 'response_count' => 'Responses'] as $field => $label)
                <label class="field">
                    <span>{{ $label }}</span>
                    <input type="number" name="{{ $field }}" value="{{ old($field, $execution->{$field} ?? 0) }}" min="0" step="1">
                    @error($field)<small class="error">{{ $message }}</small>@enderror
                </label>
            @endforeach
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Notes</h2>
        <label class="field">
            <span>Notes</span>
            <textarea name="notes" rows="5" placeholder="Execution notes, delivery issue, or optimization context.">{{ old('notes', $execution->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
