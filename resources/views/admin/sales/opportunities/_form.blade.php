@php
    $opportunity = $opportunity ?? null;
@endphp

@if (old('conversation_id', $opportunity->conversation_id ?? null))
    <input type="hidden" name="conversation_id" value="{{ old('conversation_id', $opportunity->conversation_id ?? '') }}">
@endif

<div class="customer-form-grid">
    <label class="field">
        <span>Lead</span>
        <select name="lead_id">
            <option value="" @selected(blank(old('lead_id', $opportunity->lead_id ?? null)))>Tanpa lead</option>
            @foreach ($leads as $lead)
                <option value="{{ $lead->id }}" @selected((string) old('lead_id', $opportunity->lead_id ?? '') === (string) $lead->id)>{{ $lead->name }}</option>
            @endforeach
        </select>
        @error('lead_id')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Customer</span>
        <select name="customer_id">
            <option value="">Tanpa customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) old('customer_id', $opportunity->customer_id ?? '') === (string) $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Title <strong>*</strong></span>
        <input type="text" name="title" value="{{ old('title', $opportunity->title ?? '') }}" maxlength="255" required>
        @error('title')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Company Name</span>
        <input type="text" name="company_name" value="{{ old('company_name', $opportunity->company_name ?? '') }}" maxlength="255">
        @error('company_name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Contact Name</span>
        <input type="text" name="contact_name" value="{{ old('contact_name', $opportunity->contact_name ?? '') }}" maxlength="255">
        @error('contact_name')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Estimated Value</span>
        <input type="number" name="estimated_value" value="{{ old('estimated_value', $opportunity->estimated_value ?? 0) }}" min="0" step="0.01">
        @error('estimated_value')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Probability</span>
        <input type="number" name="probability" value="{{ old('probability', $opportunity->probability ?? 0) }}" min="0" max="100" step="1">
        @error('probability')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Status <strong>*</strong></span>
        <select name="status" required>
            @foreach ($statusOptions as $status)
                <option value="{{ $status }}" @selected(old('status', $opportunity->status ?? 'open') === $status)>{{ $statusLabels[$status] ?? ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Expected Close Date</span>
        <input type="date" name="expected_close_date" value="{{ old('expected_close_date', optional($opportunity->expected_close_date ?? null)->format('Y-m-d')) }}">
        @error('expected_close_date')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Assigned To</span>
        <input type="text" name="assigned_to" value="{{ old('assigned_to', $opportunity->assigned_to ?? '') }}" maxlength="255">
        @error('assigned_to')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Notes</span>
        <textarea name="notes" rows="4">{{ old('notes', $opportunity->notes ?? '') }}</textarea>
        @error('notes')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
