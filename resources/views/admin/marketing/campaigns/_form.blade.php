@php
    $campaign = $campaign ?? null;
    $selectedType = old('type', $campaign->type ?? 'email');
    $selectedStatus = old('status', $campaign->status ?? 'draft');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Campaign Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $campaign->name ?? '') }}" maxlength="255" placeholder="Q3 Lead Nurturing Campaign" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Type <strong>*</strong></span>
                <select name="type" required>
                    @foreach ($typeOptions as $type)
                        <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
                @error('type')<small class="error">{{ $message }}</small>@enderror
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
                <span>Target Audience</span>
                <input type="text" name="target_audience" value="{{ old('target_audience', $campaign->target_audience ?? '') }}" maxlength="255" placeholder="Enterprise prospects">
                @error('target_audience')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Budget & Leads</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Budget</span>
                <input type="number" name="budget" value="{{ old('budget', $campaign->budget ?? 0) }}" min="0" step="0.01">
                @error('budget')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Expected Leads</span>
                <input type="number" name="expected_leads" value="{{ old('expected_leads', $campaign->expected_leads ?? 0) }}" min="0" step="1">
                @error('expected_leads')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Actual Leads</span>
                <input type="number" name="actual_leads" value="{{ old('actual_leads', $campaign->actual_leads ?? 0) }}" min="0" step="1">
                @error('actual_leads')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Timeline</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Start Date</span>
                <input type="date" name="start_date" value="{{ old('start_date', optional($campaign->start_date ?? null)->format('Y-m-d')) }}">
                @error('start_date')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>End Date</span>
                <input type="date" name="end_date" value="{{ old('end_date', optional($campaign->end_date ?? null)->format('Y-m-d')) }}">
                @error('end_date')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Description</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $campaign->created_by ?? '') }}" maxlength="255" placeholder="Marketing Team">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span>Description</span>
            <textarea name="description" rows="5" placeholder="Campaign objective, channel plan, and follow-up notes.">{{ old('description', $campaign->description ?? '') }}</textarea>
            @error('description')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
