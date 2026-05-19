@php
    $rule = $rule ?? null;
    $selectedTrigger = old('trigger_source', $rule->trigger_source ?? 'form_submit');
    $selectedPriority = old('priority', $rule->priority ?? 'medium');
    $selectedStatus = old('status', $rule->status ?? 'active');
    $autoAssign = old('auto_assign', $rule->auto_assign ?? false);
@endphp

<div class="sales-form-sections">
    <div class="sales-form-section">
        <h2 data-lang-en="Rule Information" data-lang-id="Informasi Aturan">Rule Information</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span><span data-lang-en="Name" data-lang-id="Nama">Name</span> <strong>*</strong></span>
                <input type="text" name="name" value="{{ old('name', $rule->name ?? '') }}" maxlength="255" placeholder="High intent form rule" data-placeholder-en="High intent form rule" data-placeholder-id="Aturan formulir minat tinggi" required>
                @error('name')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Trigger Source" data-lang-id="Sumber Trigger">Trigger Source</span> <strong>*</strong></span>
                <select name="trigger_source" required>
                    @foreach ($triggerOptions as $trigger)
                        <option value="{{ $trigger }}" @selected($selectedTrigger === $trigger)>{{ ucwords(str_replace('_', ' ', $trigger)) }}</option>
                    @endforeach
                </select>
                @error('trigger_source')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span><span data-lang-en="Priority" data-lang-id="Prioritas">Priority</span> <strong>*</strong></span>
                <select name="priority" required>
                    @foreach ($priorityOptions as $priority)
                        <option value="{{ $priority }}" @selected($selectedPriority === $priority)>{{ ucfirst($priority) }}</option>
                    @endforeach
                </select>
                @error('priority')<small class="error">{{ $message }}</small>@enderror
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
        <h2 data-lang-en="Scoring & Routing" data-lang-id="Scoring & Routing">Scoring & Routing</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Score Value" data-lang-id="Nilai Skor">Score Value</span>
                <input type="number" name="score_value" value="{{ old('score_value', $rule->score_value ?? 0) }}" min="0" max="100" step="1">
                @error('score_value')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Routing Team" data-lang-id="Tim Routing">Routing Team</span>
                <input type="text" name="routing_team" value="{{ old('routing_team', $rule->routing_team ?? '') }}" maxlength="255" placeholder="Enterprise Sales" data-placeholder-en="Enterprise Sales" data-placeholder-id="Sales Enterprise">
                @error('routing_team')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Routing User" data-lang-id="User Routing">Routing User</span>
                <input type="text" name="routing_user" value="{{ old('routing_user', $rule->routing_user ?? '') }}" maxlength="255" placeholder="Account Executive" data-placeholder-en="Account Executive" data-placeholder-id="Account Executive">
                @error('routing_user')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field checkbox-field">
                <span data-lang-en="Auto Assign" data-lang-id="Auto Assign">Auto Assign</span>
                <input type="hidden" name="auto_assign" value="0">
                <label class="inline-check">
                    <input type="checkbox" name="auto_assign" value="1" @checked((bool) $autoAssign)>
                    <span data-lang-en="Enable automatic routing" data-lang-id="Aktifkan routing otomatis">Enable automatic routing</span>
                </label>
                @error('auto_assign')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Conditions" data-lang-id="Kondisi">Conditions</h2>
        <label class="field">
            <span data-lang-en="Conditions JSON" data-lang-id="JSON Kondisi">Conditions JSON</span>
            <textarea name="conditions" rows="7" placeholder='{"source":"landing_page","score":{">=":70}}' data-placeholder-en='{"source":"landing_page","score":{">=":70}}' data-placeholder-id='{"sumber":"landing_page","skor":{">=":70}}'>{{ old('conditions', $conditionsJson ?? '') }}</textarea>
            @error('conditions')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>

    <div class="sales-form-section">
        <h2 data-lang-en="Notes" data-lang-id="Catatan">Notes</h2>
        <div class="customer-form-grid">
            <label class="field">
                <span data-lang-en="Execution Count" data-lang-id="Jumlah Eksekusi">Execution Count</span>
                <input type="number" name="execution_count" value="{{ old('execution_count', $rule->execution_count ?? 0) }}" min="0" step="1">
                @error('execution_count')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Last Executed At" data-lang-id="Dieksekusi Terakhir Pada">Last Executed At</span>
                <input type="datetime-local" name="last_executed_at" value="{{ old('last_executed_at', optional($rule->last_executed_at ?? null)->format('Y-m-d\TH:i')) }}">
                @error('last_executed_at')<small class="error">{{ $message }}</small>@enderror
            </label>

            <label class="field">
                <span data-lang-en="Created By" data-lang-id="Dibuat Oleh">Created By</span>
                <input type="text" name="created_by" value="{{ old('created_by', $rule->created_by ?? '') }}" maxlength="255" placeholder="Sales Ops" data-placeholder-en="Sales Ops" data-placeholder-id="Operasi Sales">
                @error('created_by')<small class="error">{{ $message }}</small>@enderror
            </label>
        </div>

        <label class="field">
            <span data-lang-en="Notes" data-lang-id="Catatan">Notes</span>
            <textarea name="notes" rows="5" placeholder="Routing logic, scoring intent, or sales handoff notes." data-placeholder-en="Routing logic, scoring intent, or sales handoff notes." data-placeholder-id="Logika routing, tujuan scoring, atau catatan handoff sales.">{{ old('notes', $rule->notes ?? '') }}</textarea>
            @error('notes')<small class="error">{{ $message }}</small>@enderror
        </label>
    </div>
</div>
