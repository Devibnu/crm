@php
    $rule = $rule ?? null;
    $selectedTrigger = old('trigger_source', $rule->trigger_source ?? 'form_submit');
    $selectedPriority = old('priority', $rule->priority ?? 'medium');
    $selectedStatus = old('status', $rule->status ?? 'active');
    $autoAssign = old('auto_assign', $rule->auto_assign ?? false);
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2>Rule Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Name <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $rule->name ?? '') }}" maxlength="255" placeholder="High intent form rule" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Trigger Source <strong>*</strong></span>
                <select name="trigger_source" required>
                    @foreach ($triggerOptions as $trigger)
                        <option value="{{ $trigger }}" @selected($selectedTrigger === $trigger)>{{ ucwords(str_replace('_', ' ', $trigger)) }}</option>
                    @endforeach
                </select>
                @error('trigger_source')<small class="error">{{ $message }}</small>@enderror
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
        <h2>Scoring & Routing</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Score Value</span>
                <input type="number" name="score_value" value="{{ old('score_value', $rule->score_value ?? 0) }}" min="0" max="100" step="1">
                @error('score_value')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Routing Team</span>
                <input type="text" name="routing_team" value="{{ old('routing_team', $rule->routing_team ?? '') }}" maxlength="255" placeholder="Enterprise Sales">
                @error('routing_team')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Routing User</span>
                <input type="text" name="routing_user" value="{{ old('routing_user', $rule->routing_user ?? '') }}" maxlength="255" placeholder="Account Executive">
                @error('routing_user')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field checkbox-field">
                <span>Auto Assign</span>
                <input type="hidden" name="auto_assign" value="0">
                <label class="inline-check">
                    <input type="checkbox" name="auto_assign" value="1" @checked((bool) $autoAssign)>
                    <span>Enable automatic routing</span>
                </label>
                @error('auto_assign')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2>Conditions</h2>
        <label class="field">
            <span>Conditions JSON</span>
            <textarea name="conditions" rows="7" placeholder='{"source":"landing_page","score":{">=":70}}'>{{ old('conditions', $conditionsJson ?? '') }}</textarea>
            @error('conditions')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2>Notes</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span>Execution Count</span>
                <input type="number" name="execution_count" value="{{ old('execution_count', $rule->execution_count ?? 0) }}" min="0" step="1">
                @error('execution_count')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Last Executed At</span>
                <input type="datetime-local" name="last_executed_at" value="{{ old('last_executed_at', optional($rule->last_executed_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('last_executed_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span>Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $rule->created_by ?? '') }}" maxlength="255" placeholder="Sales Ops">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span>Notes</span>
            <textarea name="notes" rows="5" placeholder="Routing logic, scoring intent, or sales handoff notes.">{{ old('notes', $rule->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
