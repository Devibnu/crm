@php
    $lead = $lead ?? null;
@endphp

<div class="customer-form-grid">
    <label class="field">
        <span>Customer</span>
        <select name="customer_id">
            <option value="">Tanpa customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $lead->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Lead Name <strong>*</strong></span>
        <input type="text" name="name" value="{{ old('name', $lead->name ?? '') }}" maxlength="255" required>
        @error('name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Company Name</span>
        <input type="text" name="company_name" value="{{ old('company_name', $lead->company_name ?? '') }}" maxlength="255">
        @error('company_name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Email</span>
        <input type="email" name="email" value="{{ old('email', $lead->email ?? '') }}" maxlength="255">
        @error('email')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Phone</span>
        <input type="text" name="phone" value="{{ old('phone', $lead->phone ?? '') }}" maxlength="100">
        @error('phone')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Source</span>
        <input type="text" name="source" value="{{ old('source', $lead->source ?? '') }}" maxlength="255">
        @error('source')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Status <strong>*</strong></span>
        <select name="status" required>
            @foreach ($statusOptions as $status)
                <option value="{{ $status }}" @selected(old('status', $lead->status ?? 'new') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Priority <strong>*</strong></span>
        <select name="priority" required>
            @foreach ($priorityOptions as $priority)
                <option value="{{ $priority }}" @selected(old('priority', $lead->priority ?? 'medium') === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
        </select>
        @error('priority')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Assigned To</span>
        <input type="text" name="assigned_to" value="{{ old('assigned_to', $lead->assigned_to ?? '') }}" maxlength="255">
        @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Notes</span>
        <textarea name="notes" rows="4">{{ old('notes', $lead->notes ?? '') }}</textarea>
        @error('notes')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
