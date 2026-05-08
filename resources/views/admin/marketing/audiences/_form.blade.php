@php
    $segment = $segment ?? null;
    $selectedType = old('type', $segment->type ?? 'demographic');
    $selectedStatus = old('status', $segment->status ?? 'active');
    $criteriaValue = old('criteria', $criteriaJson ?? '');
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Segment Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $segment->name ?? '') }}" maxlength="255" placeholder="High Intent Enterprise Audience" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Type <strong>*</strong></span>
                <select name="type" required>
                    @foreach ($typeOptions as $type)
                        <option value="{{ $type }}" @selected($selectedType === $type)>{{ ucfirst($type) }}</option>
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
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Audience Criteria</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Estimated Audience</span>
                <input type="number" name="estimated_audience" value="{{ old('estimated_audience', $segment->estimated_audience ?? 0) }}" min="0" step="1">
                @error('estimated_audience')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span>Criteria JSON</span>
            <textarea name="criteria" rows="8" placeholder='{"industry":["manufacturing"],"engagement_score":{">=":70}}'>{{ $criteriaValue }}</textarea>
            @error('criteria')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2>Description</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $segment->created_by ?? '') }}" maxlength="255" placeholder="Marketing Ops">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span>Description</span>
            <textarea name="description" rows="5" placeholder="Segment purpose, targeting notes, and campaign usage.">{{ old('description', $segment->description ?? '') }}</textarea>
            @error('description')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
