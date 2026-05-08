@php
    $policy = $policy ?? null;
    $selectedPriority = old('priority', $policy->priority ?? 'medium');
    $selectedActive = old('is_active', isset($policy) ? (int) $policy->is_active : 1);
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>SLA Details</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $policy->name ?? '') }}" maxlength="255" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Priority <strong>*</strong></span>
                <select name="priority" required>
                    @foreach ($priorityOptions as $priority)
                        <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                @error('priority')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Description</span>
                <textarea name="description" rows="4">{{ old('description', $policy->description ?? '') }}</textarea>
                @error('description')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Time Targets</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Response Time Minutes <strong>*</strong></span>
                <input type="number" name="response_time_minutes" value="{{ old('response_time_minutes', $policy->response_time_minutes ?? 60) }}" min="1" required>
                @error('response_time_minutes')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolution Time Minutes <strong>*</strong></span>
                <input type="number" name="resolution_time_minutes" value="{{ old('resolution_time_minutes', $policy->resolution_time_minutes ?? 1440) }}" min="1" required>
                @error('resolution_time_minutes')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Status</h2>
        <label class="field">
            <span>Active Status <strong>*</strong></span>
            <select name="is_active" required>
                <option value="1" @selected((string) $selectedActive === '1')>Active</option>
                <option value="0" @selected((string) $selectedActive === '0')>Inactive</option>
            </select>
            @error('is_active')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
