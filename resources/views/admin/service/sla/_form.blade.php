@php
    $policy = $policy ?? null;
    $selectedPriority = old('priority', $policy->priority ?? 'medium');
    $selectedActive = old('is_active', isset($policy) ? (int) $policy->is_active : 1);
    $selectedCalendar = old('business_calendar_id', $policy->business_calendar_id ?? '');
@endphp

<div class="lead-form-groups customer-form-groups">
    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Policy Information</h2>
            <p>Core SLA identity and the operational context for service teams.</p>
        </div>
        <div class="lead-form-grid">
            <label class="field">
                <span>Policy Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $policy->name ?? '') }}" maxlength="255" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Description</span>
                <textarea name="description" rows="4">{{ old('description', $policy->description ?? '') }}</textarea>
                @error('description')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field field-full">
                <span>Business Calendar <strong>*</strong></span>
                <select name="business_calendar_id" required>
                    <option value="">Select active business calendar</option>
                    @foreach ($businessCalendars as $calendar)
                        <option value="{{ $calendar->id }}" @selected((string) $selectedCalendar === (string) $calendar->id)>
                            {{ $calendar->name }} — {{ $calendar->timezone }}{{ $calendar->is_default ? ' — Default' : '' }}
                        </option>
                    @endforeach
                </select>
                <small>Calendar ini menentukan jam kerja, timezone, dan hari libur untuk perhitungan SLA.</small>
                @error('business_calendar_id')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Priority & Status</h2>
            <p>Define which ticket priority this SLA serves and whether it can be applied.</p>
        </div>
        <div class="lead-form-grid">
            <label class="field">
                <span>Priority <strong>*</strong></span>
                <select name="priority" required>
                    @foreach ($priorityOptions as $priority)
                        <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                @error('priority')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Active Status <strong>*</strong></span>
                <select name="is_active" required>
                    <option value="1" @selected((string) $selectedActive === '1')>Active</option>
                    <option value="0" @selected((string) $selectedActive === '0')>Inactive</option>
                </select>
                @error('is_active')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>SLA Targets</h2>
            <p>Set response and resolution targets in minutes for ticket SLA snapshots.</p>
        </div>
        <div class="lead-form-grid">
            <label class="field">
                <span>Response Target <strong>*</strong></span>
                <input type="number" name="response_time_minutes" value="{{ old('response_time_minutes', $policy->response_time_minutes ?? 60) }}" min="1" required>
                <small>Minutes until first response is due.</small>
                @error('response_time_minutes')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Response Warning %</span>
                <input type="number" name="response_warning_percentage" value="{{ old('response_warning_percentage', $policy->response_warning_percentage ?? 80) }}" min="1" max="99">
                <small>Warning threshold before response breach.</small>
                @error('response_warning_percentage')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolution Target <strong>*</strong></span>
                <input type="number" name="resolution_time_minutes" value="{{ old('resolution_time_minutes', $policy->resolution_time_minutes ?? 1440) }}" min="1" required>
                <small>Minutes until ticket resolution is due.</small>
                @error('resolution_time_minutes')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Resolution Warning %</span>
                <input type="number" name="resolution_warning_percentage" value="{{ old('resolution_warning_percentage', $policy->resolution_warning_percentage ?? 80) }}" min="1" max="99">
                <small>Warning threshold before resolution breach.</small>
                @error('resolution_warning_percentage')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </section>

    <section class="lead-form-section customer-form-section">
        <div class="customer-form-section-head">
            <h2>Policy Guidance</h2>
            <p>Operational rules applied by the SLA policy service.</p>
        </div>
        <div class="customer-profile-latest-list customer-360-sales-summary">
            <div>
                <span>Resolution Target</span>
                <strong>Greater than response</strong>
                <small>Resolution target must be larger than response target.</small>
            </div>
            <div>
                <span>Active Priority</span>
                <strong>One active policy</strong>
                <small>Only one active SLA policy is allowed for each priority.</small>
            </div>
        </div>
    </section>
</div>
