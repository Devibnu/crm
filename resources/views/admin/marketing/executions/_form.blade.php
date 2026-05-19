@php
    $execution = $execution ?? null;
    $selectedChannel = old('channel', $execution->channel ?? 'email');
    $selectedStatus = old('status', $execution->status ?? 'scheduled');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Execution Context" data-lang-id="Konteks Eksekusi">Execution Context</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Campaign" data-lang-id="Campaign">Campaign</span>
                <select name="marketing_campaign_id">
                    <option value="" data-lang-en="No campaign" data-lang-id="Tanpa campaign">Tanpa campaign</option>
                    @foreach ($campaigns as $campaign)
                        <option value="{{ $campaign->id }}" @selected((string) old('marketing_campaign_id', $execution->marketing_campaign_id ?? '') === (string) $campaign->id)>{{ $campaign->name }}</option>
                    @endforeach
                </select>
                @error('marketing_campaign_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Audience Segment" data-lang-id="Segmen Audiens">Audience Segment</span>
                <select name="audience_segment_id">
                    <option value="" data-lang-en="No segment" data-lang-id="Tanpa segmen">Tanpa segment</option>
                    @foreach ($segments as $segment)
                        <option value="{{ $segment->id }}" @selected((string) old('audience_segment_id', $execution->audience_segment_id ?? '') === (string) $segment->id)>{{ $segment->name }}</option>
                    @endforeach
                </select>
                @error('audience_segment_id')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Execution Name" data-lang-id="Nama Eksekusi">Execution Name</span> <strong>*</strong></span>
                <input type="text" name="execution_name" value="{{ old('execution_name', $execution->execution_name ?? '') }}" maxlength="255" placeholder="Email blast batch 1" data-placeholder-en="Email blast batch 1" data-placeholder-id="Batch email blast 1" required>
                @error('execution_name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Channel" data-lang-id="Channel">Channel</span> <strong>*</strong></span>
                <select name="channel" required>
                    @foreach ($channelOptions as $channel)
                        <option value="{{ $channel }}" @selected($selectedChannel === $channel)>{{ ucwords(str_replace('_', ' ', $channel)) }}</option>
                    @endforeach
                </select>
                @error('channel')<small class="error">{{ $message }}</small>@enderror
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
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Timeline" data-lang-id="Timeline">Timeline</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Scheduled At" data-lang-id="Dijadwalkan Pada">Scheduled At</span>
                <input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', optional($execution->scheduled_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('scheduled_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Started At" data-lang-id="Dimulai Pada">Started At</span>
                <input type="datetime-local" name="started_at" value="{{ old('started_at', optional($execution->started_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('started_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Completed At" data-lang-id="Selesai Pada">Completed At</span>
                <input type="datetime-local" name="completed_at" value="{{ old('completed_at', optional($execution->completed_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('completed_at')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Metrics" data-lang-id="Metrik">Metrics</h2>
        <div class="customer-form-grid">
            @foreach ([
                'sent_count' => ['en' => 'Sent', 'id' => 'Terkirim'],
                'delivered_count' => ['en' => 'Delivered', 'id' => 'Sampai'],
                'opened_count' => ['en' => 'Opened', 'id' => 'Dibuka'],
                'clicked_count' => ['en' => 'Clicked', 'id' => 'Diklik'],
                'response_count' => ['en' => 'Responses', 'id' => 'Respons'],
            ] as $field => $label)
                <label class="field">
                    <span data-lang-en="{{ $label['en'] }}" data-lang-id="{{ $label['id'] }}">{{ $label['en'] }}</span>
                    <input type="number" name="{{ $field }}" value="{{ old($field, $execution->{$field} ?? 0) }}" min="0" step="1">
                    @error($field)<small class="error">{{ $message }}</small>@enderror
                </label>
            @endforeach
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Notes" data-lang-id="Catatan">Notes</h2>
        <label class="field">
            <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
            <textarea name="notes" rows="5" placeholder="Execution notes, delivery issue, or optimization context." data-placeholder-en="Execution notes, delivery issue, or optimization context." data-placeholder-id="Catatan eksekusi, kendala pengiriman, atau konteks optimasi.">{{ old('notes', $execution->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
