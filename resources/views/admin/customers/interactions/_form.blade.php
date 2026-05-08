@php
    $interaction = $interaction ?? null;
    $selectedCustomerId = old('customer_id', $interaction->customer_id ?? ($selectedCustomer->id ?? null));
    $selectedType = old('type', $interaction->type ?? 'call');
    $interactionAt = old('interaction_at', optional($interaction->interaction_at ?? null)->format('Y-m-d\TH:i'));
@endphp

<div class="customer-form-grid">
    <label class="field">
        <span>Customer <strong>*</strong></span>
        <select name="customer_id" required>
            <option value="">Pilih customer</option>
            @foreach ($customers as $customer)
                <option value="{{ $customer->id }}" @selected((string) $selectedCustomerId === (string) $customer->id)>{{ $customer->name }}</option>
            @endforeach
        </select>
        @error('customer_id')<small class="error">{{ $message }}</small>@enderror
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

    <label class="field field-full">
        <span>Subject <strong>*</strong></span>
        <input type="text" name="subject" value="{{ old('subject', $interaction->subject ?? '') }}" maxlength="255" required>
        @error('subject')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Description</span>
        <textarea name="description" rows="4">{{ old('description', $interaction->description ?? '') }}</textarea>
        @error('description')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Interaction Date</span>
        <input type="datetime-local" name="interaction_at" value="{{ $interactionAt }}">
        @error('interaction_at')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field">
        <span>Handled By</span>
        <input type="text" name="handled_by" value="{{ old('handled_by', $interaction->handled_by ?? '') }}" maxlength="255">
        @error('handled_by')<small class="error">{{ $message }}</small>@enderror
    </label>

    <label class="field field-full">
        <span>Outcome</span>
        <input type="text" name="outcome" value="{{ old('outcome', $interaction->outcome ?? '') }}" maxlength="255">
        @error('outcome')<small class="error">{{ $message }}</small>@enderror
    </label>
</div>
